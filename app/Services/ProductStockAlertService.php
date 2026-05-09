<?php

namespace App\Services;

use App\Models\Books;
use App\Models\ConnectedDevice;
use App\Models\ProductStockAlert;
use App\Models\Stationery;
use App\Models\StationeryVariant;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ProductStockAlertService
{
    private const SUPPORTED_LOCALES = ['uz', 'ru', 'en', 'ja'];

    private const TEXTS = [
        'uz' => [
            'title' => 'Mahsulot yana sotuvda',
            'book' => '“:name” yana mavjud. Birinchi bo‘lib ulgurib qoling 📚',
            'stationery' => '“:name” yana mavjud. Qarab ko‘rishga ayni payt ✨',
            'variant' => '“:name”ning :variant varianti yana mavjud ✨',
        ],
        'ru' => [
            'title' => 'Товар снова в наличии',
            'book' => '“:name” снова доступен. Самое время заглянуть 📚',
            'stationery' => '“:name” снова доступен. Можно возвращаться ✨',
            'variant' => 'Вариант :variant для “:name” снова доступен ✨',
        ],
        'en' => [
            'title' => 'Back in stock',
            'book' => '“:name” is available again. Good time to grab it 📚',
            'stationery' => '“:name” is back in stock. Worth another look ✨',
            'variant' => 'The :variant variant of “:name” is back in stock ✨',
        ],
        'ja' => [
            'title' => '再入荷しました',
            'book' => '「:name」が再び入荷しました 📚',
            'stationery' => '「:name」が再入荷しました ✨',
            'variant' => '「:name」の:variantバリエーションが再入荷しました ✨',
        ],
    ];

    public function subscribe(User $user, string $productType, int $productId, ?int $variantId = null): array
    {
        $alert = ProductStockAlert::firstOrCreate([
            'user_id' => $user->id,
            'product_id' => $productId,
            'product_type' => $productType,
            'variant_id' => $variantId,
        ], [
            'notified_at' => null,
        ]);

        return [
            'created' => $alert->wasRecentlyCreated,
            'alert' => $alert,
        ];
    }

    public function notifyForBook(Books $book): void
    {
        if (!$book->wasChanged('count')) {
            return;
        }

        $this->notifyBookRestocked(
            $book,
            (int) $book->getOriginal('count'),
            (int) ($book->count ?? 0),
        );
    }

    public function notifyBookRestocked(Books $book, int $oldStock, int $newStock): void
    {
        if ($oldStock > 0 || $newStock <= 0) {
            return;
        }

        $alerts = ProductStockAlert::query()
            ->where('product_type', 'book')
            ->where('product_id', $book->id)
            ->whereNull('variant_id')
            ->get();

        $this->dispatchAlerts($alerts, 'book', (string) ($book->name ?? 'Mahsulot'), null);
    }

    public function notifyForStationery(Stationery $product): void
    {
        if (!$product->wasChanged('stock')) {
            return;
        }

        $this->notifyStationeryRestocked(
            $product,
            (int) $product->getOriginal('stock'),
            (int) ($product->stock ?? 0),
        );
    }

    public function notifyStationeryRestocked(Stationery $product, int $oldStock, int $newStock): void
    {
        if ($oldStock > 0 || $newStock <= 0) {
            return;
        }

        $alerts = ProductStockAlert::query()
            ->where('product_type', 'stationery')
            ->where('product_id', $product->id)
            ->whereNull('variant_id')
            ->get();

        $this->dispatchAlerts($alerts, 'stationery', (string) ($product->name ?? 'Mahsulot'), null);
    }

    public function notifyForVariant(StationeryVariant $variant): void
    {
        if (!$variant->wasChanged('stock')) {
            return;
        }

        $this->notifyVariantRestocked(
            $variant,
            (int) $variant->getOriginal('stock'),
            (int) ($variant->stock ?? 0),
        );
    }

    public function notifyVariantRestocked(StationeryVariant $variant, int $oldStock, int $newStock): void
    {
        if ($oldStock > 0 || $newStock <= 0) {
            return;
        }

        $product = $variant->product()->first(['id', 'name']);
        if (!$product) {
            return;
        }

        $generalAlerts = ProductStockAlert::query()
            ->where('product_type', 'stationery')
            ->where('product_id', $product->id)
            ->whereNull('variant_id')
            ->get();

        $variantAlerts = ProductStockAlert::query()
            ->where('product_type', 'stationery')
            ->where('product_id', $product->id)
            ->where('variant_id', $variant->id)
            ->get();

        $this->dispatchAlerts(
            $generalAlerts,
            'stationery',
            (string) ($product->name ?? 'Mahsulot'),
            null
        );

        $this->dispatchAlerts(
            $variantAlerts,
            'stationery',
            (string) ($product->name ?? 'Mahsulot'),
            (string) ($variant->color_name ?? 'variant')
        );
    }

    private function dispatchAlerts(Collection $alerts, string $productType, string $productName, ?string $variantName): void
    {
        if ($alerts->isEmpty()) {
            return;
        }

        $userIds = $alerts->pluck('user_id')->filter()->unique()->values();
        $users = User::query()
            ->whereIn('id', $userIds)
            ->get(['id', 'locale'])
            ->keyBy('id');

        $deleteIds = [];

        foreach ($alerts as $alert) {
            $user = $users->get($alert->user_id);
            if (!$user) {
                $deleteIds[] = $alert->id;
                continue;
            }

            $tokens = $this->tokensForUser((int) $user->id);
            if ($tokens->isEmpty()) {
                continue;
            }

            $locale = $this->resolveLocale($user->locale ?? null);
            $title = self::TEXTS[$locale]['title'];
            $template = $variantName !== null
                ? self::TEXTS[$locale]['variant']
                : self::TEXTS[$locale][$productType === 'book' ? 'book' : 'stationery'];

            $body = str_replace(
                [':name', ':variant'],
                [$productName, $variantName ?? ''],
                $template
            );

            $payload = [
                'type' => 'product',
                'product_id' => (string) $alert->product_id,
                'product_type' => (string) $alert->product_type,
            ];

            $result = (new FCMService('kitobchi'))->send($tokens->all(), $title, $body, $payload);

            Log::info('Product stock alert push sent', [
                'product_id' => $alert->product_id,
                'product_type' => $alert->product_type,
                'variant_id' => $alert->variant_id,
                'user_id' => $user->id,
                'locale' => $locale,
                'tokens' => $tokens->count(),
                'result' => $result,
            ]);

            $deleteIds[] = $alert->id;
        }

        if (!empty($deleteIds)) {
            ProductStockAlert::query()->whereIn('id', $deleteIds)->delete();
        }
    }

    private function tokensForUser(int $userId): Collection
    {
        return ConnectedDevice::query()
            ->where('user_type', 'user')
            ->where('user_id', $userId)
            ->whereNotNull('fcm_token')
            ->where('fcm_token', '!=', '')
            ->pluck('fcm_token')
            ->filter(fn ($token) => is_string($token) && $token !== '')
            ->unique()
            ->values();
    }

    private function resolveLocale(?string $locale): string
    {
        return in_array($locale, self::SUPPORTED_LOCALES, true) ? $locale : 'uz';
    }
}
