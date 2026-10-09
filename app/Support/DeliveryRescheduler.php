<?php

namespace App\Support;

use App\Enums\OrderStatusCode;
use App\Models\Sold;
use App\Services\OrderStatusPushService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Yetkazish kuni o'tib ketgan, lekin hali yetkazilmagan buyurtmalarni
 * avtomatik keyingi kunga ko'chiradi (vaqt oralig'i saqlanadi).
 * Asl kun va necha marta ko'chirilgani saqlanadi, mijozga push boradi.
 */
class DeliveryRescheduler
{
    /** Yakunlanmagan (hali yetkazilishi kerak) holatlar. */
    public const ACTIVE_STATUSES = [OrderStatusCode::PENDING, OrderStatusCode::PACKING, OrderStatusCode::IN_DELIVERY];

    public static function supported(): bool
    {
        return DeliverySchedule::supported() && Schema::hasColumn('solds', 'delivery_rescheduled_count');
    }

    /** Hali yetkazilmagan buyurtmalar (yangi va eski status maydonlari bilan). */
    public static function scopeActive(Builder $query): Builder
    {
        $values = array_map(fn (OrderStatusCode $s) => $s->value, self::ACTIVE_STATUSES);
        $legacy = array_map(fn (OrderStatusCode $s) => $s->legacy(), self::ACTIVE_STATUSES);

        return $query->where(function ($builder) use ($values, $legacy) {
            $builder->whereIn('status_code', $values)
                ->orWhere(fn ($fallback) => $fallback->whereNull('status_code')->whereIn('status', $legacy));
        });
    }

    /** Kechikkanlar: kuni bugundan oldin, hali yetkazilmagan. */
    public static function overdueQuery(?Carbon $today = null): Builder
    {
        $today ??= today();

        return self::scopeActive(Sold::query())
            ->whereNotNull('delivery_date')
            ->whereDate('delivery_date', '<', $today->toDateString());
    }

    /**
     * @return int ko'chirilgan buyurtmalar soni
     */
    public static function rollOverdue(?Carbon $today = null, bool $notify = true): int
    {
        if (! self::supported()) {
            return 0;
        }

        $today ??= today();
        $target = $today->copy()->startOfDay();
        $moved = 0;

        self::overdueQuery($today)->orderBy('id')->chunkById(200, function ($orders) use ($target, $notify, &$moved) {
            foreach ($orders as $order) {
                /** @var Sold $order */
                $previousEta = $order->estimatedDeliveryAt();

                $order->forceFill([
                    'delivery_original_date' => $order->delivery_original_date ?: $order->delivery_date,
                    'delivery_date' => $target->toDateString(),
                    'delivery_rescheduled_count' => (int) $order->delivery_rescheduled_count + 1,
                    'delivery_rescheduled_at' => now(),
                ])->save();
                $moved++;

                if ($notify) {
                    try {
                        app(OrderStatusPushService::class)->sendEtaChangedNotice($order, $previousEta, $order->fresh()->estimatedDeliveryAt());
                    } catch (\Throwable $e) {
                        Log::info('[DeliveryRescheduler] push yuborilmadi', ['order_id' => $order->id, 'error' => $e->getMessage()]);
                    }
                }
            }
        });

        if ($moved > 0) {
            Log::info('[DeliveryRescheduler] kechikkan yetkazishlar ko‘chirildi', ['count' => $moved, 'to' => $target->toDateString()]);
        }

        return $moved;
    }
}
