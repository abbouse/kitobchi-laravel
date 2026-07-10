<?php

namespace App\Services;

use App\Enums\PaymentStatusCode;
use App\Models\Sold;
use App\Models\SplitContract;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserCard;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

// Split shartnoma xizmati shu faylda lazy (app()) chaqiriladi — circular DI oldini olish uchun.

class PaylovOrderPaymentService
{
    private ?array $transactionColumns = null;

    public function __construct(
        private readonly OrderService $orderService,
        private readonly OrderStatusPushService $orderStatusPushService,
    ) {}

    public function payPendingOrder(Sold $order, User $user, UserCard $card): array
    {
        if ((int) $order->user_id !== (int) $user->id) {
            throw new RuntimeException('Buyurtma sizga tegishli emas.');
        }

        if ((int) $card->user_id !== (int) $user->id) {
            throw new RuntimeException('Bu karta sizga tegishli emas.');
        }

        if ((int) $order->paymentStatus === PaymentStatusCode::PAID->legacy()) {
            return $this->buildAlreadyPaidResponse($order);
        }

        $paymentStatus = PaymentStatusCode::fromLegacy($order->payment_status_code ?? $order->paymentStatus);
        if ($paymentStatus === PaymentStatusCode::HELD) {
            return $this->buildHeldResponse($order);
        }

        if ($paymentStatus !== PaymentStatusCode::CARD_PENDING) {
            throw new RuntimeException('Bu buyurtma karta bilan to‘lovni kutmayapti.');
        }

        if (! $card->is_verified || blank($card->provider_card_id)) {
            throw new RuntimeException('Tasdiqlanmagan karta bilan to‘lab bo‘lmaydi.');
        }

        $paylov = PaylovService::make();
        $paylov->ensureCardReadyForPayment($card);

        if ($reconciled = $this->reconcileExistingSuccessfulPayment($order, $user, $paylov)) {
            return $reconciled;
        }

        $holdMinutes = max(1, min(40320, (int) config('services.paylov.hold_minutes', 5760)));
        $holdCreate = $paylov->createHold(
            (string) $user->id,
            (string) $card->provider_card_id,
            (int) $order->amount,
            $holdMinutes,
            [
                'order_id' => 'NRK-'.$order->id,
                'merchant_id' => $paylov->merchantId(),
                'payment_mode' => 'hold',
            ],
        );

        $transactionId = (string) data_get($holdCreate, 'result.transactionId', '');
        if ($transactionId === '') {
            throw new RuntimeException('Paylov transactionId qaytarmadi.');
        }

        try {
            $transaction = $this->createPendingTransaction(
                $order,
                $user,
                $card,
                $transactionId,
                $holdCreate,
                [
                    'mode' => 'hold',
                    'hold' => [
                        'status' => 'held',
                        'amount' => (int) $order->amount,
                        'hold_minutes' => $holdMinutes,
                        'created_at' => now()->toIso8601String(),
                    ],
                ],
            );
        } catch (\Throwable $e) {
            try {
                $paylov->dismissHold($transactionId);
            } catch (\Throwable $dismissError) {
                Log::warning('[Paylov] Hold dismiss after transaction create failure failed', [
                    'order_id' => $order->id,
                    'transaction_id' => $transactionId,
                    'error' => $dismissError->getMessage(),
                ]);
            }

            throw $e;
        }

        try {
            DB::transaction(function () use ($order) {
                $freshOrder = Sold::query()->lockForUpdate()->find($order->id);
                if (! $freshOrder) {
                    throw new RuntimeException('Buyurtma topilmadi.');
                }

                if (PaymentStatusCode::fromLegacy($freshOrder->payment_status_code ?? $freshOrder->paymentStatus) === PaymentStatusCode::CARD_PENDING) {
                    $this->orderService->handleOrderHeld($freshOrder);
                }
            });
        } catch (\Throwable $e) {
            $this->dismissCreatedHoldAfterLocalFailure($paylov, $transaction, $transactionId, $e->getMessage());

            throw $e;
        }

        return $this->buildHoldResponse($transactionId, $holdCreate, $transaction);
    }

    public function chargeHeldOrder(Sold $order, ?int $amount = null, string $reason = 'order_handover'): ?array
    {
        $paymentStatus = PaymentStatusCode::fromLegacy($order->payment_status_code ?? $order->paymentStatus);
        if ($paymentStatus === PaymentStatusCode::PAID) {
            return $this->buildAlreadyPaidResponse($order);
        }

        if ($paymentStatus !== PaymentStatusCode::HELD) {
            return null;
        }

        // Split (Nasiya) buyurtmasi: hold faqat 1-installment uchun qo'yilgan —
        // shartnoma faollashtiriladi, buyurtma to'langan deb belgilanadi
        // (marketplace qolganini o'z zimmasiga oladi, keyingi to'lovlar jadval bo'yicha).
        if ($splitContract = $this->pendingSplitContractFor($order)) {
            return $this->settleSplitOnHandover($order, $splitContract, $amount, $reason);
        }

        $transaction = $this->latestHeldTransaction($order);
        if (! $transaction || blank($transaction->provider_transaction_id)) {
            throw new RuntimeException('Hold transaction topilmadi.');
        }

        $chargeAmount = max(0, (int) ($amount ?? $order->amount));
        if ($chargeAmount <= 0) {
            return $this->dismissHeldOrder($order, 'zero_amount_after_cancellations');
        }

        $paylov = PaylovService::make();
        $chargeResponse = $paylov->chargeHold((string) $transaction->provider_transaction_id, $chargeAmount);
        $statusResponse = [];
        try {
            $statusResponse = $paylov->getTransactions((string) $transaction->provider_transaction_id);
        } catch (\Throwable $statusError) {
            Log::warning('[Paylov] Hold charge status refresh failed', [
                'order_id' => $order->id,
                'transaction_id' => $transaction->provider_transaction_id,
                'error' => $statusError->getMessage(),
            ]);
        }
        $providerResponse = is_array($transaction->provider_response) ? $transaction->provider_response : [];
        $providerResponse['charge'] = $chargeResponse;
        $providerResponse['status'] = $statusResponse;
        $providerResponse['hold']['status'] = 'charged';
        $providerResponse['hold']['charged_amount'] = $chargeAmount;
        $providerResponse['hold']['charged_at'] = now()->toIso8601String();
        $providerResponse['hold']['charge_reason'] = $reason;

        $this->updateTransaction($transaction, [
            'amount' => $chargeAmount,
            'state' => 2,
            'perform_time' => now()->format('Y-m-d H:i:s'),
            'perform_time_unix' => time(),
            'provider_response' => $providerResponse,
        ]);

        DB::transaction(function () use ($order, $chargeAmount) {
            $freshOrder = Sold::query()->lockForUpdate()->find($order->id);
            if (! $freshOrder) {
                throw new RuntimeException('Buyurtma topilmadi.');
            }

            if ((int) $freshOrder->amount !== $chargeAmount) {
                $freshOrder->amount = $chargeAmount;
                $freshOrder->save();
            }

            if (PaymentStatusCode::fromLegacy($freshOrder->payment_status_code ?? $freshOrder->paymentStatus) !== PaymentStatusCode::PAID) {
                $user = $freshOrder->user ?: User::query()->find((int) $freshOrder->user_id);
                if (! $user) {
                    throw new RuntimeException('Buyurtma foydalanuvchisi topilmadi.');
                }

                $this->orderService->handleOrderPaid($freshOrder, $user, giveCashback: false);
            }
        });

        // OFD fiskal chek — fonda yaratiladi
        \App\Jobs\RegisterOrderFiscalReceiptJob::dispatch((int) $order->id);

        return $this->buildPaymentResponse((string) $transaction->provider_transaction_id, $statusResponse, $chargeResponse);
    }

    public function dismissHeldOrder(Sold $order, string $reason = 'order_cancelled'): ?array
    {
        // Split buyurtmasi bekor bo'lsa: shartnoma bekor, upfront hold qaytadi.
        if ($splitContract = $this->pendingSplitContractFor($order)) {
            app(SplitContractService::class)->cancelPending($splitContract, $reason);

            return ['split_contract_id' => $splitContract->id, 'status' => 'cancelled'];
        }

        $transaction = $this->latestHeldTransaction($order);
        if (! $transaction || blank($transaction->provider_transaction_id)) {
            return null;
        }

        $paylov = PaylovService::make();
        $dismissResponse = $paylov->dismissHold((string) $transaction->provider_transaction_id);
        $providerResponse = is_array($transaction->provider_response) ? $transaction->provider_response : [];
        $providerResponse['dismiss'] = $dismissResponse;
        $providerResponse['hold']['status'] = 'dismissed';
        $providerResponse['hold']['dismiss_reason'] = $reason;
        $providerResponse['hold']['dismissed_at'] = now()->toIso8601String();

        $this->updateTransaction($transaction, [
            'state' => -1,
            'reason' => 0,
            'cancel_time' => (string) intval(round(microtime(true) * 1000)),
            'provider_response' => $providerResponse,
        ]);

        return $dismissResponse;
    }

    private function pendingSplitContractFor(Sold $order): ?SplitContract
    {
        if (! Schema::hasTable('split_contracts')) {
            return null;
        }

        return SplitContract::query()
            ->where('order_id', $order->id)
            ->where('status', SplitContract::STATUS_PENDING)
            ->latest('id')
            ->first();
    }

    private function settleSplitOnHandover(
        Sold $order,
        SplitContract $contract,
        ?int $amount,
        string $reason,
    ): ?array {
        $splitService = app(SplitContractService::class);
        $chargeAmount = max(0, (int) ($amount ?? $order->amount));

        // Hamma item bekor bo'lgan — shartnoma ham bekor, hold qaytadi.
        if ($chargeAmount <= 0) {
            $splitService->cancelPending($contract, 'zero_amount_after_cancellations');

            return ['split_contract_id' => $contract->id, 'status' => 'cancelled'];
        }

        $contract = $splitService->activate($contract);

        // Topshirishgacha qisman bekor qilishlar bo'lgan bo'lsa — farq kredit
        // sifatida kelajakdagi to'lovlardan avtomatik ayiriladi.
        $originalAmount = (int) $order->amount;
        $reduction = max(0, $originalAmount - $chargeAmount);

        if ($reduction > 0) {
            try {
                $splitService->applyCancellationCredit(
                    $contract,
                    min($reduction, (int) $contract->principal_amount),
                    0,
                    'items_cancelled_before_handover',
                );
            } catch (\Throwable $e) {
                Log::error('[Split] Handover reduction credit failed', [
                    'contract_id' => $contract->id,
                    'reduction' => $reduction,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        DB::transaction(function () use ($order, $chargeAmount) {
            $freshOrder = Sold::query()->lockForUpdate()->find($order->id);
            if (! $freshOrder) {
                throw new RuntimeException('Buyurtma topilmadi.');
            }

            if ((int) $freshOrder->amount !== $chargeAmount) {
                $freshOrder->amount = $chargeAmount;
                $freshOrder->save();
            }

            if (PaymentStatusCode::fromLegacy($freshOrder->payment_status_code ?? $freshOrder->paymentStatus) !== PaymentStatusCode::PAID) {
                $user = $freshOrder->user ?: User::query()->find((int) $freshOrder->user_id);
                if (! $user) {
                    throw new RuntimeException('Buyurtma foydalanuvchisi topilmadi.');
                }

                $this->orderService->handleOrderPaid($freshOrder, $user, giveCashback: false);
            }
        });

        Log::info('[Split] Order settled on handover', [
            'order_id' => $order->id,
            'contract_id' => $contract->id,
            'reason' => $reason,
        ]);

        return [
            'split_contract_id' => $contract->id,
            'status' => 'activated',
        ];
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

        if (! $transaction || blank($transaction->provider_transaction_id)) {
            return null;
        }

        if ($this->transactionLooksHeld($transaction)) {
            DB::transaction(function () use ($order) {
                $freshOrder = Sold::query()->lockForUpdate()->find($order->id);
                if ($freshOrder && PaymentStatusCode::fromLegacy($freshOrder->payment_status_code ?? $freshOrder->paymentStatus) === PaymentStatusCode::CARD_PENDING) {
                    $this->orderService->handleOrderHeld($freshOrder);
                }
            });

            return $this->buildHoldResponse(
                (string) $transaction->provider_transaction_id,
                is_array($transaction->provider_response['create'] ?? null) ? $transaction->provider_response['create'] : [],
                $transaction,
            );
        }

        if ((int) $order->paymentStatus === PaymentStatusCode::PAID->legacy()) {
            return $this->buildPaymentResponse(
                (string) $transaction->provider_transaction_id,
                $transaction->provider_response['status'] ?? [],
                $transaction->provider_response['pay'] ?? [],
            );
        }

        $statusResponse = $paylov->getTransactions((string) $transaction->provider_transaction_id);
        if (! $this->remoteTransactionLooksPaid($statusResponse)) {
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
        $orderWasRecovered = false;
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

        DB::transaction(function () use ($order, $user, &$orderWasRecovered) {
            $freshOrder = Sold::query()->lockForUpdate()->find($order->id);
            if (! $freshOrder) {
                throw new RuntimeException('Buyurtma topilmadi.');
            }

            if ((int) $freshOrder->paymentStatus !== PaymentStatusCode::PAID->legacy()) {
                $this->orderService->handleOrderPaid($freshOrder, $user);
                $orderWasRecovered = true;
            }
        });

        if ($orderWasRecovered && $localFinalizeError !== null) {
            $this->orderStatusPushService->sendRecoveredPaymentNotice($order->fresh());
        }

        // OFD fiskal chek — fonda yaratiladi
        \App\Jobs\RegisterOrderFiscalReceiptJob::dispatch((int) $order->id);

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

    private function buildHoldResponse(string $transactionId, array $holdCreate = [], ?Transaction $transaction = null): array
    {
        return [
            'transaction_id' => $transactionId,
            'payment_status' => PaymentStatusCode::HELD->value,
            'hold' => [
                'status' => 'held',
                'amount' => (int) ($transaction?->amount ?? data_get($holdCreate, 'result.amount', 0)),
                'transaction' => data_get($holdCreate, 'result') ?? [],
            ],
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

    private function latestHeldTransaction(Sold $order): ?Transaction
    {
        return Transaction::query()
            ->where('order_id', $order->id)
            ->where('payment_type', 'order')
            ->where('provider', 'paylov')
            ->where('state', 1)
            ->latest('id')
            ->get()
            ->first(fn (Transaction $transaction) => $this->transactionLooksHeld($transaction));
    }

    private function dismissCreatedHoldAfterLocalFailure(
        PaylovService $paylov,
        Transaction $transaction,
        string $transactionId,
        string $error,
    ): void {
        $dismissResponse = null;

        try {
            $dismissResponse = $paylov->dismissHold($transactionId);
        } catch (\Throwable $dismissError) {
            Log::warning('[Paylov] Hold dismiss after local failure failed', [
                'transaction_id' => $transactionId,
                'error' => $dismissError->getMessage(),
            ]);
        }

        $providerResponse = is_array($transaction->provider_response) ? $transaction->provider_response : [];
        $providerResponse['local_finalize_error'] = $error;
        $providerResponse['dismiss_after_local_failure'] = $dismissResponse;
        $providerResponse['hold']['status'] = $dismissResponse ? 'dismissed' : 'dismiss_failed';

        $this->updateTransaction($transaction, [
            'state' => -1,
            'reason' => 0,
            'cancel_time' => (string) intval(round(microtime(true) * 1000)),
            'provider_response' => $providerResponse,
        ]);
    }

    private function transactionLooksHeld(Transaction $transaction): bool
    {
        $providerResponse = is_array($transaction->provider_response) ? $transaction->provider_response : [];

        return ($providerResponse['mode'] ?? null) === 'hold'
            || data_get($providerResponse, 'hold.status') === 'held';
    }

    private function createPendingTransaction(
        Sold $order,
        User $user,
        UserCard $card,
        string $transactionId,
        array $receipt,
        array $extraProviderResponse = [],
    ): Transaction {
        $providerResponse = array_merge([
            'create' => $receipt,
            'card_snapshot' => $this->cardSnapshot($card),
        ], $extraProviderResponse);

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
            'provider_response' => $providerResponse,
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

                if ($key === 'perform_time_unix' && ! is_null($value)) {
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
