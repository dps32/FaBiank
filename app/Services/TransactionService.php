<?php

namespace App\Services;

use App\Models\PaymentRequest;
use App\Models\Transaction;
use Illuminate\Support\Facades\Validator;

class TransactionService
{
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
            'date' => ['required', 'date'],
        ])->validate();

        return PaymentRequest::create($validated);
    }

    // Lo mismo pero con parámetros directos.
    public function createPaymentRequestTyped(int $requesterId, int $targetId, float $amount, string $date): PaymentRequest
    {
        return $this->createPaymentRequest([
            'requester_id' => $requesterId,
            'target_id' => $targetId,
            'amount' => $amount,
            'date' => $date,
        ]);
    }

    // Borra solicitud de pago por id.
    public function deletePaymentRequestById(int $id): bool
    {
        return (bool) PaymentRequest::query()->whereKey($id)->delete();
    }
}
