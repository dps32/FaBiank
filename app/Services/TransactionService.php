<?php

namespace App\Services;

use App\Models\PaymentRequest;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TransactionService
{
    // Transferencia completa: valida saldo, crea transacción y actualiza balances.
    public function transfer(User $sender, User $recipient, float $amount): Transaction
    {
        // Validar que el monto sea positivo
        if ($amount <= 0) {
            throw new \InvalidArgumentException('La cantidad debe ser mayor a 0.');
        }

        // Validar saldo suficiente
        $senderBalance = (float) $sender->balance;
        if ($senderBalance < $amount) {
            throw new \InvalidArgumentException('Saldo insuficiente para realizar la transacción.');
        }

        // Validar que no sea a sí mismo
        if ($sender->id === $recipient->id) {
            throw new \InvalidArgumentException('No puedes enviar dinero a ti mismo.');
        }

        return DB::transaction(function () use ($sender, $recipient, $amount) {
            $transaction = Transaction::create([
                'sender_id' => $sender->id,
                'receiver_id' => $recipient->id,
                'amount' => $amount,
                'date' => now('UTC'),
            ]);

            $sender->decrement('balance', $amount);
            $recipient->increment('balance', $amount);

            return $transaction;
        });
    }

    // Crea transacción y valida datos.
    public function createTransaction(array $data): Transaction
    {
        $validated = Validator::make($data, [
            'sender_id' => ['required', 'integer', 'exists:users,id'],
            'receiver_id' => ['required', 'integer', 'exists:users,id'],
            'amount' => ['required', 'numeric', 'min:0'],
            'date' => ['required', 'date'],
        ])->validate();

        return Transaction::create($validated);
    }

    // Lo mismo pero con parámetros directos.
    public function createTransactionTyped(int $senderId, int $receiverId, float $amount, string $date): Transaction
    {
        return $this->createTransaction([
            'sender_id' => $senderId,
            'receiver_id' => $receiverId,
            'amount' => $amount,
            'date' => $date,
        ]);
    }

    // Borra transacción por id.
    public function deleteTransactionById(int $id): bool
    {
        return (bool) Transaction::query()->whereKey($id)->delete();
    }

    // Crea solicitud de pago y valida datos.
    public function createPaymentRequest(array $data): PaymentRequest
    {
        $validated = Validator::make($data, [
            'requester_id' => ['required', 'integer', 'exists:users,id'],
            'target_id' => ['required', 'integer', 'exists:users,id'],
            'amount' => ['required', 'numeric', 'min:0'],
            'status' => ['nullable', 'in:pending,accepted,rejected'],
            'date' => ['required', 'date'],
        ])->validate();

        $validated['status'] = $validated['status'] ?? 'pending';

        return PaymentRequest::create($validated);
    }

    // Lo mismo pero con parámetros directos.
    public function createPaymentRequestTyped(int $requesterId, int $targetId, float $amount, string $date): PaymentRequest
    {
        return $this->createPaymentRequest([
            'requester_id' => $requesterId,
            'target_id' => $targetId,
            'amount' => $amount,
            'status' => 'pending',
            'date' => $date,
        ]);
    }

    public function requestPayment(User $requester, User $target, float $amount): PaymentRequest
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('La cantidad debe ser mayor a 0.');
        }

        if ($requester->id === $target->id) {
            throw new \InvalidArgumentException('No puedes solicitarte dinero a ti mismo.');
        }

        return PaymentRequest::create([
            'requester_id' => $requester->id,
            'target_id' => $target->id,
            'amount' => $amount,
            'status' => 'pending',
            'date' => now('UTC'),
        ]);
    }

    public function getReceivedPendingRequests(User $user, int $limit = 5): Collection
    {
        return PaymentRequest::query()
            ->with('requester:id,username')
            ->where('target_id', $user->id)
            ->where('status', 'pending')
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    public function respondToPaymentRequest(User $target, int $paymentRequestId, string $action): array
    {
        if (!in_array($action, ['accept', 'reject'], true)) {
            throw new \InvalidArgumentException('Acción inválida para solicitud.');
        }

        return DB::transaction(function () use ($target, $paymentRequestId, $action) {
            $paymentRequest = PaymentRequest::query()
                ->whereKey($paymentRequestId)
                ->lockForUpdate()
                ->first();

            if (!$paymentRequest) {
                throw new \InvalidArgumentException('La solicitud no existe.');
            }

            if ((int) $paymentRequest->target_id !== (int) $target->id) {
                throw new \InvalidArgumentException('No puedes gestionar esta solicitud.');
            }

            if ($paymentRequest->status !== 'pending') {
                throw new \InvalidArgumentException('Esta solicitud ya fue gestionada.');
            }

            $transaction = null;

            if ($action === 'accept') {
                $requester = User::query()->whereKey($paymentRequest->requester_id)->lockForUpdate()->first();

                if (!$requester) {
                    throw new \InvalidArgumentException('El usuario solicitante no existe.');
                }

                $targetLocked = User::query()->whereKey($target->id)->lockForUpdate()->first();
                if (!$targetLocked) {
                    throw new \InvalidArgumentException('El usuario pagador no existe.');
                }

                $transaction = $this->transfer($targetLocked, $requester, (float) $paymentRequest->amount);
                $paymentRequest->status = 'accepted';
            } else {
                $paymentRequest->status = 'rejected';
            }

            $paymentRequest->save();

            return [
                'paymentRequest' => $paymentRequest,
                'transaction' => $transaction,
            ];
        });
    }

    // Borra solicitud de pago por id.
    public function deletePaymentRequestById(int $id): bool
    {
        return (bool) PaymentRequest::query()->whereKey($id)->delete();
    }
}
