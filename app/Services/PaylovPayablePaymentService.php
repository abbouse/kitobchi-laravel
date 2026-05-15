<?php

namespace App\Services;

use App\Models\GiftCertificate;
use App\Models\MysteryBoxSubscription;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserCard;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class PaylovPayablePaymentService
{
    private ?array $transactionColumns = null;

    public function __construct(
        private readonly GiftCertService $giftCertService,
        private readonly MysteryBoxService $mysteryBoxService,
    ) {
    }

    public function payPendingGiftCertificate(
        GiftCertificate $certificate,
        User $user,
        UserCard $card,
    ): array {
        if ((int) $certificate->buyer_user_id !== (int) $user->id) {
            throw new RuntimeException('Sertifikat sizga tegishli emas.');
        }

        if ($certificate->status !== GiftCertificate::STATUS_PENDING) {
            throw new RuntimeException('Bu sertifikat to‘lovni kutmayapti.');
        }

        return $this->payPending(
            payable: $certificate,
            user: $user,
            card: $card,
            amount: (int) $certificate->nominal_uzs,
            paymentType: 'gift_certificate',
            account: [
                'gift_certificate_id' => 'GFT-' . $certificate->id,
            ],
            onSuccess: fn (int $id) => $this->giftCertService->activate($id),
        );
    }

    public function payPendingMysteryBox(
        MysteryBoxSubscription $subscription,
        User $user,
        UserCard $card,
    ): array {
        if ((int) $subscription->user_id !== (int) $user->id) {
            throw new RuntimeException('Obuna sizga tegishli emas.');
        }

        if ($subscription->status !== MysteryBoxSubscription::STATUS_PENDING) {
            throw new RuntimeException('Bu obuna to‘lovni kutmayapti.');
        }

        return $this->payPending(
            payable: $subscription,
            user: $user,
            card: $card,
            amount: (int) $subscription->price_uzs,
            paymentType: 'mystery_box',
            account: [
                'subscription_id' => 'MBX-' . $subscription->id,
            ],
            onSuccess: fn (int $id) => $this->mysteryBoxService->activate($id),
        );
    }

    /**
     * @param callable(int):bool $onSuccess
     */
    private function payPending(
        Model $payable,
        User $user,
        UserCard $card,
        int $amount,
        string $paymentType,
        array $account,
        callable $onSuccess,
    ): array {
        if ((int) $card->user_id !== (int) $user->id) {
            throw new RuntimeException('Bu karta sizga tegishli emas.');
        }

        if (!$card->is_verified || blank($card->provider_card_id)) {
            throw new RuntimeException('Tasdiqlanmagan karta bilan to‘lab bo‘lmaydi.');
        }

        $paylov = PaylovService::make();
        $paylov->ensureCardReadyForPayment($card);

        $receipt = $paylov->createReceipt(
            (string) $user->id,
            $amount,
            array_merge($account, [
                'merchant_id' => $paylov->merchantId(),
            ]),
        );

        $transactionId = (string) ($receipt['result']['transactionId'] ?? '');
        if ($transactionId === '') {
            throw new RuntimeException('Paylov transactionId qaytarmadi.');
        }

        $transaction = $this->createPendingTransaction(
            payable: $payable,
            user: $user,
            card: $card,
            amount: $amount,
            paymentType: $paymentType,
            transactionId: $transactionId,
            receipt: $receipt,
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
                ],
            ]);

            $activated = $onSuccess((int) $payable->getKey());
            if (!$activated) {
                throw new RuntimeException('To‘lov qabul qilindi, lekin ichki aktivatsiya muvaffaqiyatsiz tugadi.');
            }

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
                ],
            ]);

            Log::warning('[Paylov] Payable payment failed', [
                'payment_type' => $paymentType,
                'payable_id' => $payable->getKey(),
                'transaction_id' => $transactionId,
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function createPendingTransaction(
        Model $payable,
        User $user,
        UserCard $card,
        int $amount,
        string $paymentType,
        string $transactionId,
        array $receipt,
    ): Transaction {
        $payload = $this->filterTransactionPayload([
            'owner_id' => $user->id,
            'order_id' => (int) $payable->getKey(),
            'payable_id' => (int) $payable->getKey(),
            'amount' => $amount,
            'payment_type' => $paymentType,
            'state' => 1,
            'create_time' => now()->format('Y-m-d H:i:s'),
            'provider' => 'paylov',
            'provider_transaction_id' => $transactionId,
            'provider_card_id' => $card->provider_card_id,
            'provider_response' => $receipt,
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
}
