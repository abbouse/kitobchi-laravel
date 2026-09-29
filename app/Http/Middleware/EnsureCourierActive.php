<?php

namespace App\Http\Middleware;

use App\Models\Couriers;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Faqat tasdiqlangan (approved) kuryer API'dan foydalanadi.
 *
 * Avval holat faqat login paytida tekshirilardi: admin bloklagan kuryer eski
 * tokeni bilan buyurtma (jumladan naqd) olishda davom etardi. Endi har
 * so'rovda tekshiriladi; bloklangan bo'lsa token o'chiriladi va 401 qaytadi
 * (ilova sessiyani yopadi).
 */
class EnsureCourierActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $courier = Auth::guard('courier')->user();

        if ($courier instanceof Couriers && ! $courier->canWork()) {
            $courier->currentAccessToken()?->delete();

            return response()->json([
                'success' => false,
                'error_code' => 'courier_blocked',
                'message' => __('courier_api.account_blocked'),
            ], 401);
        }

        return $next($request);
    }
}
