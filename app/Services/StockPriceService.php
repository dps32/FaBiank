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

    // Llama a la API, actualiza DB y caché. Solo se invoca al pulsar "Actualizar".
    public function refreshStockPrices(): \Illuminate\Database\Eloquent\Collection
    {
        $stocks = Stock::all();

        foreach ($stocks as $stock) {
            $data = $this->fetchQuote($stock->ticker);
            if ($data !== null && $data['price'] > 0) {
                $stock->current_price = $data['price'];
                $stock->save();
            }
        }

        return $stocks->fresh();
    }

    // Llama a la API y guarda en caché 5 minutos.
    public function fetchQuote(string $ticker): ?array
    {
        $cacheKey = "stock_quote:{$ticker}";

        return Cache::remember($cacheKey, self::CACHE_TTL, fn() => $this->callApi($ticker));
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

    // Yahoo Finance v8 con interval=1m&range=1m para precio en tiempo real.
    private function callApi(string $ticker): ?array
    {
        try {
            $response = Http::timeout(8)
                ->withHeader('User-Agent', 'Mozilla/5.0')
                ->get("https://query1.finance.yahoo.com/v8/finance/chart/{$ticker}", [
                    'interval' => '1m',
                    'range'    => '1m',
                ]);

            if (!$response->ok()) {
                return null;
            }

            $meta          = $response->json('chart.result.0.meta');
            $price         = isset($meta['regularMarketPrice']) ? (float) $meta['regularMarketPrice'] : null;
            $previousClose = isset($meta['chartPreviousClose']) ? (float) $meta['chartPreviousClose'] : null;

            if (!$price || $price <= 0) {
                return null;
            }

            $change        = $previousClose ? $price - $previousClose : 0.0;
            $changePct     = $previousClose ? ($change / $previousClose) * 100 : 0.0;

            return [
                'price'      => $price,
                'change'     => round($change, 2),
                'change_pct' => round($changePct, 2),
            ];
        } catch (\Throwable $e) {
            Log::warning("StockPriceService: error fetching {$ticker}", ['error' => $e->getMessage()]);
            return null;
        }
    }
}
