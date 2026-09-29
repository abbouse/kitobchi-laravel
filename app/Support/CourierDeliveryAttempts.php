<?php

namespace App\Support;

use App\Models\ProjectSetting;
use Illuminate\Support\Facades\Schema;

/**
 * "Yetkazib bo'lmadi" sabablari va qayta urinishlar chegarasi.
 */
class CourierDeliveryAttempts
{
    public const DEFAULT_MAX = 3;

    public const REASONS = [
        'customer_unreachable' => ["Mijoz telefonni ko'tarmadi", 'Клиент не отвечает'],
        'customer_absent' => ["Mijoz manzilda yo'q", 'Клиента нет по адресу'],
        'customer_rescheduled' => ["Mijoz boshqa vaqtni so'radi", 'Клиент перенёс время'],
        'wrong_address' => ["Manzil noto'g'ri / topilmadi", 'Неверный адрес'],
        'customer_refused' => ['Mijoz qabul qilmadi', 'Клиент отказался'],
        'other' => ['Boshqa sabab', 'Другая причина'],
    ];

    /** Bu sabablarda qayta urinish ma'nosiz — darhol qaytarish kerak. */
    public const RETURN_REASONS = ['customer_refused'];

    private static ?int $cached = null;

    public static function reasonLabel(string $reason, string $locale = 'uz'): string
    {
        $pair = self::REASONS[$reason] ?? null;
        if (! $pair) {
            return $reason;
        }

        return $locale === 'ru' ? $pair[1] : $pair[0];
    }

    public static function maxAttempts(): int
    {
        if (self::$cached !== null) {
            return self::$cached;
        }
        $value = null;
        if (Schema::hasColumn('project_settings', 'courier_max_delivery_attempts')) {
            $value = ProjectSetting::query()->value('courier_max_delivery_attempts');
        }

        return self::$cached = max(1, min(5, (int) ($value ?: self::DEFAULT_MAX)));
    }

    public static function flush(): void
    {
        self::$cached = null;
    }
}
