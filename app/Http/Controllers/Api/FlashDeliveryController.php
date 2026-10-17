<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\FlashDelivery;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * Bepul yetkazish soati (Flash Delivery) holati — ilova va sayt banneri uchun.
 *
 * Ochiq endpoint: token yuborilsa, mijoz bugun foydalangan-foydalanmagani ham qaytadi.
 * Javob: enabled, active, seconds_remaining, seconds_until_next, start_time, end_time,
 *        starts_at, ends_at, duration_minutes, limit, eligible, used, server_time, promo_code
 */
class FlashDeliveryController extends Controller
{
    public function status(): JsonResponse
    {
        $user = null;
        try {
            $user = Auth::guard('user')->user();
        } catch (\Throwable) {
            $user = null;
        }

        return response()->json(FlashDelivery::status($user));
    }
}
