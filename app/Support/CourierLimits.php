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
            ->count();
    }
}
