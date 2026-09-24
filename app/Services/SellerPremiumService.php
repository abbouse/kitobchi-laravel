<?php

namespace App\Services;

use App\Models\Seller;
use App\Models\SellerPremiumSubscription;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SellerPremiumService
{
    public const PLANS = [
        'monthly' => ['id' => 'monthly', 'months' => 1, 'price' => 29000, 'label' => '1 oy'],
        'quarterly' => ['id' => 'quarterly', 'months' => 3, 'price' => 70000, 'label' => '3 oy'],
        'yearly' => ['id' => 'yearly', 'months' => 12, 'price' => 250000, 'label' => '12 oy'],
    ];

    public function plans(): array
    {
        return array_values(array_map(fn (array $plan) => [
            'type' => $plan['id'],
            'months' => $plan['months'],
            'price' => $plan['price'],
            'label' => $plan['label'],
        ], self::PLANS));
    }

    public function resolveStoreSeller(Seller $seller): Seller
    {
        return $seller->parent_id ? Seller::query()->findOrFail($seller->parent_id) : $seller;
    }

    public function currentSubscription(Seller $seller): ?SellerPremiumSubscription
    {
        return $seller->premiumSubscriptions()
            ->orderByRaw("FIELD(status, 'active', 'paused', 'cancelled')")
            ->latest('expires_at')
            ->latest('id')
            ->first();
    }

    public function syncSeller(Seller $seller): array
    {
        $subscription = $this->currentSubscription($seller);
        $directPremiumActive = $this->hasDirectPremiumAccess($seller);

        if (!$subscription) {
            if (!$directPremiumActive) {
                $this->deactivateSeller($seller);
                $seller->refresh();
            }

            return $this->snapshot($seller, null);
        }

        if ($subscription->status !== SellerPremiumSubscription::STATUS_ACTIVE) {
            if (!$directPremiumActive) {
                $this->deactivateSeller($seller);
                $seller->refresh();
            }

            return $this->snapshot($seller, $subscription);
        }

        if ($subscription->expires_at && $subscription->expires_at->isFuture()) {
            $this->activateSellerUntil($seller, $subscription->expires_at);
            return $this->snapshot($seller, $subscription);
        }

        return DB::transaction(function () use ($seller, $subscription) {
            $subscription->refresh();
            $seller->refresh();

            if ($subscription->cancel_at_period_end || !$subscription->auto_renew) {
                $subscription->update([
                    'status' => SellerPremiumSubscription::STATUS_CANCELLED,
                    'cancelled_at' => now(),
                ]);

                if (!$this->hasDirectPremiumAccess($seller)) {
                    $this->deactivateSeller($seller);
                }

                return $this->snapshot($seller, $subscription->fresh());
            }

            if ((int) $seller->balance < (int) $subscription->price_uzs) {
                $subscription->update([
                    'status' => SellerPremiumSubscription::STATUS_PAUSED,
                    'stop_reason' => 'insufficient_balance',
                    'stopped_at' => now(),
                ]);

                if (!$this->hasDirectPremiumAccess($seller)) {
                    $this->deactivateSeller($seller);
                }

                return $this->snapshot($seller, $subscription->fresh());
            }

            \App\Models\SellerBalanceEntry::record(
                $seller, -(int) $subscription->price_uzs,
                \App\Models\SellerBalanceEntry::TYPE_PREMIUM_RENEWAL,
                'seller_premium_subscription', (int) $subscription->id,
                'Premium obuna avto-uzaytirish'
            );

            $base = $subscription->expires_at && $subscription->expires_at->isFuture()
                ? $subscription->expires_at->copy()
                : now();

            $newExpiry = $base->copy()->addMonths((int) $subscription->duration_months);

            $subscription->update([
                'status' => SellerPremiumSubscription::STATUS_ACTIVE,
                'expires_at' => $newExpiry,
                'last_renewed_at' => now(),
                'stop_reason' => null,
                'stopped_at' => null,
            ]);

            $this->activateSellerUntil($seller, $newExpiry);

            return $this->snapshot($seller, $subscription->fresh());
        });
    }

    public function buy(Seller $seller, string $planKey): array
    {
        $plan = self::PLANS[$planKey] ?? null;
        if (!$plan) {
            abort(422, 'Noto‘g‘ri premium tarif.');
        }

        return DB::transaction(function () use ($seller, $plan, $planKey) {
            $this->syncSeller($seller);
            $subscription = $this->currentSubscription($seller);

            if ((int) $seller->balance < (int) $plan['price']) {
                return [
                    'success' => false,
                    'message' => 'Balans premium obunani yoqish uchun yetarli emas.',
                    'data' => $this->snapshot($seller, $subscription),
                ];
            }

            \App\Models\SellerBalanceEntry::record(
                $seller, -(int) $plan['price'],
                \App\Models\SellerBalanceEntry::TYPE_PREMIUM,
                'seller_premium_plan', null,
                'Premium obuna: ' . $plan['label']
            );

            if ($subscription && $subscription->status === SellerPremiumSubscription::STATUS_ACTIVE && $subscription->expires_at && $subscription->expires_at->isFuture()) {
                $newExpiry = $subscription->expires_at->copy()->addMonths((int) $plan['months']);
                $subscription->update([
                    'plan' => $planKey,
                    'duration_months' => $plan['months'],
                    'price_uzs' => $plan['price'],
                    'expires_at' => $newExpiry,
                    'auto_renew' => true,
                    'cancel_at_period_end' => false,
                    'cancel_requested_at' => null,
                    'status' => SellerPremiumSubscription::STATUS_ACTIVE,
                    'stop_reason' => null,
                    'stopped_at' => null,
                ]);
            } else {
                $subscription = SellerPremiumSubscription::create([
                    'seller_id' => $seller->id,
                    'plan' => $planKey,
                    'duration_months' => $plan['months'],
                    'price_uzs' => $plan['price'],
                    'status' => SellerPremiumSubscription::STATUS_ACTIVE,
                    'auto_renew' => true,
                    'cancel_at_period_end' => false,
                    'started_at' => now(),
                    'expires_at' => now()->addMonths((int) $plan['months']),
                    'last_renewed_at' => now(),
                ]);
            }

            $this->activateSellerUntil($seller, $subscription->expires_at);

            return [
                'success' => true,
                'message' => 'Premium obuna muvaffaqiyatli faollashtirildi.',
                'data' => $this->snapshot($seller->fresh(), $subscription->fresh()),
            ];
        });
    }

    public function grantByAdmin(Seller $seller, string $planKey): array
    {
        $plan = self::PLANS[$planKey] ?? null;
        if (!$plan) {
            abort(422, 'Noto‘g‘ri premium tarif.');
        }

        return DB::transaction(function () use ($seller, $plan, $planKey) {
            $seller->refresh();
            $subscription = $this->currentSubscription($seller);

            if ($subscription && $subscription->status === SellerPremiumSubscription::STATUS_ACTIVE && $subscription->expires_at && $subscription->expires_at->isFuture()) {
                $newExpiry = $subscription->expires_at->copy()->addMonths((int) $plan['months']);
                $subscription->update([
                    'plan' => $planKey,
                    'duration_months' => $plan['months'],
                    'price_uzs' => $plan['price'],
                    'expires_at' => $newExpiry,
                    'auto_renew' => true,
                    'cancel_at_period_end' => false,
                    'cancel_requested_at' => null,
                    'cancelled_at' => null,
                    'status' => SellerPremiumSubscription::STATUS_ACTIVE,
                    'stop_reason' => null,
                    'stopped_at' => null,
                    'last_renewed_at' => now(),
                ]);
            } else {
                if ($subscription && $subscription->status !== SellerPremiumSubscription::STATUS_ACTIVE) {
                    $subscription->update([
                        'status' => SellerPremiumSubscription::STATUS_CANCELLED,
                        'cancelled_at' => now(),
                        'auto_renew' => false,
                        'cancel_at_period_end' => true,
                        'stop_reason' => 'replaced_by_admin_grant',
                    ]);
                }

                $subscription = SellerPremiumSubscription::create([
                    'seller_id' => $seller->id,
                    'plan' => $planKey,
                    'duration_months' => $plan['months'],
                    'price_uzs' => $plan['price'],
                    'status' => SellerPremiumSubscription::STATUS_ACTIVE,
                    'auto_renew' => true,
                    'cancel_at_period_end' => false,
                    'started_at' => now(),
                    'expires_at' => now()->addMonths((int) $plan['months']),
                    'last_renewed_at' => now(),
                ]);
            }

            $this->activateSellerUntil($seller, $subscription->expires_at);

            return [
                'success' => true,
                'message' => 'Premium admin tomonidan muvaffaqiyatli berildi.',
                'data' => $this->snapshot($seller->fresh(), $subscription->fresh()),
            ];
        });
    }

    public function revokeByAdmin(Seller $seller): array
    {
        return DB::transaction(function () use ($seller) {
            $seller->refresh();
            $subscription = $this->currentSubscription($seller);

            if ($subscription) {
                $subscription->update([
                    'status' => SellerPremiumSubscription::STATUS_CANCELLED,
                    'auto_renew' => false,
                    'cancel_at_period_end' => true,
                    'cancel_requested_at' => now(),
                    'cancelled_at' => now(),
                    'stop_reason' => 'revoked_by_admin',
                    'stopped_at' => now(),
                ]);
            }

            $this->deactivateSeller($seller);

            return [
                'success' => true,
                'message' => 'Premium admin tomonidan o‘chirildi.',
                'data' => $this->snapshot($seller->fresh(), $subscription?->fresh()),
            ];
        });
    }

    public function cancelAtPeriodEnd(Seller $seller): array
    {
        $subscription = $this->currentSubscription($seller);

        if (!$subscription || $subscription->status !== SellerPremiumSubscription::STATUS_ACTIVE || !$subscription->expires_at || $subscription->expires_at->isPast()) {
            return [
                'success' => false,
                'message' => 'Faol premium obuna topilmadi.',
                'data' => $this->snapshot($seller, $subscription),
            ];
        }

        $subscription->update([
            'auto_renew' => false,
            'cancel_at_period_end' => true,
            'cancel_requested_at' => now(),
        ]);

        return [
            'success' => true,
            'message' => 'Obuna bekor qilindi. U muddati tugagach o‘chadi.',
            'data' => $this->snapshot($seller, $subscription->fresh()),
        ];
    }

    public function processDueRenewals(): Collection
    {
        return SellerPremiumSubscription::query()
            ->where('status', SellerPremiumSubscription::STATUS_ACTIVE)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->with('seller')
            ->get()
            ->map(function (SellerPremiumSubscription $subscription) {
                $seller = $subscription->seller;
                if (!$seller) {
                    return ['id' => $subscription->id, 'result' => 'missing_seller'];
                }

                $snapshot = $this->syncSeller($seller);

                return [
                    'id' => $subscription->id,
                    'seller_id' => $seller->id,
                    'status' => $snapshot['subscription_status'],
                    'is_premium' => $snapshot['is_premium'],
                ];
            });
    }

    public function isSellerPremium(Seller $seller): bool
    {
        $state = $this->syncSeller($seller);
        return (bool) ($state['is_premium'] ?? false);
    }

    private function hasDirectPremiumAccess(Seller $seller): bool
    {
        if (!(bool) $seller->isPremiumShop) {
            return false;
        }

        if (!$seller->isPremiumExpiresAt) {
            return true;
        }

        return $seller->isPremiumExpiresAt->isFuture();
    }

    private function activateSellerUntil(Seller $seller, ?Carbon $expiresAt): void
    {
        $seller->forceFill([
            'isPremiumShop' => true,
            'isPremiumExpiresAt' => $expiresAt,
        ])->save();
    }

    private function deactivateSeller(Seller $seller): void
    {
        $seller->forceFill([
            'isPremiumShop' => false,
            'isPremiumExpiresAt' => null,
        ])->save();
    }

    private function snapshot(Seller $seller, ?SellerPremiumSubscription $subscription): array
    {
        $subscriptionActive = (bool) (
            $subscription?->status === SellerPremiumSubscription::STATUS_ACTIVE
            && $subscription?->expires_at
            && $subscription->expires_at->isFuture()
        );
        $directPremiumActive = $this->hasDirectPremiumAccess($seller);
        $expiresAt = $subscriptionActive
            ? $subscription?->expires_at
            : $seller->isPremiumExpiresAt;
        $daysLeft = $expiresAt && $expiresAt->isFuture()
            ? max(0, (int) ceil(now()->diffInSeconds($expiresAt, false) / 86400))
            : 0;

        $isPremium = $subscriptionActive || $directPremiumActive;

        return [
            'balance' => (int) ($seller->balance ?? 0),
            'is_premium' => $isPremium,
            'premium_days_left' => $daysLeft,
            'premium_expires_at' => $expiresAt?->toISOString(),
            'plans' => $this->plans(),
            'subscription_status' => $subscription?->status,
            'subscription_plan' => $subscription?->plan,
            'auto_renew' => (bool) ($subscription?->auto_renew ?? false),
            'cancel_at_period_end' => (bool) ($subscription?->cancel_at_period_end ?? false),
            'stop_reason' => $subscription?->stop_reason,
            'cancel_requested_at' => $subscription?->cancel_requested_at?->toISOString(),
        ];
    }
}
