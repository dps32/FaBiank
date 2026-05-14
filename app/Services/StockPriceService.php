<?php

namespace App\Services;

use App\Models\Stock;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class StockPriceService
{
    private const CACHE_TTL = 300; // 5 minutos

    // Lee los stocks desde DB sin llamar a la API.
    public function getStocks(): \Illuminate\Database\Eloquent\Collection
    {
        return Stock::all();
    }

    // Llama a la API en paralelo para todos los stocks, actualiza DB y caché.
    public function refreshStockPrices(): \Illuminate\Database\Eloquent\Collection
    {
        $stocks = Stock::all();
        $this->fetchAndSaveAll($stocks);
        return $stocks->fresh();
    }

    // Igual que refreshStockPrices pero solo para los tickers indicados.
    public function refreshTickerPrices(array $tickers): \Illuminate\Database\Eloquent\Collection
    {
        $stocks = Stock::whereIn('ticker', $tickers)->get();
        $this->fetchAndSaveAll($stocks);
        return $stocks->fresh();
    }

    // Llama a la API y guarda en caché 5 minutos.
    public function fetchQuote(string $ticker): ?array
    {
        $cacheKey = "stock_quote:{$ticker}";

        return Cache::remember($cacheKey, self::CACHE_TTL, fn() => $this->callApiSingle($ticker));
    }

    // Lee de caché sin llamar a la API si no hay entrada.
    public function getCachedQuote(string $ticker): ?array
    {
        return Cache::get("stock_quote:{$ticker}");
    }

    // Mantiene compatibilidad con código que solo pide el precio.
    public function fetchPrice(string $ticker): ?float
    {
        return $this->fetchQuote($ticker)['price'] ?? null;
    }

    // Llama a Yahoo en paralelo para todos los stocks de la colección y guarda en DB y caché.
    private function fetchAndSaveAll(\Illuminate\Database\Eloquent\Collection $stocks): void
    {
        if ($stocks->isEmpty()) {
            return;
        }

        $responses = Http::pool(function ($pool) use ($stocks) {
            foreach ($stocks as $stock) {
                $pool->as($stock->ticker)
                    ->timeout(8)
                    ->withHeader('User-Agent', 'Mozilla/5.0')
                    ->get("https://query1.finance.yahoo.com/v8/finance/chart/{$stock->ticker}", [
                        'interval' => '1m',
                        'range'    => '1m',
                    ]);
            }
        });

        foreach ($stocks as $stock) {
            $response = $responses[$stock->ticker] ?? null;

            if ($response instanceof \Throwable) {
                Log::warning("StockPriceService pool: error en {$stock->ticker}", ['error' => $response->getMessage()]);
                continue;
            }

            $data = $this->parseResponse($response);
            if ($data === null) {
                continue;
            }

            $stock->current_price = $data['price'];
            $stock->save();
            Cache::put("stock_quote:{$stock->ticker}", $data, self::CACHE_TTL);
        }
    }

    // Parsea la respuesta de Yahoo y retorna el array de datos, o null si inválida.
    private function parseResponse(mixed $response): ?array
    {
        if (!$response || !$response->ok()) {
            return null;
        }

        $meta          = $response->json('chart.result.0.meta');
        $price         = isset($meta['regularMarketPrice']) ? (float) $meta['regularMarketPrice'] : null;
        $previousClose = isset($meta['chartPreviousClose']) ? (float) $meta['chartPreviousClose'] : null;

        if (!$price || $price <= 0) {
            return null;
        }

        $change    = $previousClose ? $price - $previousClose : 0.0;
        $changePct = $previousClose ? ($change / $previousClose) * 100 : 0.0;

        return [
            'price'      => $price,
            'change'     => round($change, 2),
            'change_pct' => round($changePct, 2),
        ];
    }

    // Llamada individual a Yahoo (usada en fetchQuote con caché).
    private function callApiSingle(string $ticker): ?array
    {
        try {
            $response = Http::timeout(8)
                ->withHeader('User-Agent', 'Mozilla/5.0')
                ->get("https://query1.finance.yahoo.com/v8/finance/chart/{$ticker}", [
                    'interval' => '1m',
                    'range'    => '1m',
                ]);

            return $this->parseResponse($response);
        } catch (\Throwable $e) {
            Log::warning("StockPriceService: error fetching {$ticker}", ['error' => $e->getMessage()]);
            return null;
        }
    }
}
