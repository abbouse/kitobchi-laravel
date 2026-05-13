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
use RuntimeException;

class PaylovOrderPaymentService
{
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

        $transaction = Transaction::create([
            'owner_id' => $user->id,
            'order_id' => $order->id,
            'amount' => $order->amount,
            'payment_type' => 'order',
            'state' => 1,
            'provider' => 'paylov',
            'provider_transaction_id' => $transactionId,
            'provider_card_id' => $card->provider_card_id,
            'provider_response' => $receipt,
            'receivers' => [],
        ]);

        try {
            $payResponse = $paylov->payReceipt($transactionId, $card->provider_card_id, (string) $user->id);
            $statusResponse = $paylov->getTransactions($transactionId);

            $transaction->update([
                'state' => 2,
                'perform_time' => (int) round(microtime(true) * 1000),
                'perform_time_unix' => time(),
                'provider_response' => [
                    'create' => $receipt,
                    'pay' => $payResponse,
                    'status' => $statusResponse,
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
            $transaction->update([
                'state' => -1,
                'reason' => 0,
                'cancel_time' => (int) round(microtime(true) * 1000),
                'provider_response' => [
                    'create' => $receipt,
                    'error' => $e->getMessage(),
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
}
