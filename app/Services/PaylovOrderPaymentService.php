<?php

namespace App\Services;

use App\Enums\PaymentStatusCode;
use App\Models\Sold;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserCard;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class PaylovOrderPaymentService
{
    private ?array $transactionColumns = null;

    public function __construct(
        private readonly OrderService $orderService,
    ) {
    }

    public function payPendingOrder(Sold $order, User $user, UserCard $card): array
    {
        if ((int) $order->user_id !== (int) $user->id) {
            throw new RuntimeException('Buyurtma sizga tegishli emas.');
        }

        if ((int) $card->user_id !== (int) $user->id) {
            throw new RuntimeException('Bu karta sizga tegishli emas.');
        }

        if ($order->payment_status_code !== PaymentStatusCode::CARD_PENDING->value) {
            throw new RuntimeException('Bu buyurtma karta bilan to‘lovni kutmayapti.');
        }

        if ((int) $order->paymentStatus === PaymentStatusCode::PAID->legacy()) {
            return $this->buildAlreadyPaidResponse($order);
        }

        if (!$card->is_verified || blank($card->provider_card_id)) {
            throw new RuntimeException('Tasdiqlanmagan karta bilan to‘lab bo‘lmaydi.');
        }

        $paylov = PaylovService::make();
        $paylov->ensureCardReadyForPayment($card);

        if ($reconciled = $this->reconcileExistingSuccessfulPayment($order, $user, $paylov)) {
            return $reconciled;
        }

        $receipt = $paylov->createReceipt(
            (string) $user->id,
            (int) $order->amount,
            [
                'order_id' => 'NRK-' . $order->id,
                'merchant_id' => $paylov->merchantId(),
            ],
        );

        $transactionId = (string) ($receipt['result']['transactionId'] ?? '');
        if ($transactionId === '') {
            throw new RuntimeException('Paylov transactionId qaytarmadi.');
        }

        $transaction = $this->createPendingTransaction(
            $order,
            $user,
            $card,
            $transactionId,
            $receipt,
        );

        $payResponse = null;
        $statusResponse = null;

        try {
            $payResponse = $paylov->payReceipt($transactionId, $card->provider_card_id, (string) $user->id);
            $statusResponse = $paylov->getTransactions($transactionId);

            return $this->finalizeSuccessfulPayment(
                order: $order,
                user: $user,
                card: $card,
                transaction: $transaction,
                transactionId: $transactionId,
                receipt: $receipt,
                payResponse: $payResponse,
                statusResponse: $statusResponse,
            );
        } catch (\Throwable $e) {
            $remotePaidDetected = false;

            if ($transactionId !== '' && $this->canAttemptReconcile($payResponse, $statusResponse)) {
                try {
                    $freshStatusResponse = is_array($statusResponse) && $statusResponse !== []
                        ? $statusResponse
                        : $paylov->getTransactions($transactionId);

                    if ($this->remoteTransactionLooksPaid($freshStatusResponse, $payResponse)) {
                        $remotePaidDetected = true;

                        return $this->finalizeSuccessfulPayment(
                            order: $order,
                            user: $user,
                            card: $card,
                            transaction: $transaction,
                            transactionId: $transactionId,
                            receipt: $receipt,
                            payResponse: $payResponse,
                            statusResponse: $freshStatusResponse,
                            localFinalizeError: $e->getMessage(),
                        );
                    }
                } catch (\Throwable $reconcileException) {
                    Log::warning('[Paylov] Order payment reconcile attempt failed', [
                        'order_id' => $order->id,
                        'transaction_id' => $transactionId,
                        'message' => $reconcileException->getMessage(),
                    ]);
                }
            }

            if ($remotePaidDetected) {
                $this->updateTransaction($transaction, [
                    'state' => 2,
                    'perform_time' => now()->format('Y-m-d H:i:s'),
                    'perform_time_unix' => time(),
                    'provider_response' => [
                        'create' => $receipt,
                        'pay' => $payResponse,
                        'status' => $statusResponse,
                        'error' => $e->getMessage(),
                        'needs_reconciliation' => true,
                        'card_snapshot' => $this->cardSnapshot($card),
                    ],
                ]);

                throw new RuntimeException('To‘lov qabul qilindi, lekin buyurtma tasdiqlanishi biroz kechikmoqda. Iltimos, sahifani yangilang yoki birozdan keyin qayta urinib ko‘ring.');
            }

            $this->updateTransaction($transaction, [
                'state' => -1,
                'reason' => 0,
                'cancel_time' => (string) intval(round(microtime(true) * 1000)),
                'provider_response' => [
                    'create' => $receipt,
                    'pay' => $payResponse,
                    'status' => $statusResponse,
                    'error' => $e->getMessage(),
                    'card_snapshot' => $this->cardSnapshot($card),
                ],
            ]);

            Log::warning('[Paylov] Order payment failed', [
                'order_id' => $order->id,
                'transaction_id' => $transactionId,
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function reconcileExistingSuccessfulPayment(
        Sold $order,
        User $user,
        PaylovService $paylov,
    ): ?array {
        $transaction = Transaction::query()
            ->where('owner_id', $user->id)
            ->where('order_id', $order->id)
            ->where('payment_type', 'order')
            ->where('provider', 'paylov')
            ->latest('id')
            ->first();

        if (!$transaction || blank($transaction->provider_transaction_id)) {
            return null;
        }

        if ((int) $order->paymentStatus === PaymentStatusCode::PAID->legacy()) {
            return $this->buildPaymentResponse(
                (string) $transaction->provider_transaction_id,
                $transaction->provider_response['status'] ?? [],
                $transaction->provider_response['pay'] ?? [],
            );
        }

        $statusResponse = $paylov->getTransactions((string) $transaction->provider_transaction_id);
        if (!$this->remoteTransactionLooksPaid($statusResponse)) {
            return null;
        }

        $cardSnapshot = $transaction->provider_response['card_snapshot'] ?? [];
        $payResponse = $transaction->provider_response['pay'] ?? [];
        $receipt = $transaction->provider_response['create'] ?? [];

        $card = new UserCard([
            'card_number' => $cardSnapshot['masked_number'] ?? null,
            'vendor' => $cardSnapshot['vendor'] ?? null,
            'card_name' => $cardSnapshot['card_name'] ?? null,
            'phone_number' => $cardSnapshot['phone_number'] ?? null,
            'provider_card_id' => $cardSnapshot['provider_card_id'] ?? null,
        ]);

        return $this->finalizeSuccessfulPayment(
            order: $order,
            user: $user,
            card: $card,
            transaction: $transaction,
            transactionId: (string) $transaction->provider_transaction_id,
            receipt: is_array($receipt) ? $receipt : [],
            payResponse: is_array($payResponse) ? $payResponse : [],
            statusResponse: $statusResponse,
            localFinalizeError: 'Recovered from previously charged pending order.',
        );
    }

    private function finalizeSuccessfulPayment(
        Sold $order,
        User $user,
        UserCard $card,
        Transaction $transaction,
        string $transactionId,
        array $receipt,
        ?array $payResponse,
        array $statusResponse,
        ?string $localFinalizeError = null,
    ): array {
        $providerResponse = [
            'create' => $receipt,
            'pay' => $payResponse,
            'status' => $statusResponse,
            'card_snapshot' => $this->cardSnapshot($card),
        ];

        if ($localFinalizeError) {
            $providerResponse['local_finalize_error'] = $localFinalizeError;
        }

        $this->updateTransaction($transaction, [
            'state' => 2,
            'perform_time' => now()->format('Y-m-d H:i:s'),
            'perform_time_unix' => time(),
            'provider_response' => $providerResponse,
        ]);

        DB::transaction(function () use ($order, $user) {
            $freshOrder = Sold::query()->lockForUpdate()->find($order->id);
            if (!$freshOrder) {
                throw new RuntimeException('Buyurtma topilmadi.');
            }

            if ((int) $freshOrder->paymentStatus !== PaymentStatusCode::PAID->legacy()) {
                $this->orderService->handleOrderPaid($freshOrder, $user);
            }
        });

        return $this->buildPaymentResponse($transactionId, $statusResponse, $payResponse ?? []);
    }

    private function canAttemptReconcile(?array $payResponse, ?array $statusResponse): bool
    {
        return (is_array($payResponse) && $payResponse !== [])
            || (is_array($statusResponse) && $statusResponse !== []);
    }

    private function remoteTransactionLooksPaid(array $statusResponse, ?array $payResponse = null): bool
    {
        $transaction = data_get($statusResponse, 'result.transactions.0', []);
        $state = data_get($transaction, 'state');
        if (in_array((string) $state, ['2', 'paid', 'success', 'performed', 'completed'], true)) {
            return true;
        }

        $statusCandidates = [
            data_get($transaction, 'status'),
            data_get($transaction, 'status_code'),
            data_get($transaction, 'result.status'),
            data_get($transaction, 'transactionStatus'),
        ];

        foreach ($statusCandidates as $candidate) {
            $normalized = strtolower(trim((string) $candidate));
            if (in_array($normalized, ['paid', 'success', 'succeeded', 'performed', 'completed'], true)) {
                return true;
            }
        }

        if (is_array($payResponse) && $payResponse !== [] && empty($payResponse['error']) && $transaction === []) {
            return true;
        }

        return false;
    }

    private function buildPaymentResponse(string $transactionId, array $statusResponse = [], array $payResponse = []): array
    {
        return [
            'transaction_id' => $transactionId,
            'transaction' => data_get($statusResponse, 'result.transactions.0')
                ?? ($payResponse['result'] ?? []),
        ];
    }

    private function buildAlreadyPaidResponse(Sold $order): array
    {
        $transaction = Transaction::query()
            ->where('order_id', $order->id)
            ->where('payment_type', 'order')
            ->where('provider', 'paylov')
            ->latest('id')
            ->first();

        $providerResponse = is_array($transaction?->provider_response)
            ? $transaction->provider_response
            : [];

        return $this->buildPaymentResponse(
            (string) ($transaction?->provider_transaction_id ?? $transaction?->paycom_transaction_id ?? ''),
            is_array($providerResponse['status'] ?? null) ? $providerResponse['status'] : [],
            is_array($providerResponse['pay'] ?? null) ? $providerResponse['pay'] : [],
        );
    }

    private function createPendingTransaction(
        Sold $order,
        User $user,
        UserCard $card,
        string $transactionId,
        array $receipt,
    ): Transaction {
        $payload = $this->filterTransactionPayload([
            'owner_id' => $user->id,
            'order_id' => $order->id,
            'amount' => $order->amount,
            'payment_type' => 'order',
            'state' => 1,
            'create_time' => now()->format('Y-m-d H:i:s'),
            // Legacy jadval faqat payme ustunlarini bilishi mumkin.
            'paycom_transaction_id' => $transactionId,
            'paycom_time_datetime' => now()->format('Y-m-d H:i:s'),
            'provider' => 'paylov',
            'provider_transaction_id' => $transactionId,
            'provider_card_id' => $card->provider_card_id,
            'provider_response' => [
                'create' => $receipt,
                'card_snapshot' => $this->cardSnapshot($card),
            ],
            'receivers' => [],
        ]);

        $id = DB::table('transactions')->insertGetId($payload);

        return Transaction::query()->findOrFail($id);
    }

    private function updateTransaction(Transaction $transaction, array $payload): void
    {
        $payload = $this->filterTransactionPayload($payload);

        if ($payload === []) {
            return;
        }

        DB::table('transactions')
            ->where('id', $transaction->id)
            ->update($payload);

        $transaction->refresh();
    }

    private function filterTransactionPayload(array $payload): array
    {
        $columns = $this->transactionColumns();

        return collect($payload)
            ->filter(fn ($value, $key) => in_array($key, $columns, true))
            ->map(function ($value, $key) {
                if (in_array($key, ['receivers', 'provider_response', 'perform_fiscal_data', 'cancel_fiscal_data'], true) && is_array($value)) {
                    return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }

                if ($key === 'perform_time_unix' && !is_null($value)) {
                    return (string) $value;
                }

                return $value;
            })
            ->all();
    }

    private function transactionColumns(): array
    {
        if ($this->transactionColumns !== null) {
            return $this->transactionColumns;
        }

        $this->transactionColumns = Schema::hasTable('transactions')
            ? Schema::getColumnListing('transactions')
            : [];

        return $this->transactionColumns;
    }

    private function cardSnapshot(UserCard $card): array
    {
        return [
            'masked_number' => $card->card_number,
            'vendor' => $card->vendor,
            'card_name' => $card->card_name,
            'phone_number' => $card->phone_number,
            'provider_card_id' => $card->provider_card_id,
        ];
    }
}
