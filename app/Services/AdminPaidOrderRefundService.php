<?php

namespace App\Services;

use App\Enums\PaymentStatusCode;
use App\Models\Admin;
use App\Models\OrderRefund;
use App\Models\Sold;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class AdminPaidOrderRefundService
{
    private ?array $transactionColumns = null;

    public function __construct(
        private readonly OrderService $orderService,
        private readonly OrderStatusPushService $orderStatusPushService,
    ) {}

    public function refundAndCancelOrder(Sold $order, Admin $admin, ?string $reason = null): array
    {
        if (! $admin->isSuperAdmin()) {
            throw new RuntimeException('Bu amal faqat superadmin uchun ruxsat etilgan.');
        }

        $transaction = Transaction::query()
            ->where('order_id', $order->id)
            ->where('payment_type', 'order')
            ->where('provider', 'paylov')
            ->latest('id')
            ->first();

        if (! $transaction) {
            throw new RuntimeException('Ushbu buyurtma uchun Paylov tranzaksiyasi topilmadi.');
        }

        $transactionId = (string) ($transaction->provider_transaction_id ?: $transaction->paycom_transaction_id);
        if ($transactionId === '') {
            throw new RuntimeException('Tranzaksiya identifikatori topilmadi.');
        }

        $providerResponse = is_array($transaction->provider_response)
            ? $transaction->provider_response
            : [];

        $existingCancel = is_array($providerResponse['cancel'] ?? null)
            ? $providerResponse['cancel']
            : [];
        $existingDismiss = is_array($providerResponse['dismiss'] ?? null)
            ? $providerResponse['dismiss']
            : [];

        if (($this->looksCancelled($existingCancel) || $existingDismiss !== []) && $this->isOrderCancelled($order)) {
            return [
                'transaction_id' => $transactionId,
                'cancel' => ($existingCancel['result'] ?? []) ?: $existingDismiss,
                'message' => 'Refund va bekor qilish allaqachon bajarilgan.',
            ];
        }

        $paymentStatus = PaymentStatusCode::fromLegacy($order->payment_status_code ?? $order->paymentStatus);
        $providerAction = $paymentStatus === PaymentStatusCode::HELD ? 'hold_dismiss' : 'cancel';

        $cancelResponse = $existingDismiss;
        if ($providerAction === 'cancel') {
            $paylov = PaylovService::make();
            $cancelResponse = $this->looksCancelled($existingCancel)
                ? $existingCancel
                : $paylov->cancelPayment($transactionId);

            $this->updateTransaction($transaction, [
                'cancel_time' => (string) intval(round(microtime(true) * 1000)),
                'provider_response' => array_merge($providerResponse, [
                    'cancel' => $cancelResponse,
                    'admin_refund' => [
                        'admin_id' => $admin->id,
                        'admin_name' => $admin->name,
                        'reason' => $reason,
                        'provider_action' => $providerAction,
                        'cancelled_at' => now()->toIso8601String(),
                    ],
                ]),
            ]);
        }

        $previousStatus = (string) ($order->status ?? 'F');
        $result = $this->orderService->cancelOrder($order, strict: false);

        if (($result['ok'] ?? false) !== true) {
            $this->markNeedsLocalCancel($transaction, $providerResponse, $cancelResponse, $admin, $reason, $result['message'] ?? 'Local cancel failed');
            throw new RuntimeException('Pul qaytarildi, lekin buyurtmani ichki bekor qilish yakunlanmadi. Iltimos, qayta urinib ko‘ring.');
        }

        if ($providerAction === 'hold_dismiss') {
            $transaction->refresh();
            $providerResponse = is_array($transaction->provider_response)
                ? $transaction->provider_response
                : [];
            $cancelResponse = is_array($providerResponse['dismiss'] ?? null)
                ? $providerResponse['dismiss']
                : [];
            $this->updateTransaction($transaction, [
                'provider_response' => array_merge($providerResponse, [
                    'admin_refund' => [
                        'admin_id' => $admin->id,
                        'admin_name' => $admin->name,
                        'reason' => $reason,
                        'provider_action' => $providerAction,
                        'cancelled_at' => now()->toIso8601String(),
                    ],
                ]),
            ]);
        }

        $this->orderStatusPushService->sendForTransition($order->fresh(), $previousStatus, 'F');
        $this->writeRefundLedger($order->fresh() ?? $order, $admin, $reason, $transaction, $cancelResponse, $paymentStatus, $providerAction);

        Log::warning('[AdminRefund] Order refunded and cancelled', [
            'order_id' => $order->id,
            'transaction_id' => $transactionId,
            'admin_id' => $admin->id,
            'reason' => $reason,
        ]);

        return [
            'transaction_id' => $transactionId,
            'cancel' => $cancelResponse['result'] ?? [],
            'message' => 'Pul qaytarildi va buyurtma bekor qilindi.',
        ];
    }

    private function writeRefundLedger(
        Sold $order,
        Admin $admin,
        ?string $reason,
        Transaction $transaction,
        array $providerPayload,
        PaymentStatusCode $paymentStatus,
        string $providerAction,
    ): void {
        $exists = OrderRefund::query()
            ->where('order_id', $order->id)
            ->where('type', 'admin_full_order')
            ->where('status', 'completed')
            ->exists();

        if ($exists) {
            return;
        }

        $cardRefundAmount = $paymentStatus === PaymentStatusCode::PAID
            ? max(0, (int) ($order->amount ?? $transaction->amount ?? 0))
            : 0;

        OrderRefund::query()->create([
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'type' => 'admin_full_order',
            'provider' => $providerAction === 'hold_dismiss' ? 'paylov_hold_dismiss' : 'paylov_cancel',
            'card_refund_amount' => $cardRefundAmount,
            'cashback_restore_amount' => max(0, (int) ($order->cashbackAmount ?? 0)),
            'gift_cert_restore_amount' => max(0, (int) ($order->giftCertAmount ?? 0)),
            'delivery_refund_amount' => max(0, (int) ($order->deliveryPrice ?? 0)),
            'packaging_refund_amount' => max(0, (int) ($order->packaging_price ?? 0)),
            'total_customer_value' => max(0, $cardRefundAmount + (int) ($order->cashbackAmount ?? 0) + (int) ($order->giftCertAmount ?? 0)),
            'status' => 'completed',
            'provider_transaction_id' => (string) ($transaction->provider_transaction_id ?: $transaction->paycom_transaction_id),
            'reason_code' => 'admin_refund_cancel',
            'reason_note_uz' => $reason ?: 'Admin tomonidan to‘liq bekor qilindi.',
            'reason_note_ru' => $reason ?: 'Полная отмена администратором.',
            'reason_note_en' => $reason ?: 'Fully cancelled by admin.',
            'provider_payload' => $providerPayload,
            'processed_by_admin_id' => $admin->id,
            'processed_at' => now(),
        ]);

        if ($cardRefundAmount > 0) {
            $order->forceFill([
                'refund_total_amount' => (int) ($order->refund_total_amount ?? 0) + $cardRefundAmount,
            ])->save();
        }
    }

    private function markNeedsLocalCancel(
        Transaction $transaction,
        array $providerResponse,
        array $cancelResponse,
        Admin $admin,
        ?string $reason,
        string $errorMessage,
    ): void {
        $this->updateTransaction($transaction, [
            'cancel_time' => (string) intval(round(microtime(true) * 1000)),
            'provider_response' => array_merge($providerResponse, [
                'cancel' => $cancelResponse,
                'admin_refund' => [
                    'admin_id' => $admin->id,
                    'admin_name' => $admin->name,
                    'reason' => $reason,
                    'cancelled_at' => now()->toIso8601String(),
                    'needs_local_cancel' => true,
                    'local_cancel_error' => $errorMessage,
                ],
            ]),
        ]);
    }

    private function looksCancelled(array $response): bool
    {
        return strtolower((string) data_get($response, 'result.status')) === 'cancelled';
    }

    private function isOrderCancelled(Sold $order): bool
    {
        $fresh = $order->fresh();

        return (string) ($fresh?->status_code ?? $order->status_code ?? '') === 'cancelled'
            || (string) ($fresh?->status ?? $order->status ?? '') === 'F';
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
}
