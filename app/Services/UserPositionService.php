<?php

namespace App\Services;

use App\Enums\OrderStatusCode;
use App\Enums\PaymentStatusCode;
use App\Models\LoyaltyLevel;
use App\Models\Sold;
use App\Models\User;
use App\Models\UserPositionHistory;
use Illuminate\Support\Facades\Cache;

/**
 * Kitobxon darajasi (users.position) — FAQAT xarid bo'yicha, oxirgi 12 oy:
 * yetkazilgan va to'langan buyurtmalar soni hamda mahsulotlar summasi
 * (yetkazishsiz). Chegaralar boshqaruvda (loyalty_levels). Kelgusidagi
 * chegirma va imtiyozlar shu darajaga bog'lanadi.
 *
 * Daraja birdan tushmaydi: shart bajarilmay qolsa, oxirgi o'zgarishdan
 * 90 kun o'tgach bir pog'ona pastga tushadi (birinchi qayta hisoblashda —
 * `users:recalculate-positions` — darhol aniq darajaga o'rnatiladi).
 *
 * Klub faolligi (post, izoh, layk, obunachi) endi darajaga emas, nishonlarga
 * ta'sir qiladi — qarang BadgeService.
 */
class UserPositionService
{
    public const DEFAULT_POSITIONS = ['reader', 'active_reader', 'book_lover', 'bibliophile', 'literary_mentor'];

    public const DEMOTION_AFTER_DAYS = 90;

    public function __construct(private readonly BadgeService $badges) {}

    /** @return array<int, string> pastdan yuqoriga */
    public function positions(): array
    {
        try {
            $keys = LoyaltyLevel::ordered()->pluck('key')->all();
        } catch (\Throwable) {
            $keys = [];
        }

        return $keys ?: self::DEFAULT_POSITIONS;
    }

    /**
     * Darajani qayta hisoblaydi va nishonlarni beradi.
     * Qaytaradi: yangi daraja kaliti (o'zgargan bo'lsa) yoki null.
     */
    public function evaluateAndPromote(User $user, ?string $reason = null): ?string
    {
        // Layk/izoh kabi tez-tez hodisalarda har safar og'ir hisob-kitob qilmaslik uchun
        $force = in_array($reason, ['order_completed', 'backfill_recalculation', 'manual'], true);
        if (! $force && ! Cache::add("progress:eval:{$user->id}", 1, 30)) {
            return null;
        }

        $user->refresh();
        try {
            $this->badges->evaluate($user);
        } catch (\Throwable $e) {
            report($e);
        }

        if ($user->isBlocked()) {
            return null;
        }

        $positions = $this->positions();
        $current = in_array($user->position, $positions, true) ? $user->position : $positions[0];
        $currentIndex = array_search($current, $positions, true);
        $metrics = $this->metrics($user);
        $targetIndex = array_search($this->levelFor($metrics)->key ?? $positions[0], $positions, true) ?: 0;

        if ($targetIndex === $currentIndex && $user->position === $current) {
            return null;
        }
        if ($targetIndex < $currentIndex && $reason !== 'backfill_recalculation') {
            $since = $user->position_earned_at ?? $user->created_at;
            if ($since && $since->diffInDays(now()) < self::DEMOTION_AFTER_DAYS) {
                return null;
            }
            $targetIndex = $currentIndex - 1; // birdan emas — bir pog'ona
        }

        $next = $positions[$targetIndex];
        $user->forceFill(['position' => $next, 'position_earned_at' => now()])->save();

        UserPositionHistory::create([
            'user_id' => $user->id,
            'from_position' => $current,
            'to_position' => $next,
            'metrics_snapshot' => $metrics,
            'reason' => $reason ?: 'auto_progression',
            'awarded_at' => now(),
        ]);

        return $next;
    }

    /** Shartlari bajarilgan eng yuqori daraja. */
    public function levelFor(array $metrics): ?LoyaltyLevel
    {
        $best = null;
        foreach (LoyaltyLevel::ordered() as $level) {
            if ($metrics['orders_12m'] >= $level->min_orders && $metrics['spent_12m'] >= $level->min_spent) {
                $best = $level;
            }
        }

        return $best;
    }

    /** Oxirgi 12 oydagi yetkazilgan va to'langan buyurtmalar. */
    public function metrics(User $user): array
    {
        $query = Sold::query()
            ->where('user_id', $user->id)
            ->whereNotNull('completed_at')
            ->where('completed_at', '>=', now()->subYear())
            ->where(fn ($q) => $q->whereNull('status_code')->orWhereIn('status_code', [
                OrderStatusCode::DELIVERED->value, OrderStatusCode::CUSTOMER_RECEIVED->value,
            ]))
            ->where(function ($q) {
                $q->where('payment_status_code', PaymentStatusCode::PAID->value)
                    ->orWhere(fn ($f) => $f->whereNull('payment_status_code')->where('paymentStatus', PaymentStatusCode::PAID->legacy()));
            });
        // Sovg'a qilib yuborilgan buyurtma ham xarid — hisobga olinadi (faqat javonga kirmaydi)
        $orders = 0;
        $spent = 0;
        foreach ($query->get(['amount', 'deliveryPrice']) as $o) {
            $orders++;
            $spent += max(0, (int) $o->amount - (int) ($o->deliveryPrice ?? 0));
        }

        return ['orders_12m' => $orders, 'spent_12m' => $spent];
    }

    /**
     * Ilova uchun: joriy daraja, keyingisigacha qolgani va barcha darajalar.
     */
    public function summary(User $user): array
    {
        $metrics = $this->metrics($user);
        $levels = LoyaltyLevel::ordered()->values();
        $positions = $levels->pluck('key')->all() ?: self::DEFAULT_POSITIONS;
        $currentKey = in_array($user->position, $positions, true) ? $user->position : ($positions[0] ?? 'reader');
        $idx = (int) array_search($currentKey, $positions, true);
        $next = $levels[$idx + 1] ?? null;

        $level = fn (LoyaltyLevel $l) => [
            'key' => $l->key,
            'name' => $l->name(),
            'color' => $l->color,
            'min_orders' => $l->min_orders,
            'min_spent' => $l->min_spent,
            'perks' => $l->perks(),
        ];

        return [
            'current' => $levels->firstWhere('key', $currentKey) ? $level($levels->firstWhere('key', $currentKey)) : null,
            'next' => $next ? $level($next) : null,
            'metrics' => $metrics,
            'to_next' => $next ? [
                'orders' => max(0, $next->min_orders - $metrics['orders_12m']),
                'spent' => max(0, $next->min_spent - $metrics['spent_12m']),
            ] : null,
            'levels' => $levels->map($level)->values()->all(),
        ];
    }
}
