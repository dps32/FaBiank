<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class HistoryController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user();

        $transactions = Transaction::query()
            ->with(['sender:id,name,username', 'receiver:id,name,username'])
            ->where(function ($query) use ($user) {
                $query->where('sender_id', $user->id)
                    ->orWhere('receiver_id', $user->id);
            })
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();

        return view('history', [
            'historySections' => $this->buildHistorySections($transactions, (float) $user->balance, (int) $user->id),
        ]);
    }

    private function buildHistorySections(Collection $transactions, float $currentBalance, int $userId): Collection
    {
        $items = collect();
        $runningBalance = $currentBalance;
        $displayTimezone = config('app.timezone', 'Europe/Madrid');

        foreach ($transactions as $transaction) {
            $isIncoming = (int) $transaction->receiver_id === $userId;
            $counterparty = $isIncoming
                ? ($transaction->sender?->name ?? $transaction->sender?->username)
                : ($transaction->receiver?->name ?? $transaction->receiver?->username);
            $amount = (float) $transaction->amount;
            $signedAmount = $isIncoming ? $amount : -$amount;
            $dateRaw = $transaction->getRawOriginal('date');
            $dateUtc = $dateRaw
                ? Carbon::createFromFormat('Y-m-d H:i:s', $dateRaw, 'UTC')
                : Carbon::parse($transaction->date . ' 12:00:00', 'UTC');
            $dateLocal = $dateUtc->copy()->timezone($displayTimezone);

            $items->push([
                'id' => (int) $transaction->id,
                'dateKey' => $dateLocal->toDateString(),
                'sectionLabel' => $this->buildSectionLabel($dateLocal),
                'movementLabel' => $isIncoming ? 'TRANSFERENCIA RECIBIDA' : 'TRANSFERENCIA ENVIADA',
                'counterparty' => $counterparty ?? 'Usuario',
                'occurredAtUtc' => $dateUtc->toIso8601String(),
                'timeLabel' => $dateLocal->format('H:i'),
                'signedAmount' => ($signedAmount >= 0 ? '+' : '-') . ' $' . $this->formatHistoryAmount(abs($signedAmount)),
                'amountClass' => $signedAmount >= 0 ? 'is-positive' : 'is-negative',
                'resultingBalance' => '$' . number_format($runningBalance, 2),
            ]);

            $runningBalance -= $signedAmount;
        }

        return $items
            ->groupBy('dateKey')
            ->map(function (Collection $group) {
                return [
                    'label' => $group->first()['sectionLabel'] ?? '-',
                    'items' => $group->values(),
                ];
            })
            ->values();
    }

    private function buildSectionLabel(Carbon $date): string
    {
        $today = now()->startOfDay();
        $target = $date->copy()->startOfDay();
        $daysDiff = $target->diffInDays($today, false);

        $formattedDate = mb_strtoupper($date->locale('es')->translatedFormat('j M Y'));

        if ($daysDiff <= 0) {
            return 'HOY - ' . $formattedDate;
        }

        if ($daysDiff === 1) {
            return 'AYER - ' . $formattedDate;
        }

        return 'HACE ' . $daysDiff . 'D - ' . $formattedDate;
    }

    private function formatHistoryAmount(float $amount): string
    {
        if (floor($amount) === $amount) {
            return number_format($amount, 0, '.', '.');
        }

        return number_format($amount, 2, '.', '');
    }
}
