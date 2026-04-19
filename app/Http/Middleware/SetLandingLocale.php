<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLandingLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('panel') || $request->is('panel/*')) {
            App::setLocale(config('app.locale'));

            return $next($request);
        }

        $codes = config('landing_locales.codes', ['uz']);
        $default = 'uz';
        $fromCookie = $request->cookie('kc_locale');
        $locale = is_string($fromCookie) && in_array($fromCookie, $codes, true)
            ? $fromCookie
            : $default;

        App::setLocale($locale);

        return $next($request);
    }
}
