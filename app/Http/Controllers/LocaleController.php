<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class LocaleController extends Controller
{
    public function switch(Request $request, string $locale): RedirectResponse
    {
        $codes = config('landing_locales.codes', ['uz']);
        if (! in_array($locale, $codes, true)) {
            abort(404);
        }

        $target = $request->query('redirect', '/');
        if (! is_string($target) || ! str_starts_with($target, '/') || str_starts_with($target, '//')) {
            $target = '/';
        }

        return redirect()->to(url($target))->withCookie(
            Cookie::make(
                'kc_locale',
                $locale,
                60 * 24 * 365,
                '/',
                null,
                (bool) config('session.secure'),
                true,
                false,
                config('session.same_site') ?? 'lax'
            )
        );
    }
}
