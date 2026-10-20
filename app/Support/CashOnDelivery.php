<?php

namespace App\Support;

use App\Models\ProjectSetting;
use App\Models\User;
use App\Services\UserReputationService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Naqd to'lov (yetkazilganda to'lash) qoidalari — checkout, buyurtma va
 * /counts bir xil manbadan foydalanadi:
 *
 *  1. Boshqaruvda global o'chirilgan bo'lsa — hech kimga ko'rsatilmaydi.
 *  2. Kamida bitta buyurtmasi muvaffaqiyatli yakunlanmagan (yangi) mijozga
 *     ko'rsatilmaydi — birinchi buyurtma faqat karta orqali.
 *  3. Avval naqd buyurtma qaytarilgan bo'lsa — ko'rinadi, lekin yopiq (sababi bilan).
 */
class CashOnDelivery
{
    public const CODE_DISABLED = 'cod_disabled';
    public const CODE_FIRST_ORDER = 'cod_first_order';
    public const CODE_RETURNED = 'cash_on_delivery_blocked';

    public static function globallyEnabled(): bool
    {
        if (! Schema::hasColumn('project_settings', 'cash_on_delivery_enabled')) {
            return true;
        }

        return (bool) (Cache::remember('cod:enabled', 60, fn () => (int) (ProjectSetting::query()->value('cash_on_delivery_enabled') ?? 1)));
    }

    public static function forgetCache(): void
    {
        Cache::forget('cod:enabled');
    }

    /**
     * @return array{allowed: bool, visible: bool, code: ?string, reason: ?string}
     */
    public static function forUser(?User $user): array
    {
        if (! self::globallyEnabled()) {
            return self::deny(self::CODE_DISABLED, "Hozircha naqd to'lov qabul qilinmaydi. Iltimos, karta orqali to'lang.", false);
        }

        if (! $user) {
            return self::deny(self::CODE_FIRST_ORDER, "Naqd to'lov birinchi buyurtmangiz muvaffaqiyatli yetkazilgandan keyin ochiladi.", false);
        }

        if (app(UserReputationService::class)->completedOrdersCount($user) < 1) {
            return self::deny(self::CODE_FIRST_ORDER, "Naqd to'lov birinchi buyurtmangiz muvaffaqiyatli yetkazilgandan keyin ochiladi.", false);
        }

        if (! $user->canUseCashOnDelivery()) {
            return self::deny(self::CODE_RETURNED, "Avvalgi naqd buyurtma qaytib kelgani uchun hozircha naqd to'lov yopilgan.", true);
        }

        return ['allowed' => true, 'visible' => true, 'code' => null, 'reason' => null];
    }

    private static function deny(string $code, string $reason, bool $visible): array
    {
        return ['allowed' => false, 'visible' => $visible, 'code' => $code, 'reason' => $reason];
    }
}
