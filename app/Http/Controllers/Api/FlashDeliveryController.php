<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProjectSetting;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

/**
 * Flash Delivery — kunlik 15 daqiqalik bepul yetkazish aksiyasi.
 *
 * Admin paneldan `ProjectSetting` orqali sozlanadi:
 *  - flash_delivery_enabled  : bool   — aksiya yoqilgan/o'chirilgan
 *  - flash_delivery_start_time: string — "HH:MM" formatida (masalan "20:00")
 *  - flash_delivery_duration  : int    — daqiqa (standart: 15)
 *  - flash_delivery_promo_code: string — ishlatiluvchi promokod kodi
 */
class FlashDeliveryController extends Controller
{
    /**
     * Hozirgi flash delivery holatini qaytaradi.
     *
     * Javob:
     *  {
     *    "enabled": true,          // Aksiya umuman yoqilganmi?
     *    "active": true,           // Hozir aktiv oynada ekanmi?
     *    "promo_code": "BEPUL15",  // Qo'llaniladigan promokod
     *    "seconds_remaining": 312, // Aktiv bo'lsa — qolgan soniya
     *    "seconds_until_next": null // Aktiv emas bo'lsa — keyingi boshlanishgacha soniya (0 bo'lsa bugun tugagan)
     *  }
     */
    public function status(): JsonResponse
    {
        $setting = Cache::remember('flash_delivery:setting', 60, fn () => ProjectSetting::first());

        // Aksiya umuman o'chirilgan bo'lsa — tez javob
        if (!$setting || !$setting->flash_delivery_enabled) {
            return response()->json([
                'enabled'          => false,
                'active'           => false,
                'promo_code'       => null,
                'seconds_remaining' => 0,
                'seconds_until_next' => null,
            ]);
        }

        $startTimeStr = $setting->flash_delivery_start_time ?? '20:00';
        $durationMin  = (int) ($setting->flash_delivery_duration ?? 15);
        $promoCode    = $setting->flash_delivery_promo_code ?? 'BEPULYETKAZISH';

        // Toshkent vaqti zonesida hisoblash
        $now       = Carbon::now('Asia/Tashkent');
        $todayStart = Carbon::createFromFormat(
            'Y-m-d H:i',
            $now->format('Y-m-d') . ' ' . $startTimeStr,
            'Asia/Tashkent'
        );
        $todayEnd = $todayStart->copy()->addMinutes($durationMin);

        $active           = $now->gte($todayStart) && $now->lt($todayEnd);
        $secondsRemaining = $active ? (int) $now->diffInSeconds($todayEnd) : 0;

        // Keyingi sessiygacha qolgan vaqt
        $secondsUntilNext = null;
        if (!$active) {
            if ($now->lt($todayStart)) {
                // Bugun hali boshlanmagan
                $secondsUntilNext = (int) $now->diffInSeconds($todayStart);
            } else {
                // Bugun tugagan — ertangi seansgacha
                $tomorrowStart = $todayStart->copy()->addDay();
                $secondsUntilNext = (int) $now->diffInSeconds($tomorrowStart);
            }
        }

        return response()->json([
            'enabled'            => true,
            'active'             => $active,
            'promo_code'         => $promoCode,
            'seconds_remaining'  => $secondsRemaining,
            'seconds_until_next' => $secondsUntilNext,
            'start_time'         => $startTimeStr,
            'duration_minutes'   => $durationMin,
        ]);
    }
}
