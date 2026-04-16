<?php

namespace App\Services;

use App\Models\PaymentRequest;
use App\Models\Transaction;
use App\Models\User;
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

        // Crear transacción
        $transaction = Transaction::create([
            'sender_id' => $sender->id,
            'receiver_id' => $recipient->id,
            'amount' => $amount,
            'date' => now()->toDateString(),
        ]);

        // Actualizar balances
        $sender->update([
            'balance' => $senderBalance - $amount,
        ]);

        $recipient->update([
            'balance' => ((float) $recipient->balance) + $amount,
        ]);

        return $transaction;
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
