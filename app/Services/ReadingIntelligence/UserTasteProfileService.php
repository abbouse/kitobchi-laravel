<?php

namespace App\Services\ReadingIntelligence;

use App\Models\Books;
use App\Models\FavouriteProducts;
use App\Models\MyCart;
use App\Models\Sold;
use App\Models\Stationery;
use App\Models\UserInterestSelection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Foydalanuvchining "did vektori"ni va xarid tarixiga bog'liq signallarni
 * hisoblaydi — S1 (did moslik) va D1/D2 holatlari uchun asos.
 *
 * Did vektori endi TO'RT signalning VAZNLI aralashmasi: sotib olingan (eng
 * ishonchli — pul to'langan, aniq qaror), savatga qo'shilgan (ikkilanishi
 * mumkin, lekin mavzu/janrga qiziqishni bildiradi), sevimlilarga qo'shilgan
 * (eng yumshoq REAL mahsulot signali — ko'pincha "keyinroq ko'raman" yoki
 * hatto sovg'a rejasi), va onboarding'da qo'lda tanlangan qiziqish
 * kategoriyalari (eng yumshoq umuman — shunchaki e'lon qilingan istak,
 * xatti-harakat emas; asosan sovuq-start uchun). Bitta mahsulot bir nechta
 * manbada bo'lsa (masalan ham sevimlida, ham savatda), ikki marta
 * hisoblanmaydi — eng yuqori vazn olinadi.
 *
 * MUHIM (yuklama xavfsizligi): bu servisning har bir metodi item sahifasi
 * OCHILGANDA (ya'ni juda tez-tez) chaqiriladi. Shuning uchun bu yerda:
 *   - hech qanday cheklovsiz (`LIMIT`siz) so'rov YO'Q,
 *   - hech qanday keshlanmagan foydalanuvchi-darajali so'rov YO'Q,
 *   - hech qanday N+1 (loop ichida bitta-bitta so'rov) YO'Q — barcha
 *     mahsulot ma'lumotlari `whereIn` bilan bitta so'rovda olinadi (uch
 *     manbadan yig'ilgan idlar ham bitta umumiy so'rov juftligiga qo'shiladi,
 *     manba boshiga alohida so'rov EMAS).
 *
 * Hech qanday yangi AI chaqiruvi yo'q — faqat mavjud `vectorData` (product
 * embedding, ProductVectorService orqali allaqachon hisoblangan) va
 * `solds`/`my_carts`/`favourite_products` jadvallaridan qurilgan oddiy
 * statistik agregatsiya.
 */
class UserTasteProfileService
{
    private const LOOKBACK_DAYS = 365;
    private const MAX_ORDERS_FOR_VECTOR = 20;
    private const CACHE_TTL = 3600;

    /** "Allaqachon sotib olinganmi" indeksi uchun — kengroq, lekin baribir CHEGARALANGAN tarix. */
    private const MAX_ORDERS_FOR_PURCHASE_INDEX = 300;

    /** Savat/sevimlilar — eng oxirgi shuncha yozuv, cheksiz skanerlash yo'q. */
    private const MAX_CART_ITEMS = 50;
    private const MAX_FAVORITES = 50;

    /**
     * Savat uchun ATAYLAB qisqaroq TTL: `ReadingIntelTasteCacheObserver`
     * qo'shish/`$model->delete()` orqali o'chirishda keshni darhol
     * tozalaydi, LEKIN `CartController::remove()`/`batchDelete()` kabi
     * ba'zi o'chirish yo'llari query builder (`MyCart::where(...)->delete()`)
     * orqali ishlaydi — bular Eloquent event otmaydi, observer buni
     * ushlolmaydi. Shuning uchun savat uchun umumiy 1 soat o'rniga 10
     * daqiqalik TTL — bo'shliq chegaralangan, "sevimlilar" kabi to'liq
     * observer-qamrovi yo'q.
     */
    private const CART_CACHE_TTL = 600;

    /** Signal ishonch vazni — sotib olish eng kuchli, sevimli eng yumshoq. */
    private const PURCHASE_WEIGHT = 1.0;
    private const CART_WEIGHT = 0.6;
    private const FAVORITE_WEIGHT = 0.4;

    /**
     * Onboarding'da qo'lda tanlangan qiziqish (kategoriya) — bu XATTI-
     * HARAKAT emas, shunchaki e'lon qilingan istak, shuning uchun eng past
     * vazn. Faqat sovuq-start (xarid/savat/sevimli hali yo'q) holatida
     * asosiy rol o'ynaydi.
     */
    private const INTEREST_WEIGHT = 0.25;

    /** Har bir tanlangan kategoriya uchun "markaz vektor" — shu kategoriyada eng ko'p sotilgan N ta kitobning o'rtachasi. */
    private const CATEGORY_CENTROID_SIZE = 8;
    private const CATEGORY_CENTROID_TTL = 21600; // 6 soat — kategoriya bestsellerlari tez o'zgarmaydi

    /**
     * Ishonchli vektor qurish uchun kamida shuncha DISTINCT mahsulot kerak
     * (manbasidan qat'i nazar) — bitta xarid/sevimli asosida haddan tashqari
     * tor vektor qurilib ketmasligi uchun.
     */
    private const MIN_SIGNAL_PRODUCTS = 3;

    /**
     * Foydalanuvchining xarid+savat+sevimli+e'lon qilingan qiziqishlaridan
     * qurilgan VAZNLI o'rtacha embedding. Kamida MIN_SIGNAL_PRODUCTS ta
     * signal (manbasidan qat'i nazar — real mahsulot yoki qiziqish
     * kategoriyasi) bo'lmasa null — bu holda S1 "mavjud emas" deb
     * hisoblanadi (Holat C/D ga tushadi).
     *
     * MUHIM: onboarding'da tanlangan qiziqishlar TUFAYLI, hali birorta ham
     * xarid/savat/sevimlisi yo'q YANGI mijoz ham (kamida 3 ta kategoriya
     * tanlagan bo'lsa) shaxsiylashtirilgan moslikni ko'rishi mumkin — bu
     * sovuq-start muammosini yumshatish uchun ATAYLAB qilingan.
     */
    public function tasteVector(int $userId, string $excludeType, int $excludeId): ?array
    {
        // "type:id" => ['type'=>..,'id'=>..,'weight'=>..] — bitta mahsulot
        // bir nechta manbada bo'lsa, eng yuqori vazn saqlanadi (ikki marta
        // hisoblanmaydi).
        $weighted = [];
        $accumulate = function (array $items, float $weight) use (&$weighted, $excludeType, $excludeId) {
            foreach ($items as $it) {
                if ($it['type'] === $excludeType && $it['id'] === $excludeId) {
                    continue;
                }
                $key = $it['type'] . ':' . $it['id'];
                $current = $weighted[$key]['weight'] ?? 0.0;
                $weighted[$key] = ['type' => $it['type'], 'id' => $it['id'], 'weight' => max($current, $weight)];
            }
        };

        $accumulate($this->recentPurchases($userId)->all(), self::PURCHASE_WEIGHT);
        $accumulate($this->cartItems($userId)->all(), self::CART_WEIGHT);
        $accumulate($this->favoriteItems($userId)->all(), self::FAVORITE_WEIGHT);

        $bookIds = collect($weighted)->where('type', 'book')->pluck('id')->unique()->values()->all();
        $stationeryIds = collect($weighted)->where('type', 'stationery')->pluck('id')->unique()->values()->all();

        // MUHIM: bitta-bitta emas — ikkita `whereIn` so'rov, manbalar soni
        // (xarid/savat/sevimli) qancha bo'lishidan qat'i nazar.
        $bookVectors = ! empty($bookIds)
            ? Books::query()->whereIn('id', $bookIds)->whereNotNull('vectorData')->pluck('vectorData', 'id')
            : collect();
        $stationeryVectors = ! empty($stationeryIds)
            ? Stationery::query()->whereIn('id', $stationeryIds)->whereNotNull('vectorData')->pluck('vectorData', 'id')
            : collect();

        $vectors = [];
        $weights = [];
        foreach ($weighted as $w) {
            $vec = $w['type'] === 'book'
                ? ($bookVectors[$w['id']] ?? null)
                : ($stationeryVectors[$w['id']] ?? null);

            if (is_array($vec) && ! empty($vec)) {
                $vectors[] = $vec;
                $weights[] = $w['weight'];
            }
        }

        // Onboarding'dagi e'lon qilingan qiziqishlar — har bir tanlangan
        // kategoriya uchun "markaz vektor" (shu kategoriyada eng ko'p
        // sotilgan kitoblarning o'rtachasi), eng past vaznda qo'shiladi.
        foreach ($this->selectedInterestCategoryIds($userId) as $categoryId) {
            $centroid = $this->categoryCentroidVector($categoryId);
            if ($centroid !== null) {
                $vectors[] = $centroid;
                $weights[] = self::INTEREST_WEIGHT;
            }
        }

        if (count($vectors) < self::MIN_SIGNAL_PRODUCTS) {
            return null;
        }

        return $this->weightedAverageVector($vectors, $weights);
    }

    /**
     * Did-kontekst imzosi — xarid, savat yoki sevimlilar o'zgarganda
     * o'zgaradi, shu orqali `user_book_match_reasons` keshi qo'lda
     * invalidatsiya qilinmasdan o'zi eskiradi. Nomi tarixiy sabablarga ko'ra
     * "purchase" bo'lsa-da (DB ustuni shunday), endi UCHALA signalni ham
     * qamrab oladi — chunki moslik hisobi endi shularga bog'liq.
     */
    public function purchaseContextHash(int $userId): string
    {
        $purchaseIds = $this->recentPurchases($userId)->map(fn (array $p) => $p['type'] . ':' . $p['id']);
        $cartIds = $this->cartItems($userId)->map(fn (array $c) => 'cart:' . $c['type'] . ':' . $c['id']);
        $favoriteIds = $this->favoriteItems($userId)->map(fn (array $f) => 'fav:' . $f['type'] . ':' . $f['id']);
        $interestIds = collect($this->selectedInterestCategoryIds($userId))->map(fn (int $id) => 'interest:' . $id);

        $ids = $purchaseIds->concat($cartIds)->concat($favoriteIds)->concat($interestIds)->sort()->values()->all();

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
     * Foydalanuvchi umuman qaysi TURDAN (kitob yoki kanselyariya) ko'proq
     * xarid qilgan — push-tavsiya (`RecommendBooksPush`) kabi "qaysi
     * turdan tavsiya berish kerak" degan savolga javob berish uchun.
     * Xarid tarixi umuman yo'q bo'lsa — `null` (chaqiruvchi o'zi standart
     * turni, masalan 'book', tanlaydi). Teng bo'lsa — 'book' ustuvor
     * (ilova asosan kitob-markazli).
     */
    public function dominantProductType(int $userId): ?string
    {
        $counts = ['book' => 0, 'stationery' => 0];
        foreach ($this->recentPurchases($userId) as $purchase) {
            $counts[$purchase['type']] = ($counts[$purchase['type']] ?? 0) + 1;
        }

        if ($counts['book'] === 0 && $counts['stationery'] === 0) {
            return null;
        }

        return $counts['stationery'] > $counts['book'] ? 'stationery' : 'book';
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
     * Foydalanuvchining joriy savatidagi mahsulotlar — eng oxirgi
     * MAX_CART_ITEMS ta, 1 soatga keshlangan. `MyCart` "hozirgi holat"
     * jadvali (savatdan o'chirilgan qator butunlay o'chadi), shuning uchun
     * qo'shimcha filtrlash shart emas — mavjud qator = hozir ham qiziqadi.
     *
     * @return \Illuminate\Support\Collection<int, array{type:string,id:int}>
     */
    private function cartItems(int $userId): \Illuminate\Support\Collection
    {
        $cacheKey = "reading-intel:cart:{$userId}";

        return Cache::remember($cacheKey, self::CART_CACHE_TTL, function () use ($userId) {
            return MyCart::query()
                ->where('user_id', $userId)
                ->orderByDesc('id')
                ->limit(self::MAX_CART_ITEMS)
                ->get(['product_id', 'product_type'])
                ->map(fn ($row) => [
                    'type' => $row->product_type === 'stationery' ? 'stationery' : 'book',
                    'id' => (int) $row->product_id,
                ])
                ->filter(fn (array $x) => $x['id'] > 0)
                ->values();
        });
    }

    /**
     * Foydalanuvchining sevimlilar ro'yxati — eng oxirgi MAX_FAVORITES ta,
     * 1 soatga keshlangan. Xuddi savat kabi "hozirgi holat" jadvali.
     *
     * @return \Illuminate\Support\Collection<int, array{type:string,id:int}>
     */
    private function favoriteItems(int $userId): \Illuminate\Support\Collection
    {
        $cacheKey = "reading-intel:favorites:{$userId}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($userId) {
            return FavouriteProducts::query()
                ->where('user_id', $userId)
                ->orderByDesc('id')
                ->limit(self::MAX_FAVORITES)
                ->get(['product_id', 'product_type'])
                ->map(fn ($row) => [
                    'type' => $row->product_type === 'stationery' ? 'stationery' : 'book',
                    'id' => (int) $row->product_id,
                ])
                ->filter(fn (array $x) => $x['id'] > 0)
                ->values();
        });
    }

    /**
     * Foydalanuvchi allaqachon sotib olgan mahsulot idlari (bitta turdan) —
     * push-tavsiya (`RecommendBooksPush`) kabi joylarda, tavsiya qilinayotgan
     * mahsulot ro'yxatidan allaqachon sotib olinganlarni chiqarib tashlash
     * uchun. Mavjud (keshlangan) `purchaseIndex()` dan qayta foydalanadi —
     * yangi so'rov qo'shmaydi.
     *
     * @return array<int, int>
     */
    public function purchasedProductIds(int $userId, string $type): array
    {
        $ids = [];
        foreach ($this->purchaseIndex($userId) as $key => $timestamp) {
            [$itemType, $itemId] = explode(':', $key, 2);
            if ($itemType === $type) {
                $ids[] = (int) $itemId;
            }
        }

        return $ids;
    }

    /**
     * Foydalanuvchida kamida bitta TO'LANGAN va yetkazilgan (`completed_at`
     * bor) buyurtma bormi — sovuq-start (hali xarid tarixi yo'q)
     * foydalanuvchilarni aniqlash uchun. Bitta yengil `exists()` so'rovi,
     * qisqa (5 daqiqa) keshlangan — item sahifasi ochilganda tez-tez
     * chaqirilishi mumkin, shuning uchun har safar to'liq tarix skanerlash
     * shart emas.
     */
    public function hasAnyPurchases(int $userId): bool
    {
        $cacheKey = "reading-intel:has-purchases:{$userId}";

        return Cache::remember($cacheKey, 300, function () use ($userId) {
            return Sold::query()
                ->where('user_id', $userId)
                ->whereNotNull('completed_at')
                ->exists();
        });
    }

    /**
     * Foydalanuvchi onboarding'da tanlagan kategoriya idlari — 1 soatga
     * keshlangan (`UserController::saveInterests()` saqlaganda darhol
     * tozalaydi, shuning uchun kutish shart emas).
     *
     * MUHIM: bu metod ATAYLAB public — `ReadingIntelligenceService` sotib
     * olish tarixi yo'q foydalanuvchilar uchun onboarding qiziqishlarini
     * to'g'ridan-to'g'ri (vektor orqali emas) solishtirish uchun ham
     * ishlatadi (`interestMatchPayload`).
     *
     * @return array<int, int>
     */
    public function selectedInterestCategoryIds(int $userId): array
    {
        $cacheKey = "reading-intel:interests:{$userId}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($userId) {
            return UserInterestSelection::query()
                ->where('user_id', $userId)
                ->pluck('category_id')
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();
        });
    }

    /**
     * Bitta kategoriya uchun "markaz vektor" — shu kategoriyada eng ko'p
     * sotilgan CATEGORY_CENTROID_SIZE ta (faol/tasdiqlangan) kitobning
     * o'rtacha embeddingi. 6 soatga keshlangan (kategoriya bo'yicha,
     * foydalanuvchidan mustaqil — bir marta hisoblansa, ko'p foydalanuvchi
     * uchun qayta ishlatiladi, N+1 emas).
     */
    private function categoryCentroidVector(int $categoryId): ?array
    {
        $cacheKey = "reading-intel:category-centroid:{$categoryId}";

        return Cache::remember($cacheKey, self::CATEGORY_CENTROID_TTL, function () use ($categoryId) {
            $vectors = Books::query()
                ->activeForVector()
                ->where('category_id', $categoryId)
                ->whereNotNull('vectorData')
                ->orderByDesc('totalSales')
                ->limit(self::CATEGORY_CENTROID_SIZE)
                ->pluck('vectorData')
                ->filter(fn ($v) => is_array($v) && ! empty($v))
                ->values()
                ->all();

            if (empty($vectors)) {
                return null;
            }

            return $this->weightedAverageVector($vectors, array_fill(0, count($vectors), 1.0));
        });
    }

    /**
     * @param array<int, array<int, float>> $vectors
     * @param array<int, float> $weights
     */
    private function weightedAverageVector(array $vectors, array $weights): array
    {
        $dim = count($vectors[0]);
        $sum = array_fill(0, $dim, 0.0);
        $totalWeight = 0.0;

        foreach ($vectors as $idx => $vec) {
            $weight = $weights[$idx] ?? 1.0;
            $totalWeight += $weight;
            for ($i = 0; $i < $dim; $i++) {
                $sum[$i] += ($vec[$i] ?? 0.0) * $weight;
            }
        }

        if ($totalWeight <= 0.0) {
            $totalWeight = count($vectors);
        }

        return array_map(fn (float $v) => $v / $totalWeight, $sum);
    }
}
