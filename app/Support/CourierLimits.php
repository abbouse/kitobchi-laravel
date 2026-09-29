<?php

namespace App\Support;

use App\Enums\CourierOrderStatusCode;
use App\Models\CourierOrder;
use App\Models\ProjectSetting;
use Illuminate\Support\Facades\Schema;

/**
 * Kuryer cheklovlari (boshqaruv → Sozlamalar → Kuryer).
 */
class CourierLimits
{
    public const DEFAULT_MAX_ACTIVE_ORDERS = 3;

    public const MIN_ACTIVE_ORDERS = 1;

    public const MAX_ACTIVE_ORDERS = 10;

    /** Kuryer shuncha soatdan beri "yetkazilmoqda"da tursa — qotib qolgan. */
    public const STUCK_IN_DELIVERY_HOURS = 24;

    /** Buyurtma shuncha soat kuryersiz kutsa — qotib qolgan. */
    public const STUCK_PENDING_HOURS = 3;

    /** Kuryer shuncha daqiqa ichida joylashuv/holat yubormasa — online emas. */
    public const ONLINE_FRESH_MINUTES = 15;

    private static ?int $cached = null;

    /** Bir vaqtda ruxsat etilgan faol buyurtmalar soni. */
    public static function maxActiveOrders(): int
    {
        if (self::$cached !== null) {
            return self::$cached;
        }

        $value = null;
        if (Schema::hasColumn('project_settings', 'courier_max_active_orders')) {
            $value = ProjectSetting::query()->value('courier_max_active_orders');
        }
        $value = (int) ($value ?: self::DEFAULT_MAX_ACTIVE_ORDERS);

        return self::$cached = max(self::MIN_ACTIVE_ORDERS, min(self::MAX_ACTIVE_ORDERS, $value));
    }

    /** Sozlama o'zgarganda (va testlarda) keshni tozalash. */
    public static function flush(): void
    {
        self::$cached = null;
        self::$attemptColumns = null;
    }

    /** Kuryerning hozirgi faol (yetkazilayotgan) buyurtmalari soni. */
    public static function activeOrdersCount(int $courierId): int
    {
        return CourierOrder::query()
            ->where('courier_id', $courierId)
            ->where(function ($query) {
                $query->where('status_code', CourierOrderStatusCode::IN_DELIVERY->value)
                    ->orWhere(function ($fallback) {
                        $fallback->whereNull('status_code')
                            ->where('status', CourierOrderStatusCode::IN_DELIVERY->legacy());
                    });
            })
            // Ertaga qayta yetkaziladigan kitoblar bugungi limitni band qilmaydi
            ->when(self::hasAttemptColumns(), fn ($q) => $q->where(fn ($n) => $n
                ->whereNull('next_attempt_at')
                ->orWhere('next_attempt_at', '<=', now())))
            ->count();
    }

    /**
     * Qotib qolgan kuryer buyurtmalari: uzoq vaqt kuryerda turgan yoki
     * kuryersiz kutib qolganlar.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<CourierOrder>  $query
     */
    public static function applyStuckScope($query)
    {
        return $query->where(function ($outer) {
            $outer->where(function ($q) {
                $q->where('status_code', CourierOrderStatusCode::IN_DELIVERY->value)
                    ->where('updated_at', '<', now()->subHours(self::STUCK_IN_DELIVERY_HOURS));
                if (self::hasAttemptColumns()) {
                    // Ertaga qayta urinish belgilangan buyurtma vaqti kelmaguncha qotgan emas
                    $q->where(fn ($n) => $n->whereNull('next_attempt_at')->orWhere('next_attempt_at', '<', now()));
                }
            })->when(self::hasAttemptColumns(), fn ($o) => $o->orWhere(fn ($q) => $q
                ->where('status_code', CourierOrderStatusCode::IN_DELIVERY->value)
                ->whereNotNull('return_required_at')))
            ->orWhere(function ($q) {
                $q->whereNull('courier_id')
                    ->where('status_code', CourierOrderStatusCode::PENDING->value)
                    ->where('created_at', '<', now()->subHours(self::STUCK_PENDING_HOURS));
                // Mijoz tanlagan kun yoki predzakaz sanasi kelmagan — kutish normal
                if (Schema::hasColumn('solds', 'delivery_date')) {
                    $q->whereNotIn('order_id', fn ($sub) => $sub->select('id')->from('solds')
                        ->whereDate('delivery_date', '>', today()));
                }
                if (Schema::hasColumn('solds', 'preorder_ships_at')) {
                    $q->whereNotIn('order_id', fn ($sub) => $sub->select('id')->from('solds')
                        ->whereDate('preorder_ships_at', '>', today()));
                }
            });
        });
    }

    /** Haqiqatan online kuryerlar (tasdiqlangan, online va yaqinda signal bergan). */
    public static function onlineCouriersQuery()
    {
        $since = now()->subMinutes(self::ONLINE_FRESH_MINUTES);

        return \App\Models\Couriers::query()
            ->where('status', 'approved')
            ->where('is_online', true)
            ->where(fn ($q) => $q->where('location_updated_at', '>=', $since)
                ->orWhere('availability_updated_at', '>=', $since));
    }

    private static ?bool $attemptColumns = null;

    public static function hasAttemptColumns(): bool
    {
        return self::$attemptColumns ??= Schema::hasColumn('courier_orders', 'next_attempt_at');
    }
}
