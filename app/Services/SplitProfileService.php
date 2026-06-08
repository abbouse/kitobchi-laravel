<?php

namespace App\Services;

use App\Enums\OrderStatusCode;
use App\Enums\PaymentStatusCode;
use App\Models\BookClubWarning;
use App\Models\ConnectedDevice;
use App\Models\ProjectSetting;
use App\Models\Sold;
use App\Models\SplitUserProfile;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserCard;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class SplitProfileService
{
    public function __construct(
        private readonly UserReputationService $userReputationService,
    ) {
    }

    public function settings(): array
    {
        $settings = Schema::hasTable('project_settings')
            ? ProjectSetting::query()->firstOrCreate([])
            : null;

        return [
            'enabled' => (bool) ($settings?->split_enabled ?? false),
            'public_enabled' => (bool) ($settings?->split_public_enabled ?? false),
            'upfront_percent' => (int) ($settings?->split_upfront_percent ?? 25),
            'term_days' => (int) ($settings?->split_term_days ?? 60),
            'global_min_order_sum' => (int) ($settings?->split_global_min_order_sum ?? 100000),
            'global_max_order_sum' => (int) ($settings?->split_global_max_order_sum ?? 2000000),
            'global_min_limit' => (int) ($settings?->split_global_min_limit ?? 300000),
            'global_max_limit' => (int) ($settings?->split_global_max_limit ?? 2000000),
            'min_completed_orders' => (int) ($settings?->split_min_completed_orders ?? 3),
            'min_account_age_days' => (int) ($settings?->split_min_account_age_days ?? 90),
            'min_card_age_days' => (int) ($settings?->split_min_card_age_days ?? 45),
            'min_reputation_score' => (float) ($settings?->split_min_reputation_score ?? 78),
            'max_active_contracts' => (int) ($settings?->split_max_active_contracts ?? 1),
            'default_fee_percent' => (float) ($settings?->split_default_fee_percent ?? 0),
            'card_delete_lock_enabled' => (bool) ($settings?->split_card_delete_lock_enabled ?? true),
            'refund_sender_card_id' => (string) ($settings?->paylov_refund_sender_card_id ?? ''),
            'refund_service_id' => (string) ($settings?->paylov_refund_service_id ?? ''),
        ];
    }

    public function refreshAll(?Builder $query = null, bool $persist = true): int
    {
        $count = 0;

        ($query ?? User::query())
            ->orderBy('id')
            ->chunkById(200, function ($users) use (&$count, $persist) {
                foreach ($users as $user) {
                    $this->refreshUser($user, $persist);
                    $count++;
                }
            });

        return $count;
    }

    public function warmProfilesForUsers(iterable $users): void
    {
        foreach ($users as $user) {
            if ($user instanceof User) {
                $this->refreshUser($user, true);
            }
        }
    }

    public function getFreshProfile(User $user): array
    {
        return $this->refreshUser($user, true);
    }

    public function refreshUser(User $user, bool $persist = true): array
    {
        $settings = $this->settings();
        $reputation = $this->userReputationService->recalculateUser($user, false);
        $now = now();
        $ninetyDaysAgo = $now->copy()->subDays(90);
        $oneHundredEightyDaysAgo = $now->copy()->subDays(180);

        $completedAll = $this->completedOrdersQuery($user)->count();
        $completed90d = $this->completedOrdersQuery($user)
            ->where('solds.completed_at', '>=', $ninetyDaysAgo)
            ->count();
        $completedGmv180d = (int) round(
            (float) $this->completedOrdersQuery($user)
                ->where('solds.completed_at', '>=', $oneHundredEightyDaysAgo)
                ->sum('amount')
        );

        $cancelled90d = Sold::query()
            ->where('user_id', $user->id)
            ->where('created_at', '>=', $ninetyDaysAgo)
            ->where(function ($query) {
                $query->where('status_code', OrderStatusCode::CANCELLED->value)
                    ->orWhere(function ($fallback) {
                        $fallback->whereNull('status_code')
                            ->where('status', OrderStatusCode::CANCELLED->legacy());
                    });
            })
            ->count();

        $attempts90d = max(1, $completed90d + $cancelled90d + (int) ($reputation['metrics']['cash_returned_90d'] ?? 0));
        $cancelRate90d = round($cancelled90d / $attempts90d, 4);

        $verifiedCards = UserCard::query()
            ->where('user_id', $user->id)
            ->where('is_verified', true)
            ->where(function ($query) {
                $query->whereNull('is_temporary')->orWhere('is_temporary', false);
            })
            ->orderBy('created_at')
            ->get();

        $verifiedCardsCount = $verifiedCards->count();
        $oldestVerifiedCard = $verifiedCards->first()?->created_at;
        $verifiedCardAgeDays = $oldestVerifiedCard instanceof Carbon
            ? max(0, $oldestVerifiedCard->diffInDays($now))
            : 0;

        $successfulCardPayments180d = Transaction::query()
            ->where('owner_id', $user->id)
            ->where('provider', 'paylov')
            ->where('state', 2)
            ->whereNotNull('provider_card_id')
            ->where('created_at', '>=', $oneHundredEightyDaysAgo)
            ->count();

        $deviceCount90d = Schema::hasTable('connected_devices')
            ? ConnectedDevice::query()
                ->where('user_id', $user->id)
                ->where('user_type', 'user')
                ->where('created_at', '>=', $ninetyDaysAgo)
                ->distinct('device_id')
                ->count('device_id')
            : 0;

        $cardChurn90d = UserCard::query()
            ->where('user_id', $user->id)
            ->where('created_at', '>=', $ninetyDaysAgo)
            ->count();

        $activeWarningCount = BookClubWarning::query()
            ->active()
            ->where('user_id', $user->id)
            ->count();

        $accountAgeDays = $user->created_at instanceof Carbon
            ? max(0, $user->created_at->diffInDays($now))
            : 0;
        $lastSeenAt = $this->parseDate($user->last_seen_at);

        $activeExposure = 0;
        $activeContractCount = 0;

        if (Schema::hasTable('split_contracts')) {
            $query = \Illuminate\Support\Facades\DB::table('split_contracts')
                ->where('user_id', $user->id)
                ->whereIn('status', ['active', 'overdue']);

            $activeContractCount = (int) $query->count();
            $activeExposure = (int) round((float) $query->sum('remaining_amount'));
        }

        $reputationScore = (float) ($reputation['reputation_score'] ?? $user->reputation_score ?? 0);
        $codReturnStrikes = (int) ($reputation['cod_return_strikes'] ?? $user->cod_return_strikes ?? 0);
        $reasons = $this->eligibilityReasons(
            user: $user,
            settings: $settings,
            accountAgeDays: $accountAgeDays,
            verifiedCardsCount: $verifiedCardsCount,
            verifiedCardAgeDays: $verifiedCardAgeDays,
            reputationScore: $reputationScore,
            codReturnStrikes: $codReturnStrikes,
            completedAll: $completedAll,
            activeWarningCount: $activeWarningCount,
            activeContractCount: $activeContractCount,
        );
        $eligible = $reasons === [];

        $confidenceScore = $this->confidenceScore(
            reputationScore: $reputationScore,
            completedAll: $completedAll,
            completedGmv180d: $completedGmv180d,
            verifiedCardAgeDays: $verifiedCardAgeDays,
            successfulCardPayments180d: $successfulCardPayments180d,
            deviceCount90d: $deviceCount90d,
            cardChurn90d: $cardChurn90d,
            cancelRate90d: $cancelRate90d,
            activeWarningCount: $activeWarningCount,
            isActiveRecently: (bool) ($lastSeenAt?->gte($now->copy()->subDays(14)) ?? false),
            codReturnStrikes: $codReturnStrikes,
        );

        $computedLimit = $eligible
            ? $this->computeLimit(
                settings: $settings,
                confidenceScore: $confidenceScore,
                completedAll: $completedAll,
                completedGmv180d: $completedGmv180d,
                successfulCardPayments180d: $successfulCardPayments180d,
                verifiedCardAgeDays: $verifiedCardAgeDays,
                reputationScore: $reputationScore,
            )
            : 0;

        $availableLimit = max(0, $computedLimit - $activeExposure);

        $payload = [
            'user_id' => $user->id,
            'eligible' => $eligible,
            'eligibility_reasons' => $reasons,
            'confidence_score' => $confidenceScore,
            'computed_limit' => $computedLimit,
            'available_limit' => $availableLimit,
            'active_exposure' => $activeExposure,
            'max_active_contracts' => (int) $settings['max_active_contracts'],
            'active_contract_count' => $activeContractCount,
            'reputation_score' => round($reputationScore, 2),
            'cod_return_strikes' => $codReturnStrikes,
            'account_age_days' => $accountAgeDays,
            'verified_card_age_days' => $verifiedCardAgeDays,
            'verified_cards_count' => $verifiedCardsCount,
            'successful_card_payments_180d' => $successfulCardPayments180d,
            'completed_orders_90d' => $completed90d,
            'completed_orders_all' => $completedAll,
            'completed_gmv_180d' => $completedGmv180d,
            'cancel_rate_90d' => $cancelRate90d,
            'device_count_90d' => $deviceCount90d,
            'card_churn_90d' => $cardChurn90d,
            'active_warning_count' => $activeWarningCount,
            'last_refreshed_at' => $now,
            'snapshot' => [
                'settings' => $settings,
                'metrics' => $reputation['metrics'] ?? [],
            ],
        ];

        if ($persist && Schema::hasTable('split_user_profiles')) {
            SplitUserProfile::query()->updateOrCreate(
                ['user_id' => $user->id],
                $payload,
            );
        }

        return [
            ...$payload,
            'eligibility_reasons' => array_values($reasons),
            'last_refreshed_at' => $now->toIso8601String(),
        ];
    }

    public function cardRemovalBlocked(User $user): bool
    {
        $settings = $this->settings();

        if (! $settings['card_delete_lock_enabled']) {
            return false;
        }

        if (! Schema::hasTable('split_user_profiles')) {
            return false;
        }

        $profile = SplitUserProfile::query()->where('user_id', $user->id)->first();

        if (! $profile) {
            return false;
        }

        return ((int) $profile->active_exposure > 0) || ((int) $profile->active_contract_count > 0);
    }

    private function eligibilityReasons(
        User $user,
        array $settings,
        int $accountAgeDays,
        int $verifiedCardsCount,
        int $verifiedCardAgeDays,
        float $reputationScore,
        int $codReturnStrikes,
        int $completedAll,
        int $activeWarningCount,
        int $activeContractCount,
    ): array {
        $reasons = [];

        if ($user->isBlocked()) {
            $reasons[] = 'Foydalanuvchi bloklangan.';
        }

        if (! (bool) $user->isVerified) {
            $reasons[] = 'Foydalanuvchi akkaunti tasdiqlanmagan.';
        }

        if ($verifiedCardsCount < 1) {
            $reasons[] = 'Kamida bitta tasdiqlangan Paylov karta kerak.';
        }

        if ($accountAgeDays < (int) $settings['min_account_age_days']) {
            $reasons[] = "Akkaunt yoshi kamida {$settings['min_account_age_days']} kun bo'lishi kerak.";
        }

        if ($verifiedCardAgeDays < (int) $settings['min_card_age_days']) {
            $reasons[] = "Tasdiqlangan karta yoshi kamida {$settings['min_card_age_days']} kun bo'lishi kerak.";
        }

        if ($reputationScore < (float) $settings['min_reputation_score']) {
            $reasons[] = "Reputation score kamida {$settings['min_reputation_score']} bo'lishi kerak.";
        }

        if ($codReturnStrikes > 0) {
            $reasons[] = 'Naqd qaytgan buyurtma strike mavjud.';
        }

        if ($completedAll < (int) $settings['min_completed_orders']) {
            $reasons[] = "Kamida {$settings['min_completed_orders']} ta yakunlangan pullik buyurtma kerak.";
        }

        if ($activeWarningCount > 0) {
            $reasons[] = 'Faol community ogohlantirishlari mavjud.';
        }

        if ($activeContractCount >= (int) $settings['max_active_contracts']) {
            $reasons[] = 'Faol splitlar soni limitga yetgan.';
        }

        return $reasons;
    }

    private function confidenceScore(
        float $reputationScore,
        int $completedAll,
        int $completedGmv180d,
        int $verifiedCardAgeDays,
        int $successfulCardPayments180d,
        int $deviceCount90d,
        int $cardChurn90d,
        float $cancelRate90d,
        int $activeWarningCount,
        bool $isActiveRecently,
        int $codReturnStrikes,
    ): float {
        $reputationComponent = max(0.0, min(1.0, $reputationScore / 100));
        $ordersComponent = min(1.0, log($completedAll + 1) / log(21));
        $gmvComponent = min(1.0, $completedGmv180d / 4000000);
        $cardComponent = min(1.0, $verifiedCardAgeDays / 180);
        $savedCardComponent = min(1.0, log($successfulCardPayments180d + 1) / log(13));
        $stabilityPenalty = min(1.0, max(0, $deviceCount90d - 3) * 0.12 + max(0, $cardChurn90d - 2) * 0.15);
        $cancelPenalty = min(1.0, $cancelRate90d / 0.35);
        $warningPenalty = min(1.0, $activeWarningCount * 0.35);
        $activityComponent = $isActiveRecently ? 1.0 : 0.35;

        $score = (
            ($reputationComponent * 35) +
            ($ordersComponent * 20) +
            ($gmvComponent * 15) +
            ((($cardComponent + $savedCardComponent) / 2) * 15) +
            ($activityComponent * 5) +
            ((1 - min(1.0, $stabilityPenalty)) * 10)
        );

        $score -= ($cancelPenalty * 10);
        $score -= ($warningPenalty * 12);

        if ($codReturnStrikes > 0) {
            $score -= 20;
        }

        return round(max(0.0, min(100.0, $score)), 2);
    }

    private function computeLimit(
        array $settings,
        float $confidenceScore,
        int $completedAll,
        int $completedGmv180d,
        int $successfulCardPayments180d,
        int $verifiedCardAgeDays,
        float $reputationScore,
    ): int {
        $limit = 0;
        $limit += min(600000, $completedAll * 35000);
        $limit += min(550000, (int) floor($completedGmv180d / 100000) * 25000);
        $limit += min(300000, $successfulCardPayments180d * 30000);
        $limit += min(250000, (int) floor($verifiedCardAgeDays / 30) * 20000);
        $limit += min(250000, max(0, (int) floor($reputationScore - $settings['min_reputation_score'])) * 10000);
        $limit += (int) round(($confidenceScore / 100) * 150000);

        $min = (int) $settings['global_min_limit'];
        $max = (int) $settings['global_max_limit'];

        return max($min, min($max, $limit));
    }

    private function completedOrdersQuery(User $user): Builder
    {
        return Sold::query()
            ->where('user_id', $user->id)
            ->whereNotNull('completed_at')
            ->where(function ($query) {
                $query->where('payment_status_code', PaymentStatusCode::PAID->value)
                    ->orWhere(function ($fallback) {
                        $fallback->whereNull('payment_status_code')
                            ->where('paymentStatus', PaymentStatusCode::PAID->legacy());
                    });
            });
    }

    private function parseDate(mixed $value): ?Carbon
    {
        if ($value instanceof Carbon) {
            return $value;
        }

        if (blank($value)) {
            return null;
        }

        try {
            return Carbon::parse((string) $value);
        } catch (\Throwable) {
            return null;
        }
    }
}
