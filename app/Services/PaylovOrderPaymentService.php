<?php

namespace App\Services;

use App\Enums\PaymentStatusCode;
use App\Models\Kirim;
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

        if ($order->payment_status_code !== PaymentStatusCode::CARD_PENDING->value) {
            throw new RuntimeException('Bu buyurtma karta bilan to‘lovni kutmayapti.');
        }

        if ((int) $order->paymentStatus === PaymentStatusCode::PAID->legacy()) {
            throw new RuntimeException('Buyurtma allaqachon to‘langan.');
        }

        if (!$card->is_verified || blank($card->provider_card_id)) {
            throw new RuntimeException('Tasdiqlanmagan karta bilan to‘lab bo‘lmaydi.');
        }

        $paylov = PaylovService::make();
        $paylov->ensureCardReadyForPayment($card);

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

        try {
            $payResponse = $paylov->payReceipt($transactionId, $card->provider_card_id, (string) $user->id);
            $statusResponse = $paylov->getTransactions($transactionId);

            $this->updateTransaction($transaction, [
                'state' => 2,
                'perform_time' => now()->format('Y-m-d H:i:s'),
                'perform_time_unix' => time(),
                'provider_response' => [
                    'create' => $receipt,
                    'pay' => $payResponse,
                    'status' => $statusResponse,
                    'card_snapshot' => $this->cardSnapshot($card),
                ],
            ]);

            DB::transaction(function () use ($order, $user) {
                $freshOrder = Sold::query()->lockForUpdate()->find($order->id);
                if (!$freshOrder) {
                    throw new RuntimeException('Buyurtma topilmadi.');
                }

                if ((int) $freshOrder->paymentStatus !== PaymentStatusCode::PAID->legacy()) {
                    $this->orderService->handleOrderPaid($freshOrder, $user);

                    Kirim::create([
                        'user_id' => $freshOrder->user_id,
                        'order_id' => $freshOrder->id,
                        'paymentStatus' => PaymentStatusCode::PAID->legacy(),
                        'amount' => $freshOrder->amount,
                    ]);
                }
            });

            return [
                'transaction_id' => $transactionId,
                'transaction' => $statusResponse['result']['transactions'][0] ?? ($payResponse['result'] ?? []),
            ];
        } catch (\Throwable $e) {
            $this->updateTransaction($transaction, [
                'state' => -1,
                'reason' => 0,
                'cancel_time' => (string) intval(round(microtime(true) * 1000)),
                'provider_response' => [
                    'create' => $receipt,
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
