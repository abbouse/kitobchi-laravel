<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Books;
use App\Models\Stationery;
use App\Models\Seller;
use App\Models\FavouriteProducts;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class ProductsController extends Controller
{
    /**
     * Umumiy mahsulotni formatlash (kitob yoki stationery)
     */
    private function formatProduct($product, $user = null, $type = 'book')
    {
        $isBook = $type === 'book';

        return [
            'id' => $product->id,
            'type' => $isBook ? 'book' : 'stationery',
            'name' => $product->name,
            'author' => $isBook ? ($product->author ?? null) : null,
            'material' => $isBook ? null : ($product->material ?? null),
            'category_id' => $product->category_id,
            'images' => $product->images ?? [],
            'description' => $product->description ?? null,
            'price' => $isBook ? $product->price : $product->price,
            'discountPrice' => $isBook
                ? ($product->discountPrice ?? $product->price)
                : ($product->discount_price ?? $product->price),
            'count' => $isBook ? $product->count : $product->stock,
            'sales' => $product->totalSales ?? 0,
            'weekly_sales' => $product->totalSalesWeek ?? 0,
            'lang' => $isBook ? ($product->lang ?? 'O\'zbek') : null,
            'langType' => $isBook ? ($product->langType ?? '') : null,
            'coverType' => $isBook ? ($product->coverType ?? 'Yumshoq') : null,
            'year' => $isBook ? ($product->year ?? now()->year) : null,
            'favourite' => $user ? FavouriteProducts::where('user_id', $user->id)->where('product_id', $product->id)->exists()
                : false,
            'category' => $product->category?->title ?? null,
            'tags' => $product->tags->map(function ($tag) {
                return [
                    'uz' => $tag->tag_name_uz ?? $tag->name_uz ?? null,
                    'ru' => $tag->tag_name_ru ?? $tag->name_ru ?? null,
                    'en' => $tag->tag_name_en ?? $tag->name_en ?? null,
                ];
            })->filter()->values(),
            'seller' => [
                'seller_id' => $product->seller?->id,
                'shop_name' => $product->seller?->shop_name,
                'photo' => $product->seller?->photo,
                'isVerified' => $product->seller?->isVerified,
            ],
            'variants' => !$isBook && $product->relationLoaded('variants')
                ? $product->variants->map(function ($variant) {
                    return [
                        'id' => $variant->id,
                        'color_name' => $variant->color_name,
                        'image' => $variant->image_path ?? null,
                        'stock' => $variant->stock,
                    ];
                })
                : null,
        ];
    }

    /**
     * O'zbekcha kirill-lotin transliteratsiya
     */
    private function transliterate($text, $toLatin = true)
    {
        $cyr = ['а','б','в','г','д','е','ё','ж','з','и','й','к','л','м','н','о','п','р','с','т','у','ф','х','ҳ','ч','ш','ъ','э','ю','я','ў','ғ','қ','ҳ',
                'А','Б','В','Г','Д','Е','Ё','Ж','З','И','Й','К','Л','М','Н','О','П','Р','С','Т','У','Ф','Х','Ҳ','Ч','Ш','Ъ','Э','Ю','Я','Ў','Ғ','Қ','Ҳ'];
        $lat = ['a','b','v','g','d','e','yo','j','z','i','y','k','l','m','n','o','p','r','s','t','u','f','x','h','ch','sh','\'','e','yu','ya','o\'','g\'','q','h',
                'A','B','V','G','D','E','Yo','J','Z','I','Y','K','L','M','N','O','P','R','S','T','U','F','X','H','Ch','Sh','\'','E','Yu','Ya','O\'','G\'','Q','H'];

        return $toLatin ? str_replace($cyr, $lat, $text) : str_replace($lat, $cyr, $text);
    }
    /**
     * Main page — faqat yangi kitoblar
     */
    public function index(Request $request, string $col)
    {
        $user = Auth::guard('user')->user();
        $books = Books::where('count', '>', 0)
            ->where('is_hidden', 0)
            ->where('is_approved', 1)
            ->whereHas('seller', fn($q) => $q->where('is_hidden', 0)->where('status', 'approved')->where('parent_id', 0))
            ->with(['seller', 'category', 'tags'])
            ->orderBy('created_at', 'DESC')
            ->limit((int)$col)
            ->get();

        $result = $books->map(fn($book) => $this->formatProduct($book, $user, 'book'));
        
        $token = $request->bearerToken();
    $foundToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);

    $debugData = [
        'URL' => $request->fullUrl(),
        'Full_Header' => $request->header('Authorization'),
        'Token_Short' => substr($token, 0, 10) . '...', // Xavfsizlik uchun faqat boshi
        'Sanctum_User_ID' => $user ? $user->id : 'TOPILMADI',
        'Token_Exists_In_DB' => $foundToken ? 'HA (ID: ' . $foundToken->id . ')' : 'YOQ',
        'Tokenable_ID_In_DB' => $foundToken ? $foundToken->tokenable_id : 'N/A',
    ];

    // Log fayliga yozish
    Log::info('--- Token Debugging ---', $debugData);

        return response()->json(['status' => 'success', 'data' => $result]);
    }

    /**
     * Sotuvchi va uning stationery mahsulotlari
     */
    public function seller(Request $request, $id, $page = 1)
{
    $user = Auth::guard('user')->user();
    $perPage = 20;

    $seller = Seller::where('id', $id)
        ->where('is_hidden', 0)
        ->where('parent_id', 0)
        ->where('status', 'approved')
        ->firstOrFail();

    // Kitoblar (Books)
    $books = Books::where('seller_id', $id)
        ->where('is_hidden', 0)
        ->where('is_approved', 1)
        ->where('count', '>', 0)
        ->with(['category', 'tags', 'seller'])
        ->paginate($perPage, ['*'], 'books_page', $page);

    // Stationery
    $stationeries = Stationery::where('seller_id', $id)
        ->where('is_hidden', 0)
        ->where('is_approved', 1)
        ->where('stock', '>', 0)
        ->with(['category', 'tags', 'variants', 'seller'])
        ->paginate($perPage, ['*'], 'stationeries_page', $page);

    // Formatlash
    $formattedBooks = $books->getCollection()->map(fn($book) => $this->formatProduct($book, $user, 'book'));
    $formattedStationeries = $stationeries->getCollection()->map(fn($item) => $this->formatProduct($item, $user, 'stationery'));

    $books->setCollection($formattedBooks);
    $stationeries->setCollection($formattedStationeries);

    return response()->json([
        'status' => 'success',
        'data' => [
            'seller' => [
                'id' => $seller->id,
                'shop_name' => $seller->shop_name,
                'photo' => $seller->photo,
                'firstname' => $seller->firstname,
                'lastname' => $seller->lastname,
                'region' => $seller->region,
                'activity_types' => $seller->activity_types,
                'isVerified' => $seller->isVerified
            ],
            'books' => [
                'data' => $books->items(),
                'pagination' => [
                    'current_page' => $books->currentPage(),
                    'last_page' => $books->lastPage(),
                    'has_more_pages' => $books->hasMorePages(),
                ]
            ],
            'stationeries' => [
                'data' => $stationeries->items(),
                'pagination' => [
                    'current_page' => $stationeries->currentPage(),
                    'last_page' => $stationeries->lastPage(),
                    'has_more_pages' => $stationeries->hasMorePages(),
                ]
            ],
        ]
    ]);
}

    /**
     * Tavsiyalar — kitob va stationery aralash, haftalik sotuv + yangilik bo‘yicha
     */
    public function recommendation(Request $request, string $col)
    {
        $user = Auth::guard('user')->user();
        $limit = (int)$col;
        $perType = max(3, floor($limit / 2));

        // Kitoblar
        $books = Books::where('count', '>', 0)
            ->where('is_hidden', 0)
            ->where('is_approved', 1)
            ->whereHas('seller', fn($q) => $q->where('is_hidden', 0)->where('status', 'approved')->where('parent_id', 0))
            ->with(['category', 'tags', 'seller'])
            ->orderByDesc('totalSalesWeek')
            ->orderByDesc('created_at')
            ->limit($perType)
            ->get();

        // Stationery
        $stationeries = Stationery::where('stock', '>', 0)
            ->where('is_hidden', 0)
            ->where('is_approved', 1)
            ->whereHas('seller', fn($q) => $q->where('is_hidden', 0)->where('status', 'approved')->where('parent_id', 0))
            ->with(['category', 'tags', 'variants', 'seller'])
            ->orderByDesc('totalSalesWeek')
            ->orderByDesc('created_at')
            ->limit($limit - $books->count())
            ->get();

        $combined = collect()
            ->merge($books->map(fn($b) => $this->formatProduct($b, $user, 'book')))
            ->merge($stationeries->map(fn($s) => $this->formatProduct($s, $user, 'stationery')))
            ->shuffle()
            ->take($limit);

        return response()->json(['status' => 'success', 'data' => $combined]);
    }

    /**
     * Do'konlar va ularning so‘nggi stationery mahsulotlari
     */
    public function sellersWithLatestProducts(Request $request)
{
    $user = Auth::guard('user')->user();

    $sellers = Seller::where('is_hidden', 0)
        ->where('parent_id', 0)
        ->where('status', 'approved')
        // Sotuvchida kamida bitta mahsulot (kitob yoki stationery) bo'lsa yetarli
        ->where(function ($query) {
            $query->whereHas('books', fn($q) => $q->where('count', '>', 0)
                ->where('is_hidden', 0)
                ->where('is_approved', 1))
                ->orWhereHas('stationeries', fn($q) => $q->where('stock', '>', 0)
                    ->where('is_hidden', 0)
                    ->where('is_approved', 1));
        })
        ->with([
            // Eng yangi 20 ta kitob
            'books' => fn($q) => $q->where('count', '>', 0)
                ->where('is_hidden', 0)
                ->where('is_approved', 1)
                ->latest('created_at')
                ->take(20)
                ->with(['category', 'tags', 'seller']),

            // Eng yangi 20 ta stationery
            'stationeries' => fn($q) => $q->where('stock', '>', 0)
                ->where('is_hidden', 0)
                ->where('is_approved', 1)
                ->latest('created_at')
                ->take(20)
                ->with(['category', 'tags', 'variants', 'seller']),
        ])
        ->inRandomOrder()
        ->take(50)
        ->get();

    $result = $sellers->map(function ($seller) use ($user) {
        return [
            'id' => $seller->id,
            'firstname' => $seller->firstname,
            'lastname' => $seller->lastname,
            'shop_name' => $seller->shop_name,
            'region' => $seller->region,
            'activity_types' => $seller->activity_types,
            'photo' => $seller->photo,
            'status' => $seller->status,
            'isVerified' => $seller->isVerified,
            // Alohida: kitoblar
            'books' => $seller->books->map(fn($book) => $this->formatProduct($book, $user, 'book')),

            // Alohida: stationery
            'stationeries' => $seller->stationeries->map(fn($item) => $this->formatProduct($item, $user, 'stationery')),
        ];
    });

    return response()->json([
        'status' => 'success',
        'data' => $result
    ]);
}

    /**
     * Umumiy qidiruv — kirill yoki lotin bo‘lsa ham topadi (kitob + stationery)
     */
    public function search(Request $request, string $search)
    {
        $user = Auth::guard('user')->user();
        $original = trim($search);

        if (strlen($original) < 2) {
            return response()->json(['status' => 'success', 'data' => []]);
        }

        $isCyrillic = preg_match('/[А-Яа-яЎўҒғҚқҲҳ]/u', $original);
        $latin = $isCyrillic ? $this->transliterate($original, true) : $original;
        $cyrillic = $isCyrillic ? $original : $this->transliterate($original, false);

        $results = collect();

        // Kitoblar
        $books = Books::where('is_hidden', 0)
            ->where('count', '>', 0)
            ->where('is_approved', 1)
            ->whereHas('seller', fn($q) => $q->where('is_hidden', 0)->where('status', 'approved')->where('parent_id', 0))
            ->where(function ($q) use ($latin, $cyrillic) {
                $q->where('name', 'LIKE', "%{$latin}%")
                  ->orWhere('name', 'LIKE', "%{$cyrillic}%")
                  ->orWhere('author', 'LIKE', "%{$latin}%")
                  ->orWhere('author', 'LIKE', "%{$cyrillic}%");
            })
            ->orWhereHas('category', fn($q) => $q->where('title', 'LIKE', "%{$latin}%")->orWhere('title', 'LIKE', "%{$cyrillic}%"))
            ->orWhereHas('tags', fn($q) => $q->where('tag_name_uz', 'LIKE', "%{$latin}%")->orWhere('tag_name_uz', 'LIKE', "%{$cyrillic}%"))
            ->with(['category', 'tags', 'seller'])
            ->limit(20)
            ->get();

        $results = $results->merge($books->map(fn($b) => $this->formatProduct($b, $user, 'book')));

        // Stationery
        $stationeries = Stationery::where('is_hidden', 0)
            ->where('stock', '>', 0)
            ->where('is_approved', 1)
            ->whereHas('seller', fn($q) => $q->where('is_hidden', 0)->where('status', 'approved')->where('parent_id', 0))
            ->where(function ($q) use ($latin, $cyrillic) {
                $q->where('name', 'LIKE', "%{$latin}%")
                  ->orWhere('name', 'LIKE', "%{$cyrillic}%")
                  ->orWhere('material', 'LIKE', "%{$latin}%")
                  ->orWhere('material', 'LIKE', "%{$cyrillic}%");
            })
            ->orWhereHas('category', fn($q) => $q->where('title', 'LIKE', "%{$latin}%")->orWhere('title', 'LIKE', "%{$cyrillic}%"))
            ->orWhereHas('tags', fn($q) => $q->where('tag_name_uz', 'LIKE', "%{$latin}%")->orWhere('tag_name_uz', 'LIKE', "%{$cyrillic}%"))
            ->with(['category', 'tags', 'variants', 'seller'])
            ->limit(20)
            ->get();

        $results = $results->merge($stationeries->map(fn($s) => $this->formatProduct($s, $user, 'stationery')));

        $final = $results->unique(fn($item) => $item['id'] . '_' . $item['type'])
            ->sortByDesc(fn($item) => str_contains(strtolower($item['name']), strtolower($original)) ? 1 : 0)
            ->values();

        return response()->json(['status' => 'success', 'data' => $final]);
    }

    /**
     * Kategoriyalar ro'yxati (kitob kategoriyalari deb faraz qilamiz)
     */
    public function categories(Request $request)
    {
        $categories = \App\Models\BookCategories::get(['id', 'title']);
        return response()->json(['status' => 'success', 'data' => $categories]);
    }

    /**
     * Kategoriya ichidagi stationery mahsulotlari
     */
    public function category(Request $request, string $cat_id, string $type = 'no')
    {
        $user = Auth::guard('user')->user();

        $query = Stationery::where('category_id', $cat_id)
            ->where('is_hidden', 0)
            ->where('stock', '>', 0)
            ->where('is_approved', 1)
            ->whereHas('seller', fn($q) => $q->where('is_hidden', 0)->where('status', 'approved')->where('parent_id', 0))
            ->with(['category', 'tags', 'variants', 'seller']);

        $products = $query->get();

        $result = $products->map(fn($item) => $this->formatProduct($item, $user, 'stationery'));

        return response()->json(['status' => 'success', 'data' => $result]);
    }
}