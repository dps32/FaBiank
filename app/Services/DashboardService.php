<?php

namespace App\Services;

use App\Models\Investment;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Collection;

class DashboardService
{
    public function build(User $user): array
    {
        $recentTransactions = $this->getRecentTransactions($user);
        $recentInvestments = $this->getRecentInvestments($user);

        return [
            'balance' => (float) $user->balance,
            'transactionItems' => $this->formatTransactions($recentTransactions, $user),
            'investmentItems' => $this->formatInvestments($recentInvestments),
        ];
    }

    private function getRecentTransactions(User $user): Collection
    {
        return Transaction::query()
            ->with(['sender:id,username', 'receiver:id,username'])
            ->where(function ($query) use ($user) {
                $query->where('sender_id', $user->id)
                    ->orWhere('receiver_id', $user->id);
            })
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->limit(5)
            ->get();
    }

    private function getRecentInvestments(User $user): Collection
    {
        return Investment::query()
            ->with('stock:id,name,current_price')
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->limit(5)
            ->get();
    }

    private function formatTransactions(Collection $transactions, User $user): Collection
    {
        $items = [];

        foreach ($transactions as $transaction) {
            $isIncoming = (int) $transaction->receiver_id === (int) $user->id;
            $counterparty = $isIncoming ? $transaction->sender?->username : $transaction->receiver?->username;
            $amount = (float) $transaction->amount;

            $items[] = [
                'counterparty' => $counterparty ?? 'Usuario',
                'signedAmount' => ($isIncoming ? '+' : '-') . ' $' . number_format($amount, 2),
                'amountClass' => $isIncoming ? 'is-positive' : 'is-negative',
            ];
        }

        return collect($items);
    }

    private function formatInvestments(Collection $investments): Collection
    {
        $items = [];

        foreach ($investments as $investment) {
            $currentPrice = (float) ($investment->stock?->current_price ?? 0);
            $buyPrice = (float) $investment->buy_price;
            $changePercent = 0;

            if ($buyPrice > 0) {
                $changePercent = (($currentPrice - $buyPrice) / $buyPrice) * 100;
            }

            $positionValue = $currentPrice * (int) $investment->quantity;

            $items[] = [
                'name' => $investment->stock?->name ?? 'Activo',
                'buyPrice' => $buyPrice,
                'positionValue' => $positionValue,
                'changePercent' => $changePercent,
                'amountClass' => $changePercent >= 0 ? 'is-positive' : 'is-negative',
            ];
        }

        return collect($items);
    }
}
