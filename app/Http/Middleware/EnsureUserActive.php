<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bloklangan mijoz (blok paytida tokeni qolib ketgan bo'lsa ham) API'dan
 * foydalana olmaydi: 423 + user_account_blocked — ilova login bilan bir xil
 * javobni ko'rsatadi va sessiyani yopadi.
 */
class EnsureUserActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('user')->user();
        if ($user instanceof User && $user->isBlocked()) {
            $user->currentAccessToken()?->delete();

            return response()->json([
                'status' => 'error',
                'error_code' => 'user_account_blocked',
                'message' => 'Sizning akkauntingiz bloklangan.'.($user->blocked_until ? ' Blok muddati: '.$user->blocked_until->format('d.m.Y H:i') : ''),
                'blocked_until' => $user->blocked_until?->toIso8601String(),
                'block_reason' => $user->block_reason,
            ], 423);
        }

        return $next($request);
    }
}
