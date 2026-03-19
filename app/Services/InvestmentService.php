<?php

namespace App\Services;

use App\Models\Investment;
use App\Models\InvestmentTrade;
use App\Models\Stock;
use Illuminate\Support\Facades\Validator;

class InvestmentService
{
    // Crea stock y valida datos.
    public function createStock(array $data): Stock
    {
        $validated = Validator::make($data, [
            'ticker' => ['required', 'string', 'max:255', 'unique:stocks,ticker'],
            'name' => ['required', 'string', 'max:255'],
            'current_price' => ['required', 'numeric', 'min:0'],
        ])->validate();

        return Stock::create($validated);
    }

    // Lo mismo pero con parámetros directos.
    public function createStockTyped(string $ticker, string $name, float $currentPrice): Stock
    {
        return $this->createStock([
            'ticker' => $ticker,
            'name' => $name,
            'current_price' => $currentPrice,
        ]);
    }

    // Borra stock por id.
    public function deleteStockById(int $id): bool
    {
        return (bool) Stock::query()->whereKey($id)->delete();
    }

    // Crea inversión y valida datos.
    public function createInvestment(array $data): Investment
    {
        $validated = Validator::make($data, [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'stock_id' => ['required', 'integer', 'exists:stocks,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'buy_price' => ['required', 'numeric', 'min:0'],
            'sell_price' => ['nullable', 'numeric', 'min:0'],
            'is_sold' => ['sometimes', 'boolean'],
        ])->validate();

        return Investment::create($validated);
    }

    // Lo mismo pero con parámetros directos.
    public function createInvestmentTyped(
        int $userId,
        int $stockId,
        int $quantity,
        float $buyPrice,
        ?float $sellPrice = null,
        bool $isSold = false
    ): Investment {
        return $this->createInvestment([
            'user_id' => $userId,
            'stock_id' => $stockId,
            'quantity' => $quantity,
            'buy_price' => $buyPrice,
            'sell_price' => $sellPrice,
            'is_sold' => $isSold,
        ]);
    }

    // Borra inversión por id.
    public function deleteInvestmentById(int $id): bool
    {
        return (bool) Investment::query()->whereKey($id)->delete();
    }

    // Crea trade y valida datos.
    public function createInvestmentTrade(array $data): InvestmentTrade
    {
        $validated = Validator::make($data, [
            'investment_id' => ['required', 'integer', 'exists:investments,id'],
            'type' => ['required', 'string', 'max:255'],
            'date' => ['required', 'date'],
        ])->validate();

        return InvestmentTrade::create($validated);
    }

    // Lo mismo pero con parámetros directos.
    public function createInvestmentTradeTyped(int $investmentId, string $type, string $date): InvestmentTrade
    {
        return $this->createInvestmentTrade([
            'investment_id' => $investmentId,
            'type' => $type,
            'date' => $date,
        ]);
    }

    // Borra trade por id.
    public function deleteInvestmentTradeById(int $id): bool
    {
        return (bool) InvestmentTrade::query()->whereKey($id)->delete();
    }
}
