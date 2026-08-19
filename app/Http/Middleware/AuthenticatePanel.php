<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Panel (Admin) autentifikatsiya middleware
 * — panel guard ishlatadi
 * — aktiv emaslarni bloklaydi
 */
class AuthenticatePanel
{
    public function handle(Request $request, Closure $next): Response
    {
        // Panel guard orqali tekshirish
        // ESLATMA: eski /a122 paneli chiqarib tashlangan (routes/web.php'da
        // a122.php endi ulanmaydi), shuning uchun bu yerda faqat boshqaruv.login'ga
        // yo'naltiramiz — 'admin.login' route'i endi mavjud emas.
        if (! Auth::guard('panel')->check()) {
            return redirect()
                ->route('boshqaruv.login')
                ->with('error', "Iltimos, tizimga kiring.");
        }

        $admin = Auth::guard('panel')->user();

        // Bloklanganmi?
        if (! $admin->is_active) {
            Auth::guard('panel')->logout();
            return redirect()
                ->route('boshqaruv.login')
                ->with('error', "Akkauntingiz bloklangan. Aloqa uchun: admin@kitob.uz");
        }

        // Request ga adminni yuklaymiz (view'larda auth()->user() ishlashi uchun)
        $request->merge(['_panel_admin' => $admin]);

        return $next($request);
    }
}
