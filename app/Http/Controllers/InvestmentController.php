<?php

namespace App\Http\Controllers;

use App\Models\Investment;
use App\Models\Stock;
use App\Services\DashboardService;
use App\Services\InvestmentService;
use App\Services\StockPriceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InvestmentController extends Controller
{
    public function __construct(
        private InvestmentService $investmentService,
        private StockPriceService $stockPriceService,
        private DashboardService $dashboardService,
    ) {}

    // Vista principal de inversiones.
    public function show(Request $request): View
    {
        $user = $request->user();

        $stocksRaw = $this->stockPriceService->getStocks();
        $stocks    = $this->enrichStocks($stocksRaw);

        $portfolio = Investment::query()
            ->with('stock')
            ->where('user_id', $user->id)
            ->where('is_sold', false)
            ->orderByDesc('id')
            ->get()
            ->map(fn($inv) => $this->formatInvestment($inv));

        $history = Investment::query()
            ->with('stock', 'trades')
            ->where('user_id', $user->id)
            ->where('is_sold', true)
            ->orderByDesc('id')
            ->limit(20)
            ->get()
            ->map(fn($inv) => $this->formatInvestment($inv));

        return view('investments', [
            'balance'   => (float) $user->balance,
            'stocks'    => $stocks,
            'portfolio' => $portfolio,
            'history'   => $history,
        ]);
    }

    // Devuelve precios actualizados para polling (todos los stocks).
    public function prices(Request $request): JsonResponse
    {
        $request->session()->save();

        $stocksRaw = $this->stockPriceService->refreshStockPrices();

        return response()->json([
            'stocks' => $this->enrichStocks($stocksRaw),
        ]);
    }

    // Devuelve precios en tiempo real solo para los stocks del portafolio activo del usuario.
    public function portfolioPrices(Request $request): JsonResponse
    {
        $user = $request->user();

        $tickers = Investment::query()
            ->with('stock')
            ->where('user_id', $user->id)
            ->where('is_sold', false)
            ->get()
            ->pluck('stock.ticker')
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        if (empty($tickers)) {
            return response()->json(['stocks' => []]);
        }

        // Liberamos el lock de sesión antes de llamar a Yahoo para no bloquear otras requests.
        $request->session()->save();

        $stocks = $this->stockPriceService->refreshTickerPrices($tickers);

        return response()->json([
            'stocks' => $this->enrichStocks($stocks),
        ]);
    }

    // Añade change y change_pct leyendo solo de caché (sin llamar a la API).
    private function enrichStocks(\Illuminate\Database\Eloquent\Collection $stocks): \Illuminate\Support\Collection
    {
        return $stocks->map(function ($s) {
            $quote = $this->stockPriceService->getCachedQuote($s->ticker);
            return [
                'id'            => $s->id,
                'ticker'        => $s->ticker,
                'name'          => $s->name,
                'current_price' => (float) $s->current_price,
                'change'        => $quote['change']     ?? 0.0,
                'change_pct'    => $quote['change_pct'] ?? 0.0,
            ];
        });
    }

    // Compra de acciones.
    public function buy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'stock_id' => ['required', 'integer', 'exists:stocks,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:10000'],
        ]);

        $user  = $request->user();
        $stock = Stock::findOrFail($validated['stock_id']);

        try {
            $investment = $this->investmentService->buyStock($user, $stock, $validated['quantity']);
            $this->dashboardService->invalidate($user);

            $user->refresh();

            return response()->json([
                'message'    => 'Compra realizada exitosamente.',
                'newBalance' => (float) $user->balance,
                'investment' => $this->formatInvestment($investment->load('stock')),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    // Venta de una inversión activa.
    public function sell(Request $request, int $investmentId): JsonResponse
    {
        $user       = $request->user();
        $investment = Investment::with('stock')->find($investmentId);

        if (!$investment) {
            return response()->json(['message' => 'Inversión no encontrada.'], 404);
        }

        try {
            $sold = $this->investmentService->sellInvestment($user, $investment);
            $this->dashboardService->invalidate($user);

            $user->refresh();

            return response()->json([
                'message'    => 'Venta realizada exitosamente.',
                'newBalance' => (float) $user->balance,
                'investment' => $this->formatInvestment($sold->load('stock')),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    private function formatInvestment(Investment $inv): array
    {
        $buyPrice     = (float) $inv->buy_price;
        $currentPrice = (float) ($inv->stock->current_price ?? $buyPrice);
        $sellPrice    = $inv->sell_price !== null ? (float) $inv->sell_price : null;
        $evalPrice    = $inv->is_sold ? ($sellPrice ?? $buyPrice) : $currentPrice;
        $costBasis = $buyPrice * $inv->quantity;
        $positionValue = $evalPrice * $inv->quantity;
        $gainLoss = $positionValue - $costBasis;
        $changePercent = $buyPrice > 0 ? (($evalPrice - $buyPrice) / $buyPrice) * 100 : 0;

        return [
            'id'            => $inv->id,
            'stock_id'      => $inv->stock_id,
            'ticker'        => $inv->stock->ticker ?? '',
            'name'          => $inv->stock->name ?? 'Activo',
            'quantity'      => $inv->quantity,
            'buy_price'     => $buyPrice,
            'sell_price'    => $sellPrice,
            'current_price' => $currentPrice,
            'cost_basis'    => $costBasis,
            'position_value'=> $positionValue,
            'gain_loss'     => $gainLoss,
            'change_percent'=> $changePercent,
            'is_sold'       => (bool) $inv->is_sold,
        ];
    }
}
