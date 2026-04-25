<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * API javoblarini lokalizatsiya qilish uchun middleware.
 *
 * Mobil ilovalar `lang` query yoki `Accept-Language` headerini yuborishi mumkin
 * (masalan: `?lang=uz` yoki `Accept-Language: ru`). Qabul qilingan til
 * `app()->setLocale(...)` ga uzatiladi va `__('...')` chaqiriqlari to'g'ri tilda
 * matn qaytaradi.
 *
 * Qo'llab-quvvatlanadigan tillar: uz (default), en, ru, ja.
 */
class SetApiLocale
{
    private const SUPPORTED = ['uz', 'en', 'ru', 'ja'];
    private const DEFAULT   = 'uz';

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->resolveLocale($request);
        App::setLocale($locale);

        // Javob headerida ham yuboramiz, klient tekshira oladi.
        $response = $next($request);
        if ($response instanceof Response) {
            $response->headers->set('Content-Language', $locale);
        }
        return $response;
    }

    private function resolveLocale(Request $request): string
    {
        // 1) Aniq query yoki body parametri
        $candidate = $request->input('lang')
            ?? $request->header('X-App-Locale')
            ?? null;

        if (is_string($candidate) && in_array(strtolower($candidate), self::SUPPORTED, true)) {
            return strtolower($candidate);
        }

        // 2) Accept-Language headerdagi birinchi qo'llab-quvvatlanadigan til
        $acceptLanguage = $request->header('Accept-Language');
        if (is_string($acceptLanguage) && $acceptLanguage !== '') {
            // "uz, en;q=0.9, ru;q=0.8" → ['uz', 'en', 'ru']
            $tags = preg_split('/\s*,\s*/', $acceptLanguage) ?: [];
            foreach ($tags as $tag) {
                $base = strtolower(substr(trim((string)$tag), 0, 2));
                if (in_array($base, self::SUPPORTED, true)) {
                    return $base;
                }
            }
        }

        return self::DEFAULT;
    }
}
