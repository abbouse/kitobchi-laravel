<?php

namespace App\Services;

use App\Enums\OrderStatusCode;
use App\Enums\PaymentStatusCode;
use App\Models\BookClub;
use App\Models\Books;
use App\Models\ProductReviewPrompt;
use App\Models\Sold;
use App\Models\Stationery;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class ProductReviewPromptService
{
    public const FIRST_DELAY_DAYS = 3;
    public const SECOND_DELAY_DAYS = 7;

    public function scheduleForCompletedOrder(Sold $order): void
    {
        if (!$order->user_id || !$order->isCompletedAndPaid() || !$order->completed_at) {
            return;
        }

        $completedAt = $order->completed_at->copy();

        foreach ($this->extractReviewableItems($order) as $item) {
            $prompt = ProductReviewPrompt::query()->firstOrNew([
                'sold_id' => $order->id,
                'user_id' => $order->user_id,
                'product_id' => $item['product_id'],
                'product_type' => $item['product_type'],
            ]);

            $prompt->product_name = $item['product_name'];
            $prompt->first_due_at = $completedAt->copy()->addDays(self::FIRST_DELAY_DAYS);
            $prompt->second_due_at = $completedAt->copy()->addDays(self::SECOND_DELAY_DAYS);

            if ($prompt->exists && $prompt->close_reason === 'order_reverted') {
                $prompt->closed_at = null;
                $prompt->close_reason = null;
            }

            if ($this->hasReviewedProduct($order->user_id, $item['product_id'], $item['product_type'])) {
                $prompt->closed_at = now();
                $prompt->close_reason = 'review_posted';
            }

            $prompt->save();
        }
    }

    public function closeForOrder(Sold $order, string $reason = 'order_reverted'): void
    {
        ProductReviewPrompt::query()
            ->where('sold_id', $order->id)
            ->whereNull('closed_at')
            ->update([
                'closed_at' => now(),
                'close_reason' => $reason,
                'updated_at' => now(),
            ]);
    }

    public function markReviewedByPost(BookClub $post): void
    {
        if (!$post->user_id || !$post->product_id || !in_array((string) $post->product_type, ['book', 'stationery'], true)) {
            return;
        }

        $this->markReviewedByUserProduct(
            (int) $post->user_id,
            (int) $post->product_id,
            (string) $post->product_type,
        );
    }

    public function markReviewedByUserProduct(int $userId, int $productId, string $productType): void
    {
        ProductReviewPrompt::query()
            ->where('user_id', $userId)
            ->where('product_id', $productId)
            ->where('product_type', $productType)
            ->whereNull('closed_at')
            ->update([
                'closed_at' => now(),
                'close_reason' => 'review_posted',
                'updated_at' => now(),
            ]);
    }

    public function hasReviewedProduct(int $userId, int $productId, string $productType): bool
    {
        return BookClub::query()
            ->where('user_id', $userId)
            ->where('product_id', $productId)
            ->where('product_type', $productType)
            ->where('is_deleted', false)
            ->exists();
    }

    public function isProductStillPublic(int $productId, string $productType): bool
    {
        return match ($productType) {
            'book' => Books::query()
                ->whereKey($productId)
                ->where('is_hidden', 0)
                ->where('is_approved', 1)
                ->whereHas('seller', fn ($q) => $q
                    ->where('is_hidden', 0)
                    ->where('status', 'approved')
                    ->where(function ($sellerQ) {
                        $sellerQ->whereNull('parent_id')
                            ->orWhere('parent_id', 0);
                    }))
                ->exists(),
            'stationery' => Stationery::query()
                ->whereKey($productId)
                ->where('is_hidden', 0)
                ->where('is_approved', 1)
                ->whereHas('seller', fn ($q) => $q
                    ->where('is_hidden', 0)
                    ->where('status', 'approved')
                    ->where(function ($sellerQ) {
                        $sellerQ->whereNull('parent_id')
                            ->orWhere('parent_id', 0);
                    }))
                ->exists(),
            default => false,
        };
    }

    public function syncRecentCompletedOrders(int $days = 14): void
    {
        $since = now()->subDays($days);

        Sold::query()
            ->whereNotNull('completed_at')
            ->where('completed_at', '>=', $since)
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
            })
            ->orderBy('id')
            ->chunkById(100, function ($orders) {
                foreach ($orders as $order) {
                    $this->scheduleForCompletedOrder($order);
                }
            });
    }

    public function duePrompts(CarbonInterface $now): Collection
    {
        return ProductReviewPrompt::query()
            ->with('user:id,locale')
            ->whereNull('closed_at')
            ->where(function ($query) use ($now) {
                $query->where(function ($first) use ($now) {
                    $first->whereNull('first_sent_at')
                        ->where('first_due_at', '<=', $now);
                })->orWhere(function ($second) use ($now) {
                    $second->whereNotNull('first_sent_at')
                        ->whereNull('second_sent_at')
                        ->whereNotNull('second_due_at')
                        ->where('second_due_at', '<=', $now);
                });
            })
            ->orderByRaw('CASE WHEN first_sent_at IS NULL THEN first_due_at ELSE second_due_at END ASC')
            ->get();
    }

    private function extractReviewableItems(Sold $order): Collection
    {
        return collect($order->items ?? [])
            ->map(function ($item) {
                $type = (string) ($item['type'] ?? '');
                $productId = (int) ($item['item_id'] ?? 0);
                if (!in_array($type, ['book', 'stationery'], true) || $productId <= 0) {
                    return null;
                }

                return [
                    'product_id' => $productId,
                    'product_type' => $type,
                    'product_name' => $item['name'] ?? null,
                ];
            })
            ->filter()
            ->unique(fn (array $item) => $item['product_type'].'_'.$item['product_id'])
            ->values();
    }
}
