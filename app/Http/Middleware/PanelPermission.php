<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class PanelPermission
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $admin = Auth::guard('panel')->user();

        if (! $admin) {
            return redirect()->route('panel.login');
        }

        // Superadmin — hamma joiga kiradi
        if ($admin->isSuperAdmin()) {
            return $next($request);
        }

        // Berilgan ruxsatlardan birortasi admin'da bormi?
        foreach ($permissions as $perm) {
            if ($admin->hasPermission($perm)) {  // ← can() emas, hasPermission()
                return $next($request);
            }
        }

        abort(403, "Bu bo'limga kirishga ruxsatingiz yo'q.");
    }
}