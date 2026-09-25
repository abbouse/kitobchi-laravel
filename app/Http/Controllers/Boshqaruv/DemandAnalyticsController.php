<?php

declare(strict_types=1);

namespace App\Http\Controllers\Boshqaruv;

use App\Http\Controllers\Controller;
use App\Models\Books;
use App\Models\BranchStock;
use App\Models\FavouriteProducts;
use App\Models\MyCart;
use App\Models\ProductStockAlert;
use App\Models\Stationery;
use App\Support\ProductImageUrls;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class DemandAnalyticsController extends Controller
{
    /**
     * Mijozlar talabi, xohishlar (wishlist) va savat analitikasi boshqaruv paneli.
     */
    public function index(Request $request): Response
    {
        $tab = (string) $request->input('tab', 'all_wishes');
        $type = (string) $request->input('type', 'all'); // 'all', 'book', 'stationery'
        $search = trim((string) $request->input('search', ''));
        $page = max(1, (int) $request->input('page', 1));
        $perPage = 15;

        // KPI hisob-kitoblar
        $kpi = $this->calculateKpis();

        // Tanlangan tab bo'yicha ma'lumotlar
        $itemsData = match ($tab) {
            'cart_out_of_stock'     => $this->getCartOutOfStock($type, $search, $page, $perPage),
            'wishlist_out_of_stock' => $this->getWishlistOutOfStock($type, $search, $page, $perPage),
            'stock_alerts'          => $this->getStockAlerts($type, $search, $page, $perPage),
            'top_cart_items'        => $this->getTopCartItems($type, $search, $page, $perPage),
            'top_wishlist_items'    => $this->getTopWishlistItems($type, $search, $page, $perPage),
            default                 => $this->getAllWishes($type, $search, $page, $perPage),
        };

        return Inertia::render('DemandAnalytics', [
            'kpi' => $kpi,
            'items' => $itemsData['items'],
            'pagination' => $itemsData['pagination'],
            'filters' => [
                'tab' => $tab,
                'type' => $type,
                'search' => $search,
            ],
            'tabs' => [
                ['key' => 'all_wishes', 'label' => 'Mijozlar xohishlari (barchasi)', 'icon' => 'ti-heart-filled', 'badge' => $kpi['totalWishesProducts']],
                ['key' => 'cart_out_of_stock', 'label' => 'Savatda bor lekin tugagan', 'icon' => 'ti-shopping-cart-x', 'badge' => $kpi['cartOutOfStockProducts']],
                ['key' => 'wishlist_out_of_stock', 'label' => 'Sevimli lekin tugagan', 'icon' => 'ti-ban', 'badge' => $kpi['wishlistOutOfStockProducts']],
                ['key' => 'stock_alerts', 'label' => 'Qayta sotuvni kutayotganlar', 'icon' => 'ti-bell-filled', 'badge' => $kpi['stockAlertProducts']],
                ['key' => 'top_cart_items', 'label' => 'Savatdagi eng ko\'p saqlangan', 'icon' => 'ti-shopping-cart', 'badge' => $kpi['topCartProductsCount']],
                ['key' => 'top_wishlist_items', 'label' => 'Sevimlidagi eng ko\'p saqlangan', 'icon' => 'ti-heart-filled', 'badge' => $kpi['totalWishlistItems']],
            ],
        ]);
    }

    private function calculateKpis(): array
    {
        $hasCarts = Schema::hasTable('my_carts');
        $hasFavs = Schema::hasTable('favourite_products');
        $hasStocks = Schema::hasTable('branch_stocks');
        $hasAlerts = Schema::hasTable('product_stock_alerts');

        $totalWishlistItems = $hasFavs ? FavouriteProducts::query()->count() : 0;
        $totalWishesProducts = $hasFavs ? FavouriteProducts::query()->distinct('product_id')->count('product_id') : 0;
        $uniqueWishlistUsers = $hasFavs ? FavouriteProducts::query()->distinct('user_id')->count('user_id') : 0;

        $totalCartItems = $hasCarts ? MyCart::query()->count() : 0;
        $totalCartUnits = $hasCarts ? (int) MyCart::query()->sum('count_item') : 0;
        $topCartProductsCount = $hasCarts ? MyCart::query()->distinct('product_id')->count('product_id') : 0;
        $uniqueCartUsers = $hasCarts ? MyCart::query()->distinct('user_id')->count('user_id') : 0;

        $totalStockAlerts = $hasAlerts ? ProductStockAlert::query()->count() : 0;
        $stockAlertProducts = $hasAlerts ? ProductStockAlert::query()->distinct('product_id')->count('product_id') : 0;

        $cartOutOfStockProducts = 0;
        $cartOutOfStockUnits = 0;
        $lostRevenueInCarts = 0;

        $wishlistOutOfStockProducts = 0;
        $unrealizedWishlistDemand = 0;

        if ($hasCarts && $hasStocks && Schema::hasTable('books')) {
            $bookStockSql = BranchStock::availableSql('book', 'books.id');
            $cartBookStats = DB::table('my_carts')
                ->join('books', 'my_carts.product_id', '=', 'books.id')
                ->where('my_carts.product_type', 'book')
                ->whereRaw("{$bookStockSql} <= 0")
                ->selectRaw('COUNT(DISTINCT books.id) as out_books, SUM(my_carts.count_item) as out_units, SUM(my_carts.count_item * COALESCE(books.discountPrice, books.price, 0)) as lost_sum')
                ->first();

            $cartOutOfStockProducts += (int) ($cartBookStats->out_books ?? 0);
            $cartOutOfStockUnits += (int) ($cartBookStats->out_units ?? 0);
            $lostRevenueInCarts += (float) ($cartBookStats->lost_sum ?? 0);
        }

        if ($hasFavs && $hasStocks && Schema::hasTable('books')) {
            $bookStockSql = BranchStock::availableSql('book', 'books.id');
            $favBookStats = DB::table('favourite_products')
                ->join('books', 'favourite_products.product_id', '=', 'books.id')
                ->where('favourite_products.product_type', 'book')
                ->whereRaw("{$bookStockSql} <= 0")
                ->selectRaw('COUNT(DISTINCT books.id) as out_books, SUM(COALESCE(books.discountPrice, books.price, 0)) as demand_sum')
                ->first();

            $wishlistOutOfStockProducts += (int) ($favBookStats->out_books ?? 0);
            $unrealizedWishlistDemand += (float) ($favBookStats->demand_sum ?? 0);
        }

        return [
            'totalWishlistItems' => $totalWishlistItems,
            'totalWishesProducts' => $totalWishesProducts,
            'uniqueWishlistUsers' => $uniqueWishlistUsers,
            'totalCartItems' => $totalCartItems,
            'totalCartUnits' => $totalCartUnits,
            'topCartProductsCount' => $topCartProductsCount,
            'uniqueCartUsers' => $uniqueCartUsers,
            'totalStockAlerts' => $totalStockAlerts,
            'stockAlertProducts' => $stockAlertProducts,
            'cartOutOfStockProducts' => $cartOutOfStockProducts,
            'cartOutOfStockUnits' => $cartOutOfStockUnits,
            'lostRevenueInCarts' => $lostRevenueInCarts,
            'wishlistOutOfStockProducts' => $wishlistOutOfStockProducts,
            'unrealizedWishlistDemand' => $unrealizedWishlistDemand,
        ];
    }

    /**
     * Mijozlar hohishlariga qo'shgan kitoblar umumiy ko'rinadigan qism (Barchasi).
     */
    private function getAllWishes(string $type, string $search, int $page, int $perPage): array
    {
        if (! Schema::hasTable('favourite_products') || ! Schema::hasTable('books')) {
            return ['items' => [], 'pagination' => $this->emptyPagination()];
        }

        $stockSql = BranchStock::availableSql('book', 'books.id');

        $query = DB::table('favourite_products')
            ->join('books', 'favourite_products.product_id', '=', 'books.id')
            ->leftJoin('sellers', 'books.seller_id', '=', 'sellers.id')
            ->where('favourite_products.product_type', 'book')
            ->groupBy(
                'books.id',
                'books.name',
                'books.author',
                'books.price',
                'books.discountPrice',
                'books.images',
                'books.artikul',
                'books.isbn',
                'books.seller_id',
                'sellers.shop_name',
                'sellers.phone_number',
                'sellers.business_role'
            )
            ->selectRaw("
                books.id,
                books.name,
                books.author,
                books.price,
                books.discountPrice,
                books.images,
                books.artikul,
                books.isbn,
                books.seller_id,
                sellers.shop_name,
                sellers.phone_number as seller_phone,
                COALESCE(sellers.business_role, 'seller') as seller_role,
                'book' as product_type,
                COUNT(DISTINCT favourite_products.user_id) as users_count,
                COUNT(DISTINCT favourite_products.user_id) * COALESCE(books.discountPrice, books.price, 0) as potential_demand,
                GREATEST(0, COALESCE({$stockSql}, 0)) as available_stock
            ");

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('books.name', 'like', "%{$search}%")
                    ->orWhere('books.author', 'like', "%{$search}%")
                    ->orWhere('books.isbn', 'like', "%{$search}%")
                    ->orWhere('sellers.shop_name', 'like', "%{$search}%");
            });
        }

        $query->orderByDesc('users_count')->orderByDesc('potential_demand');

        $total = $query->get()->count();
        $offset = ($page - 1) * $perPage;
        $rows = $query->skip($offset)->take($perPage)->get();

        $items = $rows->map(function ($row) {
            $imageUrls = ProductImageUrls::build($row->images);
            $firstImage = $imageUrls['original'][0] ?? null;

            $stock = (int) $row->available_stock;
            $urgency = $stock <= 0 ? 'high' : ($stock < 5 ? 'medium' : 'normal');

            return [
                'id' => $row->id,
                'name' => $row->name,
                'author' => $row->author,
                'productType' => 'book',
                'productTypeLabel' => 'Kitob',
                'image' => $firstImage,
                'price' => (float) $row->price,
                'discountPrice' => $row->discountPrice ? (float) $row->discountPrice : null,
                'artikul' => $row->artikul,
                'isbn' => $row->isbn,
                'availableStock' => $stock,
                'usersCount' => (int) $row->users_count,
                'potentialDemand' => (float) $row->potential_demand,
                'sellerId' => $row->seller_id,
                'sellerName' => $row->shop_name ?: 'Kitobchi',
                'sellerPhone' => $row->seller_phone,
                'sellerRole' => $row->seller_role,
                'urgency' => $urgency,
                'actionLabel' => $stock <= 0 ? "Zaxirani to'ldirish" : "Katalogda ko'rish",
            ];
        })->values()->all();

        return [
            'items' => $items,
            'pagination' => [
                'page' => $page,
                'totalPages' => max(1, (int) ceil($total / $perPage)),
                'total' => $total,
                'from' => $total > 0 ? $offset + 1 : 0,
                'to' => min($offset + $perPage, $total),
            ],
        ];
    }

    /**
     * Savatda bor lekin hozirda omborda TUGAGAN kitoblar ro'yxati (Darhol omborni to'ldirish kerak).
     */
    private function getCartOutOfStock(string $type, string $search, int $page, int $perPage): array
    {
        if (! Schema::hasTable('my_carts') || ! Schema::hasTable('books')) {
            return ['items' => [], 'pagination' => $this->emptyPagination()];
        }

        $stockSql = BranchStock::availableSql('book', 'books.id');

        $query = DB::table('my_carts')
            ->join('books', 'my_carts.product_id', '=', 'books.id')
            ->leftJoin('sellers', 'books.seller_id', '=', 'sellers.id')
            ->where('my_carts.product_type', 'book')
            ->whereRaw("{$stockSql} <= 0")
            ->groupBy(
                'books.id',
                'books.name',
                'books.author',
                'books.price',
                'books.discountPrice',
                'books.images',
                'books.artikul',
                'books.isbn',
                'books.seller_id',
                'sellers.shop_name',
                'sellers.phone_number',
                'sellers.business_role'
            )
            ->selectRaw("
                books.id,
                books.name,
                books.author,
                books.price,
                books.discountPrice,
                books.images,
                books.artikul,
                books.isbn,
                books.seller_id,
                sellers.shop_name,
                sellers.phone_number as seller_phone,
                COALESCE(sellers.business_role, 'seller') as seller_role,
                'book' as product_type,
                COUNT(DISTINCT my_carts.user_id) as users_count,
                SUM(my_carts.count_item) as requested_units,
                SUM(my_carts.count_item * COALESCE(books.discountPrice, books.price, 0)) as lost_revenue,
                0 as available_stock
            ");

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('books.name', 'like', "%{$search}%")
                    ->orWhere('books.author', 'like', "%{$search}%")
                    ->orWhere('books.isbn', 'like', "%{$search}%")
                    ->orWhere('sellers.shop_name', 'like', "%{$search}%");
            });
        }

        $query->orderByDesc('requested_units')->orderByDesc('lost_revenue');

        $total = $query->get()->count();
        $offset = ($page - 1) * $perPage;
        $rows = $query->skip($offset)->take($perPage)->get();

        $items = $rows->map(function ($row) {
            $imageUrls = ProductImageUrls::build($row->images);
            $firstImage = $imageUrls['original'][0] ?? null;

            return [
                'id' => $row->id,
                'name' => $row->name,
                'author' => $row->author,
                'productType' => 'book',
                'productTypeLabel' => 'Kitob',
                'image' => $firstImage,
                'price' => (float) $row->price,
                'discountPrice' => $row->discountPrice ? (float) $row->discountPrice : null,
                'artikul' => $row->artikul,
                'isbn' => $row->isbn,
                'availableStock' => 0,
                'usersCount' => (int) $row->users_count,
                'requestedUnits' => (int) $row->requested_units,
                'potentialLostRevenue' => (float) $row->lost_revenue,
                'sellerId' => $row->seller_id,
                'sellerName' => $row->shop_name ?: 'Kitobchi',
                'sellerPhone' => $row->seller_phone,
                'sellerRole' => $row->seller_role,
                'urgency' => (int) $row->requested_units >= 3 ? 'high' : 'medium',
                'actionLabel' => "Omborga buyurtma qilish",
            ];
        })->values()->all();

        return [
            'items' => $items,
            'pagination' => [
                'page' => $page,
                'totalPages' => max(1, (int) ceil($total / $perPage)),
                'total' => $total,
                'from' => $total > 0 ? $offset + 1 : 0,
                'to' => min($offset + $perPage, $total),
            ],
        ];
    }

    /**
     * Sevimlida bor lekin omborda TUGAGAN kitoblar ro'yxati.
     */
    private function getWishlistOutOfStock(string $type, string $search, int $page, int $perPage): array
    {
        if (! Schema::hasTable('favourite_products') || ! Schema::hasTable('books')) {
            return ['items' => [], 'pagination' => $this->emptyPagination()];
        }

        $stockSql = BranchStock::availableSql('book', 'books.id');

        $query = DB::table('favourite_products')
            ->join('books', 'favourite_products.product_id', '=', 'books.id')
            ->leftJoin('sellers', 'books.seller_id', '=', 'sellers.id')
            ->where('favourite_products.product_type', 'book')
            ->whereRaw("{$stockSql} <= 0")
            ->groupBy(
                'books.id',
                'books.name',
                'books.author',
                'books.price',
                'books.discountPrice',
                'books.images',
                'books.artikul',
                'books.isbn',
                'books.seller_id',
                'sellers.shop_name',
                'sellers.phone_number',
                'sellers.business_role'
            )
            ->selectRaw("
                books.id,
                books.name,
                books.author,
                books.price,
                books.discountPrice,
                books.images,
                books.artikul,
                books.isbn,
                books.seller_id,
                sellers.shop_name,
                sellers.phone_number as seller_phone,
                COALESCE(sellers.business_role, 'seller') as seller_role,
                'book' as product_type,
                COUNT(DISTINCT favourite_products.user_id) as users_count,
                COUNT(DISTINCT favourite_products.user_id) * COALESCE(books.discountPrice, books.price, 0) as potential_demand,
                0 as available_stock
            ");

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('books.name', 'like', "%{$search}%")
                    ->orWhere('books.author', 'like', "%{$search}%")
                    ->orWhere('books.isbn', 'like', "%{$search}%")
                    ->orWhere('sellers.shop_name', 'like', "%{$search}%");
            });
        }

        $query->orderByDesc('users_count')->orderByDesc('potential_demand');

        $total = $query->get()->count();
        $offset = ($page - 1) * $perPage;
        $rows = $query->skip($offset)->take($perPage)->get();

        $items = $rows->map(function ($row) {
            $imageUrls = ProductImageUrls::build($row->images);
            $firstImage = $imageUrls['original'][0] ?? null;

            return [
                'id' => $row->id,
                'name' => $row->name,
                'author' => $row->author,
                'productType' => 'book',
                'productTypeLabel' => 'Kitob',
                'image' => $firstImage,
                'price' => (float) $row->price,
                'discountPrice' => $row->discountPrice ? (float) $row->discountPrice : null,
                'artikul' => $row->artikul,
                'isbn' => $row->isbn,
                'availableStock' => 0,
                'usersCount' => (int) $row->users_count,
                'potentialDemand' => (float) $row->potential_demand,
                'sellerId' => $row->seller_id,
                'sellerName' => $row->shop_name ?: 'Kitobchi',
                'sellerPhone' => $row->seller_phone,
                'sellerRole' => $row->seller_role,
                'urgency' => (int) $row->users_count >= 5 ? 'high' : 'medium',
                'actionLabel' => "Qayta sotuvga chiqarish",
            ];
        })->values()->all();

        return [
            'items' => $items,
            'pagination' => [
                'page' => $page,
                'totalPages' => max(1, (int) ceil($total / $perPage)),
                'total' => $total,
                'from' => $total > 0 ? $offset + 1 : 0,
                'to' => min($offset + $perPage, $total),
            ],
        ];
    }

    /**
     * Qayta sotuvga chiqishini kutayotgan mijozlar so'rovlari (ProductStockAlerts).
     */
    private function getStockAlerts(string $type, string $search, int $page, int $perPage): array
    {
        if (! Schema::hasTable('product_stock_alerts') || ! Schema::hasTable('books')) {
            return ['items' => [], 'pagination' => $this->emptyPagination()];
        }

        $stockSql = BranchStock::availableSql('book', 'books.id');

        $query = DB::table('product_stock_alerts')
            ->join('books', 'product_stock_alerts.product_id', '=', 'books.id')
            ->leftJoin('sellers', 'books.seller_id', '=', 'sellers.id')
            ->where('product_stock_alerts.product_type', 'book')
            ->groupBy(
                'books.id',
                'books.name',
                'books.author',
                'books.price',
                'books.discountPrice',
                'books.images',
                'books.artikul',
                'books.isbn',
                'books.seller_id',
                'sellers.shop_name',
                'sellers.phone_number',
                'sellers.business_role'
            )
            ->selectRaw("
                books.id,
                books.name,
                books.author,
                books.price,
                books.discountPrice,
                books.images,
                books.artikul,
                books.isbn,
                books.seller_id,
                sellers.shop_name,
                sellers.phone_number as seller_phone,
                COALESCE(sellers.business_role, 'seller') as seller_role,
                'book' as product_type,
                COUNT(DISTINCT product_stock_alerts.user_id) as users_count,
                COUNT(product_stock_alerts.id) as total_alerts,
                COUNT(CASE WHEN product_stock_alerts.notified_at IS NOT NULL THEN 1 END) as notified_count,
                MAX(product_stock_alerts.created_at) as latest_request_at,
                COUNT(DISTINCT product_stock_alerts.user_id) * COALESCE(books.discountPrice, books.price, 0) as potential_demand,
                GREATEST(0, COALESCE({$stockSql}, 0)) as available_stock
            ");

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('books.name', 'like', "%{$search}%")
                    ->orWhere('books.author', 'like', "%{$search}%")
                    ->orWhere('books.isbn', 'like', "%{$search}%")
                    ->orWhere('sellers.shop_name', 'like', "%{$search}%");
            });
        }

        $query->orderByDesc('users_count')->orderByDesc('latest_request_at');

        $total = $query->get()->count();
        $offset = ($page - 1) * $perPage;
        $rows = $query->skip($offset)->take($perPage)->get();

        $items = $rows->map(function ($row) {
            $imageUrls = ProductImageUrls::build($row->images);
            $firstImage = $imageUrls['original'][0] ?? null;

            return [
                'id' => $row->id,
                'name' => $row->name,
                'author' => $row->author,
                'productType' => 'book',
                'productTypeLabel' => 'Kitob',
                'image' => $firstImage,
                'price' => (float) $row->price,
                'discountPrice' => $row->discountPrice ? (float) $row->discountPrice : null,
                'artikul' => $row->artikul,
                'isbn' => $row->isbn,
                'availableStock' => (int) $row->available_stock,
                'usersCount' => (int) $row->users_count,
                'potentialDemand' => (float) $row->potential_demand,
                'sellerId' => $row->seller_id,
                'sellerName' => $row->shop_name ?: 'Kitobchi',
                'sellerPhone' => $row->seller_phone,
                'sellerRole' => $row->seller_role,
                'urgency' => (int) $row->users_count >= 3 ? 'high' : 'medium',
                'actionLabel' => "Qayta kelganda xabar berish",
            ];
        })->values()->all();

        return [
            'items' => $items,
            'pagination' => [
                'page' => $page,
                'totalPages' => max(1, (int) ceil($total / $perPage)),
                'total' => $total,
                'from' => $total > 0 ? $offset + 1 : 0,
                'to' => min($offset + $perPage, $total),
            ],
        ];
    }

    /**
     * Savatdagi eng ko'p saqlangan kitoblar (eng ko'p savatga qo'shilganlar).
     */
    private function getTopCartItems(string $type, string $search, int $page, int $perPage): array
    {
        if (! Schema::hasTable('my_carts') || ! Schema::hasTable('books')) {
            return ['items' => [], 'pagination' => $this->emptyPagination()];
        }

        $stockSql = BranchStock::availableSql('book', 'books.id');

        $query = DB::table('my_carts')
            ->join('books', 'my_carts.product_id', '=', 'books.id')
            ->leftJoin('sellers', 'books.seller_id', '=', 'sellers.id')
            ->where('my_carts.product_type', 'book')
            ->groupBy(
                'books.id',
                'books.name',
                'books.author',
                'books.price',
                'books.discountPrice',
                'books.images',
                'books.artikul',
                'books.isbn',
                'books.seller_id',
                'sellers.shop_name',
                'sellers.phone_number',
                'sellers.business_role'
            )
            ->selectRaw("
                books.id,
                books.name,
                books.author,
                books.price,
                books.discountPrice,
                books.images,
                books.artikul,
                books.isbn,
                books.seller_id,
                sellers.shop_name,
                sellers.phone_number as seller_phone,
                COALESCE(sellers.business_role, 'seller') as seller_role,
                'book' as product_type,
                COUNT(DISTINCT my_carts.user_id) as users_count,
                SUM(my_carts.count_item) as requested_units,
                SUM(my_carts.count_item * COALESCE(books.discountPrice, books.price, 0)) as total_in_cart_value,
                GREATEST(0, COALESCE({$stockSql}, 0)) as available_stock
            ");

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('books.name', 'like', "%{$search}%")
                    ->orWhere('books.author', 'like', "%{$search}%")
                    ->orWhere('books.isbn', 'like', "%{$search}%")
                    ->orWhere('sellers.shop_name', 'like', "%{$search}%");
            });
        }

        $query->orderByDesc('users_count')->orderByDesc('requested_units');

        $total = $query->get()->count();
        $offset = ($page - 1) * $perPage;
        $rows = $query->skip($offset)->take($perPage)->get();

        $items = $rows->map(function ($row) {
            $imageUrls = ProductImageUrls::build($row->images);
            $firstImage = $imageUrls['original'][0] ?? null;

            return [
                'id' => $row->id,
                'name' => $row->name,
                'author' => $row->author,
                'productType' => 'book',
                'productTypeLabel' => 'Kitob',
                'image' => $firstImage,
                'price' => (float) $row->price,
                'discountPrice' => $row->discountPrice ? (float) $row->discountPrice : null,
                'artikul' => $row->artikul,
                'isbn' => $row->isbn,
                'availableStock' => (int) $row->available_stock,
                'usersCount' => (int) $row->users_count,
                'requestedUnits' => (int) $row->requested_units,
                'potentialDemand' => (float) $row->total_in_cart_value,
                'sellerId' => $row->seller_id,
                'sellerName' => $row->shop_name ?: 'Kitobchi',
                'sellerPhone' => $row->seller_phone,
                'sellerRole' => $row->seller_role,
                'urgency' => (int) $row->available_stock <= 0 ? 'high' : ((int) $row->available_stock < 5 ? 'medium' : 'normal'),
                'actionLabel' => "Sotuvni tezlashtirish",
            ];
        })->values()->all();

        return [
            'items' => $items,
            'pagination' => [
                'page' => $page,
                'totalPages' => max(1, (int) ceil($total / $perPage)),
                'total' => $total,
                'from' => $total > 0 ? $offset + 1 : 0,
                'to' => min($offset + $perPage, $total),
            ],
        ];
    }

    /**
     * Sevimlidagi eng ko'p saqlangan kitoblar (eng ko'p wishlistga qo'shilganlar).
     */
    private function getTopWishlistItems(string $type, string $search, int $page, int $perPage): array
    {
        if (! Schema::hasTable('favourite_products') || ! Schema::hasTable('books')) {
            return ['items' => [], 'pagination' => $this->emptyPagination()];
        }

        $stockSql = BranchStock::availableSql('book', 'books.id');

        $query = DB::table('favourite_products')
            ->join('books', 'favourite_products.product_id', '=', 'books.id')
            ->leftJoin('sellers', 'books.seller_id', '=', 'sellers.id')
            ->where('favourite_products.product_type', 'book')
            ->groupBy(
                'books.id',
                'books.name',
                'books.author',
                'books.price',
                'books.discountPrice',
                'books.images',
                'books.artikul',
                'books.isbn',
                'books.seller_id',
                'sellers.shop_name',
                'sellers.phone_number',
                'sellers.business_role'
            )
            ->selectRaw("
                books.id,
                books.name,
                books.author,
                books.price,
                books.discountPrice,
                books.images,
                books.artikul,
                books.isbn,
                books.seller_id,
                sellers.shop_name,
                sellers.phone_number as seller_phone,
                COALESCE(sellers.business_role, 'seller') as seller_role,
                'book' as product_type,
                COUNT(DISTINCT favourite_products.user_id) as users_count,
                COUNT(DISTINCT favourite_products.user_id) * COALESCE(books.discountPrice, books.price, 0) as potential_demand,
                GREATEST(0, COALESCE({$stockSql}, 0)) as available_stock
            ");

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('books.name', 'like', "%{$search}%")
                    ->orWhere('books.author', 'like', "%{$search}%")
                    ->orWhere('books.isbn', 'like', "%{$search}%")
                    ->orWhere('sellers.shop_name', 'like', "%{$search}%");
            });
        }

        $query->orderByDesc('users_count')->orderByDesc('potential_demand');

        $total = $query->get()->count();
        $offset = ($page - 1) * $perPage;
        $rows = $query->skip($offset)->take($perPage)->get();

        $items = $rows->map(function ($row) {
            $imageUrls = ProductImageUrls::build($row->images);
            $firstImage = $imageUrls['original'][0] ?? null;

            return [
                'id' => $row->id,
                'name' => $row->name,
                'author' => $row->author,
                'productType' => 'book',
                'productTypeLabel' => 'Kitob',
                'image' => $firstImage,
                'price' => (float) $row->price,
                'discountPrice' => $row->discountPrice ? (float) $row->discountPrice : null,
                'artikul' => $row->artikul,
                'isbn' => $row->isbn,
                'availableStock' => (int) $row->available_stock,
                'usersCount' => (int) $row->users_count,
                'potentialDemand' => (float) $row->potential_demand,
                'sellerId' => $row->seller_id,
                'sellerName' => $row->shop_name ?: 'Kitobchi',
                'sellerPhone' => $row->seller_phone,
                'sellerRole' => $row->seller_role,
                'urgency' => (int) $row->available_stock <= 0 ? 'high' : 'normal',
                'actionLabel' => "Targ'ibot / Aksiya",
            ];
        })->values()->all();

        return [
            'items' => $items,
            'pagination' => [
                'page' => $page,
                'totalPages' => max(1, (int) ceil($total / $perPage)),
                'total' => $total,
                'from' => $total > 0 ? $offset + 1 : 0,
                'to' => min($offset + $perPage, $total),
            ],
        ];
    }

    private function emptyPagination(): array
    {
        return [
            'page' => 1,
            'totalPages' => 1,
            'total' => 0,
            'from' => 0,
            'to' => 0,
        ];
    }
}
