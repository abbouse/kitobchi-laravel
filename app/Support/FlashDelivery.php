<?php

namespace App\Support;

use App\Models\ProjectSetting;
use App\Models\Sold;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * "Bepul yetkazish soati" (Flash Delivery): har kuni belgilangan vaqtda
 * N daqiqa davomida buyurtma bergan har bir mijozga yetkazish bepul.
 *
 * Har bir mijoz uchun limit (project_settings.flash_delivery_limit):
 *  - daily — har kuni bir marta (standart)
 *  - once  — umrida bir marta
 *
 * Promokod kiritish shart emas: aksiya vaqtida checkout'da avtomatik qo'llanadi.
 */
class FlashDelivery
{
    public const TZ = 'Asia/Tashkent';

    /** To'lov sahifasida ushlanib qolganlar uchun tugashdan keyingi kichik muhlat (soniya). */
    public const GRACE_SECONDS = 120;

    public static function setting(): ?ProjectSetting
    {
        return Cache::remember('flash_delivery:setting', 60, fn () => ProjectSetting::query()->first());
    }

    /**
     * @return array{start: Carbon, end: Carbon}|null  bugungi (yoki hozir davom etayotgan) oyna
     */
    public static function window(?Carbon $now = null): ?array
    {
        $setting = self::setting();
        if (! $setting || ! ($setting->flash_delivery_enabled ?? false)) {
            return null;
        }

        $now = ($now ?? Carbon::now())->copy()->setTimezone(self::TZ);
        $time = preg_match('/^\d{2}:\d{2}$/', (string) $setting->flash_delivery_start_time) ? $setting->flash_delivery_start_time : '20:00';
        $duration = max(1, (int) ($setting->flash_delivery_duration ?? 15));

        $start = Carbon::createFromFormat('Y-m-d H:i', $now->format('Y-m-d') . ' ' . $time, self::TZ)->startOfMinute();
        // Yarim tundan o'tadigan oyna (masalan 23:55 + 15 daqiqa): kechagi oyna hali davom etayotgan bo'lishi mumkin
        $yesterday = $start->copy()->subDay();
        if ($now->lt($start) && $now->lt($yesterday->copy()->addMinutes($duration))) {
            $start = $yesterday;
        }

        return ['start' => $start, 'end' => $start->copy()->addMinutes($duration)];
    }

    public static function isActive(int $graceSeconds = 0, ?Carbon $now = null): bool
    {
        $window = self::window($now);
        if (! $window) {
            return false;
        }
        $now = ($now ?? Carbon::now())->copy()->setTimezone(self::TZ);

        return $now->gte($window['start']) && $now->lt($window['end']->copy()->addSeconds($graceSeconds));
    }

    public static function limitMode(): string
    {
        $mode = (string) (self::setting()?->flash_delivery_limit ?? 'daily');

        return in_array($mode, ['daily', 'once'], true) ? $mode : 'daily';
    }

    /** Mijoz bu aksiyadan (bugun yoki umuman) foydalanganmi. */
    public static function usedBy(User $user, ?Carbon $now = null): bool
    {
        if (! Schema::hasColumn('solds', 'flash_free_delivery')) {
            return false;
        }

        $query = Sold::query()
            ->where('user_id', $user->id)
            ->where('flash_free_delivery', true)
            ->where(fn ($q) => $q->whereNull('status_code')->orWhereNotIn('status_code', ['cancelled']));

        if (self::limitMode() === 'daily') {
            $window = self::window($now);
            $dayStart = $window ? $window['start']->copy()->startOfDay() : Carbon::now(self::TZ)->startOfDay();
            $query->whereBetween('created_at', [$dayStart->copy()->setTimezone(config('app.timezone')), $dayStart->copy()->endOfDay()->setTimezone(config('app.timezone'))]);
        }

        return $query->exists();
    }

    /** Hozir shu mijozga bepul yetkazish qo'llanadimi (to'lov muhlati bilan). */
    public static function appliesTo(?User $user, ?Carbon $now = null): bool
    {
        return $user !== null && self::isActive(self::GRACE_SECONDS, $now) && ! self::usedBy($user, $now);
    }

    /**
     * Ilova va sayt uchun holat.
     */
    public static function status(?User $user = null, ?Carbon $now = null): array
    {
        $setting = self::setting();
        $window = self::window($now);
        $nowTz = ($now ?? Carbon::now())->copy()->setTimezone(self::TZ);

        if (! $window) {
            return [
                'enabled' => false,
                'active' => false,
                'promo_code' => null,
                'seconds_remaining' => 0,
                'seconds_until_next' => null,
                'eligible' => null,
                'used' => false,
                'server_time' => $nowTz->toIso8601String(),
            ];
        }

        $active = $nowTz->gte($window['start']) && $nowTz->lt($window['end']);
        $nextStart = $nowTz->lt($window['start']) ? $window['start'] : $window['start']->copy()->addDay();
        $used = $user ? self::usedBy($user, $now) : false;

        return [
            'enabled' => true,
            'active' => $active,
            'promo_code' => $setting->flash_delivery_promo_code ?: null,
            'seconds_remaining' => $active ? (int) $nowTz->diffInSeconds($window['end']) : 0,
            'seconds_until_next' => $active ? null : (int) $nowTz->diffInSeconds($nextStart),
            'start_time' => $window['start']->format('H:i'),
            'end_time' => $window['end']->format('H:i'),
            'duration_minutes' => (int) $window['start']->diffInMinutes($window['end']),
            'starts_at' => ($active ? $window['start'] : $nextStart)->toIso8601String(),
            'ends_at' => ($active ? $window['end'] : $nextStart->copy()->addMinutes((int) $window['start']->diffInMinutes($window['end'])))->toIso8601String(),
            'limit' => self::limitMode(),
            // Mehmon uchun null; kirgan mijoz uchun: bugun (yoki umuman) hali ishlatmagan bo'lsa true
            'eligible' => $user ? ! $used : null,
            'used' => $used,
            'server_time' => $nowTz->toIso8601String(),
        ];
    }
}
