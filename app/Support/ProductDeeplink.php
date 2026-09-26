<?php

declare(strict_types=1);

namespace App\Support;

/**
 * MAHSULOT HAVOLALARI — bitta joyda.
 *
 * Ilgari `kitobchi://...` havolalari kamida 8 xil joyda qo'lda yig'ilardi
 * (`ProductsController::deeplink`, `SmartRedirectController`, `routes/web.php`,
 * `Web\ProductCatalogController`, ...). Format o'zgarsa hammasini topish kerak
 * edi. Endi shu yerdan olinadi.
 */
final class ProductDeeplink
{
    public static function baseUrl(): string
    {
        return rtrim((string) config('app.url', 'https://kitobchi.com'), '/');
    }

    /** `book` | `stationery` | `seller` uchun yo'l qismi. */
    public static function path(string $type, int|string $id): string
    {
        return match ($type) {
            'stationery' => "stationery/{$id}",
            'seller' => "seller/{$id}",
            default => "book/{$id}",
        };
    }

    /**
     * Mahsulot havolalari to'plami. `artikul` berilsa qisqa havola ham qo'shiladi.
     *
     * @return array<string, string>
     */
    public static function forProduct(string $type, int|string $id, ?string $artikul = null): array
    {
        $base = self::baseUrl();
        $path = self::path($type, $id);

        $links = [
            'web_url' => "{$base}/{$path}",
            'app_scheme_url' => "kitobchi://{$path}",
            'smart_redirect_url' => "{$base}/r/{$path}",
        ];

        if ($artikul !== null && $artikul !== '') {
            $links['short_url'] = "{$base}/art/{$artikul}";
        }

        return $links;
    }

    /** @return array<string, string> */
    public static function stores(): array
    {
        return [
            'play_store_url' => 'https://play.google.com/store/apps/details?id=com.kitobchi.kitobchi',
            'app_store_url' => 'https://apps.apple.com/uz/app/kitobchi/id6753818078',
        ];
    }
}
