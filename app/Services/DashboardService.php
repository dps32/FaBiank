<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function build(User $user): array
    {
        $recentTransactions = $this->getRecentTransactions($user);
        $receivedPaymentRequests = $this->getReceivedPaymentRequests($user);
        $recentInvestments = $this->getRecentInvestments($user);

        return [
            'balance' => (float) $user->balance,
            'transactionItems' => $recentTransactions,
            'paymentRequestItems' => $receivedPaymentRequests,
            'investmentItems' => $recentInvestments,
        ];
    }

    private function getRecentTransactions(User $user): Collection
    {
        return DB::table('transactions as t')
            ->leftJoin('users as sender', 'sender.id', '=', 't.sender_id')
            ->leftJoin('users as receiver', 'receiver.id', '=', 't.receiver_id')
            ->where(function ($query) use ($user) {
                $query->where('t.sender_id', $user->id)
                    ->orWhere('t.receiver_id', $user->id);
            })
            ->orderByDesc('t.date')
            ->orderByDesc('t.id')
            ->limit(5)
            ->select([
                't.sender_id',
                't.receiver_id',
                't.amount',
                'sender.name as sender_name',
                'sender.username as sender_username',
                'receiver.name as receiver_name',
                'receiver.username as receiver_username',
            ])
            ->get()
            ->map(function ($transaction) use ($user) {
                $isIncoming = (int) $transaction->receiver_id === (int) $user->id;
                $counterparty = $isIncoming
                    ? ($transaction->sender_name ?: $transaction->sender_username)
                    : ($transaction->receiver_name ?: $transaction->receiver_username);
                $amount = (float) $transaction->amount;

                return [
                    'counterparty' => $counterparty ?: 'Usuario',
                    'signedAmount' => ($isIncoming ? '+' : '-') . ' $' . number_format($amount, 2),
                    'amountClass' => $isIncoming ? 'is-positive' : 'is-negative',
                ];
            });
    }

    private function getRecentInvestments(User $user): Collection
    {
        return DB::table('investments as i')
            ->leftJoin('stocks as s', 's.id', '=', 'i.stock_id')
            ->where('i.user_id', $user->id)
            ->orderByDesc('i.id')
            ->limit(5)
            ->select([
                's.name as stock_name',
                's.current_price',
                'i.quantity',
                'i.buy_price',
            ])
            ->get()
            ->map(function ($investment) {
                $currentPrice = (float) ($investment->current_price ?? 0);
                $buyPrice = (float) $investment->buy_price;
                $changePercent = $buyPrice > 0
                    ? (($currentPrice - $buyPrice) / $buyPrice) * 100
                    : 0;

                return [
                    'name' => $investment->stock_name ?: 'Activo',
                    'buyPrice' => $buyPrice,
                    'positionValue' => $currentPrice * (int) $investment->quantity,
                    'changePercent' => $changePercent,
                    'amountClass' => $changePercent >= 0 ? 'is-positive' : 'is-negative',
                ];
            });
    }

    private function getReceivedPaymentRequests(User $user): Collection
    {
        return DB::table('payment_requests as pr')
            ->leftJoin('users as requester', 'requester.id', '=', 'pr.requester_id')
            ->where('pr.target_id', $user->id)
            ->where('pr.status', 'pending')
            ->orderByDesc('pr.date')
            ->orderByDesc('pr.id')
            ->limit(5)
            ->select([
                'pr.id',
                'pr.amount',
                'pr.date',
                'requester.name as requester_name',
                'requester.username as requester_username',
            ])
            ->get()
            ->map(function ($paymentRequest) {
                $requester = $paymentRequest->requester_name ?: $paymentRequest->requester_username;

                return [
                    'id' => (int) $paymentRequest->id,
                    'requester' => $requester ?: 'Usuario',
                    'amount' => (float) $paymentRequest->amount,
                    'amountLabel' => '$' . number_format((float) $paymentRequest->amount, 2),
                    'date' => $paymentRequest->date ? (string) \Carbon\Carbon::parse($paymentRequest->date)->format('Y-m-d') : '-',
                ];
            });
    }
}
