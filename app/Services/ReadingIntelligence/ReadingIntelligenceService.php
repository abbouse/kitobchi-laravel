<?php

namespace App\Services\ReadingIntelligence;

use App\Models\BookReadingInsight;
use App\Models\Books;
use App\Models\Sold;
use App\Models\Stationery;
use App\Models\User;
use App\Models\UserBookMatchReason;
use App\Services\OpenAIService;
use App\Services\VectorSearchService;
use Illuminate\Support\Facades\Cache;

/**
 * "Reading Intelligence" — item sahifasidagi "bu menga mosmi?" kartochkasi
 * uchun asosiy orkestrator. docs/reading-intelligence-card-logic.md dagi
 * qaror daraxtini (Holat A/B/C/D1/D2/E/F/G) amalga oshiradi.
 *
 * Ustuvorlik tartibi (birinchi mos kelgani ishlatiladi):
 *   1. D2 — mahsulot allaqachon sotib olingan          → 'reminder'
 *   2. D1 — shu kategoriyada salbiy tarix bor           → 'neutral_fact'
 *   3. S1 kuchli (>= taste_strong_threshold)            → 'match'
 *   4. S1 zaif ijobiy (taste_soft..strong oralig'i)     → 'soft_hint'
 *   5. S2 kollaborativ kuchli                           → 'discovery'
 *   6. S3 universal sifat yetarli                       → 'quality'
 *   7. Hech narsa yo'q                                  → 'new'
 *
 * Karta HECH QACHON butunlay yashirilmaydi — faqat ishonch darajasi
 * pasayganda shaxsiylashtirishdan faktik kontentga tushadi.
 */
class ReadingIntelligenceService
{
    public function __construct(
        private readonly OpenAIService $ai,
        private readonly VectorSearchService $vectorSearch,
        private readonly UserTasteProfileService $tasteProfile,
        private readonly ReadingInsightGenerator $insightGenerator,
    ) {
    }

    public function forProduct(string $type, int $id, ?User $user, string $locale): ?array
    {
        $product = $this->loadProduct($type, $id);
        if (! $product) {
            return null;
        }

        $categoryId = $product->category_id ? (int) $product->category_id : null;

        // ── D2: allaqachon sotib olingan ────────────────────────────────
        // MUHIM: bu tekshiruv insight (AI kontent) generatsiyasidan OLDIN —
        // "allaqachon sotib olingan" holatda mahsulot tavsifi kerak emas,
        // shuning uchun bu yo'lda hech qachon keraksiz AI chaqiruvi bo'lmaydi.
        if ($user) {
            $purchasedAt = $this->tasteProfile->purchasedAt($user->id, $type, $id);
            if ($purchasedAt) {
                return $this->reminderPayload($purchasedAt, $locale);
            }
        }

        $insight = $this->insightGenerator->get($type, $id);

        // ── D1: shu kategoriyada salbiy tarix ───────────────────────────
        if ($user && $categoryId && $this->tasteProfile->hasNegativeSignalForCategory($user->id, $categoryId, $type)) {
            return $this->neutralFactPayload($product, $type, $insight, $locale);
        }

        // ── S1: did moslik ───────────────────────────────────────────────
        if ($user && is_array($product->vectorData) && ! empty($product->vectorData)) {
            $tasteVector = $this->tasteProfile->tasteVector($user->id, $type, $id);

            if ($tasteVector !== null) {
                $score = $this->ai->calculateSimilarity($tasteVector, $product->vectorData);
                $strong = (float) config('reading_intelligence.taste_strong_threshold');
                $soft = (float) config('reading_intelligence.taste_soft_threshold');

                if ($score >= $strong) {
                    return $this->matchPayload($user, $product, $type, $score, $locale);
                }

                if ($score >= $soft) {
                    return $this->softHintPayload($product, $insight, $score, $locale);
                }
            }
        }

        // ── S2: kollaborativ signal (boshqa janr, kashfiyot) ────────────
        if ($user && $categoryId) {
            $collaborative = $this->collaborativeAffinity($user->id, $categoryId, $type, $id, $locale);
            if ($collaborative !== null) {
                return $this->discoveryPayload($collaborative, $locale);
            }
        }

        // ── S3: universal sifat ──────────────────────────────────────────
        $quality = $this->qualityPayload($product, $type, $locale);
        if ($quality !== null) {
            return $user ? $quality : $this->withGuestCta($quality, $locale);
        }

        // ── Hech narsa yo'q: faqat kontent ──────────────────────────────
        $new = $this->newProductPayload($product, $insight, $locale);

        return $user ? $new : $this->withGuestCta($new, $locale);
    }

    /**
     * Mehmon (tizimga kirmagan) foydalanuvchi uchun: shaxsiy moslik/D1/D2/S2
     * signallari umuman hisoblanmaydi (bular userga bog'liq), shuning uchun
     * mehmon faqat 'quality' yoki 'new' yo'liga tushadi. Bu ikkalasiga ham
     * "tizimga kiring — shaxsiy tavsiya ko'ring" degan qo'shimcha bo'lim
     * qo'shamiz, shu orqali mehmonga NEGA umumiy kontent ko'rsatilayotgani
     * va nima qilsa shaxsiylashtirilgan tajriba olishi tushuntiriladi.
     */
    private function withGuestCta(array $payload, string $locale): array
    {
        $payload['sheet']['guest_cta'] = [
            'label' => __('reading_intelligence.guest_cta_label'),
            'detail' => __('reading_intelligence.guest_cta_detail'),
        ];

        return $payload;
    }

    // ─── Holat A — aniq mos ──────────────────────────────────────────────

    private function matchPayload(User $user, Books|Stationery $product, string $type, float $score, string $locale): array
    {
        $percent = (int) round($score * 100);
        $reason = $this->personalReason($user, $product, $type, $score, $locale);
        $similar = $this->similarSection($product, $type, $locale);

        return [
            'variant' => 'match',
            'teaser' => [
                'icon' => 'sparkles',
                'title' => __('reading_intelligence.teaser_match_title', ['percent' => $percent]),
                'subtitle' => __('reading_intelligence.teaser_match_subtitle'),
            ],
            'sheet' => array_filter([
                'match' => [
                    'label' => __('reading_intelligence.section_match'),
                    'percent' => $percent,
                    'reason' => $reason,
                ],
                'traits' => $this->traitsSection($product, $type, $locale),
                'similar' => $similar,
                // Front-end'da statik kalitlarni qayta tarjima qilmaslik
                // uchun sarlavhani ham shu yerda, tayyor holda yuboramiz.
                'similar_label' => $similar ? __('reading_intelligence.section_similar') : null,
            ]),
        ];
    }

    /**
     * Sabab jumlasi — (foydalanuvchi, mahsulot, til) bo'yicha keshlanadi.
     * `purchase_context_hash` orqali yangi xarid bo'lganda o'zi eskiradi.
     */
    private function personalReason(User $user, Books|Stationery $product, string $type, float $score, string $locale): ?string
    {
        $contextHash = $this->tasteProfile->purchaseContextHash($user->id);

        $cached = UserBookMatchReason::query()
            ->where('user_id', $user->id)
            ->where('product_type', $type)
            ->where('product_id', $product->id)
            ->where('locale', $locale)
            ->first();

        if ($cached && $cached->purchase_context_hash === $contextHash && $cached->reason_text) {
            return $cached->reason_text;
        }

        $reason = $this->generatePersonalReason($product, $type, $locale);

        UserBookMatchReason::updateOrCreate(
            [
                'user_id' => $user->id,
                'product_type' => $type,
                'product_id' => $product->id,
                'locale' => $locale,
            ],
            [
                'match_score' => $score,
                'reason_text' => $reason,
                'purchase_context_hash' => $contextHash,
                'generated_at' => now(),
            ]
        );

        return $reason;
    }

    private function generatePersonalReason(Books|Stationery $product, string $type, string $locale): ?string
    {
        $localeNames = ['uz' => 'o\'zbek', 'ru' => 'rus', 'en' => 'ingliz', 'ja' => 'yapon'];
        $localeName = $localeNames[$locale] ?? 'o\'zbek';

        $facts = [
            'nomi' => $product->name,
            'muallif' => $type === 'book' ? ($product->author ?? null) : null,
            'kategoriya' => $this->categoryName($product->category, $locale),
        ];
        $factsText = collect($facts)->filter()->map(fn ($v, $k) => "{$k}: {$v}")->implode(', ');

        $prompt = "Quyidagi mahsulot uchun, foydalanuvchining oldingi xaridlariga o'xshashligi asosida, "
            . "NEGA mos kelishini tushuntiruvchi BITTA qisqa jumla yoz (10-15 so'z, {$localeName} tilida). "
            . "Umumiy gap emas — mahsulot xususiyatiga konkret ishora qil.\n\nMahsulot: {$factsText}\n\n"
            . 'FAQAT JSON qaytar: {"reason": "..."}';

        $result = $this->ai->askJson($prompt, maxTokens: 150, temperature: 0.5);
        $reason = $result['reason'] ?? null;

        return (is_string($reason) && trim($reason) !== '') ? trim($reason) : null;
    }

    // ─── Holat B — boshqa janr, kashfiyot (kollaborativ) ────────────────

    /**
     * V1 amalga oshirish: oxirgi 2000 ta tugallangan buyurtma ichida shu
     * kategoriyadan mahsulot sotib olgan foydalanuvchilarni sample qilib,
     * ular ichida aynan shu mahsulotni ham olganlar ulushini hisoblaydi.
     *
     * Natija 1 soatga keshlanadi (config: collaborative_cache_ttl), shuning
     * uchun har so'rovda emas — kamdan-kam qayta hisoblanadi.
     *
     * KEYINGI QADAM (masshtab kattalashganda): buni har kunlik cron orqali
     * oldindan hisoblab, alohida jadvalga (masalan
     * `category_product_affinity`) yozib qo'yish kerak bo'ladi — `solds.items`
     * JSON ustunini har safar skanerlash 2000 chegarasida hozircha yetarli,
     * lekin doimiy yechim emas.
     */
    private function collaborativeAffinity(int $userId, int $categoryId, string $type, int $productId, string $locale): ?array
    {
        $minSample = (int) config('reading_intelligence.collaborative_min_sample_size');
        $minRate = (float) config('reading_intelligence.collaborative_min_positive_rate');
        $ttl = (int) config('reading_intelligence.collaborative_cache_ttl');

        $cacheKey = "reading-intel:collab:{$type}:{$productId}:cat:{$categoryId}";

        $stat = Cache::remember($cacheKey, $ttl, function () use ($categoryId, $type, $productId) {
            $categoryProductIds = ($type === 'book' ? Books::query() : Stationery::query())
                ->where('category_id', $categoryId)
                ->pluck('id')
                ->all();

            if (empty($categoryProductIds)) {
                return null;
            }

            $categoryProductIds = array_flip($categoryProductIds);

            $orders = Sold::query()
                ->whereNotNull('completed_at')
                ->orderByDesc('completed_at')
                ->limit(2000)
                ->get(['user_id', 'items']);

            $categoryBuyers = [];
            $alsoBoughtProduct = [];

            foreach ($orders as $order) {
                $boughtCategory = false;
                $boughtProduct = false;

                foreach ((array) $order->items as $item) {
                    $itemType = ($item['type'] ?? 'book') === 'stationery' ? 'stationery' : 'book';
                    if ($itemType !== $type) {
                        continue;
                    }
                    $itemId = (int) ($item['item_id'] ?? 0);
                    if (isset($categoryProductIds[$itemId])) {
                        $boughtCategory = true;
                    }
                    if ($itemId === $productId) {
                        $boughtProduct = true;
                    }
                }

                if ($boughtCategory) {
                    $categoryBuyers[$order->user_id] = true;
                    if ($boughtProduct) {
                        $alsoBoughtProduct[$order->user_id] = true;
                    }
                }
            }

            $sampleSize = count($categoryBuyers);
            if ($sampleSize === 0) {
                return null;
            }

            return [
                'rate' => count($alsoBoughtProduct) / $sampleSize,
                'sample_size' => $sampleSize,
            ];
        });

        if (! $stat || $stat['sample_size'] < $minSample || $stat['rate'] < $minRate) {
            return null;
        }

        $category = ($type === 'book' ? Books::query() : Stationery::query())
            ->whereKey($productId)
            ->with('category')
            ->first()
            ?->category;

        $stat['cluster_label'] = $this->categoryName($category, $locale) ?? '';

        return $stat;
    }

    /**
     * Kategoriya nomini so'ralgan tilda qaytaradi (BookCategories/
     * StationeryCategory da `name_uz`, `name_ru`, `name_en`, `name_ja`
     * ustunlari bor). Til qiymati topilmasa 'uz' ga tushadi — hech qachon
     * bo'sh qaytmaydi.
     */
    private function categoryName(?object $category, string $locale): ?string
    {
        if (! $category) {
            return null;
        }

        $field = 'name_' . $locale;
        $value = $category->{$field} ?? $category->name_uz ?? null;

        return $value !== null ? (string) $value : null;
    }

    private function discoveryPayload(array $collaborative, string $locale): array
    {
        $percent = (int) round(($collaborative['rate'] ?? 0) * 100);
        $clusterLabel = $collaborative['cluster_label'] ?? '';

        return [
            'variant' => 'discovery',
            'teaser' => [
                'icon' => 'compass',
                'title' => __('reading_intelligence.teaser_discovery_title'),
                'subtitle' => __('reading_intelligence.teaser_discovery_subtitle', [
                    'percent' => $percent,
                    'cluster' => $clusterLabel,
                ]),
            ],
            'sheet' => [
                'discovery' => [
                    'label' => __('reading_intelligence.teaser_discovery_title'),
                    'detail' => __('reading_intelligence.teaser_discovery_subtitle', [
                        'percent' => $percent,
                        'cluster' => $clusterLabel,
                    ]),
                ],
            ],
        ];
    }

    // ─── Holat G — yumshoq ishora ─────────────────────────────────────────

    private function softHintPayload(Books|Stationery $product, ?BookReadingInsight $insight, float $score, string $locale): array
    {
        $hint = $insight?->localizedReviewSynthesis($locale) ?? $insight?->localizedAudienceFit($locale);
        $type = is_a($product, Books::class) ? 'book' : 'stationery';
        $similar = $this->similarSection($product, $type, $locale);

        return [
            'variant' => 'soft_hint',
            'teaser' => [
                'icon' => 'message-circle',
                'title' => $hint ?? __('reading_intelligence.teaser_soft_title'),
                'subtitle' => __('reading_intelligence.teaser_soft_title'),
            ],
            'sheet' => array_filter([
                'traits' => $this->traitsSection($product, $type, $locale),
                'similar' => $similar,
                'similar_label' => $similar ? __('reading_intelligence.section_similar') : null,
            ]),
        ];
    }

    // ─── Holat C/E — faqat universal sifat ─────────────────────────────

    private function qualityPayload(Books|Stationery $product, string $type, string $locale): ?array
    {
        $minReviews = (int) config('reading_intelligence.min_reviews_for_quality');
        $reviewsCount = (int) ($product->ugc_reviews_count ?? 0);
        $sales = (int) ($product->totalSales ?? 0);

        if ($reviewsCount < $minReviews && $sales < 5) {
            return null;
        }

        $rank = $this->categoryRank($product, $type);
        if ($rank === null) {
            return null;
        }

        return [
            'variant' => 'quality',
            'teaser' => [
                'icon' => 'trending-up',
                'title' => __('reading_intelligence.teaser_quality_title', ['rank' => $rank]),
                'subtitle' => __('reading_intelligence.teaser_quality_subtitle'),
            ],
            'sheet' => [
                'quality' => [
                    'label' => __('reading_intelligence.teaser_quality_title', ['rank' => $rank]),
                    'detail' => __('reading_intelligence.bestseller_label'),
                ],
            ],
        ];
    }

    private function categoryRank(Books|Stationery $product, string $type): ?int
    {
        if (! $product->category_id) {
            return null;
        }

        $topN = (int) config('reading_intelligence.rank_top_n');
        $ttl = (int) config('reading_intelligence.rank_cache_ttl');
        $cacheKey = "reading-intel:rank:{$type}:{$product->category_id}";

        $topIds = Cache::remember($cacheKey, $ttl, function () use ($product, $type, $topN) {
            // activeForVector() — status/is_approved/is_hidden/faol sotuvchi
            // shartlarini birlashtiradi (Books.php/Stationery.php da bir xil
            // ta'riflangan), shu orqali yashirin/tasdiqlanmagan mahsulotlar
            // reyting ro'yxatiga tushmaydi.
            $query = $type === 'book' ? Books::query() : Stationery::query();

            return $query
                ->activeForVector()
                ->where('category_id', $product->category_id)
                ->orderByDesc('totalSales')
                ->limit($topN)
                ->pluck('id')
                ->all();
        });

        $position = array_search($product->id, $topIds, true);

        return $position === false ? null : ($position + 1 > 5 ? $topN : $position + 1);
    }

    // ─── Holat F — yangi mahsulot (faqat kontent) ──────────────────────

    private function newProductPayload(Books|Stationery $product, ?BookReadingInsight $insight, string $locale): array
    {
        return [
            'variant' => 'new',
            'teaser' => [
                'icon' => 'tag',
                'title' => __('reading_intelligence.teaser_new_title'),
                'subtitle' => $this->difficultySummary($product, $insight, $locale) ?? __('reading_intelligence.teaser_generic_subtitle'),
            ],
            'sheet' => array_filter([
                'traits' => $this->traitsSection($product, is_a($product, Books::class) ? 'book' : 'stationery', $locale),
            ]),
        ];
    }

    // ─── Holat D1 — did salbiy: neytral fakt kartasi (BO'SH EMAS) ──────

    private function neutralFactPayload(Books|Stationery $product, string $type, ?BookReadingInsight $insight, string $locale): array
    {
        return [
            'variant' => 'neutral_fact',
            'teaser' => [
                'icon' => 'book',
                'title' => $this->productFactLine($product, $type, $locale),
                'subtitle' => $this->difficultySummary($product, $insight, $locale) ?? '',
            ],
            'sheet' => array_filter([
                'traits' => $this->traitsSection($product, $type, $locale),
            ]),
        ];
    }

    // ─── Holat D2 — allaqachon sotib olingan ────────────────────────────

    private function reminderPayload(\Illuminate\Support\Carbon $purchasedAt, string $locale): array
    {
        return [
            'variant' => 'reminder',
            'teaser' => [
                'icon' => 'info-circle',
                'title' => __('reading_intelligence.teaser_reminder_title'),
                'subtitle' => __('reading_intelligence.teaser_reminder_subtitle', [
                    'date' => $purchasedAt->translatedFormat('d.m.Y'),
                ]),
            ],
            'sheet' => [
                'reminder' => [
                    'label' => __('reading_intelligence.section_reminder'),
                    'detail' => __('reading_intelligence.teaser_reminder_subtitle', [
                        'date' => $purchasedAt->translatedFormat('d.m.Y'),
                    ]),
                ],
            ],
        ];
    }

    // ─── Umumiy bo'limlar ────────────────────────────────────────────────

    private function traitsSection(Books|Stationery $product, string $type, string $locale): ?array
    {
        $insight = $this->insightGenerator->get($type, $product->id);
        if (! $insight) {
            return null;
        }

        $moodLabels = collect($insight->mood_tags ?? [])
            ->map(fn (string $key) => __('reading_intelligence.mood_' . $key))
            ->filter()
            ->values()
            ->all();

        return array_filter([
            'difficulty' => $insight->difficulty
                ? __('reading_intelligence.difficulty_' . $insight->difficulty)
                : null,
            'mood' => $moodLabels ?: null,
            'audience_fit' => $insight->localizedAudienceFit($locale),
            'audience_avoid' => $insight->localizedAudienceAvoid($locale),
        ]);
    }

    private function similarSection(Books|Stationery $product, string $type, string $locale): ?array
    {
        if (! is_array($product->vectorData) || empty($product->vectorData)) {
            return null;
        }

        // MUHIM: front-end endi "similar" ro'yxatini gorizontal skroll
        // qatorida ko'rsatadi (qattiq 2-3 ta bilan cheklangan grid emas),
        // shuning uchun bu yerda ham chegarani kengaytiramiz — nechta
        // o'xshash mahsulot bo'lsa, shunchasi (6 tagacha) qaytariladi.
        $results = $this->vectorSearch
            ->searchByVector($product->vectorData, $type, limit: 8, minScore: 0.5, inStockOnly: true)
            ->filter(fn ($item) => (int) $item->id !== (int) $product->id)
            ->take(6);

        if ($results->isEmpty()) {
            return null;
        }

        return $results->map(fn ($item) => [
            'id' => $item->id,
            'name' => $item->name,
            'score' => __('reading_intelligence.similar_score', [
                'percent' => (int) round(($item->_similarity ?? 0) * 100),
            ]),
        ])->values()->all();
    }

    private function difficultySummary(Books|Stationery $product, ?BookReadingInsight $insight, string $locale): ?string
    {
        if (! $insight || ! $insight->difficulty) {
            return null;
        }

        $parts = [__('reading_intelligence.difficulty_' . $insight->difficulty)];
        if ($product instanceof Books && $product->pages) {
            $parts[] = __('reading_intelligence.pages_count', ['count' => $product->pages]);
        }

        return implode(' · ', $parts);
    }

    private function productFactLine(Books|Stationery $product, string $type, string $locale): string
    {
        $parts = array_filter([
            $this->categoryName($product->category, $locale),
            $type === 'book' && $product->pages
                ? __('reading_intelligence.pages_count', ['count' => $product->pages])
                : null,
        ]);

        return implode(' · ', $parts) ?: $product->name;
    }

    private function loadProduct(string $type, int $id): Books|Stationery|null
    {
        return $type === 'book'
            ? Books::query()->with('category')->find($id)
            : Stationery::query()->with('category')->find($id);
    }
}
