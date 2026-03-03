<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Books;
use App\Models\Stationery;
use App\Models\BookCategories;
use App\Models\StationeryCategory;
use App\Models\FavouriteProducts;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class SearchController extends Controller
{
    /**
     * Transliteratsiya - Lotin ↔ Kirill konvertatsiyasi (Mukammal)
     */
    private function transliterate($text)
    {
        // O'zbek lotin-kirill mapping (ikki tomonlama)
        $latinToCyrillic = [
            'oʻ' => 'ў', 'gʻ' => 'ғ', 'sh' => 'ш', 'ch' => 'ч', 'ng' => 'нг',
            'yo' => 'ё', 'yu' => 'ю', 'ya' => 'я', 'ts' => 'ц',
            'o\'' => 'ў', 'g\'' => 'ғ', // Alternative variants
            'a' => 'а', 'b' => 'б', 'v' => 'в', 'd' => 'д', 'e' => 'е',
            'j' => 'ж', 'z' => 'з', 'i' => 'и', 'y' => 'й', 'k' => 'к',
            'l' => 'л', 'm' => 'м', 'n' => 'н', 'o' => 'о', 'p' => 'п',
            'r' => 'р', 's' => 'с', 't' => 'т', 'u' => 'у', 'f' => 'ф',
            'x' => 'х', 'h' => 'ҳ', 'q' => 'қ', 'g' => 'г'
        ];

        $cyrillicToLatin = [
            'ў' => 'o\'', 'ғ' => 'g\'', 'ш' => 'sh', 'ч' => 'ch', 'нг' => 'ng',
            'ё' => 'yo', 'ю' => 'yu', 'я' => 'ya', 'ц' => 'ts',
            'а' => 'a', 'б' => 'b', 'в' => 'v', 'д' => 'd', 'е' => 'e',
            'ж' => 'j', 'з' => 'z', 'и' => 'i', 'й' => 'y', 'к' => 'k',
            'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o', 'п' => 'p',
            'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f',
            'х' => 'x', 'ҳ' => 'h', 'қ' => 'q', 'г' => 'g'
        ];

        $text = mb_strtolower($text, 'UTF-8');
        $variants = [$text];

        // Lotin -> Kirill (uzun pattern birinchi)
        $cyrillic = $text;
        foreach ($latinToCyrillic as $lat => $cyr) {
            $cyrillic = str_replace($lat, $cyr, $cyrillic);
        }
        if ($cyrillic !== $text) {
            $variants[] = $cyrillic;
        }

        // Kirill -> Lotin (uzun pattern birinchi)
        $latin = $text;
        foreach ($cyrillicToLatin as $cyr => $lat) {
            $latin = str_replace($cyr, $lat, $latin);
        }
        if ($latin !== $text) {
            $variants[] = $latin;
        }

        // Alternative o' va g' variantlari
        $altVariants = [];
        foreach ($variants as $variant) {
            $alt1 = str_replace("o'", 'oʻ', $variant);
            $alt2 = str_replace("g'", 'gʻ', $variant);
            $alt3 = str_replace('oʻ', "o'", $variant);
            $alt4 = str_replace('gʻ', "g'", $variant);
            $altVariants[] = $alt1;
            $altVariants[] = $alt2;
            $altVariants[] = $alt3;
            $altVariants[] = $alt4;
        }

        return array_unique(array_merge($variants, $altVariants));
    }

    /**
     * Fuzzy search - typo tolerant qidiruv
     */
    private function generateFuzzyVariants($word)
    {
        if (strlen($word) < 3) {
            return [$word];
        }

        $variants = [$word];
        
        // 1 harf xato uchun SQL LIKE pattern
        // Misol: "kitob" -> "k_tob", "ki_ob", "kit_b", "kito_"
        $len = mb_strlen($word);
        for ($i = 0; $i < $len; $i++) {
            $pattern = mb_substr($word, 0, $i) . '_' . mb_substr($word, $i + 1);
            $variants[] = $pattern;
        }

        return array_unique($variants);
    }

    /**
     * Qidiruv so'zlarini bo'lib tahlil qilish (Mukammal Algoritm)
     */
    private function analyzeSearchQuery($query)
    {
        $query = trim($query);
        $words = preg_split('/\s+/u', $query);
        
        $analyzed = [
            'original' => $query,
            'words' => $words,
            'variants' => [],
            'fuzzy_variants' => [],
            'is_short' => strlen($query) < 4,
            'word_count' => count($words)
        ];

        // 1. Har bir so'z uchun transliteratsiya
        foreach ($words as $word) {
            if (strlen($word) >= 2) {
                $analyzed['variants'] = array_merge(
                    $analyzed['variants'],
                    $this->transliterate($word)
                );
                
                // Fuzzy variants (typo tolerance)
                $analyzed['fuzzy_variants'] = array_merge(
                    $analyzed['fuzzy_variants'],
                    $this->generateFuzzyVariants($word)
                );
            }
        }

        // 2. To'liq qidiruv uchun
        $analyzed['variants'] = array_merge(
            $analyzed['variants'],
            $this->transliterate($query)
        );

        // 3. Takrorlanuvchilarni olib tashlash
        $analyzed['variants'] = array_unique($analyzed['variants']);
        $analyzed['fuzzy_variants'] = array_unique($analyzed['fuzzy_variants']);

        // 4. N-gram tokenization (3+ so'zlar uchun)
        if (count($words) >= 3) {
            $analyzed['bigrams'] = [];
            for ($i = 0; $i < count($words) - 1; $i++) {
                $bigram = $words[$i] . ' ' . $words[$i + 1];
                $analyzed['bigrams'] = array_merge(
                    $analyzed['bigrams'],
                    $this->transliterate($bigram)
                );
            }
        }

        return $analyzed;
    }

    /**
     * Mahsulotni formatlash - barcha tillarda
     */
    private function formatProduct($product, $user = null)
    {
        try {
            $isBook = $product instanceof Books;

            // Kategoriya
            $category = null;
            if ($product->category) {
                $category = [
                    'id'        => $product->category->id ?? null,
                    'name_uz'   => $product->category->name_uz ?? '',
                    'name_ru'   => $product->category->name_ru ?? '',
                    'name_en'   => $product->category->name_en ?? '',
                    'name_ja'   => $product->category->name_ja ?? '',
                    'slug'      => $product->category->slug ?? '',
                    'icon'      => $product->category->icon ?? '',
                ];
            }

            // Taglar
            $tags = [];
            if ($product->relationLoaded('tags') && $product->tags) {
                $tags = $product->tags->map(function($tag) {
                    return [
                        'uz' => $tag->tag_name_uz ?? '',
                        'ru' => $tag->tag_name_ru ?? '',
                        'en' => $tag->tag_name_en ?? '',
                        'ja' => $tag->tag_name_ja ?? '',
                    ];
                })->toArray();
            }

            // Sotuvchi
            $seller = null;
            if ($product->relationLoaded('seller') && $product->seller) {
                $seller = [
                    'seller_id' => $product->seller->id ?? null,
                    'shop_name' => $product->seller->shop_name ?? '',
                    'photo'     => $product->seller->photo ?? '',
                    'isVerified' => $product->seller?->isVerified ?? false,
                ];
            }

            // Favourite tekshirish
            $isFavourite = false;
            if ($user) {
                $isFavourite = FavouriteProducts::where('user_id', $user->id)
                    ->where('product_id', $product->id)
                    ->where('product_type', $isBook ? 'book' : 'stationery')
                    ->exists();
            }

            return [
                'id'             => $product->id,
                'name'           => $product->name ?? '',
                'author'         => $isBook ? ($product->author ?? '') : null,
                'material'       => !$isBook ? ($product->material ?? '') : null,
                'category_id'    => $product->category_id ?? null,
                'images'         => $product->images ?? [],
                'description'    => $product->description ?? '',
                'price'          => (double) ($product->price ?? 0),
                'count'          => $isBook ? ($product->count ?? 0) : ($product->stock ?? 0),
                'sales'          => $product->sales ?? 0,
                'weekly_sales'   => $product->totalSalesWeek ?? 0,
                'lang'           => $isBook ? ($product->lang ?? "O'zbek") : null,
                'langType'       => $isBook ? ($product->langType ?? '') : null,
                'coverType'      => $isBook ? ($product->coverType ?? 'Yumshoq') : null,
                'year'           => $isBook ? ($product->year ?? date('Y')) : null,
                'discountPrice'  => $isBook
                    ? ($product->discountPrice ?? null)
                    : ($product->discount_price ?? null),
                'product_type'   => $isBook ? 'book' : 'stationery',
                'favourite'      => $isFavourite,
                'category'       => $category,
                'tags'           => $tags,
                'seller'         => $seller,
                'relevance_score' => $product->relevance_score ?? 0,
                'variants' => !$isBook && $product->relationLoaded('variants')
                    ? $product->variants->map(function ($variant) {
                        return [
                            'id' => $variant->id,
                            'color_name' => $variant->color_name,
                            'image' => $variant->image_path ?? null,
                            'stock' => $variant->stock,
                        ];
                    })->toArray()
                    : null,
            ];
        } catch (\Exception $e) {
            Log::error('Format product error: ' . $e->getMessage(), [
                'product_id' => $product->id ?? null,
                'trace' => $e->getTraceAsString()
            ]);
            
            return null;
        }
    }

    /**
     * Universal qidiruv - Mukammal Algoritm
     * GET /api/search
     */
    public function search(Request $request)
    {
        try {
            // Validatsiya
            $validator = Validator::make($request->all(), [
                'q'           => 'nullable|string|max:255',
                'type'        => 'required|string|in:book,stationery,all',
                'category_id' => 'nullable|integer',
                'sort'        => 'nullable|string|in:newest,price_asc,price_desc,alpha_asc,alpha_desc,discount,relevance',
                'page'        => 'nullable|integer|min:1',
                'min_price'   => 'nullable|numeric|min:0',
                'max_price'   => 'nullable|numeric|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Validatsiya xatosi',
                    'errors'  => $validator->errors()
                ], 422);
            }

            // Parametrlar
            $query      = trim($request->query('q', ''));
            $type       = $request->query('type', 'all');
            $categoryId = $request->query('category_id');
            $sort       = $request->query('sort', 'relevance');
            $page       = max(1, (int) $request->query('page', 1));
            $perPage    = 20;
            $minPrice   = $request->query('min_price');
            $maxPrice   = $request->query('max_price');

            // Agar query < 2 va category yo'q bo'lsa
            if (strlen($query) < 2 && !$categoryId) {
                return response()->json([
                    'status'  => 'success',
                    'data'    => [],
                    'pagination' => [
                        'current_page' => 1,
                        'last_page'    => 1,
                        'total'        => 0,
                        'per_page'     => $perPage,
                    ],
                    'query_info' => [
                        'original' => $query,
                        'message' => 'Kamida 2 ta belgi kiriting yoki kategoriyani tanlang'
                    ]
                ]);
            }

            // Qidiruv so'zlarini tahlil qilish
            $analyzed = $this->analyzeSearchQuery($query);

            $user = auth('sanctum')->user();
            $results = collect();

            // Kitoblar qidiruvi
            if (in_array($type, ['book', 'all'])) {
                $books = $this->searchBooks($analyzed, $categoryId, $minPrice, $maxPrice);
                $results = $results->merge($books);
            }

            // Kanselyariya qidiruvi
            if (in_array($type, ['stationery', 'all'])) {
                $stationery = $this->searchStationery($analyzed, $categoryId, $minPrice, $maxPrice);
                $results = $results->merge($stationery);
            }

            // Formatlaash
            $results = $results->map(fn($p) => $this->formatProduct($p, $user))
                               ->filter()
                               ->values();

            // Saralash
            $results = $this->sortResults($results, $sort);

            // Pagination
            $total = $results->count();
            $paginatedItems = $results->slice(($page - 1) * $perPage, $perPage)->values();

            $paginator = new LengthAwarePaginator(
                $paginatedItems,
                $total,
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            );

            return response()->json([
                'status'     => 'success',
                'data'       => $paginatedItems->toArray(),
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page'    => $paginator->lastPage(),
                    'total'        => $paginator->total(),
                    'per_page'     => $paginator->perPage(),
                ],
                'query_info' => [
                    'original' => $analyzed['original'],
                    'search_variants' => $analyzed['variants'],
                    'type' => $type,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Search error: ' . $e->getMessage(), [
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Server xatosi yuz berdi',
                'error'   => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Kitoblarni qidirish - Ultra Mukammal Algoritm
     */
    private function searchBooks($analyzed, $categoryId = null, $minPrice = null, $maxPrice = null)
    {
        try {
            $bookQuery = Books::with(['category', 'tags', 'seller'])
                ->where('is_hidden', 0)
                ->where('is_approved', 1)
                ->where('count', '>', 0);

            // Seller tekshiruvi
            $bookQuery->whereHas('seller', function($q) {
                $q->where('is_hidden', 0)
                  ->where('status', 'approved')
                  ->where('parent_id', 0);
            });

            // Qidiruv - Ultra Mukammal Algoritm
            if (!empty($analyzed['variants'])) {
                $bookQuery->where(function ($q) use ($analyzed) {
                    $variants = $analyzed['variants'];
                    $words = $analyzed['words'];
                    $fuzzyVariants = $analyzed['fuzzy_variants'] ?? [];
                    $bigrams = $analyzed['bigrams'] ?? [];

                    // LEVEL 1: Aniq mos kelish (exact match)
                    foreach ($variants as $variant) {
                        $q->orWhere(DB::raw('LOWER(name)'), '=', mb_strtolower($variant))
                          ->orWhere(DB::raw('LOWER(author)'), '=', mb_strtolower($variant));
                    }

                    // LEVEL 2: Boshidan mos kelish (starts with)
                    foreach ($variants as $variant) {
                        $q->orWhere('name', 'LIKE', "{$variant}%")
                          ->orWhere('author', 'LIKE', "{$variant}%");
                    }

                    // LEVEL 3: O'rtasida mos kelish (contains)
                    foreach ($variants as $variant) {
                        $q->orWhere('name', 'LIKE', "%{$variant}%")
                          ->orWhere('author', 'LIKE', "%{$variant}%")
                          ->orWhere('description', 'LIKE', "%{$variant}%");
                    }

                    // LEVEL 4: Kategoriya (barcha 4 til)
                    $q->orWhereHas('category', function($c) use ($variants) {
                        foreach ($variants as $variant) {
                            $c->orWhere('name_uz', 'LIKE', "%{$variant}%")
                              ->orWhere('name_ru', 'LIKE', "%{$variant}%")
                              ->orWhere('name_en', 'LIKE', "%{$variant}%")
                              ->orWhere('name_ja', 'LIKE', "%{$variant}%");
                        }
                    });

                    // LEVEL 5: Taglar (barcha 4 til)
                    $q->orWhereHas('tags', function($t) use ($variants) {
                        foreach ($variants as $variant) {
                            $t->orWhere('tag_name_uz', 'LIKE', "%{$variant}%")
                              ->orWhere('tag_name_ru', 'LIKE', "%{$variant}%")
                              ->orWhere('tag_name_en', 'LIKE', "%{$variant}%")
                              ->orWhere('tag_name_ja', 'LIKE', "%{$variant}%");
                        }
                    });

                    // LEVEL 6: Sotuvchi do'kon nomi
                    $q->orWhereHas('seller', function($s) use ($variants) {
                        foreach ($variants as $variant) {
                            $s->where('shop_name', 'LIKE', "%{$variant}%");
                        }
                    });

                    // LEVEL 7: Har bir alohida so'z (multi-word search)
                    foreach ($words as $word) {
                        if (strlen($word) >= 2) {
                            $wordVariants = $this->transliterate($word);
                            foreach ($wordVariants as $wv) {
                                $q->orWhere('name', 'LIKE', "%{$wv}%")
                                  ->orWhere('author', 'LIKE', "%{$wv}%")
                                  ->orWhere('description', 'LIKE', "%{$wv}%");
                            }
                        }
                    }

                    // LEVEL 8: Fuzzy search (typo tolerance)
                    if (!empty($fuzzyVariants)) {
                        foreach ($fuzzyVariants as $fuzzy) {
                            $q->orWhere('name', 'LIKE', str_replace('_', '_', $fuzzy))
                              ->orWhere('author', 'LIKE', str_replace('_', '_', $fuzzy));
                        }
                    }

                    // LEVEL 9: Bigrams (2-so'zli birikmalar)
                    if (!empty($bigrams)) {
                        foreach ($bigrams as $bigram) {
                            $q->orWhere('name', 'LIKE', "%{$bigram}%")
                              ->orWhere('author', 'LIKE', "%{$bigram}%");
                        }
                    }
                });

                // Relevance Score - Juda Mukammal Hisoblash
                $original = mb_strtolower($analyzed['original']);
                $bookQuery->selectRaw("books.*, (
                    CASE
                        -- Aniq mos kelish (1000-900)
                        WHEN LOWER(name) = ? THEN 1000
                        WHEN LOWER(author) = ? THEN 950
                        WHEN LOWER(name) LIKE ? THEN 900
                        WHEN LOWER(author) LIKE ? THEN 850
                        
                        -- Boshidan mos kelish (800-700)
                        WHEN LOWER(name) LIKE ? THEN 800
                        WHEN LOWER(author) LIKE ? THEN 750
                        
                        -- O'rtasida mos kelish (600-400)
                        WHEN LOWER(name) LIKE ? THEN 600
                        WHEN LOWER(author) LIKE ? THEN 550
                        WHEN LOWER(description) LIKE ? THEN 500
                        
                        -- Chegirma mavjud bonus (+100)
                        WHEN discountPrice IS NOT NULL AND discountPrice > 0 THEN 450
                        
                        -- Yangi mahsulot bonus (oxirgi 30 kun, +50)
                        WHEN DATEDIFF(NOW(), created_at) <= 30 THEN 400
                        
                        -- Sotuvchi tasdiqlangan bonus (+30)
                        WHEN EXISTS(
                            SELECT 1 FROM sellers 
                            WHERE sellers.id = books.seller_id 
                            AND sellers.isVerified = 1
                        ) THEN 370
                        
                        -- Default
                        ELSE 300
                    END +
                    -- Haftalik sotuvlar bonusi (0-200)
                    LEAST(totalSalesWeek * 2, 200) +
                    -- Umumiy sotuvlar bonusi (0-100)
                    LEAST(totalSales, 100)
                ) as relevance_score", [
                    $original,                    // name exact
                    $original,                    // author exact
                    $original,                    // name exact (case-insensitive)
                    $original,                    // author exact (case-insensitive)
                    "{$original}%",               // name starts
                    "{$original}%",               // author starts
                    "%{$original}%",              // name contains
                    "%{$original}%",              // author contains
                    "%{$original}%",              // description contains
                ]);
            }

            // Kategoriya filtri
            if ($categoryId) {
                $bookQuery->where('category_id', $categoryId);
            }

            // Narx filtri
            if ($minPrice !== null) {
                $bookQuery->where('price', '>=', $minPrice);
            }
            if ($maxPrice !== null) {
                $bookQuery->where('price', '<=', $maxPrice);
            }

            return $bookQuery->get();

        } catch (\Exception $e) {
            Log::error('Search books error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return collect();
        }
    }

    /**
     * Kanselyariyalarni qidirish - Ultra Mukammal Algoritm
     */
    private function searchStationery($analyzed, $categoryId = null, $minPrice = null, $maxPrice = null)
    {
        try {
            $stationeryQuery = Stationery::with(['category', 'tags', 'seller', 'variants'])
                ->where('is_hidden', 0)
                ->where('is_approved', 1)
                ->where('stock', '>', 0);

            // Seller tekshiruvi
            $stationeryQuery->whereHas('seller', function($q) {
                $q->where('is_hidden', 0)
                  ->where('status', 'approved')
                  ->where('parent_id', 0);
            });

            // Qidiruv - Ultra Mukammal Algoritm
            if (!empty($analyzed['variants'])) {
                $stationeryQuery->where(function ($q) use ($analyzed) {
                    $variants = $analyzed['variants'];
                    $words = $analyzed['words'];
                    $fuzzyVariants = $analyzed['fuzzy_variants'] ?? [];
                    $bigrams = $analyzed['bigrams'] ?? [];

                    // LEVEL 1: Aniq mos kelish (exact match)
                    foreach ($variants as $variant) {
                        $q->orWhere(DB::raw('LOWER(name)'), '=', mb_strtolower($variant))
                          ->orWhere(DB::raw('LOWER(material)'), '=', mb_strtolower($variant));
                    }

                    // LEVEL 2: Boshidan mos kelish (starts with)
                    foreach ($variants as $variant) {
                        $q->orWhere('name', 'LIKE', "{$variant}%")
                          ->orWhere('material', 'LIKE', "{$variant}%");
                    }

                    // LEVEL 3: O'rtasida mos kelish (contains)
                    foreach ($variants as $variant) {
                        $q->orWhere('name', 'LIKE', "%{$variant}%")
                          ->orWhere('material', 'LIKE', "%{$variant}%")
                          ->orWhere('description', 'LIKE', "%{$variant}%");
                    }

                    // LEVEL 4: Kategoriya (barcha 4 til)
                    $q->orWhereHas('category', function($c) use ($variants) {
                        foreach ($variants as $variant) {
                            $c->orWhere('name_uz', 'LIKE', "%{$variant}%")
                              ->orWhere('name_ru', 'LIKE', "%{$variant}%")
                              ->orWhere('name_en', 'LIKE', "%{$variant}%")
                              ->orWhere('name_ja', 'LIKE', "%{$variant}%");
                        }
                    });

                    // LEVEL 5: Taglar (barcha 4 til)
                    $q->orWhereHas('tags', function($t) use ($variants) {
                        foreach ($variants as $variant) {
                            $t->orWhere('name_uz', 'LIKE', "%{$variant}%")
                              ->orWhere('name_ru', 'LIKE', "%{$variant}%")
                              ->orWhere('name_en', 'LIKE', "%{$variant}%")
                              ->orWhere('name_ja', 'LIKE', "%{$variant}%");
                        }
                    });

                    // LEVEL 6: Sotuvchi do'kon nomi
                    $q->orWhereHas('seller', function($s) use ($variants) {
                        foreach ($variants as $variant) {
                            $s->where('shop_name', 'LIKE', "%{$variant}%");
                        }
                    });

                    // LEVEL 7: Variant ranglari bo'yicha qidiruv
                    $q->orWhereHas('variants', function($v) use ($variants) {
                        foreach ($variants as $variant) {
                            $v->where('color_name', 'LIKE', "%{$variant}%");
                        }
                    });

                    // LEVEL 8: Har bir alohida so'z (multi-word search)
                    foreach ($words as $word) {
                        if (strlen($word) >= 2) {
                            $wordVariants = $this->transliterate($word);
                            foreach ($wordVariants as $wv) {
                                $q->orWhere('name', 'LIKE', "%{$wv}%")
                                  ->orWhere('material', 'LIKE', "%{$wv}%")
                                  ->orWhere('description', 'LIKE', "%{$wv}%");
                            }
                        }
                    }

                    // LEVEL 9: Fuzzy search (typo tolerance)
                    if (!empty($fuzzyVariants)) {
                        foreach ($fuzzyVariants as $fuzzy) {
                            $q->orWhere('name', 'LIKE', str_replace('_', '_', $fuzzy))
                              ->orWhere('material', 'LIKE', str_replace('_', '_', $fuzzy));
                        }
                    }

                    // LEVEL 10: Bigrams (2-so'zli birikmalar)
                    if (!empty($bigrams)) {
                        foreach ($bigrams as $bigram) {
                            $q->orWhere('name', 'LIKE', "%{$bigram}%")
                              ->orWhere('material', 'LIKE', "%{$bigram}%");
                        }
                    }
                });

                // Relevance Score - Juda Mukammal Hisoblash
                $original = mb_strtolower($analyzed['original']);
                $stationeryQuery->selectRaw("stationeries.*, (
                    CASE
                        -- Aniq mos kelish (1000-900)
                        WHEN LOWER(name) = ? THEN 1000
                        WHEN LOWER(material) = ? THEN 950
                        WHEN LOWER(name) LIKE ? THEN 900
                        WHEN LOWER(material) LIKE ? THEN 850
                        
                        -- Boshidan mos kelish (800-700)
                        WHEN LOWER(name) LIKE ? THEN 800
                        WHEN LOWER(material) LIKE ? THEN 750
                        
                        -- O'rtasida mos kelish (600-400)
                        WHEN LOWER(name) LIKE ? THEN 600
                        WHEN LOWER(material) LIKE ? THEN 550
                        WHEN LOWER(description) LIKE ? THEN 500
                        
                        -- Chegirma mavjud bonus (+100)
                        WHEN discount_price IS NOT NULL AND discount_price > 0 THEN 450
                        
                        -- Yangi mahsulot bonus (oxirgi 30 kun, +50)
                        WHEN DATEDIFF(NOW(), created_at) <= 30 THEN 400
                        
                        -- Sotuvchi tasdiqlangan bonus (+30)
                        WHEN EXISTS(
                            SELECT 1 FROM sellers 
                            WHERE sellers.id = stationeries.seller_id 
                            AND sellers.isVerified = 1
                        ) THEN 370
                        
                        -- Ko'p variantli mahsulot bonusi (+20)
                        WHEN (
                            SELECT COUNT(*) FROM stationery_variants 
                            WHERE stationery_variants.product_id = stationeries.id
                        ) > 3 THEN 350
                        
                        -- Default
                        ELSE 300
                    END +
                    -- Haftalik sotuvlar bonusi (0-200)
                    LEAST(totalSalesWeek * 2, 200) +
                    -- Umumiy sotuvlar bonusi (0-100)
                    LEAST(totalSales, 100)
                ) as relevance_score", [
                    $original,                    // name exact
                    $original,                    // material exact
                    $original,                    // name exact (case-insensitive)
                    $original,                    // material exact (case-insensitive)
                    "{$original}%",               // name starts
                    "{$original}%",               // material starts
                    "%{$original}%",              // name contains
                    "%{$original}%",              // material contains
                    "%{$original}%",              // description contains
                ]);
            }

            // Kategoriya filtri
            if ($categoryId) {
                $stationeryQuery->where('category_id', $categoryId);
            }

            // Narx filtri (discount_price yoki price)
            if ($minPrice !== null) {
                $stationeryQuery->where(function($q) use ($minPrice) {
                    $q->where(function($sq) use ($minPrice) {
                        $sq->whereNotNull('discount_price')
                           ->where('discount_price', '>=', $minPrice);
                    })->orWhere(function($sq) use ($minPrice) {
                        $sq->whereNull('discount_price')
                           ->where('price', '>=', $minPrice);
                    });
                });
            }
            if ($maxPrice !== null) {
                $stationeryQuery->where(function($q) use ($maxPrice) {
                    $q->where(function($sq) use ($maxPrice) {
                        $sq->whereNotNull('discount_price')
                           ->where('discount_price', '<=', $maxPrice);
                    })->orWhere(function($sq) use ($maxPrice) {
                        $sq->whereNull('discount_price')
                           ->where('price', '<=', $maxPrice);
                    });
                });
            }

            return $stationeryQuery->get();

        } catch (\Exception $e) {
            Log::error('Search stationery error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return collect();
        }
    }

    /**
     * Natijalarni saralash - Mukammal Algoritm
     */
    private function sortResults($results, $sort)
    {
        try {
            return match ($sort) {
                'relevance'  => $results->sortByDesc('relevance_score')->values(),
                'price_asc'  => $results->sortBy('price')->values(),
                'price_desc' => $results->sortByDesc('price')->values(),
                'alpha_asc'  => $results->sortBy(function($p) {
                    return mb_strtolower($p['name'], 'UTF-8');
                })->values(),
                'alpha_desc' => $results->sortByDesc(function($p) {
                    return mb_strtolower($p['name'], 'UTF-8');
                })->values(),
                'discount'   => $results->sortByDesc(function($p) {
                    $discount = $p['discountPrice'] ?? $p['price'];
                    return $p['price'] - $discount;
                })->values(),
                'popular'    => $results->sortByDesc('weekly_sales')->values(),
                'newest'     => $results->sortByDesc('id')->values(),
                default      => $results->sortByDesc('relevance_score')->values(),
            };
        } catch (\Exception $e) {
            Log::error('Sort results error: ' . $e->getMessage());
            return $results;
        }
    }

    /**
     * Kategoriyalarni olish
     * GET /api/search/categories
     */
    public function allCategories()
    {
        try {
            $bookCategories = BookCategories::where('is_active', 1)
                ->select('id', 'name_uz', 'name_ru', 'name_en', 'name_ja', 'slug', 'icon')
                ->orderBy('name_uz')
                ->get();

            $stationeryCategories = StationeryCategory::where('is_active', 1)
                ->select('id', 'name_uz', 'name_ru', 'name_en', 'name_ja', 'slug', 'icon')
                ->orderBy('name_uz')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'book'       => $bookCategories,
                    'stationery' => $stationeryCategories,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Get categories error: ' . $e->getMessage());
            
            return response()->json([
                'status'  => 'error',
                'message' => 'Kategoriyalarni yuklashda xato',
                'error'   => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Ommabop qidiruvlar
     * GET /api/search/trending
     */
    public function trendingSearches()
    {
        try {
            // Bu metodda cache yoki analytics ma'lumotlaridan foydalanamiz
            // Hozircha static ma'lumot qaytaramiz
            
            $trending = [
                ['query' => 'Dasturlash', 'count' => 1250],
                ['query' => 'Biznes', 'count' => 980],
                ['query' => 'Daftar', 'count' => 856],
                ['query' => 'Qalam', 'count' => 742],
                ['query' => 'Roman', 'count' => 650],
            ];

            return response()->json([
                'status' => 'success',
                'data' => $trending
            ]);

        } catch (\Exception $e) {
            Log::error('Get trending searches error: ' . $e->getMessage());
            
            return response()->json([
                'status'  => 'error',
                'message' => 'Ommabop qidiruvlarni yuklashda xato'
            ], 500);
        }
    }

    /**
     * Qidiruv tavsiyalari (autocomplete)
     * GET /api/search/suggestions
     */
    public function suggestions(Request $request)
    {
        try {
            $query = trim($request->query('q', ''));
            
            if (strlen($query) < 2) {
                return response()->json([
                    'status' => 'success',
                    'data' => []
                ]);
            }

            $analyzed = $this->analyzeSearchQuery($query);
            $suggestions = [];

            // Kitoblardan tavsiyalar
            $books = Books::where('is_hidden', 0)
                ->where('is_approved', 1)
                ->where('count', '>', 0)
                ->where(function($q) use ($analyzed) {
                    foreach ($analyzed['variants'] as $variant) {
                        $q->orWhere('name', 'LIKE', "{$variant}%")
                          ->orWhere('author', 'LIKE', "{$variant}%");
                    }
                })
                ->select('name', 'author')
                ->limit(5)
                ->get();

            foreach ($books as $book) {
                $suggestions[] = [
                    'text' => $book->name,
                    'type' => 'book'
                ];
                if ($book->author) {
                    $suggestions[] = [
                        'text' => $book->author,
                        'type' => 'author'
                    ];
                }
            }

            // Kanselyariyadan tavsiyalar
            $stationery = Stationery::where('is_hidden', 0)
                ->where('is_approved', 1)
                ->where('stock', '>', 0)
                ->where(function($q) use ($analyzed) {
                    foreach ($analyzed['variants'] as $variant) {
                        $q->orWhere('name', 'LIKE', "{$variant}%");
                    }
                })
                ->select('name')
                ->limit(5)
                ->get();

            foreach ($stationery as $item) {
                $suggestions[] = [
                    'text' => $item->name,
                    'type' => 'stationery'
                ];
            }

            // Takrorlanuvchilarni olib tashlash
            $suggestions = collect($suggestions)
                ->unique('text')
                ->take(10)
                ->values();

            return response()->json([
                'status' => 'success',
                'data' => $suggestions
            ]);

        } catch (\Exception $e) {
            Log::error('Get suggestions error: ' . $e->getMessage());
            
            return response()->json([
                'status'  => 'error',
                'message' => 'Tavsiyalarni yuklashda xato'
            ], 500);
        }
    }
}