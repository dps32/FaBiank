<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\DashboardService;
use App\Services\TransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentRequestController extends Controller
{
    public function __construct(
        private TransactionService $transactionService,
        private DashboardService $dashboardService
    ) {
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'targetId' => ['nullable', 'integer', 'exists:users,id'],
            'targetUsername' => ['nullable', 'string', 'max:255'],
            'requestAmount' => ['required', 'numeric', 'min:0.01'],
        ]);

        try {
            $requester = $request->user();
            $target = null;

            if (!empty($validated['targetId'])) {
                $target = User::find($validated['targetId']);
            }

            if (!$target && !empty($validated['targetUsername'])) {
                $target = User::query()
                    ->where('username', $validated['targetUsername'])
                    ->first();
            }

            if (!$target) {
                return response()->json([
                    'success' => false,
                    'message' => 'Debes seleccionar un usuario válido.',
                ], 422);
            }

            $paymentRequest = $this->transactionService->requestPayment(
                $requester,
                $target,
                (float) $validated['requestAmount']
            );

            $this->dashboardService->invalidate($target);

            return response()->json([
                'success' => true,
                'message' => 'Solicitud de pago creada.',
                'paymentRequest' => $paymentRequest,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la solicitud.',
            ], 500);
        }
    }

    public function respond(Request $request, int $paymentRequestId): JsonResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'in:accept,reject'],
        ]);

        try {
            $target = $request->user();
            $result = $this->transactionService->respondToPaymentRequest(
                $target,
                $paymentRequestId,
                $validated['action']
            );

            $target->refresh();

            $this->dashboardService->invalidate($target);
            $requester = User::find($result['paymentRequest']->requester_id);
            if ($requester) {
                $this->dashboardService->invalidate($requester);
            }

            return response()->json([
                'success' => true,
                'message' => $validated['action'] === 'accept'
                    ? 'Solicitud aceptada y pago realizado.'
                    : 'Solicitud rechazada.',
                'paymentRequest' => $result['paymentRequest'],
                'transaction' => $result['transaction'],
                'newBalance' => (float) $target->balance,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la solicitud.',
            ], 500);
        }
    }
}
