<?php

namespace App\Http\Middleware;

use App\Models\Seller;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Har so'rovda do'kon (va xodim) faolligini tekshiradi.
 *
 * Avval holat faqat login paytida tekshirilardi: admin bloklagan do'kon eski
 * tokeni bilan buyurtmalar, mahsulotlar va hatto pul yechish bilan ishlashda
 * davom etardi; bloklangan do'konning xodimlari esa umuman to'xtatilmasdi.
 * Endi token o'chiriladi va 401 + error_code qaytadi — ilova sessiyani yopadi
 * va sababni ko'rsatadi.
 */
class EnsureSellerActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $seller = Auth::guard('seller')->user();
        if (! $seller instanceof Seller) {
            return $next($request);
        }

        $store = $seller->storeAccount();
        $code = null;
        if ($store->status !== 'approved') {
            $code = in_array($store->status, ['blocked', 'banned'], true) ? 'seller_blocked' : 'seller_inactive';
        } elseif ($seller->parent_id && $seller->staff_status !== 'active') {
            $code = 'seller_staff_inactive';
        }

        if ($code === null) {
            return $next($request);
        }

        $seller->currentAccessToken()?->delete();

        return response()->json([
            'success' => false,
            'error_code' => $code,
            'message' => match ($code) {
                'seller_blocked' => 'Do‘koningiz admin tomonidan cheklangan.'.($store->block_reason ? ' Sabab: '.$store->block_reason : ''),
                'seller_staff_inactive' => 'Xodim akkaunti faolsizlantirilgan.',
                default => 'Do‘kon profili faol emas.',
            },
            'blocked_until' => $store->blocked_until?->toIso8601String(),
            'reason' => $store->block_reason,
        ], 401);
    }
}
