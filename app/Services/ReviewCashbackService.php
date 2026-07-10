<?php

namespace App\Services;

use App\Enums\PaymentStatusCode;
use App\Models\BookClub;
use App\Models\CashbackHistory;
use App\Models\ProductReviewPrompt;
use App\Models\ProjectSetting;
use App\Models\Sold;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Sotib olingan mahsulotga izoh qoldirgan mijozga keshbek berish xizmati.
 *
 * Qoidalar:
 *   - Faqat mijoz O'ZI sotib olgan (yakunlangan + to'langan buyurtma) mahsulot
 *   - Har bir (user, product) juftligi uchun FAQAT 1 MARTA — mijoz bitta
 *     mahsulotga nechta izoh yozmasin, keshbek bir marta beriladi
 *   - Miqdor va yoqish/o'chirish boshqaruvdan sozlanadi
 *     (project_settings.review_cashback_enabled / review_cashback_amount)
 */
class ReviewCashbackService
{
    public const ACTION = 'review_bonus';

    private const SETTINGS_CACHE_KEY = 'review-cashback:settings';
    private const SETTINGS_CACHE_TTL = 300; // 5 daqiqa

    public function __construct(
        private readonly CashbackHistoryService $historyService,
    ) {
    }

    // ─── Sozlamalar ──────────────────────────────────────────────────────────

    /**
     * @return array{enabled: bool, amount: int}
     */
    public function settings(): array
    {
        return Cache::remember(self::SETTINGS_CACHE_KEY, self::SETTINGS_CACHE_TTL, function () {
            $settings = ProjectSetting::query()->first();

            return [
                'enabled' => $settings === null
                    ? true
                    : ($settings->review_cashback_enabled ?? true),
                'amount' => max(0, (int) ($settings?->review_cashback_amount ?? 100)),
            ];
        });
    }

    public static function forgetSettingsCache(): void
    {
        Cache::forget(self::SETTINGS_CACHE_KEY);
    }

    // ─── Keshbek berish ──────────────────────────────────────────────────────

    /**
     * Post (izoh) uchun keshbek berishga urinadi.
     *
     * @return array{awarded: bool, amount: int}|null
     *         null — post mahsulot izohи emas yoki sozlama o'chirilgan
     */
    public function awardForPost(BookClub $post): ?array
    {
        $userId      = (int) ($post->user_id ?? 0);
        $productId   = (int) ($post->product_id ?? 0);
        $productType = (string) ($post->product_type ?? '');

        if ($userId <= 0 || $productId <= 0 || ! in_array($productType, ['book', 'stationery'], true)) {
            return null;
        }

        $config = $this->settings();

        if (! $config['enabled'] || $config['amount'] <= 0) {
            return null;
        }

        try {
            // 1. Mijoz bu mahsulotni sotib olganmi?
            if (! $this->hasPurchasedProduct($userId, $productId, $productType)) {
                return ['awarded' => false, 'amount' => $config['amount']];
            }

            // 2. Bu mahsulot uchun avval keshbek berilganmi?
            if ($this->hasAwarded($userId, $productId, $productType)) {
                return ['awarded' => false, 'amount' => $config['amount']];
            }

            // 3. Atomik berish — parallel so'rovlarda ikki marta bermaslik uchun
            //    hammasi bitta tranzaksiya + lock ichida
            $awarded = DB::transaction(function () use ($userId, $productId, $productType, $config, $post) {
                // Lock ostida qayta tekshirish
                $already = CashbackHistory::query()
                    ->where('user_id', $userId)
                    ->where('action', self::ACTION)
                    ->where('meta->product_id', $productId)
                    ->where('meta->product_type', $productType)
                    ->lockForUpdate()
                    ->exists();

                if ($already) {
                    return false;
                }

                $balanceBefore = (int) DB::table('users')
                    ->where('id', $userId)
                    ->lockForUpdate()
                    ->value('cashback');

                DB::table('users')
                    ->where('id', $userId)
                    ->increment('cashback', $config['amount']);

                $this->historyService->record(
                    userId: $userId,
                    action: self::ACTION,
                    amount: $config['amount'],
                    balanceBefore: $balanceBefore,
                    balanceAfter: $balanceBefore + $config['amount'],
                    meta: [
                        'product_id'   => $productId,
                        'product_type' => $productType,
                        'post_id'      => $post->id,
                    ],
                );

                return true;
            });

            if ($awarded) {
                Log::info('Review cashback awarded', [
                    'user_id'      => $userId,
                    'product_id'   => $productId,
                    'product_type' => $productType,
                    'amount'       => $config['amount'],
                ]);
            }

            return ['awarded' => $awarded, 'amount' => $config['amount']];
        } catch (\Throwable $e) {
            Log::error('Review cashback award failed', [
                'user_id' => $userId,
                'product_id' => $productId,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    // ─── Tekshiruvlar ────────────────────────────────────────────────────────

    /**
     * Bu (user, product) juftligiga avval keshbek berilganmi?
     */
    public function hasAwarded(int $userId, int $productId, string $productType): bool
    {
        return CashbackHistory::query()
            ->where('user_id', $userId)
            ->where('action', self::ACTION)
            ->where('meta->product_id', $productId)
            ->where('meta->product_type', $productType)
            ->exists();
    }

    /**
     * Mijoz mahsulotni haqiqatda sotib olganmi (yakunlangan + to'langan)?
     *
     * Avval tez yo'l: product_review_prompts jadvali (har bir yakunlangan
     * buyurtma itemi uchun yoziladi). Topilmasa — eski buyurtmalar uchun
     * items JSON ni skan qilamiz (fallback).
     */
    public function hasPurchasedProduct(int $userId, int $productId, string $productType): bool
    {
        $viaPrompt = ProductReviewPrompt::query()
            ->where('user_id', $userId)
            ->where('product_id', $productId)
            ->where('product_type', $productType)
            ->exists();

        if ($viaPrompt) {
            return true;
        }

        // Fallback — eski buyurtmalar (prompt yozilmagan davr)
        $found = false;

        Sold::query()
            ->where('user_id', $userId)
            ->whereNotNull('completed_at')
            ->where(function ($query) {
                $query->where('payment_status_code', PaymentStatusCode::PAID->value)
                    ->orWhere(function ($fallback) {
                        $fallback->whereNull('payment_status_code')
                            ->where('paymentStatus', PaymentStatusCode::PAID->legacy());
                    });
            })
            ->orderByDesc('id')
            ->select(['id', 'items'])
            ->chunk(100, function ($orders) use (&$found, $productId, $productType) {
                foreach ($orders as $order) {
                    foreach (collect($order->items ?? []) as $item) {
                        if ((int) ($item['item_id'] ?? 0) === $productId
                            && (string) ($item['type'] ?? '') === $productType) {
                            $found = true;

                            return false; // chunk to'xtatish
                        }
                    }
                }

                return true;
            });

        return $found;
    }
}
