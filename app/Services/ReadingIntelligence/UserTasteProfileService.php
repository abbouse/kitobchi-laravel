<?php

namespace App\Services\ReadingIntelligence;

use App\Models\Books;
use App\Models\Sold;
use App\Models\Stationery;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Foydalanuvchining "did vektori"ni va xarid tarixiga bog'liq signallarni
 * hisoblaydi — S1 (did moslik) va D1/D2 holatlari uchun asos.
 *
 * MUHIM (yuklama xavfsizligi): bu servisning har bir metodi item sahifasi
 * OCHILGANDA (ya'ni juda tez-tez) chaqiriladi. Shuning uchun bu yerda:
 *   - hech qanday cheklovsiz (`LIMIT`siz) so'rov YO'Q,
 *   - hech qanday keshlanmagan foydalanuvchi-darajali so'rov YO'Q,
 *   - hech qanday N+1 (loop ichida bitta-bitta so'rov) YO'Q — barcha
 *     mahsulot ma'lumotlari `whereIn` bilan bitta so'rovda olinadi.
 *
 * Hech qanday yangi AI chaqiruvi yo'q — faqat mavjud `vectorData` (product
 * embedding, ProductVectorService orqali allaqachon hisoblangan) va `solds`
 * jadvalidan qurilgan oddiy statistik agregatsiya.
 */
class UserTasteProfileService
{
    private const LOOKBACK_DAYS = 365;
    private const MAX_ORDERS_FOR_VECTOR = 20;
    private const MIN_ORDERS_FOR_TASTE = 3;
    private const CACHE_TTL = 3600;

    /** "Allaqachon sotib olinganmi" indeksi uchun — kengroq, lekin baribir CHEGARALANGAN tarix. */
    private const MAX_ORDERS_FOR_PURCHASE_INDEX = 300;

    /**
     * Foydalanuvchining oxirgi xaridlaridan qurilgan o'rtacha embedding.
     * Kamida MIN_ORDERS_FOR_TASTE ta tugallangan xarid bo'lmasa null —
     * bu holda S1 "mavjud emas" deb hisoblanadi (Holat C/D ga tushadi).
     */
    public function tasteVector(int $userId, string $excludeType, int $excludeId): ?array
    {
        $purchases = $this->recentPurchases($userId)
            ->reject(fn (array $p) => $p['type'] === $excludeType && $p['id'] === $excludeId)
            ->values();

        if ($purchases->count() < self::MIN_ORDERS_FOR_TASTE) {
            return null;
        }

        $bookIds = $purchases->where('type', 'book')->pluck('id')->unique()->values()->all();
        $stationeryIds = $purchases->where('type', 'stationery')->pluck('id')->unique()->values()->all();

        // MUHIM: bitta-bitta emas — ikkita `whereIn` so'rov (ko'pi bilan
        // 20 mahsulot uchun 20 ta alohida so'rov o'rniga).
        $bookVectors = ! empty($bookIds)
            ? Books::query()->whereIn('id', $bookIds)->whereNotNull('vectorData')->pluck('vectorData', 'id')
            : collect();
        $stationeryVectors = ! empty($stationeryIds)
            ? Stationery::query()->whereIn('id', $stationeryIds)->whereNotNull('vectorData')->pluck('vectorData', 'id')
            : collect();

        $vectors = [];
        foreach ($purchases as $p) {
            $vec = $p['type'] === 'book'
                ? ($bookVectors[$p['id']] ?? null)
                : ($stationeryVectors[$p['id']] ?? null);

            if (is_array($vec) && ! empty($vec)) {
                $vectors[] = $vec;
            }
        }

        if (count($vectors) < self::MIN_ORDERS_FOR_TASTE) {
            return null;
        }

        return $this->averageVector($vectors);
    }

    /**
     * Xarid tarixi imzosi — yangi xarid qilinganda o'zgaradi, shu orqali
     * `user_book_match_reasons` keshi qo'lda invalidatsiya qilinmasdan
     * o'zi eskiradi.
     */
    public function purchaseContextHash(int $userId): string
    {
        $ids = $this->recentPurchases($userId)
            ->map(fn (array $p) => $p['type'] . ':' . $p['id'])
            ->sort()
            ->values()
            ->all();

        return md5(implode('|', $ids));
    }

    /**
     * Foydalanuvchi bu aniq mahsulotni allaqachon sotib olganmi — Holat D2
     * ("bu kitobni allaqachon sotib olgansiz") uchun.
     *
     * Bitta keshlangan indeksdan O(1) qidiradi — har chaqiruvda butun
     * buyurtmalar tarixini qayta skanerlamaydi.
     */
    public function purchasedAt(int $userId, string $type, int $id): ?Carbon
    {
        $index = $this->purchaseIndex($userId);
        $timestamp = $index[$type . ':' . $id] ?? null;

        return $timestamp ? Carbon::parse($timestamp) : null;
    }

    /**
     * Foydalanuvchi shu KATEGORIYADA oldin buyurtmani bekor qilganmi —
     * Holat D1 ("did signali salbiy") uchun taxminiy signal. Granular
     * reyting/sharh tizimi bo'lmagani uchun eng ishonchli mavjud proksi —
     * shu kategoriyadagi bekor qilingan buyurtma.
     */
    public function hasNegativeSignalForCategory(int $userId, ?int $categoryId, string $type): bool
    {
        if (! $categoryId) {
            return false;
        }

        $cacheKey = "reading-intel:neg-signal:{$userId}:{$type}:{$categoryId}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($userId, $categoryId, $type) {
            $cancelled = Sold::query()
                ->where('user_id', $userId)
                ->where('status', 'F')
                ->orderByDesc('created_at')
                ->limit(30)
                ->get(['items']);

            $productIds = [];
            foreach ($cancelled as $order) {
                foreach ((array) $order->items as $item) {
                    $itemType = ($item['type'] ?? 'book') === 'stationery' ? 'stationery' : 'book';
                    if ($itemType !== $type) {
                        continue;
                    }
                    $productId = (int) ($item['item_id'] ?? 0);
                    if ($productId) {
                        $productIds[] = $productId;
                    }
                }
            }

            if (empty($productIds)) {
                return false;
            }

            // MUHIM: har bir mahsulot uchun alohida so'rov o'rniga —
            // bitta `whereIn` + `exists()`.
            $query = $type === 'book' ? Books::query() : Stationery::query();

            return $query
                ->whereIn('id', array_unique($productIds))
                ->where('category_id', $categoryId)
                ->exists();
        });
    }

    /**
     * Kollaborativ (S2) klasterlash uchun foydalanuvchining eng ko'p xarid
     * qilingan kategoriyasi.
     */
    public function dominantCategoryId(int $userId, string $type): ?int
    {
        $counts = [];
        foreach ($this->recentPurchases($userId) as $purchase) {
            if ($purchase['type'] !== $type) {
                continue;
            }
            $categoryId = $purchase['category_id'];
            if (! $categoryId) {
                continue;
            }
            $counts[$categoryId] = ($counts[$categoryId] ?? 0) + 1;
        }

        if (empty($counts)) {
            return null;
        }

        arsort($counts);

        return (int) array_key_first($counts);
    }

    /**
     * Did-vektor va kategoriya statistikasi uchun — oxirgi 20 ta buyurtma,
     * 1 soatga keshlangan. Bitta so'rov + ikkita batch kategoriya lookup.
     *
     * @return \Illuminate\Support\Collection<int, array{type:string,id:int,category_id:?int}>
     */
    private function recentPurchases(int $userId): \Illuminate\Support\Collection
    {
        $cacheKey = "reading-intel:purchases:{$userId}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($userId) {
            $orders = Sold::query()
                ->where('user_id', $userId)
                ->whereNotNull('completed_at')
                ->where('completed_at', '>=', now()->subDays(self::LOOKBACK_DAYS))
                ->orderByDesc('completed_at')
                ->limit(self::MAX_ORDERS_FOR_VECTOR)
                ->get(['items']);

            $bookIds = [];
            $stationeryIds = [];
            $flat = collect();

            foreach ($orders as $order) {
                foreach ((array) $order->items as $item) {
                    $type = ($item['type'] ?? 'book') === 'stationery' ? 'stationery' : 'book';
                    $id = (int) ($item['item_id'] ?? 0);
                    if (! $id) {
                        continue;
                    }
                    if ($type === 'book') {
                        $bookIds[] = $id;
                    } else {
                        $stationeryIds[] = $id;
                    }
                    $flat->push(['type' => $type, 'id' => $id]);
                }
            }

            $bookCategories = $bookIds
                ? Books::query()->whereIn('id', array_unique($bookIds))->pluck('category_id', 'id')
                : collect();
            $stationeryCategories = $stationeryIds
                ? Stationery::query()->whereIn('id', array_unique($stationeryIds))->pluck('category_id', 'id')
                : collect();

            return $flat->map(function (array $p) use ($bookCategories, $stationeryCategories) {
                $p['category_id'] = $p['type'] === 'book'
                    ? ($bookCategories[$p['id']] ?? null)
                    : ($stationeryCategories[$p['id']] ?? null);

                return $p;
            })->values();
        });
    }

    /**
     * "type:id" → oxirgi sotib olingan sana (ISO string) — Holat D2 uchun.
     * MAX_ORDERS_FOR_PURCHASE_INDEX bilan chegaralangan, 1 soatga keshlangan.
     * Foydalanuvchi juda ko'p buyurtma qilgan bo'lsa, eng ESKI xaridlar
     * indeksga kirmasligi mumkin — bu load xavfsizligi uchun ongli
     * murosaga kelish (chegarasiz skanerlash o'rniga).
     *
     * @return array<string, string>
     */
    private function purchaseIndex(int $userId): array
    {
        $cacheKey = "reading-intel:purchase-index:{$userId}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($userId) {
            $orders = Sold::query()
                ->where('user_id', $userId)
                ->whereNotNull('completed_at')
                ->orderByDesc('completed_at')
                ->limit(self::MAX_ORDERS_FOR_PURCHASE_INDEX)
                ->get(['items', 'completed_at']);

            $index = [];
            foreach ($orders as $order) {
                foreach ((array) $order->items as $item) {
                    $itemType = ($item['type'] ?? 'book') === 'stationery' ? 'stationery' : 'book';
                    $itemId = (int) ($item['item_id'] ?? 0);
                    if (! $itemId) {
                        continue;
                    }
                    $key = $itemType . ':' . $itemId;
                    // orderByDesc bo'lgani uchun birinchi uchraganida eng
                    // yangi sana — qayta yozmaymiz.
                    if (! isset($index[$key])) {
                        $index[$key] = $order->completed_at->toIso8601String();
                    }
                }
            }

            return $index;
        });
    }

    /**
     * @param array<int, array<int, float>> $vectors
     */
    private function averageVector(array $vectors): array
    {
        $dim = count($vectors[0]);
        $sum = array_fill(0, $dim, 0.0);

        foreach ($vectors as $vec) {
            for ($i = 0; $i < $dim; $i++) {
                $sum[$i] += $vec[$i] ?? 0.0;
            }
        }

        $count = count($vectors);

        return array_map(fn (float $v) => $v / $count, $sum);
    }
}
