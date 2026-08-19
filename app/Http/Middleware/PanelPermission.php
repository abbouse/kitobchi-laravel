<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Boshqaruv route guruhlariga qo'llaniladi: `panel.permission:catalog` yoki
 * bir nechta modul birga (OR mantig'i bilan) — `panel.permission:marketing,push`.
 *
 * Ikki bosqichli tekshiruv:
 *  1) Kirish huquqi — superadmin har doim kiradi, boshqalar $admin->hasPermission()
 *     orqali tekshiriladi.
 *  2) Faqat-ko'rish (Auditor) cheklovi — is_read_only=true bo'lgan admin GET/HEAD
 *     dan boshqa hech qanday so'rovni (POST/PUT/PATCH/DELETE) bajara olmaydi,
 *     hatto tegishli permission bo'lsa ham. Bu superadmindan ham ustun turadi —
 *     agar kimdir xato bilan superadminni ham read-only qilib qo'ysa, baribir
 *     yozish taqiqlanadi (ataylab shunday: "faqat ko'rish" degani har doim
 *     shunday bo'lishi kerak).
 */
class PanelPermission
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $admin = Auth::guard('panel')->user();

        if (! $admin) {
            return redirect()->route('boshqaruv.login');
        }

        $hasAccess = $admin->isSuperAdmin();

        if (! $hasAccess) {
            foreach ($permissions as $perm) {
                if ($admin->hasPermission($perm)) {
                    $hasAccess = true;
                    break;
                }
            }
        }

        if (! $hasAccess) {
            abort(403, "Bu bo'limga kirishga ruxsatingiz yo'q.");
        }

        if (($admin->is_read_only ?? false) && ! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
            abort(403, "Hisobingiz faqat ko'rish (auditor) rejimida — o'zgartirish amallari cheklangan.");
        }

        return $next($request);
    }
}
