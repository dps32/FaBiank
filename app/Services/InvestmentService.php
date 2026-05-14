<?php

namespace App\Services;

use App\Models\Investment;
use App\Models\InvestmentTrade;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Support\Facades\DB;
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

    // Compra acciones: descuenta balance, crea inversión y registra trade.
    public function buyStock(User $user, Stock $stock, int $quantity): Investment
    {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('La cantidad debe ser mayor a 0.');
        }

        $price = (float) $stock->current_price;
        $total = $price * $quantity;

        if ((float) $user->balance < $total) {
            throw new \InvalidArgumentException('Saldo insuficiente para realizar la compra.');
        }

        return DB::transaction(function () use ($user, $stock, $quantity, $price, $total) {
            $user->decrement('balance', $total, []);

            $investment = Investment::create([
                'user_id'   => $user->id,
                'stock_id'  => $stock->id,
                'quantity'  => $quantity,
                'buy_price' => $price,
                'is_sold'   => false,
            ]);

            InvestmentTrade::create([
                'investment_id' => $investment->id,
                'type'          => 'buy',
                'date'          => now('UTC'),
            ]);

            return $investment;
        });
    }

    // Vende una inversión activa: acredita balance y registra trade.
    public function sellInvestment(User $user, Investment $investment): Investment
    {
        if ($investment->user_id !== $user->id) {
            throw new \InvalidArgumentException('Esta inversión no te pertenece.');
        }

        if ($investment->is_sold) {
            throw new \InvalidArgumentException('Esta inversión ya fue vendida.');
        }

        $currentPrice = (float) $investment->stock->current_price;
        $proceeds = $currentPrice * $investment->quantity;

        return DB::transaction(function () use ($user, $investment, $currentPrice, $proceeds) {
            $investment->lockForUpdate();

            if ($investment->is_sold) {
                throw new \InvalidArgumentException('Esta inversión ya fue vendida.');
            }

            $user->increment('balance', $proceeds, []);

            $investment->update([
                'sell_price' => $currentPrice,
                'is_sold'    => true,
            ]);

            InvestmentTrade::create([
                'investment_id' => $investment->id,
                'type'          => 'sell',
                'date'          => now('UTC'),
            ]);

            return $investment->fresh();
        });
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
