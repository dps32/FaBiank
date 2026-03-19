<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;

class FinanceDataService
{
    // Usa los otros servicios por dentro.
    public function __construct(
        private readonly UserService $userService,
        private readonly TransactionService $transactionService,
        private readonly InvestmentService $investmentService
    ) {
    }

    // Inserta según el nombre de tabla lógico.
    public function insert(string $entity, array $data): Model
    {
        return match ($entity) {
            'users' => $this->userService->create($data),
            'transactions' => $this->transactionService->createTransaction($data),
            'payment_requests' => $this->transactionService->createPaymentRequest($data),
            'stocks' => $this->investmentService->createStock($data),
            'investments' => $this->investmentService->createInvestment($data),
            'investment_trade' => $this->investmentService->createInvestmentTrade($data),
            default => throw new \InvalidArgumentException("Entidad no soportada: {$entity}"),
        };
    }

    // Atajo para crear usuario.
    public function insertUser(
        string $username,
        string $password,
        string $phoneNumber,
        float $balance = 0,
        bool $isVerified = false,
        ?string $smsCode = null
    ): \App\Models\User {
        return $this->userService->createTyped(
            username: $username,
            password: $password,
            phoneNumber: $phoneNumber,
            balance: $balance,
            isVerified: $isVerified,
            smsCode: $smsCode
        );
    }

    // Atajo para crear transacción.
    public function insertTransaction(int $senderId, int $receiverId, float $amount, string $date): \App\Models\Transaction
    {
        return $this->transactionService->createTransactionTyped($senderId, $receiverId, $amount, $date);
    }

    // Atajo para crear solicitud de pago.
    public function insertPaymentRequest(int $requesterId, int $targetId, float $amount, string $date): \App\Models\PaymentRequest
    {
        return $this->transactionService->createPaymentRequestTyped($requesterId, $targetId, $amount, $date);
    }

    // Atajo para crear stock.
    public function insertStock(string $ticker, string $name, float $currentPrice): \App\Models\Stock
    {
        return $this->investmentService->createStockTyped($ticker, $name, $currentPrice);
    }

    // Atajo para crear inversión.
    public function insertInvestment(
        int $userId,
        int $stockId,
        int $quantity,
        float $buyPrice,
        ?float $sellPrice = null,
        bool $isSold = false
    ): \App\Models\Investment {
        return $this->investmentService->createInvestmentTyped(
            userId: $userId,
            stockId: $stockId,
            quantity: $quantity,
            buyPrice: $buyPrice,
            sellPrice: $sellPrice,
            isSold: $isSold
        );
    }

    // Atajo para crear trade.
    public function insertInvestmentTrade(int $investmentId, string $type, string $date): \App\Models\InvestmentTrade
    {
        return $this->investmentService->createInvestmentTradeTyped($investmentId, $type, $date);
    }

    // Borra por entidad y por id.
    public function deleteById(string $entity, int $id): bool
    {
        return match ($entity) {
            'users' => $this->userService->deleteById($id),
            'transactions' => $this->transactionService->deleteTransactionById($id),
            'payment_requests' => $this->transactionService->deletePaymentRequestById($id),
            'stocks' => $this->investmentService->deleteStockById($id),
            'investments' => $this->investmentService->deleteInvestmentById($id),
            'investment_trade' => $this->investmentService->deleteInvestmentTradeById($id),
            default => throw new \InvalidArgumentException("Entidad no soportada: {$entity}"),
        };
    }
}
