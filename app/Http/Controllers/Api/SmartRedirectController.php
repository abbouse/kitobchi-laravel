<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\ProductDeeplink;
use Illuminate\Http\Request;

class SmartRedirectController extends Controller
{
    /**
     * Ilovani yuklab olish uchun yagona havola (Instagram bio, QR kod va h.k.):
     * Android → Google Play, iPhone/iPad → App Store, kompyuter → sayt.
     */
    public function app(Request $request)
    {
        $userAgent = strtolower((string) $request->userAgent());
        $stores = ProductDeeplink::stores();

        if (str_contains($userAgent, 'android')) {
            return redirect()->away($stores['play_store_url']);
        }

        if (str_contains($userAgent, 'iphone') || str_contains($userAgent, 'ipad')) {
            return redirect()->away($stores['app_store_url']);
        }

        return redirect()->away(rtrim((string) (config('app.url') ?: 'https://kitobchi.com'), '/'));
    }

    public function redirect(Request $request, string $type, int|string $id)
    {
        $type = strtolower(trim((string) $type));
        $id = trim((string) $id);
        $userAgent = strtolower((string) $request->userAgent());
        $isAndroid = str_contains($userAgent, 'android');
        $isIos = str_contains($userAgent, 'iphone') || str_contains($userAgent, 'ipad');

        $path = match ($type) {
            'stationery', 'k' => "stationery/{$id}",
            'seller', 's'     => "seller/{$id}",
            'category', 'c'   => "category/{$id}",
            default           => "book/{$id}",
        };

        $baseUrl = config('app.url') ?: 'https://kitobchi.com';
        $baseUrl = rtrim((string) $baseUrl, '/');
        if (! str_starts_with($baseUrl, 'http')) {
            $baseUrl = 'https://kitobchi.com';
        }

        $queryString = $request->getQueryString();
        $querySuffix = $queryString ? "?{$queryString}" : '';

        $webUrl = "{$baseUrl}/{$path}{$querySuffix}";
        $appScheme = "kitobchi://{$path}{$querySuffix}";
        ['play_store_url' => $playStore, 'app_store_url' => $appStore] = ProductDeeplink::stores();

        if ($isAndroid || $isIos) {
            $fallbackUrl = $isAndroid ? $playStore : $appStore;

            $html = '<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kitobchi ochilmoqda...</title>
    <script>
        window.location.href = "' . $appScheme . '";
        setTimeout(function() {
            window.location.href = "' . $webUrl . '";
        }, 1200);
    </script>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, sans-serif; display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100vh; margin: 0; background: #fafafa; color: #333; text-align: center; }
        .spinner { width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #4f46e5; border-radius: 50%; animation: spin 1s linear infinite; margin-bottom: 16px; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        a { color: #4f46e5; text-decoration: none; font-weight: 600; margin-top: 12px; display: inline-block; }
    </style>
</head>
<body>
    <div class="spinner"></div>
    <h3>Kitobchi ilovasi ochilmoqda...</h3>
    <p>Ilova ochilmasa, <a href="' . $webUrl . '">saytda ko‘rish</a> yoki <a href="' . $fallbackUrl . '">ilovada ochish</a></p>
</body>
</html>';

            return response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
        }

        return redirect()->away($webUrl);
    }
}
