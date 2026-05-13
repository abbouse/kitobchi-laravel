<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateHubDesk
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::guard('hub_web')->check()) {
            return redirect()->route('hubdesk.login');
        }

        /** @var \App\Models\HubStaff $staff */
        $staff = Auth::guard('hub_web')->user();
        if (! $staff->is_active) {
            Auth::guard('hub_web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('hubdesk.login')->withErrors([
                'username' => 'Akkaunt o‘chiq yoki bloklangan.',
            ]);
        }

        $staff->forceFill([
            'last_seen_at' => now(),
        ])->save();

        return $next($request);
    }
}
