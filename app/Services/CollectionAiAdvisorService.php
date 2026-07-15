<?php

namespace App\Services;

use App\Models\Books;
use App\Models\MyCart;
use App\Models\ProductViewLog;
use App\Models\SearchHistory;
use App\Models\SellerOrderItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * AI to'plam maslahatchisi: BIZNING real talab ma'lumotimiz (sotuv tezligi, savatga
 * qo'shish, ko'rishlar, qidiruvlar) + AI'ning bozor bilimi asosida ombordagi kitoblardan
 * mavzuli to'plam tavsiya qiladi. Narx CollectionPricingService bilan deterministik
 * hisoblanadi (margin-himoyalangan chegirma).
 */
class CollectionAiAdvisorService
{
    public function __construct(
        private readonly OpenAIService $openAIService,
        private readonly CollectionPricingService $pricingService,
        private readonly SellerOrderSettlementService $settlementService,
    ) {}

    /**
     * @param  array<string, mixed>  $opts  theme_hint, size, target_discount_percent, category_id
     * @return array<string, mixed>
     */
    public function recommend(array $opts = []): array
    {
        $days = max(1, (int) config('collection_advisor.demand_days', 30));
        $since = now()->subDays($days);

        [$sales, $carts, $views] = $this->gatherDemand($since);
        $candidates = $this->buildCandidates($sales, $carts, $views, $opts);

        if ($candidates->count() < 3) {
            return [
                'ok' => false,
                'message' => "Tavsiya uchun ombordagi kitoblar yetarli emas (kamida 3 ta faol kitob kerak).",
            ];
        }

        $searches = $this->topSearches($since);
        $decision = $this->askAi($candidates, $searches, $opts);

        if (! $decision || empty($decision['book_ids'])) {
            return [
                'ok' => false,
                'message' => "AI hozircha tavsiya bera olmadi. Birozdan so'ng qayta urinib ko'ring.",
            ];
        }

        $byId = $candidates->keyBy('id');
        $minBooks = max(2, (int) config('collection_advisor.min_books', 4));
        $maxBooks = max($minBooks, (int) config('collection_advisor.max_books', 8));

        $selected = collect($decision['book_ids'])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->filter(fn ($id) => $byId->has($id))
            ->take($maxBooks)
            ->map(fn ($id) => $byId->get($id))
            ->values();

        if ($selected->count() < 2) {
            return ['ok' => false, 'message' => "AI tanlagan kitoblar ombordagi ro'yxatga mos kelmadi."];
        }

        $books = $this->resolveBooksForPricing($selected);

        $suggestedDiscount = $this->clampDiscount($decision['suggested_discount_percent'] ?? null, $opts);
        $pricing = $this->pricingService->calculate(
            $books->map(fn ($b) => [
                'price' => $b['price'],
                'quantity' => $b['quantity'],
                'commission_percent' => $b['commission_percent'],
            ])->all(),
            $suggestedDiscount,
        );

        $theme = is_array($decision['theme'] ?? null) ? $decision['theme'] : [];

        return [
            'ok' => true,
            'theme' => [
                'title_uz' => $this->text($theme['title_uz'] ?? '', 120),
                'title_ru' => $this->text($theme['title_ru'] ?? '', 120),
                'subtitle_uz' => $this->text($theme['subtitle_uz'] ?? '', 180),
                'subtitle_ru' => $this->text($theme['subtitle_ru'] ?? '', 180),
                'description_uz' => $this->text($theme['description_uz'] ?? '', 600),
                'description_ru' => $this->text($theme['description_ru'] ?? '', 600),
            ],
            'books' => $books->all(),
            'pricing' => $pricing,
            'market_analysis' => $this->text($decision['market_analysis_uz'] ?? '', 800),
            'reasoning' => $this->text($decision['reasoning_uz'] ?? '', 900),
            'demand' => [
                'window_days' => $days,
                'candidate_count' => $candidates->count(),
                'top_searches' => $searches->take(12)->values()->all(),
                'method' => 'Ichki talab (sotuv, savat, ko\'rish, qidiruv) + AI bozor tahlili',
            ],
        ];
    }

    /** @return array{0:Collection,1:Collection,2:Collection} */
    private function gatherDemand(\DateTimeInterface $since): array
    {
        // Har bir signal alohida himoyalangan — biror jadvalda ustun bo'lmasa ham
        // feature ishlaydi (o'sha signal 0 bo'ladi).
        $safe = function (\Closure $query): Collection {
            try {
                return $query();
            } catch (\Throwable $e) {
                Log::warning('Collection advisor demand query failed', ['error' => $e->getMessage()]);

                return collect();
            }
        };

        $sales = $safe(fn () => SellerOrderItem::query()
            ->where('type', 'book')
            ->whereNull('cancelled_at')
            ->where('created_at', '>=', $since)
            ->groupBy('product_id')
            ->selectRaw('product_id, SUM(quantity) as agg')
            ->pluck('agg', 'product_id'));

        $carts = $safe(fn () => MyCart::query()
            ->where('product_type', 'book')
            ->where('created_at', '>=', $since)
            ->groupBy('product_id')
            ->selectRaw('product_id, COUNT(*) as agg')
            ->pluck('agg', 'product_id'));

        $views = $safe(fn () => ProductViewLog::query()
            ->where('product_type', 'book')
            ->where('created_at', '>=', $since)
            ->groupBy('product_id')
            ->selectRaw('product_id, COUNT(*) as agg')
            ->pluck('agg', 'product_id'));

        return [$sales, $carts, $views];
    }

    private function buildCandidates(Collection $sales, Collection $carts, Collection $views, array $opts): Collection
    {
        $weights = (array) config('collection_advisor.weights', []);
        $wSales = (float) ($weights['sales'] ?? 5);
        $wCarts = (float) ($weights['carts'] ?? 3);
        $wViews = (float) ($weights['views'] ?? 1);
        $limit = max(10, (int) config('collection_advisor.candidate_limit', 40));

        $demandIds = collect($sales->keys())
            ->merge($carts->keys())
            ->merge($views->keys())
            ->unique()
            ->values();

        $base = Books::query()
            ->with(['seller:id,shop_name', 'category:id,name_uz,name_ru'])
            ->where('status', true)
            ->where('is_approved', 1)
            ->where('is_hidden', 0)
            ->where('count', '>', 0)
            ->whereHas('seller', fn ($q) => $q->where('status', 'approved')->where('is_hidden', 0));

        if (! empty($opts['category_id'])) {
            $base->where('category_id', (int) $opts['category_id']);
        }

        // Talab signali bor kitoblar + yangi ombordagilar bilan to'ldiramiz.
        $withDemand = (clone $base)->whereIn('id', $demandIds->all())->limit(120)->get();
        $fresh = (clone $base)->latest('updated_at')->limit(40)->get();

        return $withDemand->concat($fresh)
            ->unique('id')
            ->map(function (Books $book) use ($sales, $carts, $views, $wSales, $wCarts, $wViews) {
                $s = (float) ($sales[$book->id] ?? 0);
                $c = (float) ($carts[$book->id] ?? 0);
                $v = (float) ($views[$book->id] ?? 0);
                $price = (int) (($book->discountPrice ?: $book->price) ?? 0);

                return [
                    'id' => (int) $book->id,
                    'name' => (string) $book->name,
                    'author' => (string) ($book->author ?? ''),
                    'category' => (string) ($book->category?->name_uz ?: ($book->category?->name_ru ?? '')),
                    'price' => $price,
                    'base_price' => (int) ($book->price ?? 0),
                    'stock' => (int) ($book->count ?? 0),
                    'seller' => (string) ($book->seller?->shop_name ?? ''),
                    'seller_id' => (int) $book->seller_id,
                    'image' => collect($book->images ?? [])->first(),
                    'sales' => (int) $s,
                    'carts' => (int) $c,
                    'views' => (int) $v,
                    'score' => round($s * $wSales + $c * $wCarts + $v * $wViews, 2),
                ];
            })
            ->sortByDesc('score')
            ->take($limit)
            ->values();
    }

    private function topSearches(\DateTimeInterface $since): Collection
    {
        try {
            return SearchHistory::query()
                ->where('created_at', '>=', $since)
                ->whereNotNull('text')
                ->where('text', '!=', '')
                ->groupBy('text')
                ->selectRaw('text, SUM(COALESCE(search_count, 1)) as freq')
                ->orderByDesc('freq')
                ->limit(25)
                ->pluck('text')
                ->map(fn ($t) => trim((string) $t))
                ->filter(fn ($t) => mb_strlen($t) >= 2)
                ->values();
        } catch (\Throwable $e) {
            Log::warning('Collection advisor search query failed', ['error' => $e->getMessage()]);

            return collect();
        }
    }

    private function askAi(Collection $candidates, Collection $searches, array $opts): ?array
    {
        $minBooks = max(2, (int) config('collection_advisor.min_books', 4));
        $maxBooks = max($minBooks, (int) config('collection_advisor.max_books', 8));
        $dMin = (float) config('collection_advisor.target_discount_min', 8);
        $dMax = (float) config('collection_advisor.target_discount_max', 25);
        $size = isset($opts['size']) ? max($minBooks, min($maxBooks, (int) $opts['size'])) : null;
        $themeHint = trim((string) ($opts['theme_hint'] ?? ''));

        $catalog = $candidates->map(fn ($b) => [
            'id' => $b['id'],
            'name' => $b['name'],
            'author' => $b['author'],
            'category' => $b['category'],
            'price' => $b['price'],
            'sales' => $b['sales'],
            'carts' => $b['carts'],
            'views' => $b['views'],
        ])->all();

        $system = <<<'PROMPT'
Siz Kitobchi (O'zbekiston kitob marketplace) uchun tajribali kontent-strateg va marketing menejerisiz.
Vazifa: FAQAT berilgan katalogdagi (ya'ni bizda ombori bor) kitoblardan O'zbek o'quvchilari
uchun jozibali, MANTIQAN BOG'LIQ mavzuli to'plam (bundle) tuzish.

Chuqur tahlil qiling:
- sales/carts/views — bizning real talab signallarimiz (sotuv, savatga qo'shish, ko'rish). Yuqori bo'lsa — talab kuchli.
- top_searches — o'quvchilar nimani qidirmoqda (mavzuga ishora).
- BOZOR BILIMI: o'z bilimingizdan foydalaning — o'zbek, rus va jahon kitob bozorida qaysi janr, muallif va mavzular doimiy talabga ega (mashhur mualliflar, doimiy bestsellerlar, ommabop yo'nalishlar). Shuni ichki talab signallari bilan birlashtiring. (Sizda real-vaqt internet yo'q, shuning uchun eng yangi 2026 relizlarga emas, isbotlangan talabga tayaning.)
- Kitoblarni bitta aniq mavzu/g'oya atrofida birlashtiring (masalan: "Shaxsiy rivojlanish", "O'zbek nasri", "Bolalar uchun", "Biznes va moliya"). Tasodifiy aralashma emas.
- Talab yuqori + mavzuga mos + narx muvozanati bo'lgan kitoblarni afzal ko'ring.

Qoidalar:
- Faqat katalogdagi `id` larni tanlang. Boshqa kitob o'ylab topmang.
- Kamida MIN, ko'pi bilan MAX ta kitob.
- suggested_discount_percent — marketing chegirmasi, DMIN..DMAX oralig'ida (haqiqiy narxni hisoblamang, faqat foiz taklif qiling; pul hisobini tizim o'zi qiladi).
- Sarlavha/tavsif jozibali, lekin ortiqcha va'dasiz. uz va ru tilda.

Faqat JSON qaytaring:
{
  "theme": {
    "title_uz": "", "title_ru": "",
    "subtitle_uz": "", "subtitle_ru": "",
    "description_uz": "", "description_ru": ""
  },
  "book_ids": [id, id, ...],
  "suggested_discount_percent": 0,
  "market_analysis_uz": "o'zbek/mintaqa kitob bozori bo'yicha qisqa tahlil: qaysi janr/muallif/mavzu talabga ega va nega bu to'plam bozorga mos",
  "reasoning_uz": "nega aynan shu kitoblar va mavzu — talab va marketing asosida qisqa izoh"
}
PROMPT;

        $user = [
            'min_books' => $minBooks,
            'max_books' => $maxBooks,
            'preferred_size' => $size,
            'discount_range' => ['min' => $dMin, 'max' => $dMax],
            'theme_hint' => $themeHint !== '' ? $themeHint : null,
            'top_searches' => $searches->take(20)->values()->all(),
            'catalog' => $catalog,
        ];

        try {
            $result = $this->openAIService->askJsonWithMessages(
                [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user', 'content' => json_encode($user, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)],
                ],
                1600,
                0.5,
                long: true,
                model: (string) config('collection_advisor.model', 'gpt-4o-mini'),
            );
        } catch (\Throwable $e) {
            Log::warning('Collection AI advisor failed', ['error' => $e->getMessage()]);

            return null;
        }

        return is_array($result) ? $result : null;
    }

    /**
     * Tanlangan kitoblar uchun komissiya foizini aniqlaydi (settlement bilan bir xil).
     */
    private function resolveBooksForPricing(Collection $selected): Collection
    {
        $sellerIds = $selected->pluck('seller_id')->filter()->unique()->all();
        $sellers = \App\Models\Seller::query()->whereIn('id', $sellerIds)->get()->keyBy('id');

        return $selected->map(function (array $b) use ($sellers) {
            $seller = $sellers->get($b['seller_id']);
            $commission = $seller
                ? $this->settlementService->resolveCommissionPercent($seller, (int) $b['price'])
                : 0;

            return array_merge($b, [
                'quantity' => 1,
                'commission_percent' => (int) $commission,
                'line_commission' => (int) round($b['price'] * $commission / 100),
            ]);
        })->values();
    }

    private function clampDiscount(mixed $value, array $opts): ?float
    {
        if (isset($opts['target_discount_percent']) && is_numeric($opts['target_discount_percent'])) {
            $value = $opts['target_discount_percent'];
        }
        if (! is_numeric($value)) {
            return null;
        }
        $dMin = (float) config('collection_advisor.target_discount_min', 8);
        $dMax = (float) config('collection_advisor.target_discount_max', 25);

        return max($dMin, min($dMax, (float) $value));
    }

    private function text(mixed $value, int $limit): string
    {
        $value = trim((string) $value);

        return mb_substr($value, 0, $limit);
    }
}
