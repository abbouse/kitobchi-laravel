<?php

namespace App\Services;

use App\Enums\OrderStatusCode;
use App\Enums\PaymentStatusCode;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserCard;

class RefundCardLockService
{
    public function cardRemovalBlocked(User $user, ?UserCard $card = null): bool
    {
        $providerCardId = trim((string) ($card?->provider_card_id ?? ''));
        if ($providerCardId === '') {
            return false;
        }

        return Transaction::query()
            ->join('solds', 'solds.id', '=', 'transactions.order_id')
            ->where('transactions.owner_id', $user->id)
            ->where('transactions.provider', 'paylov')
            ->where('transactions.payment_type', 'order')
            ->where('transactions.state', 2)
            ->where('transactions.provider_card_id', $providerCardId)
            ->where(function ($query) {
                $query->whereIn('solds.status_code', [
                    OrderStatusCode::PENDING->value,
                    OrderStatusCode::PACKING->value,
                ])->orWhere(function ($fallback) {
                    $fallback->whereNull('solds.status_code')
                        ->whereIn('solds.status', [
                            OrderStatusCode::PENDING->legacy(),
                            OrderStatusCode::PACKING->legacy(),
                        ]);
                });
            })
            ->where(function ($query) {
                $query->where('solds.payment_status_code', PaymentStatusCode::PAID->value)
                    ->orWhere(function ($fallback) {
                        $fallback->whereNull('solds.payment_status_code')
                            ->where('solds.paymentStatus', PaymentStatusCode::PAID->legacy());
                    });
            })
            ->exists();
    }
}
