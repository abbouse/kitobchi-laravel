<?php

namespace App\Services;

use App\Enums\OrderStatusCode;
use App\Enums\PaymentStatusCode;
use App\Models\BookClubWarning;
use App\Models\Sold;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class UserReputationService
{
    public const BASELINE_SCORE = 82.00;
    public const BASELINE_WEIGHT = 8;

    public function recalculateAll(?Builder $query = null, bool $persist = true): int
    {
        $count = 0;

        ($query ?? User::query())
            ->orderBy('id')
            ->chunkById(200, function ($users) use (&$count, $persist) {
                foreach ($users as $user) {
                    $this->recalculateUser($user, $persist);
                    $count++;
                }
            });

        return $count;
    }

    public function recalculateUser(User $user, bool $persist = true): array
    {
        $metrics = $this->collectMetrics($user);

        if ($metrics['evidence_count'] <= 0) {
            $score = self::BASELINE_SCORE;
        } else {
            $completionScore = $metrics['completion_rate'];
            $cancelScore = 1 - min($metrics['cancel_rate'] / 0.35, 1);
            $cashReturnScore = 1 - min($metrics['cash_return_rate'] / 0.20, 1);
            $warningScore = 1 - min($metrics['active_warning_count'] / max(1, BookClubModerationService::BLOCK_THRESHOLD), 1);
            $recentActivityScore = $this->logScore($metrics['completed_90d'], 20);
            $lifetimeConfidenceScore = $this->logScore($metrics['completed_all'], 120);

            $operationalScore = (
                ($completionScore * 0.38) +
                ($cancelScore * 0.17) +
                ($cashReturnScore * 0.27) +
                ($warningScore * 0.10) +
                ($recentActivityScore * 0.04) +
                ($lifetimeConfidenceScore * 0.04)
            );

            $signalScore = 45 + ($operationalScore * 50);
            $evidenceWeight = min(35, max(1, $metrics['evidence_count']));

            $score = (
                (self::BASELINE_SCORE * self::BASELINE_WEIGHT) +
                ($signalScore * $evidenceWeight)
            ) / (self::BASELINE_WEIGHT + $evidenceWeight);

            if ($metrics['cash_returned_all'] >= 1) {
                $score -= 22;
            }

            if ($metrics['active_warning_count'] >= 3) {
                $score -= min(18, ($metrics['active_warning_count'] - 2) * 4);
            }
        }

        $score = round($this->clamp($score, 1.0, 100.0), 2);
        $cashOnDeliveryAllowed = $metrics['cash_returned_all'] < 1;

        if ($persist) {
            $user->forceFill([
                'reputation_score' => $score,
                'cash_on_delivery_allowed' => $cashOnDeliveryAllowed,
                'cod_return_strikes' => (int) $metrics['cash_returned_all'],
                'reputation_last_calculated_at' => now(),
            ])->save();
        }

        return [
            'user_id' => (int) $user->id,
            'reputation_score' => $score,
            'cash_on_delivery_allowed' => $cashOnDeliveryAllowed,
            'cod_return_strikes' => (int) $metrics['cash_returned_all'],
            'metrics' => $metrics,
        ];
    }

    private function collectMetrics(User $user): array
    {
        $now = now();
        $ninetyDaysAgo = $now->copy()->subDays(90);

        $completedAll = $this->completedOrdersQuery($user)->count();
        $completed90d = $this->completedOrdersQuery($user)
            ->where('solds.completed_at', '>=', $ninetyDaysAgo)
            ->count();

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

        $cashReturnedAll = Sold::query()
            ->where('user_id', $user->id)
            ->where(function ($query) {
                $query->where('status_code', OrderStatusCode::RETURNED->value)
                    ->orWhere(function ($fallback) {
                        $fallback->whereNull('status_code')
                            ->where('status', OrderStatusCode::RETURNED->legacy());
                    });
            })
            ->where(function ($query) {
                $query->where('payment_status_code', PaymentStatusCode::CASH_PENDING->value)
                    ->orWhere(function ($fallback) {
                        $fallback->whereNull('payment_status_code')
                            ->where('paymentStatus', PaymentStatusCode::CASH_PENDING->legacy());
                    });
            })
            ->count();

        $cashReturned90d = Sold::query()
            ->where('user_id', $user->id)
            ->where('created_at', '>=', $ninetyDaysAgo)
            ->where(function ($query) {
                $query->where('status_code', OrderStatusCode::RETURNED->value)
                    ->orWhere(function ($fallback) {
                        $fallback->whereNull('status_code')
                            ->where('status', OrderStatusCode::RETURNED->legacy());
                    });
            })
            ->where(function ($query) {
                $query->where('payment_status_code', PaymentStatusCode::CASH_PENDING->value)
                    ->orWhere(function ($fallback) {
                        $fallback->whereNull('payment_status_code')
                            ->where('paymentStatus', PaymentStatusCode::CASH_PENDING->legacy());
                    });
            })
            ->count();

        $activeWarningCount = BookClubWarning::query()
            ->active()
            ->where('user_id', $user->id)
            ->count();

        $attempts90d = max(1, $completed90d + $cancelled90d + $cashReturned90d);
        $evidenceCount = $completedAll + $cancelled90d + $cashReturnedAll + $activeWarningCount;

        return [
            'completed_all' => (int) $completedAll,
            'completed_90d' => (int) $completed90d,
            'cancelled_90d' => (int) $cancelled90d,
            'cash_returned_all' => (int) $cashReturnedAll,
            'cash_returned_90d' => (int) $cashReturned90d,
            'active_warning_count' => (int) $activeWarningCount,
            'evidence_count' => (int) $evidenceCount,
            'completion_rate' => $completed90d / $attempts90d,
            'cancel_rate' => $cancelled90d / $attempts90d,
            'cash_return_rate' => $cashReturned90d / $attempts90d,
        ];
    }

    private function completedOrdersQuery(User $user): Builder
    {
        return Sold::query()
            ->where('user_id', $user->id)
            ->where(function ($query) {
                $query->where('status_code', OrderStatusCode::DELIVERED->value)
                    ->orWhere(function ($fallback) {
                        $fallback->whereNull('status_code')
                            ->where('status', OrderStatusCode::DELIVERED->legacy());
                    });
            })
            ->where(function ($query) {
                $query->where('payment_status_code', PaymentStatusCode::PAID->value)
                    ->orWhere(function ($fallback) {
                        $fallback->whereNull('payment_status_code')
                            ->where('paymentStatus', PaymentStatusCode::PAID->legacy());
                    });
            });
    }

    private function logScore(int $value, int $cap): float
    {
        if ($value <= 0) {
            return 0.0;
        }

        return min(1.0, log($value + 1) / log($cap + 1));
    }

    private function clamp(float $value, float $min, float $max): float
    {
        return max($min, min($max, $value));
    }
}
