<?php

namespace App\Services;

use App\Models\ProjectSetting;
use App\Models\Transaction;
use RuntimeException;

class PaylovP2pRefundService
{
    public function refundToOriginalCard(Transaction $transaction, int $amount, array $meta = []): array
    {
        if ($amount <= 0) {
            throw new RuntimeException('Refund summasi noto‘g‘ri.');
        }

        $providerCardId = (string) (
            $transaction->provider_card_id
            ?: data_get($transaction->provider_response, 'card_snapshot.provider_card_id')
        );

        if ($providerCardId === '') {
            throw new RuntimeException('Original to‘lov kartasi topilmadi.');
        }

        $settings = ProjectSetting::query()->first();
        $senderCardId = trim((string) ($settings?->paylov_refund_sender_card_id ?? ''));
        $serviceId = trim((string) ($settings?->paylov_refund_service_id ?? ''));

        if ($senderCardId === '') {
            throw new RuntimeException('Paylov refund sender card sozlanmagan.');
        }

        $paylov = PaylovService::make();
        $receiver = $paylov->p2pReceiver($providerCardId);
        $created = $paylov->p2pTransferCreate(
            receiverCardNumberOrRef: $providerCardId,
            amount: $amount,
            senderCardId: $senderCardId,
            serviceId: $serviceId !== '' ? $serviceId : null,
        );

        $p2pTransactionId = (string) data_get($created, 'result.transactionId', '');
        if ($p2pTransactionId === '') {
            throw new RuntimeException('Paylov P2P transactionId qaytmadi.');
        }

        $confirmed = $paylov->p2pTransferConfirm($p2pTransactionId, $senderCardId);

        return [
            'receiver' => $receiver,
            'create' => $created,
            'confirm' => $confirmed,
            'transaction_id' => $p2pTransactionId,
            'receiver_card_ref' => $providerCardId,
            'meta' => $meta,
        ];
    }
}
