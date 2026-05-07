<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\DashboardService;
use App\Services\TransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function __construct(
        private TransactionService $transactionService,
        private DashboardService $dashboardService
    ) {
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'recipientId' => ['nullable', 'integer', 'exists:users,id'],
            'recipientUsername' => ['nullable', 'string', 'max:255'],
            'transferAmount' => ['required', 'numeric', 'min:0.01'],
        ], [
            'recipientId.exists' => 'El usuario no existe.',
            'recipientUsername.max' => 'El usuario no es valido.',
            'transferAmount.required' => 'Debes ingresar una cantidad.',
            'transferAmount.numeric' => 'La cantidad debe ser un número válido.',
            'transferAmount.min' => 'La cantidad debe ser mayor a 0.01.',
        ]);

        try {
            $sender = auth()->user();
            $recipient = null;

            if (!empty($validated['recipientId'])) {
                $recipient = User::find($validated['recipientId']);
            }

            if (!$recipient && !empty($validated['recipientUsername'])) {
                $recipient = User::query()
                    ->where('username', $validated['recipientUsername'])
                    ->first();
            }

            if (!$recipient) {
                return response()->json([
                    'success' => false,
                    'message' => 'Debes seleccionar un usuario válido.',
                ], 422);
            }

            $amount = (float) $validated['transferAmount'];

            $transaction = $this->transactionService->transfer($sender, $recipient, $amount);
            DashboardService::forgetForUserIds([$sender->id, $recipient->id]);
            $sender->refresh();

            $this->dashboardService->invalidate($sender);
            if ($recipient instanceof User) {
                $this->dashboardService->invalidate($recipient);
            }

            return response()->json([
                'success' => true,
                'message' => 'Dinero enviado correctamente.',
                'transaction' => $transaction,
                'newBalance' => (float) $sender->balance,
                'recipientName' => $recipient instanceof User ? $recipient->name : $recipientUsername,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la transacción.',
            ], 500);
        }
    }
}
