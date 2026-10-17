<?php

namespace App\Http\Middleware;

use App\Models\HubStaff;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Hub xodimi va hubning o'zi faol bo'lsagina API ishlaydi. Avval faqat login
 * tekshirilardi: o'chirilgan xodim yoki yopilgan hub eski token bilan
 * qabul/jo'natish amallarini bajarishda davom etardi.
 */
class EnsureHubStaffActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $staff = Auth::guard('hub')->user();
        if ($staff instanceof HubStaff && (! $staff->is_active || ($staff->hub && ! $staff->hub->is_active))) {
            $staff->currentAccessToken()?->delete();

            return response()->json([
                'success' => false,
                'error_code' => $staff->is_active ? 'hub_inactive' : 'hub_staff_blocked',
                'message' => $staff->is_active ? 'Hub vaqtincha yopilgan.' : 'Xodim akkaunti o‘chirilgan.',
            ], 401);
        }

        return $next($request);
    }
}
