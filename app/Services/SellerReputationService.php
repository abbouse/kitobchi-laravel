<?php

namespace App\Services;

use App\Enums\OrderStatusCode;
use App\Enums\PaymentStatusCode;
use App\Enums\SellerOrderStatusCode;
use App\Models\Seller;
use App\Models\SellerOrder;
use Illuminate\Database\Eloquent\Builder;

class SellerReputationService
{
    public const BASELINE_PUBLIC_RATING = 5.00;
    public const BASELINE_INTERNAL_SCORE = 78.00;
    public const BASELINE_WEIGHT = 10;

    public function recalculateAll(?Builder $query = null, bool $persist = true): int
    {
        $count = 0;

        ($query ?? Seller::query()
            ->where(function ($q) {
                $q->whereNull('parent_id')
                    ->orWhere('parent_id', 0);
            })
            ->where('status', 'approved'))
            ->orderBy('id')
            ->chunkById(100, function ($sellers) use (&$count, $persist) {
                foreach ($sellers as $seller) {
                    $this->recalculateSeller($seller, $persist);
                    $count++;
                }
            });

        return $count;
    }

    public function recalculateSeller(Seller $seller, bool $persist = true): array
    {
        $metrics = $this->collectMetrics($seller);

        if ($metrics['evidence_count'] <= 0) {
            $publicRating = self::BASELINE_PUBLIC_RATING;
            $internalScore = self::BASELINE_INTERNAL_SCORE;
        } else {
            $successRate = $metrics['success_rate'];
            $cancelScore = 1 - min($metrics['cancel_rate'] / 0.25, 1);
            $returnScore = 1 - min($metrics['return_rate'] / 0.18, 1);
            $responseScore = $this->responseScore($metrics['response_time_hours']);
            $recentActivityScore = $this->logScore($metrics['completed_30d'], 30);
            $lifetimeConfidenceScore = $this->logScore($metrics['completed_all'], 200);

            $operationalScore = (
                ($successRate * 0.34) +
                ($cancelScore * 0.20) +
                ($returnScore * 0.18) +
                ($responseScore * 0.16) +
                ($recentActivityScore * 0.07) +
                ($lifetimeConfidenceScore * 0.05)
            );

            $signalStars = 2.8 + ($operationalScore * 2.2);
            $evidenceWeight = min(40, max(1, $metrics['evidence_count']));

            $publicRating = (
                (self::BASELINE_PUBLIC_RATING * self::BASELINE_WEIGHT) +
                ($signalStars * $evidenceWeight)
            ) / (self::BASELINE_WEIGHT + $evidenceWeight);

            $internalScore = 45 + ($operationalScore * 55);

            if ($metrics['cancelled_90d'] >= 5 && $metrics['cancel_rate'] >= 0.15) {
                $publicRating -= 0.20;
                $internalScore -= 8;
            }

            if ($metrics['returned_90d'] >= 3 && $metrics['return_rate'] >= 0.10) {
                $publicRating -= 0.20;
                $internalScore -= 6;
            }
        }

        $publicRating = round($this->clamp($publicRating, 1.0, 5.0), 2);
        $internalScore = round($this->clamp($internalScore, 1.0, 100.0), 2);

        if ($persist) {
            $seller->forceFill([
                'successful_orders' => (int) $metrics['completed_all'],
                'rating' => $publicRating,
                'rating_reviews_count' => (int) $metrics['completed_all'],
                'reputation_score' => $internalScore,
                'reputation_last_calculated_at' => now(),
            ])->save();
        }

        return [
            'seller_id' => (int) $seller->id,
            'rating' => $publicRating,
            'reputation_score' => $internalScore,
            'rating_reviews_count' => (int) $metrics['completed_all'],
            'metrics' => $metrics,
        ];
    }

    private function collectMetrics(Seller $seller): array
    {
        $now = now();
        $thirtyDaysAgo = $now->copy()->subDays(30);
        $ninetyDaysAgo = $now->copy()->subDays(90);

        $completedAll = $this->completedOrdersQuery($seller)->count();
        $completed30d = $this->completedOrdersQuery($seller)
            ->where('solds.completed_at', '>=', $thirtyDaysAgo)
            ->count();
        $completed90d = $this->completedOrdersQuery($seller)
            ->where('solds.completed_at', '>=', $ninetyDaysAgo)
            ->count();

        $cancelled90d = SellerOrder::query()
            ->where('seller_id', $seller->id)
            ->where('created_at', '>=', $ninetyDaysAgo)
            ->where(function ($query) {
                $query->where('status_code', SellerOrderStatusCode::CANCELLED->value)
                    ->orWhere(function ($fallback) {
                        $fallback->whereNull('status_code')
                            ->where('status', SellerOrderStatusCode::CANCELLED->legacy());
                    });
            })
            ->count();

        $returned90d = SellerOrder::query()
            ->join('solds', 'solds.id', '=', 'seller_orders.order_id')
            ->where('seller_orders.seller_id', $seller->id)
            ->where('seller_orders.created_at', '>=', $ninetyDaysAgo)
            ->where(function ($query) {
                $query->where('solds.status_code', OrderStatusCode::RETURNED->value)
                    ->orWhere(function ($fallback) {
                        $fallback->whereNull('solds.status_code')
                            ->where('solds.status', 'R');
                    });
            })
            ->count();

        $evidenceCount = $completedAll + $cancelled90d + $returned90d;
        $attempts90d = max(1, $completed90d + $cancelled90d + $returned90d);
        $responseTimeHours = max(0.25, (float) ($seller->response_time_hours ?? 24));

        return [
            'completed_all' => (int) $completedAll,
            'completed_30d' => (int) $completed30d,
            'completed_90d' => (int) $completed90d,
            'cancelled_90d' => (int) $cancelled90d,
            'returned_90d' => (int) $returned90d,
            'evidence_count' => (int) $evidenceCount,
            'success_rate' => $completed90d / $attempts90d,
            'cancel_rate' => $cancelled90d / $attempts90d,
            'return_rate' => $returned90d / $attempts90d,
            'response_time_hours' => round($responseTimeHours, 2),
        ];
    }

    private function completedOrdersQuery(Seller $seller): Builder
    {
        return SellerOrder::query()
            ->join('solds', 'solds.id', '=', 'seller_orders.order_id')
            ->where('seller_orders.seller_id', $seller->id)
            ->where(function ($query) {
                $query->where('solds.status_code', OrderStatusCode::DELIVERED->value)
                    ->orWhere(function ($fallback) {
                        $fallback->whereNull('solds.status_code')
                            ->where('solds.status', 'C');
                    });
            })
            ->where(function ($query) {
                $query->where('solds.payment_status_code', PaymentStatusCode::PAID->value)
                    ->orWhere(function ($fallback) {
                        $fallback->whereNull('solds.payment_status_code')
                            ->where('solds.paymentStatus', 2);
                    });
            });
    }

    private function responseScore(float $hours): float
    {
        return match (true) {
            $hours <= 1 => 1.00,
            $hours <= 3 => 0.96,
            $hours <= 6 => 0.90,
            $hours <= 12 => 0.82,
            $hours <= 24 => 0.72,
            $hours <= 48 => 0.58,
            default => 0.45,
        };
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
