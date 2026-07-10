<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\HasProductVisibility;
use App\Models\Books;
use App\Models\Stationery;
use App\Models\BookCategories;
use App\Models\StationeryCategory;
use App\Models\FavouriteProducts;
use App\Support\ProductImageUrls;
use App\Support\ProductPayloadFormatter;
use App\Models\SearchHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class SearchController extends Controller
{
    use HasProductVisibility;

    // ─────────────────────────────────────────────
    // TRANSLITERATION
    // ─────────────────────────────────────────────
    private function transliterate(string $text): array
    {
        $latinToCyrillic = [
            'oʻ'=>'ў','gʻ'=>'ғ','sh'=>'ш','ch'=>'ч','ng'=>'нг',
            'yo'=>'ё','yu'=>'ю','ya'=>'я','ts'=>'ц',
            "o'"=>'ў',"g'"=>'ғ',
            'a'=>'а','b'=>'б','v'=>'в','d'=>'д','e'=>'е',
            'j'=>'ж','z'=>'з','i'=>'и','y'=>'й','k'=>'к',
            'l'=>'л','m'=>'м','n'=>'н','o'=>'о','p'=>'п',
            'r'=>'р','s'=>'с','t'=>'т','u'=>'у','f'=>'ф',
            'x'=>'х','h'=>'ҳ','q'=>'қ','g'=>'г',
        ];

        $cyrillicToLatin = [
            'ў'=>"o'",'ғ'=>"g'",'ш'=>'sh','ч'=>'ch','нг'=>'ng',
            'ё'=>'yo','ю'=>'yu','я'=>'ya','ц'=>'ts',
            'а'=>'a','б'=>'b','в'=>'v','д'=>'d','е'=>'e',
            'ж'=>'j','з'=>'z','и'=>'i','й'=>'y','к'=>'k',
            'л'=>'l','м'=>'m','н'=>'n','о'=>'o','п'=>'p',
            'р'=>'r','с'=>'s','т'=>'t','у'=>'u','ф'=>'f',
            'х'=>'x','ҳ'=>'h','қ'=>'q','г'=>'g',
        ];

        $text     = mb_strtolower($text, 'UTF-8');
        $variants = [$text];

        $cyrillic = $text;
        foreach ($latinToCyrillic as $lat => $cyr) {
            $cyrillic = str_replace($lat, $cyr, $cyrillic);
        }
        if ($cyrillic !== $text) $variants[] = $cyrillic;

        $latin = $text;
        foreach ($cyrillicToLatin as $cyr => $lat) {
            $latin = str_replace($cyr, $lat, $latin);
        }
        if ($latin !== $text) $variants[] = $latin;

        $alt = [];
        foreach ($variants as $v) {
            $alt[] = str_replace("o'", 'oʻ', $v);
            $alt[] = str_replace("g'", 'gʻ', $v);
            $alt[] = str_replace('oʻ', "o'", $v);
            $alt[] = str_replace('gʻ', "g'", $v);
        }

        return array_unique(array_merge($variants, $alt));
    }

    private function expandLooseVariants(array $variants): array
    {
        $rules = [
            ['q', 'k'],
            ['k', 'q'],
            ['x', 'h'],
            ['h', 'x'],
            ['w', 'v'],
            ['v', 'w'],
            ['c', 'k'],
            ['ts', 's'],
            ['yo', 'o'],
            ['yu', 'u'],
            ['ya', 'a'],
            ["o'", 'o'],
            ['oʻ', 'o'],
            ["g'", 'g'],
            ['gʻ', 'g'],
        ];

        $expanded = [];

        foreach ($variants as $variant) {
            $expanded[] = $variant;

            foreach ($rules as [$from, $to]) {
                if (str_contains($variant, $from)) {
                    $expanded[] = str_replace($from, $to, $variant);
                }
            }
        }

        return array_values(array_unique(array_filter($expanded)));
    }

    private function buildSearchVariants(string $text): array
    {
        $text = trim($text);
        if ($text === '') {
            return [];
        }

        $variants = $this->transliterate($text);
        $variants = array_merge($variants, $this->expandLooseVariants($variants));

        $squeezed = $this->squeezeRepeats($text);
        if ($squeezed !== $text && mb_strlen($squeezed) >= 2) {
            $squeezedVariants = $this->transliterate($squeezed);
            $variants = array_merge($variants, $squeezedVariants, $this->expandLooseVariants($squeezedVariants));
        }

        return array_values(array_unique(array_filter($variants)));
    }

    private function buildBooleanQuery(array $variants): string
    {
        return collect($variants)
            ->filter(fn($v) => mb_strlen($v) >= 2)
            ->map(fn($v) => '+' . preg_replace('/[+\-><()"~*@]/', '', $v) . '*')
            ->unique()
            ->implode(' ');
    }

    private function analyzeQuery(string $query): array
    {
        $query    = trim($query);
        $words    = preg_split('/\s+/u', $query);
        $variants = [];

        foreach ($words as $word) {
            if (mb_strlen($word) >= 2) {
                $variants = array_merge($variants, $this->buildSearchVariants($word));
            }
        }
        $variants = array_merge($variants, $this->buildSearchVariants($query));
        $variants = array_unique($variants);

        return [
            'original' => $query,
            'boolean'  => $this->buildBooleanQuery($variants),
            'variants' => $variants,
        ];
    }

    /**
     * LIKE pattern lar — teg qidirish uchun ham ishlatiladi
     */
    private function buildLikePatterns(string $query): array
    {
        $variants = $this->buildSearchVariants($query);
        $patterns = [];

        foreach ($variants as $v) {
            $patterns[] = $v . '%';
            $patterns[] = '% ' . $v . '%';
            $patterns[] = '%' . $v . '%';
        }

        return array_unique($patterns);
    }

    private function canonicalFuzzyText(string $text): string
    {
        $text = mb_strtolower(trim($text), 'UTF-8');
        $text = str_replace(['ʼ', '`', '‘', '’', 'ʻ', 'ʼ'], "'", $text);

        $variants = $this->transliterate($text);
        $latin = collect($variants)->first(function ($variant) {
            return !preg_match('/\p{Cyrillic}/u', $variant);
        }) ?? $text;

        $latin = str_replace(
            ["o'", "g'", 'oʻ', 'gʻ'],
            ['o', 'g', 'o', 'g'],
            $latin
        );

        $latin = str_replace(
            ['yo', 'yu', 'ya', 'ts'],
            ['o', 'u', 'a', 's'],
            $latin
        );

        $latin = str_replace(
            ['q', 'x', 'w', 'c'],
            ['k', 'h', 'v', 'k'],
            $latin
        );

        $latin = preg_replace('/[^a-z0-9\s]+/u', ' ', $latin) ?? $latin;
        $latin = preg_replace('/\s+/u', ' ', trim($latin)) ?? trim($latin);

        return $latin;
    }

    private function squeezeRepeats(string $text): string
    {
        return preg_replace('/(.)\1+/u', '$1', $text) ?? $text;
    }

    private function tokenSimilarity(string $left, string $right): float
    {
        if ($left === '' || $right === '') {
            return 0.0;
        }

        if ($left === $right) {
            return 1.0;
        }

        $distance = levenshtein($left, $right);
        $maxLen = max(strlen($left), strlen($right), 1);

        return max(0.0, 1 - ($distance / $maxLen));
    }

    private function fuzzySimilarityScore(string $query, string $candidate): float
    {
        $queryNorm = $this->canonicalFuzzyText($query);
        $candidateNorm = $this->canonicalFuzzyText($candidate);

        if ($queryNorm === '' || $candidateNorm === '') {
            return 0.0;
        }

        $queryFlat = str_replace(' ', '', $queryNorm);
        $candidateFlat = str_replace(' ', '', $candidateNorm);
        $queryNoRepeat = $this->squeezeRepeats($queryFlat);
        $candidateNoRepeat = $this->squeezeRepeats($candidateFlat);

        $baseScore = max(
            $this->tokenSimilarity($queryFlat, $candidateFlat),
            $this->tokenSimilarity($queryNoRepeat, $candidateNoRepeat)
        );

        $queryTokens = array_values(array_filter(explode(' ', $queryNorm)));
        $candidateTokens = array_values(array_filter(explode(' ', $candidateNorm)));
        $tokenScores = [];

        foreach ($queryTokens as $queryToken) {
            $best = 0.0;
            foreach ($candidateTokens as $candidateToken) {
                $best = max(
                    $best,
                    $this->tokenSimilarity($queryToken, $candidateToken),
                    $this->tokenSimilarity(
                        $this->squeezeRepeats($queryToken),
                        $this->squeezeRepeats($candidateToken)
                    )
                );
            }
            $tokenScores[] = $best;
        }

        $tokenAverage = empty($tokenScores)
            ? 0.0
            : array_sum($tokenScores) / count($tokenScores);

        $containsBonus = str_contains($candidateNorm, $queryNorm) || str_contains($queryNorm, $candidateNorm)
            ? 0.08
            : 0.0;
        $prefixBonus = !empty($queryTokens) && !empty($candidateTokens) &&
                Str::startsWith($candidateTokens[0], $queryTokens[0][0] ?? '')
            ? 0.03
            : 0.0;

        return min(1.0, ($baseScore * 0.58) + ($tokenAverage * 0.34) + $containsBonus + $prefixBonus);
    }

    private function genericSearchIntent(string $query): ?string
    {
        $normalized = $this->canonicalFuzzyText($query);
        if ($normalized === '') {
            return null;
        }

        $aliases = [
            'book' => ['kitob', 'kitoblar', 'book', 'books'],
            'stationery' => ['kantselyariya', 'kanselyariya', 'stationery', 'office'],
        ];

        $bestType = null;
        $bestScore = 0.0;

        foreach ($aliases as $type => $words) {
            foreach ($words as $word) {
                $score = $this->fuzzySimilarityScore($normalized, $word);
                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestType = $type;
                }
            }
        }

        return $bestScore >= 0.76 ? $bestType : null;
    }

    private function getBookFuzzyCorpus(
        ?int $sellerId = null,
        $categoryId = null,
        ?float $minPrice = null,
        ?float $maxPrice = null
    ) {
        $cacheKey = 'search_fuzzy_books_' . md5(json_encode([$sellerId, $categoryId, $minPrice, $maxPrice]));

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($sellerId, $categoryId, $minPrice, $maxPrice) {
            return $this->visibleBooks([])
                ->with('authorProfile:id,name')
                ->when($sellerId, fn ($q) => $q->where('seller_id', $sellerId))
                ->when($categoryId, fn ($q) => $q->where('category_id', $categoryId))
                ->when($minPrice !== null, fn ($q) => $q->where('price', '>=', $minPrice))
                ->when($maxPrice !== null, fn ($q) => $q->where('price', '<=', $maxPrice))
                ->select('id', 'artikul', 'name', 'author_id', 'totalSalesWeek', 'totalSales')
                ->orderByDesc('totalSalesWeek')
                ->orderByDesc('totalSales')
                ->limit(2500)
                ->get()
                ->map(function ($item) {
                    $authorName = trim((string) ($item->authorProfile->name ?? $item->author ?? ''));
                    return [
                        'id' => (int) $item->id,
                        'type' => 'book',
                        'display_name' => trim((string) $item->name),
                        'label' => trim(($item->name ?? '') . ' ' . ($item->artikul ?? '') . ' ' . $authorName),
                        'popularity' => (int) (($item->totalSalesWeek ?? 0) * 3 + ($item->totalSales ?? 0)),
                    ];
                });
        });
    }

    private function getStationeryFuzzyCorpus(
        ?int $sellerId = null,
        $categoryId = null,
        ?float $minPrice = null,
        ?float $maxPrice = null
    ) {
        $cacheKey = 'search_fuzzy_stationery_' . md5(json_encode([$sellerId, $categoryId, $minPrice, $maxPrice]));

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($sellerId, $categoryId, $minPrice, $maxPrice) {
            return $this->visibleStationeries([])
                ->when($sellerId, fn ($q) => $q->where('seller_id', $sellerId))
                ->when($categoryId, fn ($q) => $q->where('category_id', $categoryId))
                ->when($minPrice !== null, fn ($q) => $q->where('price', '>=', $minPrice))
                ->when($maxPrice !== null, fn ($q) => $q->where('price', '<=', $maxPrice))
                ->select('id', 'artikul', 'name', 'material', 'totalSalesWeek', 'totalSales')
                ->orderByDesc('totalSalesWeek')
                ->orderByDesc('totalSales')
                ->limit(2500)
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => (int) $item->id,
                        'type' => 'stationery',
                        'display_name' => trim((string) $item->name),
                        'label' => trim(($item->name ?? '') . ' ' . ($item->artikul ?? '') . ' ' . ($item->material ?? '')),
                        'popularity' => (int) (($item->totalSalesWeek ?? 0) * 3 + ($item->totalSales ?? 0)),
                    ];
                });
        });
    }

    private function rankFuzzyCorpus(string $query, $corpus, int $limit = 12): array
    {
        $ranked = collect($corpus)
            ->map(function ($item) use ($query) {
                $score = $this->fuzzySimilarityScore($query, $item['label'] ?? $item['display_name'] ?? '');
                $item['fuzzy_score'] = min(1.0, $score + min(((int) ($item['popularity'] ?? 0)) / 5000, 0.06));
                return $item;
            })
            ->filter(fn ($item) => ($item['fuzzy_score'] ?? 0) >= 0.56)
            ->sortByDesc('fuzzy_score')
            ->take($limit)
            ->values();

        $top = $ranked->first();

        return [
            'items' => $ranked,
            'did_you_mean' => ($top['fuzzy_score'] ?? 0) >= 0.68 ? ($top['display_name'] ?? null) : null,
        ];
    }

    private function fuzzyFallbackSearch(
        string $query,
        string $type,
        ?int $sellerId = null,
        $categoryId = null,
        ?float $minPrice = null,
        ?float $maxPrice = null
    ): array {
        $intent = $this->genericSearchIntent($query);

        if ($intent === 'book' && in_array($type, ['book', 'all'], true)) {
            $items = $this->getPopularBooks(12)
                ->map(function ($product) {
                    $product->relevance_score = 45;
                    return $product;
                });

            return [
                'items' => $items,
                'did_you_mean' => 'kitob',
                'search_mode' => 'generic_fuzzy_books',
            ];
        }

        if ($intent === 'stationery' && in_array($type, ['stationery', 'all'], true)) {
            $items = $this->getPopularStationeries(12)
                ->map(function ($product) {
                    $product->relevance_score = 45;
                    return $product;
                });

            return [
                'items' => $items,
                'did_you_mean' => 'kantselyariya',
                'search_mode' => 'generic_fuzzy_stationery',
            ];
        }

        $candidates = collect();
        $didYouMean = null;

        if (in_array($type, ['book', 'all'], true)) {
            $bookRanked = $this->rankFuzzyCorpus(
                $query,
                $this->getBookFuzzyCorpus($sellerId, $categoryId, $minPrice, $maxPrice)
            );

            $didYouMean ??= $bookRanked['did_you_mean'];
            $candidates = $candidates->merge($bookRanked['items']);
        }

        if (in_array($type, ['stationery', 'all'], true)) {
            $statRanked = $this->rankFuzzyCorpus(
                $query,
                $this->getStationeryFuzzyCorpus($sellerId, $categoryId, $minPrice, $maxPrice)
            );

            $didYouMean ??= $statRanked['did_you_mean'];
            $candidates = $candidates->merge($statRanked['items']);
        }

        $candidates = $candidates
            ->sortByDesc('fuzzy_score')
            ->take(12)
            ->values();

        $bookIds = $candidates->where('type', 'book')->pluck('id')->values();
        $statIds = $candidates->where('type', 'stationery')->pluck('id')->values();
        $scores = $candidates->mapWithKeys(fn ($item) => [
            $item['type'] . ':' . $item['id'] => $item['fuzzy_score']
        ]);

        $books = collect();
        $stationeries = collect();

        if ($bookIds->isNotEmpty()) {
            $books = $this->visibleBooks(['category', 'seller', 'tags'])
                ->whereIn('id', $bookIds)
                ->get()
                ->sortBy(fn ($item) => $bookIds->search($item->id))
                ->values()
                ->map(function ($item) use ($scores) {
                    $item->relevance_score = round(($scores['book:' . $item->id] ?? 0) * 100, 2);
                    return $item;
                });
        }

        if ($statIds->isNotEmpty()) {
            $stationeries = $this->visibleStationeries(['category', 'seller', 'tags', 'variants'])
                ->whereIn('id', $statIds)
                ->get()
                ->sortBy(fn ($item) => $statIds->search($item->id))
                ->values()
                ->map(function ($item) use ($scores) {
                    $item->relevance_score = round(($scores['stationery:' . $item->id] ?? 0) * 100, 2);
                    return $item;
                });
        }

        return [
            'items' => $books->merge($stationeries)
                ->sortByDesc(fn ($item) => $item->relevance_score ?? 0)
                ->values(),
            'did_you_mean' => $didYouMean,
            'search_mode' => 'fuzzy_fallback',
        ];
    }

    /**
     * Book teglar: book_tags.tag_name_uz / tag_name_ru / tag_name_en / tag_name_ja
     */
    private function applyBookTagFilter($query, array $patterns): object
    {
        return $query->whereHas('tags', function ($t) use ($patterns) {
            $t->where(function ($w) use ($patterns) {
                foreach ($patterns as $i => $p) {
                    $method = $i === 0 ? 'where' : 'orWhere';
                    $w->$method(function ($inner) use ($p) {
                        $inner->where('tag_name_uz', 'LIKE', $p)
                              ->orWhere('tag_name_ru', 'LIKE', $p)
                              ->orWhere('tag_name_en', 'LIKE', $p)
                              ->orWhere('tag_name_ja', 'LIKE', $p);
                    });
                }
            });
        });
    }

    /**
     * Stationery teglar: stationery_tags.name_uz / name_ru / name_en / name_ja
     */
    private function applyStationeryTagFilter($query, array $patterns): object
    {
        return $query->whereHas('tags', function ($t) use ($patterns) {
            $t->where(function ($w) use ($patterns) {
                foreach ($patterns as $i => $p) {
                    $method = $i === 0 ? 'where' : 'orWhere';
                    $w->$method(function ($inner) use ($p) {
                        $inner->where('name_uz', 'LIKE', $p)
                              ->orWhere('name_ru', 'LIKE', $p)
                              ->orWhere('name_en', 'LIKE', $p)
                              ->orWhere('name_ja', 'LIKE', $p);
                    });
                }
            });
        });
    }

    // ─────────────────────────────────────────────
    // FORMAT PRODUCT
    // ─────────────────────────────────────────────
    private function formatProduct($product, $user = null): ?array
    {
        try {
            $isBook = $product instanceof Books;
            return ProductPayloadFormatter::format($product, [
                'user' => $user,
                'type' => $isBook ? 'book' : 'stationery',
                'category_format' => 'object',
                'extra' => [
                    'relevance_score' => (float) ($product->relevance_score ?? 0),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('formatProduct error', ['id' => $product->id ?? null]);
            return null;
        }
    }

    private function extractResultName(array $formattedItems): ?string
    {
        $first = collect($formattedItems)->first();
        if (!$first) return null;
        return trim($first['name'] ?? '') ?: null;
    }

    // ─────────────────────────────────────────────
    // HISTORY UPSERT
    // ─────────────────────────────────────────────
    private function upsertHistory(
        Request $request,
        string  $query,
        bool    $isDraft,
        ?string $resultName = null
    ): void {
        try {
            $user      = auth('sanctum')->user();
            $sessionId = $user ? null : $request->header('X-Session-Id');
            $cleanText = mb_strtolower(trim($query));

            if (mb_strlen($cleanText) < 2) return;

            if ($isDraft) {
                $existing = SearchHistory::where('text', $cleanText)
                    ->where('is_draft', true)->first();
                if ($existing) {
                    $existing->increment('search_count');
                    $existing->touch();
                } else {
                    SearchHistory::create([
                        'user_id'      => $user?->id,
                        'session_id'   => $sessionId,
                        'text'         => $cleanText,
                        'result_name'  => null,
                        'is_draft'     => true,
                        'search_count' => 1,
                    ]);
                }
                return;
            }

            if ($resultName) {
                $existing = SearchHistory::where('result_name', $resultName)
                    ->where('is_draft', false)->first();
                if ($existing) {
                    $existing->increment('search_count');
                    $existing->touch();
                    Cache::forget('search_trending');
                    $this->ensurePersonalHistory($user, $sessionId, $cleanText, $resultName);
                    return;
                }
            }

            SearchHistory::create([
                'user_id'      => $user?->id,
                'session_id'   => $sessionId,
                'text'         => $cleanText,
                'result_name'  => $resultName,
                'is_draft'     => false,
                'search_count' => 1,
            ]);
            Cache::forget('search_trending');
        } catch (\Throwable $e) {
            Log::error('upsertHistory error', ['query' => $query, 'error' => $e->getMessage()]);
        }
    }

    private function ensurePersonalHistory($user, ?string $sessionId, string $cleanText, string $resultName): void
    {
        $q = SearchHistory::where('result_name', $resultName)->where('is_draft', false);
        if ($user) {
            $q->where('user_id', $user->id);
        } else {
            $q->where('session_id', $sessionId);
        }
        if (!$q->exists()) {
            SearchHistory::create([
                'user_id'      => $user?->id,
                'session_id'   => $sessionId,
                'text'         => $cleanText,
                'result_name'  => $resultName,
                'is_draft'     => false,
                'search_count' => 0,
            ]);
        } else {
            $q->update(['updated_at' => now()]);
        }
    }

    // ─────────────────────────────────────────────
    // MAIN SEARCH
    // ─────────────────────────────────────────────
    public function search(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'q'            => 'nullable|string|max:255',
            'type'         => 'nullable|string|in:book,stationery,all',
            'category_id'  => 'nullable|integer',
            'sort'         => 'nullable|string|in:relevance,popular,newest,price_asc,price_desc,alpha_asc,alpha_desc,discount',
            'page'         => 'nullable|integer|min:1',
            'min_price'    => 'nullable|numeric|min:0',
            'max_price'    => 'nullable|numeric|min:0',
            'save_history' => 'nullable|in:0,1',
            'draft'        => 'nullable|in:0,1',
            'seller_id'    => 'nullable|integer',
            'tag'          => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $query       = trim($request->query('q', ''));
        $tag         = trim($request->query('tag', ''));  // ← teg qidirish
        $type        = $request->query('type', 'all');
        $categoryId  = $request->query('category_id');
        $sort        = $request->query('sort', 'relevance');
        $page        = max(1, (int)$request->query('page', 1));
        $perPage     = 20;
        $saveHistory = $request->query('save_history') === '1';
        $sellerId    = $request->query('seller_id') ? (int)$request->query('seller_id') : null;
        $forceDraft  = $request->query('draft') === '1';
        $minPrice    = $request->query('min_price') ? (float)$request->query('min_price') : null;
        $maxPrice    = $request->query('max_price') ? (float)$request->query('max_price') : null;

        // Qidiruv so'z yoki teg yoki kategoriya bo'lishi kerak
        if (mb_strlen($query) < 2 && mb_strlen($tag) < 2 && !$categoryId) {
            return response()->json([
                'status'     => 'success',
                'data'       => [],
                'pagination' => ['has_more' => false],
            ]);
        }

        try {
            $user     = auth('sanctum')->user();
            $analyzed = mb_strlen($query) >= 2 ? $this->analyzeQuery($query) : null;
            $didYouMean = null;
            $searchMode = 'default';

            $bookPaginator = null;
            $statPaginator = null;

            if (in_array($type, ['book', 'all'])) {
                $bookPaginator = $this->queryBooksSmart(
                    $analyzed, $tag, $categoryId, $sort,
                    $page, $perPage, $query, $sellerId, $minPrice, $maxPrice
                );
            }
            if (in_array($type, ['stationery', 'all'])) {
                $statPaginator = $this->queryStationerySmart(
                    $analyzed, $tag, $categoryId, $sort,
                    $page, $perPage, $query, $sellerId, $minPrice, $maxPrice
                );
            }

            $items = collect();
            if ($bookPaginator) {
                $items = $items->merge(
                    collect($bookPaginator->items())
                        ->map(fn($p) => $this->formatProduct($p, $user))
                );
            }
            if ($statPaginator) {
                $items = $items->merge(
                    collect($statPaginator->items())
                        ->map(fn($p) => $this->formatProduct($p, $user))
                );
            }

            if ($sort === 'relevance') {
                $items = $items->sortByDesc('relevance_score')->values();
            }

            $total         = ($bookPaginator?->total() ?? 0) + ($statPaginator?->total() ?? 0);
            $hasMore       = ($bookPaginator?->hasMorePages() ?? false)
                          || ($statPaginator?->hasMorePages() ?? false);
            $filteredItems = $items->filter()->values();

            if ($filteredItems->count() < 4 && mb_strlen($query) >= 3) {
                $fuzzyFallback = $this->fuzzyFallbackSearch(
                    $query,
                    $type,
                    $sellerId,
                    $categoryId,
                    $minPrice,
                    $maxPrice
                );

                $didYouMean = $fuzzyFallback['did_you_mean'] ?? null;
                $searchMode = $fuzzyFallback['search_mode'] ?? 'default';

                $fallbackItems = collect($fuzzyFallback['items'] ?? [])
                    ->map(fn($p) => $this->formatProduct($p, $user))
                    ->filter()
                    ->values();

                if ($fallbackItems->isNotEmpty()) {
                    if ($filteredItems->isEmpty()) {
                        $filteredItems = $fallbackItems;
                        $total = $fallbackItems->count();
                        $hasMore = false;
                    } else {
                        $existingKeys = $filteredItems
                            ->map(fn ($item) => ($item['type'] ?? '') . ':' . ($item['id'] ?? ''))
                            ->all();

                        $filteredItems = $filteredItems
                            ->merge(
                                $fallbackItems->filter(function ($item) use ($existingKeys) {
                                    $key = ($item['type'] ?? '') . ':' . ($item['id'] ?? '');
                                    return !in_array($key, $existingKeys, true);
                                })
                            )
                            ->take(max($perPage, 12))
                            ->values();

                        $total = max($total, $filteredItems->count());
                    }
                }
            }

            // ── Semantik (vector) fallback ─────────────────────────────
            // Matnli + fuzzy qidiruv ham yetarli natija bermasa,
            // ma'no bo'yicha eng yaqin mahsulotlarni qidiramiz.
            if ($filteredItems->count() < 4 && mb_strlen($query) >= 3) {
                try {
                    $vectorType = in_array($type, ['book', 'stationery'], true) ? $type : 'both';

                    $semantic = app(\App\Services\VectorSearchService::class)
                        ->search($query, $vectorType, 12, 0.35);

                    if ($sellerId)          $semantic = $semantic->where('seller_id', $sellerId)->values();
                    if ($categoryId)        $semantic = $semantic->where('category_id', (int) $categoryId)->values();
                    if ($minPrice !== null) $semantic = $semantic->filter(fn ($p) => (float) $p->price >= $minPrice)->values();
                    if ($maxPrice !== null) $semantic = $semantic->filter(fn ($p) => (float) $p->price <= $maxPrice)->values();

                    $semanticItems = $semantic
                        ->map(fn ($p) => $this->formatProduct($p, $user))
                        ->filter()
                        ->values();

                    if ($semanticItems->isNotEmpty()) {
                        $existingKeys = $filteredItems
                            ->map(fn ($item) => ($item['type'] ?? '') . ':' . ($item['id'] ?? ''))
                            ->all();

                        $newItems = $semanticItems->filter(function ($item) use ($existingKeys) {
                            $key = ($item['type'] ?? '') . ':' . ($item['id'] ?? '');
                            return !in_array($key, $existingKeys, true);
                        });

                        if ($newItems->isNotEmpty()) {
                            $filteredItems = $filteredItems
                                ->merge($newItems)
                                ->take(max($perPage, 12))
                                ->values();

                            $total      = max($total, $filteredItems->count());
                            $searchMode = $searchMode === 'default' ? 'semantic' : $searchMode . '+semantic';
                        }
                    }
                } catch (\Throwable $e) {
                    Log::warning('Semantic search fallback failed', [
                        'query' => $query,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // History — faqat matn qidiruv uchun, teg emas
            if ($saveHistory && mb_strlen($query) >= 2 && $page === 1) {
                if ($filteredItems->isNotEmpty()) {
                    $resultName = $this->extractResultName($filteredItems->toArray());
                    $this->upsertHistory($request, $query, false, $resultName);
                } else {
                    $this->upsertHistory($request, $query, true, null);
                }
            } elseif ($forceDraft && mb_strlen($query) >= 2) {
                $this->upsertHistory($request, $query, true, null);
            }

            return response()->json([
                'status'     => 'success',
                'data'       => $filteredItems->toArray(),
                'did_you_mean' => $didYouMean,
                'search_mode' => $searchMode,
                'pagination' => [
                    'current_page' => $page,
                    'per_page'     => $perPage,
                    'total'        => $total,
                    'has_more'     => $hasMore,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Search error', ['query' => $query, 'error' => $e->getMessage()]);
            return response()->json(['status' => 'error', 'message' => 'Server xatosi'], 500);
        }
    }

    // ─────────────────────────────────────────────
    // SMART QUERIES
    // ─────────────────────────────────────────────

    /**
     * Kitoblar uchun smart qidiruv:
     *   1. Matnli qidiruv (FULLTEXT + LIKE) — name, author, description
     *   2. Teg qidirish — name_uz/ru/en/ja (har qanday tildan)
     *   3. Ikkalasi bir vaqtda bo'lishi mumkin (AND mantiq)
     */
    private function queryBooksSmart(
        ?array $analyzed,
        string $tag,
        $categoryId,
        string $sort,
        int    $page,
        int    $perPage,
        string $rawQuery,
        ?int   $sellerId   = null,
        ?float $minPrice   = null,
        ?float $maxPrice   = null
    ) {
        $q = $this->visibleBooks(['category', 'seller', 'tags']);

        if ($sellerId) $q->where('seller_id', $sellerId);
        if ($categoryId) $q->where('category_id', $categoryId);
        if ($minPrice !== null) $q->where('price', '>=', $minPrice);
        if ($maxPrice !== null) $q->where('price', '<=', $maxPrice);

        $hasText = $analyzed !== null && !empty($analyzed['boolean']);
        $hasTag  = mb_strlen($tag) >= 2;

        if ($hasText) {
            // ── Matnli qidiruv ────────────────────────────────────────
            $bool       = $analyzed['boolean'];
            $searchPatterns = $this->buildLikePatterns($rawQuery);

            $authorColumnAvailable = Books::hasAuthorColumn();
            $fulltextColumns = $authorColumnAvailable
                ? ['name', 'author', 'description']
                : ['name', 'description'];
            $matchColumnsSql = implode(', ', $fulltextColumns);
            $hasFulltext = $this->hasFulltextIndex('books', $fulltextColumns);

            if ($hasFulltext) {
                $q->where(function ($w) use ($bool, $searchPatterns, $matchColumnsSql) {
                    $w->whereRaw(
                        "MATCH({$matchColumnsSql}) AGAINST(? IN BOOLEAN MODE)", [$bool]
                    )->orWhere(function ($or) use ($searchPatterns) {
                        foreach ($searchPatterns as $i => $pattern) {
                            $method = $i === 0 ? 'where' : 'orWhere';
                            $or->$method(function ($inner) use ($pattern) {
                                $inner->where('name', 'LIKE', $pattern)
                                      ->orWhere('artikul', 'LIKE', $pattern)
                                      ->orWhereHas('authorProfile', fn ($authorQuery) => $authorQuery->where('name', 'LIKE', $pattern))
                                      ->orWhere('description', 'LIKE', $pattern);
                            });
                        }
                    });
                })->selectRaw(
                    "books.*,
                     MATCH({$matchColumnsSql}) AGAINST(? IN BOOLEAN MODE) * 10 +
                     LEAST(totalSalesWeek * 3, 300) +
                     LEAST(totalSales, 100) AS relevance_score",
                    [$bool]
                );
            } else {
                $q->where(function ($w) use ($searchPatterns) {
                    foreach ($searchPatterns as $i => $pattern) {
                        $method = $i === 0 ? 'where' : 'orWhere';
                        $w->$method(function ($inner) use ($pattern) {
                            $inner->where('name', 'LIKE', $pattern)
                                  ->orWhere('artikul', 'LIKE', $pattern)
                                  ->orWhereHas('authorProfile', fn ($authorQuery) => $authorQuery->where('name', 'LIKE', $pattern))
                                  ->orWhere('description', 'LIKE', $pattern);
                        });
                    }
                })->selectRaw(
                    "books.*,
                     LEAST(totalSalesWeek * 3, 300) +
                     LEAST(totalSales, 100) AS relevance_score"
                );
            }
        } else {
            $q->selectRaw('books.*, 0 AS relevance_score');
        }

        if ($hasTag) {
            // ── Teg qidirish — uz/ru/en/ja barcha tillarda ───────────
            $tagPatterns = $this->buildLikePatterns($tag);
            $this->applyBookTagFilter($q, $tagPatterns);
        }

        // Hech qanday qidiruv yo'q bo'lsa (faqat category/seller filter)
        // → hamma ko'rinuvchi mahsulot

        $this->applySortToQuery($q, $sort, 'book');

        return $q->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Kantselyariya uchun smart qidiruv:
     *   1. Matnli qidiruv — name, description, material
     *   2. Teg qidirish — name_uz/ru/en/ja
     */
    private function queryStationerySmart(
        ?array $analyzed,
        string $tag,
        $categoryId,
        string $sort,
        int    $page,
        int    $perPage,
        string $rawQuery,
        ?int   $sellerId   = null,
        ?float $minPrice   = null,
        ?float $maxPrice   = null
    ) {
        $q = $this->visibleStationeries(['category', 'seller', 'tags', 'variants']);

        if ($sellerId) $q->where('seller_id', $sellerId);
        if ($categoryId) $q->where('category_id', $categoryId);
        if ($minPrice !== null) $q->where('price', '>=', $minPrice);
        if ($maxPrice !== null) $q->where('price', '<=', $maxPrice);

        $hasText = $analyzed !== null && !empty($analyzed['boolean']);
        $hasTag  = mb_strlen($tag) >= 2;

        if ($hasText) {
            $bool       = $analyzed['boolean'];
            $searchPatterns = $this->buildLikePatterns($rawQuery);

            // FULLTEXT index mavjudligini tekshiramiz
            // Agar yo'q bo'lsa — faqat LIKE bilan ishlaymiz
            $hasFulltext = $this->hasFulltextIndex('stationeries', ['name', 'description', 'material']);

            if ($hasFulltext) {
                $q->where(function ($w) use ($bool, $searchPatterns) {
                    $w->whereRaw(
                        "MATCH(name, description, material) AGAINST(? IN BOOLEAN MODE)", [$bool]
                    )->orWhere(function ($or) use ($searchPatterns) {
                        foreach ($searchPatterns as $i => $pattern) {
                            $method = $i === 0 ? 'where' : 'orWhere';
                            $or->$method(function ($inner) use ($pattern) {
                                $inner->where('name', 'LIKE', $pattern)
                                      ->orWhere('artikul', 'LIKE', $pattern)
                                      ->orWhere('description', 'LIKE', $pattern)
                                      ->orWhere('material', 'LIKE', $pattern);
                            });
                        }
                    });
                })->selectRaw(
                    "stationeries.*,
                     MATCH(name, description, material) AGAINST(? IN BOOLEAN MODE) * 10 +
                     LEAST(totalSalesWeek * 3, 300) AS relevance_score",
                    [$bool]
                );
            } else {
                // FULLTEXT yo'q — faqat LIKE
                $q->where(function ($w) use ($searchPatterns) {
                    foreach ($searchPatterns as $i => $pattern) {
                        $method = $i === 0 ? 'where' : 'orWhere';
                        $w->$method(function ($inner) use ($pattern) {
                            $inner->where('name', 'LIKE', $pattern)
                                  ->orWhere('artikul', 'LIKE', $pattern)
                                  ->orWhere('description', 'LIKE', $pattern)
                                  ->orWhere('material', 'LIKE', $pattern);
                        });
                    }
                })->selectRaw(
                    "stationeries.*,
                     LEAST(totalSalesWeek * 3, 300) AS relevance_score"
                );
            }
        } else {
            $q->selectRaw('stationeries.*, 0 AS relevance_score');
        }

        if ($hasTag) {
            $tagPatterns = $this->buildLikePatterns($tag);
            $this->applyStationeryTagFilter($q, $tagPatterns);
        }

        $this->applySortToQuery($q, $sort, 'stationery');

        return $q->paginate($perPage, ['*'], 'page', $page);
    }

    // ─────────────────────────────────────────────
    // SUGGESTIONS
    // ─────────────────────────────────────────────
    // ─────────────────────────────────────────────
    // RASM ORQALI QIDIRUV
    // ─────────────────────────────────────────────

    /**
     * POST /search/image — rasm yuborilsa, undan mahsulot topadi.
     *
     * Kaskad (aniqroqdan noaniqroqqa):
     *   1. ISBN rasmda ko'rinsa → aniq ISBN qidiruvi (eng ishonchli)
     *   2. Aniqlangan nom/muallif → LIKE qidiruvi
     *   3. Semantik (vector) qidiruv — search_query bo'yicha
     *
     * Javob: data (mahsulotlar) + recognized (rasmdan aniqlangan ma'lumot,
     * ilova "Rasmda: X" ko'rsatishi va inputga yozib qo'yishi uchun).
     */
    public function imageSearch(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,jpg,png,webp,heic|max:8192',
            'q'     => 'nullable|string|max:255',
        ]);

        try {
            $user = auth('sanctum')->user();
            $extraText = trim((string) $request->input('q', ''));

            // Rasmni saqlamasdan to'g'ridan-to'g'ri base64 qilamiz
            $file = $request->file('image');
            $mime = $file->getMimeType() ?: 'image/jpeg';
            $dataUrl = 'data:' . $mime . ';base64,' . base64_encode($file->get());

            $analysis = app(\App\Services\OpenAIService::class)
                ->analyzeProductImage($dataUrl, $extraText);

            $recognized = [
                'product_type' => $analysis['product_type'],
                'title'        => $analysis['title'],
                'author'       => $analysis['author'],
                'isbn'         => $analysis['isbn'] ?? null,
                'description'  => $analysis['description'],
                'search_query' => $analysis['search_query'],
            ];

            $results = collect();
            $seen = [];
            $push = function ($items, string $matchType) use (&$results, &$seen) {
                foreach ($items as $item) {
                    $key = ($item->_type ?? ($item instanceof Books ? 'book' : 'stationery')) . ':' . $item->id;
                    if (isset($seen[$key])) {
                        continue;
                    }
                    $seen[$key] = true;
                    $item->_match_type = $matchType;
                    $results->push($item);
                }
            };

            // ── 1. ISBN — aniq moslik ──────────────────────────────────
            if (! empty($analysis['isbn'])) {
                $isbnBooks = $this->visibleBooks(['category', 'seller', 'tags'])
                    ->whereIsbn($analysis['isbn'])
                    ->limit(5)
                    ->get()
                    ->each(fn ($b) => $b->_type = 'book');

                $push($isbnBooks, 'isbn');
            }

            // ── 2. Aniqlangan nom/muallif — LIKE ───────────────────────
            $type = $analysis['product_type'];

            if (in_array($type, ['book', 'other'], true)) {
                $q = $this->visibleBooks(['category', 'seller', 'tags']);
                $applied = false;

                $q->where(function ($sub) use ($analysis, &$applied) {
                    if (filled($analysis['title']) && mb_strlen($analysis['title']) >= 3) {
                        $sub->orWhere('name', 'LIKE', '%' . $analysis['title'] . '%');
                        $applied = true;
                    }
                    if (filled($analysis['author']) && mb_strlen($analysis['author']) >= 3) {
                        $sub->orWhere('author', 'LIKE', '%' . $analysis['author'] . '%');
                        $applied = true;
                    }
                });

                if ($applied) {
                    $push($q->limit(10)->get()->each(fn ($b) => $b->_type = 'book'), 'text');
                }
            }

            if (in_array($type, ['stationery', 'other'], true) && filled($analysis['title'])) {
                $stationeries = $this->visibleStationeries(['category', 'seller', 'tags'])
                    ->where('name', 'LIKE', '%' . $analysis['title'] . '%')
                    ->limit(10)
                    ->get()
                    ->each(fn ($s) => $s->_type = 'stationery');

                $push($stationeries, 'text');
            }

            // ── 3. Semantik (vector) qidiruv ───────────────────────────
            $searchQuery = trim(implode(' ', array_filter([$analysis['search_query'], $extraText])));

            if ($searchQuery !== '' && $results->count() < 12) {
                $vectorType = match ($type) {
                    'book'       => 'book',
                    'stationery' => 'stationery',
                    default      => 'both',
                };

                $semantic = app(\App\Services\VectorSearchService::class)
                    ->search($searchQuery, $vectorType, 12, 0.25);

                $push($semantic, 'semantic');
            }

            $items = $results
                ->take(20)
                ->map(fn ($p) => $this->formatProduct($p, $user))
                ->filter()
                ->values();

            return response()->json([
                'status'     => 'success',
                'recognized' => $recognized,
                'data'       => $items->toArray(),
                'pagination' => ['has_more' => false],
            ]);
        } catch (\Throwable $e) {
            Log::error('Image search error', ['error' => $e->getMessage()]);

            return response()->json(['status' => 'error', 'message' => 'Rasmni qayta ishlashda xatolik'], 500);
        }
    }

    public function suggestions(Request $request)
    {
        $query    = trim($request->query('q', ''));
        $sellerId = $request->query('seller_id') ? (int)$request->query('seller_id') : null;

        if (mb_strlen($query) < 1) {
            return response()->json(['status' => 'success', 'data' => []]);
        }

        $patterns = $this->buildLikePatterns($query);
        $sugg     = [];
        $seen     = [];

        // ── Kitoblar: nom + muallif + teglar ─────────────────────────
        $bookQuery = $this->visibleBooks(['tags'])
            ->with('authorProfile:id,name')
            ->when($sellerId, fn($q) => $q->where('seller_id', $sellerId))
            ->where(function ($w) use ($patterns) {
                $w->where(function ($nameAuthor) use ($patterns) {
                    // Nom va muallif bo'yicha
                    foreach ($patterns as $i => $p) {
                        $method = $i === 0 ? 'where' : 'orWhere';
                        $nameAuthor->$method(function ($inner) use ($p) {
                            $inner->where('name', 'LIKE', $p)
                                  ->orWhere('artikul', 'LIKE', $p)
                                  ->orWhereHas('authorProfile', fn ($authorQuery) => $authorQuery->where('name', 'LIKE', $p));
                        });
                    }
                })->orWhereHas('tags', function ($t) use ($patterns) {
                    // Teg bo'yicha — book_tags: tag_name_uz/ru/en/ja
                    $t->where(function ($tw) use ($patterns) {
                        foreach ($patterns as $i => $p) {
                            $method = $i === 0 ? 'where' : 'orWhere';
                            $tw->$method(function ($inner) use ($p) {
                                $inner->where('tag_name_uz', 'LIKE', $p)
                                      ->orWhere('tag_name_ru', 'LIKE', $p)
                                      ->orWhere('tag_name_en', 'LIKE', $p)
                                      ->orWhere('tag_name_ja', 'LIKE', $p);
                            });
                        }
                    });
                });
            })
            ->select('name', 'artikul', 'author_id')
            ->orderByDesc('totalSalesWeek')
            ->limit(8)
            ->get();

        foreach ($bookQuery as $b) {
            $name = trim($b->name ?? '');
            if ($name && !isset($seen[$name])) {
                $sugg[]      = ['text' => $name, 'type' => 'book'];
                $seen[$name] = true;
            }
            $author = trim($b->author ?? '');
            if ($author && !isset($seen[$author])) {
                $sugg[]        = ['text' => $author, 'type' => 'author'];
                $seen[$author] = true;
            }
        }

        // ── Teglar — alohida suggestion sifatida ────────────────────
        // Foydalanuvchi tilidan qat'i nazar barcha tillarda qidiradi
        // va teg nomini original tilida qaytaradi
        // book_tags: tag_name_uz/ru/en/ja ustunlari
        // stationery_tags: name_uz/ru/en/ja ustunlari
        // Ikkalasini UNION qilamiz
        $tagResults = DB::table('book_tags')
            ->where(function ($w) use ($patterns) {
                foreach ($patterns as $i => $p) {
                    $method = $i === 0 ? 'where' : 'orWhere';
                    $w->$method(function ($inner) use ($p) {
                        $inner->where('tag_name_uz', 'LIKE', $p)
                              ->orWhere('tag_name_ru', 'LIKE', $p)
                              ->orWhere('tag_name_en', 'LIKE', $p)
                              ->orWhere('tag_name_ja', 'LIKE', $p);
                    });
                }
            })
            ->select(
                'tag_name_uz as name_uz',
                'tag_name_ru as name_ru',
                'tag_name_en as name_en',
                'tag_name_ja as name_ja'
            )
            ->distinct()
            ->limit(5)
            ->get();

        // Stationery teglarini ham qo'shamiz
        $statTagResults = DB::table('stationery_tags')
            ->where(function ($w) use ($patterns) {
                foreach ($patterns as $i => $p) {
                    $method = $i === 0 ? 'where' : 'orWhere';
                    $w->$method(function ($inner) use ($p) {
                        $inner->where('name_uz', 'LIKE', $p)
                              ->orWhere('name_ru', 'LIKE', $p)
                              ->orWhere('name_en', 'LIKE', $p)
                              ->orWhere('name_ja', 'LIKE', $p);
                    });
                }
            })
            ->select('name_uz', 'name_ru', 'name_en', 'name_ja')
            ->distinct()
            ->limit(5)
            ->get();

        $allTagResults = $tagResults->merge($statTagResults);

        foreach ($allTagResults as $tag) {
            // Qaysi tilda moslik bo'lsa shu tilni ko'rsatamiz,
            // prioritet: uz → en → ru → ja
            $display = $tag->name_uz
                ?? $tag->name_en
                ?? $tag->name_ru
                ?? $tag->name_ja;

            if ($display && !isset($seen['tag_' . $display])) {
                $sugg[]                   = ['text' => $display, 'type' => 'tag'];
                $seen['tag_' . $display]  = true;
            }
        }

        // ── Kantselyariya: nom + teglar ───────────────────────────────
        $statQuery = $this->visibleStationeries(['tags'])
            ->when($sellerId, fn($q) => $q->where('seller_id', $sellerId))
            ->where(function ($w) use ($patterns) {
                $w->where(function ($namePart) use ($patterns) {
                    foreach ($patterns as $i => $p) {
                        $method = $i === 0 ? 'where' : 'orWhere';
                        $namePart->$method(function ($inner) use ($p) {
                            $inner->where('name', 'LIKE', $p)
                                ->orWhere('artikul', 'LIKE', $p);
                        });
                    }
                })->orWhereHas('tags', function ($t) use ($patterns) {
                    $t->where(function ($tw) use ($patterns) {
                        foreach ($patterns as $i => $p) {
                            $method = $i === 0 ? 'where' : 'orWhere';
                            $tw->$method(function ($inner) use ($p) {
                                $inner->where('name_uz', 'LIKE', $p)
                                      ->orWhere('name_ru', 'LIKE', $p)
                                      ->orWhere('name_en', 'LIKE', $p)
                                      ->orWhere('name_ja', 'LIKE', $p);
                            });
                        }
                    });
                });
            })
            ->select('name', 'artikul')
            ->orderByDesc('totalSalesWeek')
            ->limit(5)
            ->get();

        foreach ($statQuery as $s) {
            $name = trim($s->name ?? '');
            if ($name && !isset($seen[$name])) {
                $sugg[]      = ['text' => $name, 'type' => 'stationery'];
                $seen[$name] = true;
            }
        }

        $result = array_slice($sugg, 0, 12);

        if (empty($result) && mb_strlen($query) >= 2) {
            $this->upsertHistory($request, $query, true, null);
        }

        return response()->json(['status' => 'success', 'data' => $result]);
    }

    // ─────────────────────────────────────────────
    // CATEGORY BY SELLERS
    // ─────────────────────────────────────────────
    public function categoryBySellers(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_id' => 'required|integer',
            'type'        => 'nullable|string|in:book,stationery,all',
            'sort'        => 'nullable|string|in:popular,newest,price_asc,price_desc,alpha_asc,alpha_desc,discount',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $categoryId = (int)$request->query('category_id');
        $type       = $request->query('type', 'book');
        $sort       = $request->query('sort', 'popular');
        $user       = auth('sanctum')->user();

        try {
            $sellers = collect();

            if (in_array($type, ['book', 'all'])) {
                $bookQuery = $this->visibleBooks(['category', 'seller', 'tags'])
                    ->where('category_id', $categoryId);
                $this->applySortToQuery($bookQuery, $sort, 'book');
                $books   = $bookQuery->get();
                $grouped = $books->groupBy(fn($b) => $b->seller?->id ?? 0);

                foreach ($grouped as $sellerId => $sellerBooks) {
                    if (!$sellerId) continue;
                    $sellerInfo = $sellerBooks->first()->seller;
                    $sellers->push([
                        'seller_id'    => $sellerInfo->id,
                        'shop_name'    => $sellerInfo->shop_name ?? '',
                        'photo'        => $sellerInfo->photo ?? '',
                        'isVerified'   => (bool)($sellerInfo->isVerified ?? false),
                        'books'        => $sellerBooks->take(15)
                            ->map(fn($p) => $this->formatProduct($p, $user))
                            ->filter()->values()->toArray(),
                        'stationeries' => [],
                    ]);
                }
            }

            if (in_array($type, ['stationery', 'all'])) {
                $statQuery = $this->visibleStationeries(['category', 'seller', 'tags', 'variants'])
                    ->where('category_id', $categoryId);
                $this->applySortToQuery($statQuery, $sort, 'stationery');
                $stationeries = $statQuery->get();
                $grouped      = $stationeries->groupBy(fn($s) => $s->seller?->id ?? 0);

                foreach ($grouped as $sellerId => $sellerStats) {
                    if (!$sellerId) continue;
                    $sellerInfo     = $sellerStats->first()->seller;
                    $existingIndex  = $sellers->search(fn($s) => $s['seller_id'] === $sellerInfo->id);
                    $formattedStats = $sellerStats->take(15)
                        ->map(fn($p) => $this->formatProduct($p, $user))
                        ->filter()->values()->toArray();

                    if ($existingIndex !== false) {
                        $existing                 = $sellers[$existingIndex];
                        $existing['stationeries'] = $formattedStats;
                        $sellers[$existingIndex]  = $existing;
                    } else {
                        $sellers->push([
                            'seller_id'    => $sellerInfo->id,
                            'shop_name'    => $sellerInfo->shop_name ?? '',
                            'photo'        => $sellerInfo->photo ?? '',
                            'isVerified'   => (bool)($sellerInfo->isVerified ?? false),
                            'books'        => [],
                            'stationeries' => $formattedStats,
                        ]);
                    }
                }
            }

            $sorted = $sellers
                ->filter(fn($s) => !empty($s['books']) || !empty($s['stationeries']))
                ->sortByDesc(fn($s) =>
                    collect($s['books'])->sum('weekly_sales') +
                    collect($s['stationeries'])->sum('weekly_sales')
                )
                ->values();

            return response()->json(['status' => 'success', 'data' => $sorted->toArray()]);
        } catch (\Throwable $e) {
            Log::error('categoryBySellers error', [
                'category_id' => $categoryId,
                'error'       => $e->getMessage(),
            ]);
            return response()->json(['status' => 'error', 'message' => 'Server xatosi'], 500);
        }
    }

    // ─────────────────────────────────────────────
    // HISTORY
    // ─────────────────────────────────────────────
    public function history(Request $request)
    {
        $user      = auth('sanctum')->user();
        $sessionId = $request->header('X-Session-Id');

        $histories = SearchHistory::when(
                $user,
                fn($q) => $q->where('user_id', $user->id),
                fn($q) => $q->where('session_id', $sessionId)
            )
            ->where('is_draft', false)
            ->orderByDesc('updated_at')
            ->limit(12)
            ->get(['text', 'result_name']);

        return response()->json(['status' => 'success', 'data' => $histories]);
    }

    public function clearHistory(Request $request)
    {
        $user      = auth('sanctum')->user();
        $sessionId = $request->header('X-Session-Id');

        SearchHistory::when(
            $user,
            fn($q) => $q->where('user_id', $user->id),
            fn($q) => $q->where('session_id', $sessionId)
        )
        ->where('is_draft', false)
        ->delete();

        return response()->json(['status' => 'success']);
    }

    // ─────────────────────────────────────────────
    // TRENDING
    // ─────────────────────────────────────────────
    public function trendingSearches()
    {
        $data = Cache::remember('search_trending', now()->addMinutes(30), function () {
            return SearchHistory::select(
                    DB::raw("NULLIF(result_name, '') as result_name"),
                    DB::raw('MIN(text) as text'),
                    DB::raw('SUM(search_count) as total_count'),
                    DB::raw('MAX(updated_at) as last_searched')
                )
                ->where('updated_at', '>=', now()->subDays(7))
                ->where('is_draft', false)
                ->where('search_count', '>', 0)
                ->where(function ($query) {
                    $query->whereNotNull('result_name')
                        ->orWhereNotNull('text');
                })
                ->groupBy('result_name')
                ->orderByDesc('total_count')
                ->orderByDesc('last_searched')
                ->limit(5)
                ->get();
        });

        return response()->json(['status' => 'success', 'data' => $data]);
    }

    // ─────────────────────────────────────────────
    // DRAFT SEARCHES (admin)
    // ─────────────────────────────────────────────
    public function draftSearches(Request $request)
    {
        $drafts = SearchHistory::where('is_draft', true)
            ->select(
                'text',
                DB::raw('SUM(search_count) as count'),
                DB::raw('MAX(updated_at) as last_searched')
            )
            ->groupBy('text')
            ->orderByDesc('count')
            ->orderByDesc('last_searched')
            ->paginate(50);

        return response()->json(['status' => 'success', 'data' => $drafts]);
    }

    // ─────────────────────────────────────────────
    // RECOMMENDATIONS
    // ─────────────────────────────────────────────
    public function recommendations(Request $request)
    {
        $user      = auth('sanctum')->user();
        $sessionId = $request->header('X-Session-Id');
        $type      = $request->query('type', 'all');

        $recentQueries = SearchHistory::when(
                $user,
                fn($q) => $q->where('user_id', $user->id),
                fn($q) => $q->where('session_id', $sessionId)
            )
            ->where('is_draft', false)
            ->orderByDesc('updated_at')
            ->limit(6)
            ->pluck('text')
            ->unique()
            ->take(4);

        $books        = collect();
        $stationeries = collect();

        if ($recentQueries->isEmpty()) {
            $books        = $this->getPopularBooks(12);
            $stationeries = $this->getPopularStationeries(8);
        } else {
            $combinedQuery = $recentQueries->implode(' ');
            $analyzed      = $this->analyzeQuery($combinedQuery);

            if (in_array($type, ['book', 'all'])) {
                $books = $this->visibleBooks(['category', 'seller', 'tags'])
                    ->where(function ($q) use ($analyzed, $combinedQuery) {
                        if (Books::hasAuthorColumn()) {
                            $q->whereRaw(
                                "MATCH(name, author, description) AGAINST(? IN NATURAL LANGUAGE MODE)",
                                [$analyzed['boolean']]
                            )
                            ->orWhereRaw(
                                "MATCH(name, author, description) AGAINST(? IN NATURAL LANGUAGE MODE)",
                                [$combinedQuery]
                            );
                        } else {
                            $q->whereRaw(
                                "MATCH(name, description) AGAINST(? IN NATURAL LANGUAGE MODE)",
                                [$analyzed['boolean']]
                            )
                            ->orWhereRaw(
                                "MATCH(name, description) AGAINST(? IN NATURAL LANGUAGE MODE)",
                                [$combinedQuery]
                            );
                        }

                        $q->orWhere('name', 'LIKE', "%{$combinedQuery}%")
                          ->orWhere('artikul', 'LIKE', "%{$combinedQuery}%")
                          ->orWhereHas('authorProfile', fn ($authorQuery) => $authorQuery->where('name', 'LIKE', "%{$combinedQuery}%"));
                    })
                    ->orderByDesc('totalSalesWeek')
                    ->limit(14)
                    ->get();
            }

            if (in_array($type, ['stationery', 'all'])) {
                $stationeries = $this->visibleStationeries(['category', 'seller', 'tags', 'variants'])
                    ->where(function ($q) use ($analyzed, $combinedQuery) {
                        $q->whereRaw(
                            "MATCH(name, description, material) AGAINST(? IN NATURAL LANGUAGE MODE)",
                            [$analyzed['boolean']]
                        )
                        ->orWhere('name', 'LIKE', "%{$combinedQuery}%")
                        ->orWhere('artikul', 'LIKE', "%{$combinedQuery}%");
                    })
                    ->orderByDesc('totalSalesWeek')
                    ->limit(8)
                    ->get();
            }
        }

        $items = $books->map(fn($p) => $this->formatProduct($p, $user))
            ->merge($stationeries->map(fn($p) => $this->formatProduct($p, $user)))
            ->filter()->shuffle()->values();

        if ($items->isEmpty()) {
            $books        = $this->getPopularBooks(12);
            $stationeries = $this->getPopularStationeries(8);
            $items        = $books->map(fn($p) => $this->formatProduct($p, $user))
                ->merge($stationeries->map(fn($p) => $this->formatProduct($p, $user)))
                ->filter()->values();
        }

        return response()->json([
            'status'   => 'success',
            'data'     => $items,
            'based_on' => $recentQueries->isEmpty() ? 'popular' : 'user_history',
        ]);
    }

    // ─────────────────────────────────────────────
    // ALL CATEGORIES
    // ─────────────────────────────────────────────
    public function allCategories()
    {
        try {
            $bookCats = BookCategories::where('is_active', 1)
                ->select('id', 'name_uz', 'name_ru', 'name_en', 'slug', 'icon')
                ->orderBy('name_uz')
                ->get();

            $statCats = StationeryCategory::where('is_active', 1)
                ->select('id', 'name_uz', 'name_ru', 'name_en')
                ->orderBy('name_uz')
                ->get();

            return response()->json([
                'status' => 'success',
                'data'   => ['book' => $bookCats, 'stationery' => $statCats],
            ]);
        } catch (\Throwable $e) {
            return response()->json(['status' => 'error', 'message' => 'Xato'], 500);
        }
    }

    // ─────────────────────────────────────────────
    // SORT HELPER
    // ─────────────────────────────────────────────

    /**
     * Jadvalda ma'lum ustunlar uchun FULLTEXT index borligini tekshiradi.
     * Natija cache da saqlanadi — har so'rovda DB ga urmasin.
     */
    private function hasFulltextIndex(string $table, array $columns): bool
    {
        $cacheKey = "fulltext_index_{$table}_" . implode('_', $columns);
        return Cache::remember($cacheKey, now()->addHours(24), function () use ($table, $columns) {
            try {
                $indexes = DB::select("SHOW INDEX FROM `{$table}` WHERE Index_type = 'FULLTEXT'");
                $indexedCols = collect($indexes)->pluck('Column_name')->toArray();
                foreach ($columns as $col) {
                    if (!in_array($col, $indexedCols)) return false;
                }
                return true;
            } catch (\Throwable $e) {
                return false;
            }
        });
    }

    private function applySortToQuery($q, string $sort, string $type): void
    {
        if ($sort === 'relevance') {
            $q->orderByDesc('relevance_score');
            return;
        }

        match ($sort) {
            'price_asc'  => $q->orderBy('price'),
            'price_desc' => $q->orderByDesc('price'),
            'alpha_asc'  => $q->orderByRaw('LOWER(name) ASC'),
            'alpha_desc' => $q->orderByRaw('LOWER(name) DESC'),
            'newest'     => $q->orderByDesc('id'),
            'popular'    => $q->orderByDesc('totalSalesWeek'),
            'discount'   => $type === 'book'
                ? $q->orderByRaw('IF(discountPrice > 0, price - discountPrice, 0) DESC')
                : $q->orderByRaw('IF(discount_price > 0, price - discount_price, 0) DESC'),
            default      => $q->orderByDesc('totalSalesWeek'),
        };
    }

    // ─────────────────────────────────────────────
    // POPULAR HELPERS
    // ─────────────────────────────────────────────
    private function getPopularBooks(int $limit = 12)
    {
        return $this->visibleBooks(['category', 'seller', 'tags'])
            ->orderByDesc('totalSalesWeek')
            ->limit($limit)
            ->get();
    }

    private function getPopularStationeries(int $limit = 8)
    {
        return $this->visibleStationeries(['category', 'seller', 'tags', 'variants'])
            ->orderByDesc('totalSalesWeek')
            ->limit($limit)
            ->get();
    }
}
