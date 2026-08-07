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
 * MUHIM QOIDA (-1-bosqich, HAMMASIDAN OLDIN): bu karta FAQAT KITOB uchun
 * ishlaydi. Kanselyariya (stationery) uchun `forProduct()` darhol `null`
 * qaytaradi — "qiyinlik darajasi"/"kayfiyat" kabi tushunchalar o'qish
 * tajribasiga xos, jismoniy kanselyariya buyumiga mos kelmaydi. AI ham bu
 * tur uchun generatsiya qilmaydi (qarang: `GenerateReadingInsights`).
 *
 * MUHIM QOIDA (0-bosqich): agar AI bu mahsulotni hali
 * tahlil qilmagan bo'lsa (`book_reading_insights`da yozuv yo'q) — karta
 * BUTUNLAY qaytarilmaydi (`null`), Item sahifasida bu bo'lim umuman
 * ko'rinmaydi. Sabab: qiyinlik/kayfiyat/kimlar-uchun tahlili bo'lmasa,
 * qolgan har qanday signal (kashfiyot foizi, reyting, eslatma) yolg'iz
 * (bitta qatorli) va "yarim tayyor" ko'rinadi — bu ishonchni pasaytiradi.
 * Fon jarayoni (`reading-intelligence:generate-insights`, endi har
 * daqiqada ishlaydi) tez orada barcha mahsulotlarga yetib boradi, shunda
 * karta to'liq holda paydo bo'ladi.
 *
 * Insight mavjud bo'lganda, ustuvorlik tartibi (birinchi mos kelgani
 * ishlatiladi) — VA HAR BIR holat endi albatta `traits` (qiyinlik/
 * kayfiyat/kimlar uchun mos-emas) bilan BOYITILGAN holda qaytadi, hech
 * qachon yagona/yolg'iz bo'lim sifatida emas:
 *   1. D2 — mahsulot allaqachon sotib olingan          → 'reminder'
 *   2. D1 — shu kategoriyada salbiy tarix bor           → 'neutral_fact'
 *   3. YANGI — xarid tarixi yo'q, qiziqish+kollaborativ → 'interest_match'
 *   4. S1 kuchli (>= taste_strong_threshold)            → 'match'
 *   5. S1 zaif ijobiy (taste_soft..strong oralig'i)     → 'soft_hint'
 *   6. S2 kollaborativ kuchli                           → 'discovery'
 *   7. S3 universal sifat yetarli                       → 'quality'
 *   8. Hech narsa yo'q (lekin insight BOR)               → 'new'
 *
 * Mehmon (tizimga kirmagan) foydalanuvchi ham endi 'quality'/'new'
 * holatida to'liq `traits` (kayfiyat + kimlar uchun mos) ko'radi —
 * faqat `guest_cta` bilan bezatilgan yalang'och karta emas.
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
        // ── FAQAT KITOB: kanselyariya (stationery) uchun bu karta ATAYLAB
        // ko'rsatilmaydi. "Qiyinlik darajasi" va "kayfiyat" — sof KITOBIY
        // tushunchalar (o'qish tajribasiga oid), ruchka/daftar/sumka kabi
        // jismoniy mahsulotlarga mos kelmaydi — AI ularni majburan
        // "o'ylab topishga" majbur bo'lardi. Shuning uchun AI ham bu tur
        // uchun umuman generatsiya qilmaydi (qarang: `GenerateReadingInsights`
        // — endi faqat 'book'), bu yerda esa hech qanday hisoblash
        // boshlanmasdanoq erta chiqamiz.
        if ($type !== 'book') {
            return null;
        }

        $product = $this->loadProduct($type, $id);
        if (! $product) {
            return null;
        }

        $categoryId = $product->category_id ? (int) $product->category_id : null;

        // ── 0-BOSQICH: AI hali tahlil qilmagan bo'lsa — karta umuman
        // ko'rsatilmaydi (yuqoridagi klass docblock'idagi izohga qarang).
        $insight = $this->insightGenerator->get($type, $id);
        if ($insight === null) {
            return null;
        }

        // ── D2: allaqachon sotib olingan ────────────────────────────────
        if ($user) {
            $purchasedAt = $this->tasteProfile->purchasedAt($user->id, $type, $id);
            if ($purchasedAt) {
                return $this->reminderPayload($purchasedAt, $product, $type, $locale, $insight);
            }
        }

        // ── D1: shu kategoriyada salbiy tarix ───────────────────────────
        if ($user && $categoryId && $this->tasteProfile->hasNegativeSignalForCategory($user->id, $categoryId, $type)) {
            return $this->neutralFactPayload($product, $type, $insight, $locale, $user);
        }

        // ── YANGI — sotib olish tarixi YO'Q mijoz: onboarding qiziqish +
        // kollaborativ signal ────────────────────────────────────────────
        // MUHIM: bu tekshiruv ATAYLAB S1 (vektor did moslik)dan OLDIN, lekin
        // FAQAT hech qanday xarid tarixi bo'lmagan foydalanuvchilar uchun
        // ishlaydi ("agar mijozda sotib olishlar bo'lmasa"). Xarid tarixi
        // bor foydalanuvchilar uchun S1'ning to'liq (xarid+savat+sevimli+
        // qiziqish) vaznli vektori ancha ishonchli, shuning uchun ularga
        // tegilmaymiz. Sotib olmagan mijoz uchun esa vektor asosidagi moslik
        // ko'pincha yetarlicha kuchli bo'lmaydi (faqat past vaznli — 0.25 —
        // qiziqish markazidan qurilgani uchun), shu bois bu yerda ANIQROQ va
        // TUSHUNARLIROQ (izohlanadigan) signal — "siz shu yo'nalishni
        // tanlagansiz" + "boshqa xaridorlar orasida bu mahsulot mashhur" —
        // ishlatiladi.
        if ($user && $categoryId && ! $this->tasteProfile->hasAnyPurchases($user->id)) {
            $interestMatch = $this->interestMatchPayload($user, $product, $type, $categoryId, $locale, $insight);
            if ($interestMatch !== null) {
                return $interestMatch;
            }
        }

        // ── S1: did moslik ───────────────────────────────────────────────
        if ($user && is_array($product->vectorData) && ! empty($product->vectorData)) {
            $tasteVector = $this->tasteProfile->tasteVector($user->id, $type, $id);

            if ($tasteVector !== null) {
                $score = $this->ai->calculateSimilarity($tasteVector, $product->vectorData);
                $strong = (float) config('reading_intelligence.taste_strong_threshold');
                $soft = (float) config('reading_intelligence.taste_soft_threshold');

                if ($score >= $strong) {
                    return $this->matchPayload($user, $product, $type, $score, $locale, $insight);
                }

                if ($score >= $soft) {
                    return $this->softHintPayload($product, $insight, $score, $locale, $user);
                }
            }
        }

        // ── S2: kollaborativ signal (boshqa janr, kashfiyot) ────────────
        if ($user && $categoryId) {
            $collaborative = $this->collaborativeAffinity($user->id, $categoryId, $type, $id, $locale);
            if ($collaborative !== null) {
                return $this->discoveryPayload($collaborative, $product, $type, $locale, $user, $insight);
            }
        }

        // ── S3: universal sifat ──────────────────────────────────────────
        $quality = $this->qualityPayload($product, $type, $locale, $user, $insight);
        if ($quality !== null) {
            return $user ? $quality : $this->withGuestCta($quality, $locale);
        }

        // ── Hech narsa yo'q: faqat kontent ──────────────────────────────
        $new = $this->newProductPayload($product, $insight, $locale, $user);

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

    private function matchPayload(?User $user, Books|Stationery $product, string $type, float $score, string $locale, ?BookReadingInsight $insight = null): array
    {
        $percent = (int) round($score * 100);
        $reason = $this->personalReason($user, $product, $type, $score, $locale);
        $similar = $this->similarSection($product, $type, $locale, $user);

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
                'traits' => $this->traitsSection($insight, $locale),
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

    // ─── YANGI — sotib olish tarixi yo'q: qiziqish + kollaborativ ───────

    /**
     * Sotib olish tarixi yo'q mijoz uchun: onboarding'da tanlangan
     * qiziqish (mahsulot kategoriyasi shu ro'yxatda bormi) + mavjud bo'lsa
     * kollaborativ signal (`collaborativeAffinity` — shu kategoriyada
     * xarid qilganlarning necha foizi aynan shu mahsulotni ham olgan)
     * birlashtirilib, bitta izohlanadigan N% moslik hisoblanadi.
     *
     * Mahsulot kategoriyasi foydalanuvchi tanlagan qiziqishlar ro'yxatida
     * bo'lmasa — null qaytadi, zanjir S1/S2/S3/S4 ga davom etadi (karta
     * hech qachon bo'sh qolmaydi, faqat kamroq shaxsiylashtirilgan holatga
     * tushadi).
     */
    private function interestMatchPayload(User $user, Books|Stationery $product, string $type, int $categoryId, string $locale, ?BookReadingInsight $insight = null): ?array
    {
        $interestIds = $this->tasteProfile->selectedInterestCategoryIds($user->id);
        if (! in_array($categoryId, $interestIds, true)) {
            return null;
        }

        // Komponent 1 — qiziqish mosligi: foydalanuvchi ushbu kategoriyani
        // o'zi, qo'lda tanlagan — shuning uchun bazaviy ishonch darajasi
        // o'rtacha-yuqori (62%), lekin hali "kuchli" (S1 strong) darajasida
        // emas, chunki bu haqiqiy xatti-harakat emas, e'lon qilingan istak.
        $interestComponent = 62.0;

        // Komponent 2 — kollaborativ signal (mavjud bo'lsa): shu
        // kategoriyada xarid qilganlarning necha foizi aynan shu
        // mahsulotni ham olgan (mavjud `collaborativeAffinity` qayta
        // ishlatiladi — u foydalanuvchining O'ZI xarid qilganiga bog'liq
        // emas, faqat mahsulot+kategoriya darajasida ishlaydi).
        $collaborative = $this->collaborativeAffinity($user->id, $categoryId, $type, $product->id, $locale);

        $hasCollaborative = $collaborative !== null;
        $percent = $interestComponent;

        if ($hasCollaborative) {
            $collabPercent = min(100.0, ($collaborative['rate'] ?? 0) * 100);
            // Ikkala komponent teng vaznda aralashtiriladi — sof
            // e'lon qilingan istak (qiziqish) va real ijtimoiy dalil
            // (boshqalar xaridi) bir xil darajada hisobga olinadi.
            $percent = ($interestComponent * 0.45) + ($collabPercent * 0.55);
        }

        // 50-96% oralig'ida — hech qachon "100% mos" kabi aldamchi da'vo
        // qilinmaydi (bu haqiqiy xarid tarixiga asoslanmagan taxmin),
        // lekin 50% dan past ham ko'rsatilmaydi (unda umuman ko'rsatmaslik
        // ma'noliroq — shu holatda zanjir S2/S3/S4'ga tushadi).
        $percent = (int) round(min(96, max(50, $percent)));

        $clusterLabel = $this->categoryName($product->category, $locale) ?? '';

        $reasonKey = $hasCollaborative
            ? 'reading_intelligence.interest_match_reason_collab'
            : 'reading_intelligence.interest_match_reason';
        $subtitleKey = $hasCollaborative
            ? 'reading_intelligence.teaser_interest_match_subtitle_collab'
            : 'reading_intelligence.teaser_interest_match_subtitle';

        $similar = $this->similarSection($product, $type, $locale, $user);

        return [
            'variant' => 'interest_match',
            'teaser' => [
                'icon' => 'heart',
                'title' => __('reading_intelligence.teaser_interest_match_title', ['percent' => $percent]),
                'subtitle' => __($subtitleKey, ['cluster' => $clusterLabel]),
            ],
            'sheet' => array_filter([
                'interest_match' => [
                    'label' => __('reading_intelligence.section_interest_match'),
                    'percent' => $percent,
                    'reason' => __($reasonKey, ['cluster' => $clusterLabel]),
                ],
                'traits' => $this->traitsSection($insight, $locale),
                'similar' => $similar,
                'similar_label' => $similar ? __('reading_intelligence.section_similar') : null,
            ]),
        ];
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

        $categoryStat = $this->categoryCollaborativeStats($type, $categoryId);
        if ($categoryStat === null) {
            return null;
        }

        $sampleSize = $categoryStat['sample_size'];
        $buyersOfThisProduct = $categoryStat['product_buyers'][$productId] ?? 0;
        $rate = $sampleSize > 0 ? $buyersOfThisProduct / $sampleSize : 0.0;

        if ($sampleSize < $minSample || $rate < $minRate) {
            return null;
        }

        $category = ($type === 'book' ? Books::query() : Stationery::query())
            ->whereKey($productId)
            ->with('category')
            ->first()
            ?->category;

        return [
            'rate' => $rate,
            'sample_size' => $sampleSize,
            'cluster_label' => $this->categoryName($category, $locale) ?? '',
        ];
    }

    /**
     * Bitta KATEGORIYA uchun kollaborativ statistika — "shu kategoriyadan
     * xarid qilganlar" to'plami VA har bir mahsulot bo'yicha ular orasidan
     * necha kishi aynan o'sha mahsulotni ham olgani, bitta 2000-ta-
     * buyurtmalik skanerlashda birgalikda hisoblanadi.
     *
     * MUHIM (tezlik tuzatishi): avval bu og'ir skanerlash HAR BIR
     * MAHSULOT uchun alohida keshlangan edi (`collab:{type}:{productId}:
     * cat:{categoryId}`), lekin ichidagi eng qimmat qism — 2000 ta
     * buyurtmani skanerlash — aslida faqat KATEGORIYAGA bog'liq, mahsulotga
     * emas. Natijada bitta kategoriyadagi har xil mahsulot sahifasi
     * (masalan, "interest_match" holatida — xarid tarixi yo'q
     * foydalanuvchilar uchun eng tez-tez ishlaydigan yo'l) ochilganda
     * xuddi shu 2000 ta buyurtma qayta-qayta skanerlanardi — bu
     * "moslik %" tahlilining item sahifasida kech chiqishining asosiy
     * sababi edi. Endi kesh KATEGORIYA darajasida — bir marta (soatiga)
     * hisoblanadi, o'sha kategoriyadagi qolgan barcha mahsulotlar uchun
     * darhol (massiv qidiruvi, O(1)) ishlatiladi.
     *
     * @return array{sample_size:int, product_buyers:array<int,int>}|null
     */
    private function categoryCollaborativeStats(string $type, int $categoryId): ?array
    {
        $ttl = (int) config('reading_intelligence.collaborative_cache_ttl');
        $cacheKey = "reading-intel:collab-cat:{$type}:{$categoryId}";

        return Cache::remember($cacheKey, $ttl, function () use ($categoryId, $type) {
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
            // product_id => [user_id => true] — kategoriya xaridorlaridan
            // qanchasi aynan shu mahsulotni ham olgani.
            $productBuyers = [];

            foreach ($orders as $order) {
                $boughtCategory = false;
                $categoryProductsInOrder = [];

                foreach ((array) $order->items as $item) {
                    $itemType = ($item['type'] ?? 'book') === 'stationery' ? 'stationery' : 'book';
                    if ($itemType !== $type) {
                        continue;
                    }
                    $itemId = (int) ($item['item_id'] ?? 0);
                    if (isset($categoryProductIds[$itemId])) {
                        $boughtCategory = true;
                        $categoryProductsInOrder[] = $itemId;
                    }
                }

                if ($boughtCategory) {
                    $categoryBuyers[$order->user_id] = true;
                    foreach ($categoryProductsInOrder as $productId) {
                        $productBuyers[$productId][$order->user_id] = true;
                    }
                }
            }

            $sampleSize = count($categoryBuyers);
            if ($sampleSize === 0) {
                return null;
            }

            return [
                'sample_size' => $sampleSize,
                'product_buyers' => array_map('count', $productBuyers),
            ];
        });
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

    private function discoveryPayload(array $collaborative, Books|Stationery $product, string $type, string $locale, ?User $user = null, ?BookReadingInsight $insight = null): array
    {
        $percent = (int) round(($collaborative['rate'] ?? 0) * 100);
        $clusterLabel = $collaborative['cluster_label'] ?? '';
        $similar = $this->similarSection($product, $type, $locale, $user);

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
            // MUHIM: faqat 'discovery' emas — 'traits'/'similar' ham
            // qo'shiladi, aks holda karta bitta yolg'iz jumladan iborat
            // bo'lib qolar edi (foydalanuvchi buni "yarim tayyor" deb
            // topgan edi).
            'sheet' => array_filter([
                'discovery' => [
                    'label' => __('reading_intelligence.teaser_discovery_title'),
                    'detail' => __('reading_intelligence.teaser_discovery_subtitle', [
                        'percent' => $percent,
                        'cluster' => $clusterLabel,
                    ]),
                ],
                'traits' => $this->traitsSection($insight, $locale),
                'similar' => $similar,
                'similar_label' => $similar ? __('reading_intelligence.section_similar') : null,
            ]),
        ];
    }

    // ─── Holat G — yumshoq ishora ─────────────────────────────────────────

    private function softHintPayload(Books|Stationery $product, ?BookReadingInsight $insight, float $score, string $locale, ?User $user = null): array
    {
        $hint = $insight?->localizedReviewSynthesis($locale) ?? $insight?->localizedAudienceFit($locale);
        $type = is_a($product, Books::class) ? 'book' : 'stationery';
        $similar = $this->similarSection($product, $type, $locale, $user);

        return [
            'variant' => 'soft_hint',
            'teaser' => [
                'icon' => 'message-circle',
                'title' => $hint ?? __('reading_intelligence.teaser_soft_title'),
                'subtitle' => __('reading_intelligence.teaser_soft_title'),
            ],
            'sheet' => array_filter([
                'traits' => $this->traitsSection($insight, $locale),
                'similar' => $similar,
                'similar_label' => $similar ? __('reading_intelligence.section_similar') : null,
            ]),
        ];
    }

    // ─── Holat C/E — faqat universal sifat ─────────────────────────────

    private function qualityPayload(Books|Stationery $product, string $type, string $locale, ?User $user = null, ?BookReadingInsight $insight = null): ?array
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

        // MUHIM: 'quality' + 'traits' + 'similar' birga — bu yo'l MEHMON
        // foydalanuvchilarga ham ko'rsatiladi (guest_cta bilan birga),
        // shuning uchun ular ham kayfiyat/qiyinlik va "kimlar uchun mos"
        // tahlilini ko'rishlari kerak, faqat "top N talikda" jumlasi emas.
        $similar = $this->similarSection($product, $type, $locale, $user);

        return [
            'variant' => 'quality',
            'teaser' => [
                'icon' => 'trending-up',
                'title' => __('reading_intelligence.teaser_quality_title', ['rank' => $rank]),
                'subtitle' => __('reading_intelligence.teaser_quality_subtitle'),
            ],
            'sheet' => array_filter([
                'quality' => [
                    'label' => __('reading_intelligence.teaser_quality_title', ['rank' => $rank]),
                    'detail' => __('reading_intelligence.bestseller_label'),
                ],
                'traits' => $this->traitsSection($insight, $locale),
                'similar' => $similar,
                'similar_label' => $similar ? __('reading_intelligence.section_similar') : null,
            ]),
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

    private function newProductPayload(Books|Stationery $product, ?BookReadingInsight $insight, string $locale, ?User $user = null): array
    {
        $type = is_a($product, Books::class) ? 'book' : 'stationery';
        $similar = $this->similarSection($product, $type, $locale, $user);

        return [
            'variant' => 'new',
            'teaser' => [
                'icon' => 'tag',
                'title' => __('reading_intelligence.teaser_new_title'),
                // MUHIM: bu yerda ATAYLAB sahifa soni/qiyinlik darajasi
                // YOZILMAYDI — banner qisqa, "antiqa" (qiziqtiruvchi) matn
                // bilan bosishga undaydi, batafsil tahlil (qiyinlik,
                // kayfiyat, kimlar uchun mos) esa faqat bosilgandan keyin,
                // to'liq sheet ichida ochiladi.
                'subtitle' => __('reading_intelligence.teaser_curious_subtitle'),
            ],
            'sheet' => array_filter([
                'traits' => $this->traitsSection($insight, $locale),
                'similar' => $similar,
                'similar_label' => $similar ? __('reading_intelligence.section_similar') : null,
            ]),
        ];
    }

    // ─── Holat D1 — did salbiy: neytral fakt kartasi (BO'SH EMAS) ──────

    private function neutralFactPayload(Books|Stationery $product, string $type, ?BookReadingInsight $insight, string $locale, ?User $user = null): array
    {
        $similar = $this->similarSection($product, $type, $locale, $user);

        return [
            'variant' => 'neutral_fact',
            'teaser' => [
                'icon' => 'book',
                'title' => $this->productFactLine($product, $type, $locale),
                // MUHIM: sahifa soni/qiyinlik darajasi endi bu yerda
                // yozilmaydi (newProductPayload'dagi izohga qarang) —
                // qiziqtiruvchi umumiy matn ishlatiladi.
                'subtitle' => __('reading_intelligence.teaser_curious_subtitle'),
            ],
            'sheet' => array_filter([
                'traits' => $this->traitsSection($insight, $locale),
                'similar' => $similar,
                'similar_label' => $similar ? __('reading_intelligence.section_similar') : null,
            ]),
        ];
    }

    // ─── Holat D2 — allaqachon sotib olingan ────────────────────────────

    private function reminderPayload(\Illuminate\Support\Carbon $purchasedAt, Books|Stationery $product, string $type, string $locale, ?BookReadingInsight $insight = null): array
    {
        // MUHIM: reminder ham endi 'traits' bilan boyitilgan — foydalanuvchi
        // buni yolg'iz, "quruq" eslatma sifatida ko'rmasligi kerak, balki
        // mahsulot haqidagi to'liq tahlil bilan birga (masalan qayta
        // sotib olishga undash yoki sovg'a sifatida tavsiya qilish uchun
        // foydali bo'lishi mumkin).
        return [
            'variant' => 'reminder',
            'teaser' => [
                'icon' => 'info-circle',
                'title' => __('reading_intelligence.teaser_reminder_title'),
                'subtitle' => __('reading_intelligence.teaser_reminder_subtitle', [
                    'date' => $purchasedAt->translatedFormat('d.m.Y'),
                ]),
            ],
            'sheet' => array_filter([
                'reminder' => [
                    'label' => __('reading_intelligence.section_reminder'),
                    'detail' => __('reading_intelligence.teaser_reminder_subtitle', [
                        'date' => $purchasedAt->translatedFormat('d.m.Y'),
                    ]),
                ],
                'traits' => $this->traitsSection($insight, $locale),
            ]),
        ];
    }

    // ─── Umumiy bo'limlar ────────────────────────────────────────────────

    /**
     * MUHIM (tezlik): bu metod avval `$insight`ni o'zi qayta so'rar edi
     * (`insightGenerator->get()`), garchi u ALLAQACHON `forProduct()`
     * boshida bir marta olingan bo'lsa ham — har bir sheet yig'ilishida
     * qo'shimcha (keraksiz) kesh so'rovi degani edi. Endi tayyor
     * `$insight` to'g'ridan-to'g'ri parametr sifatida uzatiladi — qayta
     * so'rov yo'q.
     */
    private function traitsSection(?BookReadingInsight $insight, string $locale): ?array
    {
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
            // MUHIM: 'audience_avoid' ("kimlar uchun mos emas") ATAYLAB
            // olib tashlandi — salbiy/qo'rqituvchi ohang bergani uchun.
            // 'audience_fit' endi o'zi chuqurroq (2-3 gapli) tahlil bo'lib,
            // nega mos kelishini yoritadi (qarang: `ReadingInsightGenerator`
            // dagi yangi prompt).
            'audience_fit' => $insight->localizedAudienceFit($locale),
        ]);
    }

    /**
     * "Sizga yana yoqishi mumkin" bo'limi — UCH bosqichli zaxira zanjiri,
     * shu orqali bu bo'lim deyarli hech qachon "o'xshash mahsulot yo'q"
     * deb bo'sh qolmaydi (maksimal darajada sotishga xizmat qiladi):
     *   1. Joriy mahsulotga VEKTOR bo'yicha eng o'xshashlar — eng aniq
     *      signal, mavjud bo'lsa shu ishlatiladi.
     *   2. (1) bo'sh bo'lsa VA foydalanuvchi ro'yxatdan o'tgan bo'lsa —
     *      uning eng ko'p XARID QILGAN toifasidan eng ko'p sotilganlar
     *      ("avval sotib olganlaringizga o'xshash" — har bir xarid uchun
     *      alohida qimmat vektor qidiruvi o'rniga, bitta kategoriya
     *      bo'yicha bitta oddiy so'rov — soddaroq, lekin ishonchli).
     *   3. Hali ham bo'sh bo'lsa — mahsulot toifasidagi (yoki umuman)
     *      ENG KO'P SOTILGAN mahsulotlar (oxirgi zaxira, doim biror
     *      natija beradi).
     */
    private function similarSection(Books|Stationery $product, string $type, string $locale, ?User $user = null): ?array
    {
        $results = $this->vectorSimilar($product, $type);

        if ($results->isEmpty() && $user) {
            $results = $this->purchaseBasedSimilar($user, $type, (int) $product->id);
        }

        if ($results->isEmpty()) {
            $results = $this->bestSellingSimilar($product, $type);
        }

        if ($results->isEmpty()) {
            return null;
        }

        // MUHIM: front-end "similar" ro'yxatini gorizontal skroll qatorida
        // ko'rsatadi (qattiq 2-3 ta bilan cheklangan grid emas), shuning
        // uchun bu yerda ham 6 tagacha qaytariladi. Vektor natijalari
        // `_similarity` (0-1) bilan keladi — "X% o'xshash" matni; xarid-
        // asosidagi/eng-ko'p-sotilgan natijalarda bu yo'q, ular uchun
        // "eng ko'p sotilganlardan" yorlig'i ishlatiladi.
        return $results->take(6)->map(function ($item) {
            $similarity = $item->_similarity ?? null;

            return [
                'id' => $item->id,
                'name' => $item->name,
                'score' => $similarity !== null
                    ? __('reading_intelligence.similar_score', ['percent' => (int) round($similarity * 100)])
                    : __('reading_intelligence.bestseller_label'),
            ];
        })->values()->all();
    }

    private function vectorSimilar(Books|Stationery $product, string $type): \Illuminate\Support\Collection
    {
        if (! is_array($product->vectorData) || empty($product->vectorData)) {
            return collect();
        }

        return $this->vectorSearch
            ->searchByVector($product->vectorData, $type, limit: 8, minScore: 0.5, inStockOnly: true)
            ->filter(fn ($item) => (int) $item->id !== (int) $product->id)
            ->take(6);
    }

    /**
     * Foydalanuvchi eng ko'p xarid qilgan kategoriyadan, u ALLAQACHON
     * sotib olmagan, eng ko'p sotilgan mahsulotlar.
     */
    private function purchaseBasedSimilar(User $user, string $type, int $excludeId): \Illuminate\Support\Collection
    {
        $categoryId = $this->tasteProfile->dominantCategoryId($user->id, $type);
        if (! $categoryId) {
            return collect();
        }

        $query = $type === 'book' ? Books::query() : Stationery::query();

        return $query
            ->activeForVector()
            ->where('category_id', $categoryId)
            ->where('id', '!=', $excludeId)
            ->orderByDesc('totalSales')
            ->limit(6)
            ->get();
    }

    /**
     * Oxirgi zaxira — mahsulot toifasidagi (topilmasa, umuman) eng ko'p
     * sotilganlar. Karta HECH QACHON "o'xshash mahsulot yo'q" deb bo'sh
     * qolmasligi uchun.
     */
    private function bestSellingSimilar(Books|Stationery $product, string $type): \Illuminate\Support\Collection
    {
        $query = $type === 'book' ? Books::query() : Stationery::query();
        $query->activeForVector()->where('id', '!=', $product->id);

        if ($product->category_id) {
            $query->where('category_id', $product->category_id);
        }

        return $query->orderByDesc('totalSales')->limit(6)->get();
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

    // MUHIM: bu yerda ATAYLAB sahifa soni qo'shilmaydi — teaser banner
    // qisqa va qiziqtiruvchi bo'lishi kerak, ortiqcha "quruq" fakt (necha
    // bet) emas. Faqat kategoriya nomi (yoki mahsulot topilmasa, uning
    // nomi) yetarli.
    private function productFactLine(Books|Stationery $product, string $type, string $locale): string
    {
        return $this->categoryName($product->category, $locale) ?? $product->name;
    }

    private function loadProduct(string $type, int $id): Books|Stationery|null
    {
        return $type === 'book'
            ? Books::query()->with('category')->find($id)
            : Stationery::query()->with('category')->find($id);
    }
}
