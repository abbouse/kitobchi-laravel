<?php

namespace App\Http\Controllers\Api\Seller;

use App\Enums\OrderStatusCode;
use App\Enums\PaymentStatusCode;
use App\Http\Controllers\Controller;
use App\Models\Books;
use App\Models\Gifts;
use App\Models\ProductViewLog;
use App\Models\Seller;
use App\Models\SellerLocation;
use App\Models\SellerOrder;
use App\Models\SellerOrderItem;
use App\Models\Stationery;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HisobotController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:seller');
    }

    private function getStoreSellerId($seller): int
    {
        return (int) ($seller->parent_id ?: $seller->id);
    }

    private function hasDashboardAccess($seller): bool
    {
        if (! $seller->parent_id) {
            return true;
        }

        return in_array((int) $seller->role, [1, 4], true);
    }

    private function resolveSellerScopeIds(int $storeSellerId)
    {
        return Seller::query()
            ->where('id', $storeSellerId)
            ->orWhere('parent_id', $storeSellerId)
            ->pluck('id')
            ->filter()
            ->unique()
            ->values();
    }

    private function resolvePeriod(Request $request, ?Seller $storeSeller = null): array
    {
        $period = (string) $request->query('period', '30d');
        $now = now();

        switch ($period) {
            case '7d':
                $start = $now->copy()->startOfDay()->subDays(6);
                $group = 'day';
                break;
            case '90d':
                $period = '90d';
                $start = $now->copy()->startOfDay()->subDays(89);
                $group = 'day';
                break;
            case '6m':
                $period = '6m';
                $start = $now->copy()->startOfMonth()->subMonths(5);
                $group = 'month';
                break;
            case '1y':
                $period = '1y';
                $start = $now->copy()->startOfMonth()->subMonths(11);
                $group = 'month';
                break;
            case '30d':
            default:
                $period = '30d';
                $start = $now->copy()->startOfDay()->subDays(29);
                $group = 'day';
                break;
        }

        $storeStartedAt = $storeSeller?->created_at
            ? Carbon::parse($storeSeller->created_at)->startOfDay()
            : null;

        if ($storeStartedAt && $start->lt($storeStartedAt)) {
            $start = $storeStartedAt->copy();
        }

        $end = $now->copy()->endOfDay();
        if ($storeStartedAt && $end->lt($start)) {
            $end = $start->copy()->endOfDay();
        }
        $previousEnd = $start->copy()->subSecond();
        $previousStart = $previousEnd->copy()->subSeconds($end->diffInSeconds($start));

        return [
            'period' => $period,
            'start' => $start,
            'end' => $end,
            'previous_start' => $previousStart,
            'previous_end' => $previousEnd,
            'group' => $group,
        ];
    }

    private function baseCompletedOrders($sellerScopeIds, Carbon $start, Carbon $end)
    {
        $query = SellerOrder::query()
            ->join('solds', 'solds.id', '=', 'seller_orders.order_id')
            ->whereIn('seller_orders.seller_id', $sellerScopeIds)
            ->whereNotNull('solds.completed_at')
            ->whereBetween('solds.completed_at', [$start, $end]);
        $this->applyCompletedPaidSoldFilter($query);

        return $query;
    }

    private function applyCompletedPaidSoldFilter($query, string $table = 'solds')
    {
        return $query
            ->where(function ($statusQuery) use ($table) {
                $statusQuery->whereIn("{$table}.status_code", [
                    OrderStatusCode::DELIVERED->value,
                    OrderStatusCode::CUSTOMER_RECEIVED->value,
                ])
                    ->orWhere(function ($fallback) use ($table) {
                        $fallback->whereNull("{$table}.status_code")
                            ->whereIn("{$table}.status", [
                                OrderStatusCode::DELIVERED->legacy(),
                                OrderStatusCode::CUSTOMER_RECEIVED->legacy(),
                            ]);
                    });
            })
            ->where(function ($paymentQuery) use ($table) {
                $paymentQuery->where("{$table}.payment_status_code", PaymentStatusCode::PAID->value)
                    ->orWhere(function ($fallback) use ($table) {
                        $fallback->whereNull("{$table}.payment_status_code")
                            ->where("{$table}.paymentStatus", PaymentStatusCode::PAID->legacy());
                    });
            });
    }

    private function resolveSellerLocation(int $storeSellerId, ?int $locationId): ?SellerLocation
    {
        if (! $locationId) {
            return null;
        }

        return SellerLocation::query()
            ->where('seller_id', $storeSellerId)
            ->where('is_deleted', false)
            ->where('id', $locationId)
            ->first();
    }

    private function requestedLocationIdForSeller($seller, Request $request): ?int
    {
        if ($seller->parent_id) {
            return $seller->seller_location_id
                ? (int) $seller->seller_location_id
                : -1;
        }

        return $request->filled('location_id')
            ? (int) $request->query('location_id')
            : null;
    }

    private function applyBranchFilterToSellerOrders($query, ?SellerLocation $location, string $table = 'seller_orders')
    {
        if (! $location) {
            return $query;
        }

        $jsonLocationExpr = "CAST(JSON_UNQUOTE(JSON_EXTRACT({$table}.address, '$[0].location_id')) AS UNSIGNED)";

        if ($location->is_main) {
            return $query->where(function ($inner) use ($table, $jsonLocationExpr, $location) {
                $inner->whereRaw("{$jsonLocationExpr} = ?", [$location->id])
                    ->orWhere(function ($legacy) use ($table, $jsonLocationExpr) {
                        $legacy->whereRaw("{$jsonLocationExpr} IS NULL")
                            ->where(function ($delivery) use ($table) {
                                $delivery->whereRaw("LOWER(COALESCE({$table}.delivery_type, '')) != ?", ['pickup'])
                                    ->orWhereNull("{$table}.delivery_type");
                            });
                    });
            });
        }

        return $query->whereRaw("{$jsonLocationExpr} = ?", [$location->id]);
    }

    public function index(Request $request)
    {
        $seller = Auth::guard('seller')->user();
        if (! $seller) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $storeSellerId = $this->getStoreSellerId($seller);
        $storeSeller = Seller::query()->find($storeSellerId);
        $period = $this->resolvePeriod($request, $storeSeller);
        $selectedLocation = $this->resolveSellerLocation(
            $storeSellerId,
            $this->requestedLocationIdForSeller($seller, $request)
        );

        if ($seller->parent_id && ! $selectedLocation) {
            return response()->json([
                'success' => false,
                'message' => 'Xodimga faol filial biriktirilmagan.',
            ], 403);
        }

        if (! $this->hasDashboardAccess($seller)) {
            return response()->json([
                'success' => true,
                'data' => [
                    'period' => $period['period'],
                    'sales_count' => 0,
                    'sales_price' => 0,
                    'seller_balance' => 0,
                    'seller_clients' => 0,
                    'kitobchi_clients' => User::count(),
                    'average_order_value' => 0,
                    'items_sold' => 0,
                    'repeat_clients' => 0,
                    'total_views' => 0,
                    'recommended_views' => 0,
                    'top_sellers_label' => $period['start']->format('d.m.Y')
                        .' – '
                        .$period['end']->format('d.m.Y'),
                    'top_sellers' => [],
                    'top_products' => [],
                ],
            ], 200);
        }

        $payload = (function () use (
            $storeSellerId,
            $selectedLocation,
            $period
        ) {
            $sellerScopeIds = $this->resolveSellerScopeIds($storeSellerId);

            $ordersQuery = $this->baseCompletedOrders($sellerScopeIds, $period['start'], $period['end']);
            $this->applyBranchFilterToSellerOrders($ordersQuery, $selectedLocation);
            $salesCount = (clone $ordersQuery)->count();
            $salesPrice = (int) ((clone $ordersQuery)->sum('seller_orders.amount') ?? 0);
            $previousOrdersQuery = $this->baseCompletedOrders(
                $sellerScopeIds,
                $period['previous_start'],
                $period['previous_end']
            );
            $this->applyBranchFilterToSellerOrders($previousOrdersQuery, $selectedLocation);
            $previousSalesPrice = (int) ((clone $previousOrdersQuery)->sum('seller_orders.amount') ?? 0);
            $sellerBalance = (int) optional(Seller::find($storeSellerId))->balance;
            $sellerClients = (clone $ordersQuery)
                ->distinct('seller_orders.client_id')
                ->count('seller_orders.client_id');

            $itemsSoldQuery = SellerOrderItem::query()
                ->join('seller_orders', 'seller_orders.id', '=', 'seller_order_items.order_id')
                ->join('solds', 'solds.id', '=', 'seller_orders.order_id')
                ->whereIn('seller_order_items.seller_id', $sellerScopeIds)
                ->whereNotNull('solds.completed_at')
                ->whereBetween('solds.completed_at', [$period['start'], $period['end']]);
            $this->applyCompletedPaidSoldFilter($itemsSoldQuery);
            $this->applyBranchFilterToSellerOrders($itemsSoldQuery, $selectedLocation, 'seller_orders');
            $itemsSold = (int) $itemsSoldQuery->sum('seller_order_items.quantity');

            $repeatClientsQuery = SellerOrder::query()
                ->join('solds', 'solds.id', '=', 'seller_orders.order_id')
                ->whereIn('seller_orders.seller_id', $sellerScopeIds)
                ->whereNotNull('solds.completed_at')
                ->whereBetween('solds.completed_at', [$period['start'], $period['end']])
                ->select('seller_orders.client_id', DB::raw('COUNT(*) as orders_count'))
                ->groupBy('seller_orders.client_id');
            $this->applyCompletedPaidSoldFilter($repeatClientsQuery);
            $this->applyBranchFilterToSellerOrders($repeatClientsQuery, $selectedLocation);
            $repeatClients = (int) $repeatClientsQuery
                ->having('orders_count', '>', 1)
                ->get()
                ->count();

            $viewLogsQuery = ProductViewLog::query()
                ->whereIn('seller_id', $sellerScopeIds)
                ->whereBetween('created_at', [$period['start'], $period['end']]);

            $totalViews = (int) (clone $viewLogsQuery)->count();
            $recommendedViews = (int) (clone $viewLogsQuery)
                ->where('recommendation_active', true)
                ->count();

            $topSellers = SellerOrder::query()
                ->join('solds', 'solds.id', '=', 'seller_orders.order_id')
                ->select('seller_orders.seller_id', DB::raw('SUM(seller_orders.amount) as total_sales'), DB::raw('COUNT(*) as orders_count'))
                ->whereIn('seller_orders.seller_id', $sellerScopeIds)
                ->whereNotNull('solds.completed_at')
                ->whereBetween('solds.completed_at', [$period['start'], $period['end']])
                ->groupBy('seller_orders.seller_id');
            $this->applyCompletedPaidSoldFilter($topSellers);
            $this->applyBranchFilterToSellerOrders($topSellers, $selectedLocation);
            $topSellers = $topSellers
                ->orderByDesc('total_sales')
                ->orderByDesc('orders_count')
                ->orderBy('seller_orders.seller_id')
                ->take(10)
                ->get();

            $sellerIds = $topSellers->pluck('seller_id')->filter()->unique()->values();
            $sellerMap = Seller::query()
                ->whereIn('id', $sellerIds)
                ->get(['id', 'shop_name', 'photo'])
                ->keyBy('id');

            $topSellersPayload = $topSellers->values()->map(function ($row, $index) use ($sellerMap) {
                $sellerInfo = $sellerMap->get($row->seller_id);

                return [
                    'seller_id' => (int) $row->seller_id,
                    'name' => $sellerInfo->shop_name ?? 'Unknown',
                    'photo' => $sellerInfo->photo,
                    'sales_amount' => (int) $row->total_sales,
                    'orders_count' => (int) $row->orders_count,
                    'rank' => $index + 1,
                ];
            })->all();

            $topProducts = SellerOrderItem::query()
                ->select(
                    'seller_order_items.product_id',
                    'seller_order_items.type',
                    DB::raw('SUM(seller_order_items.quantity) as total_quantity'),
                    DB::raw('SUM(seller_order_items.price * seller_order_items.quantity) as total_price')
                )
                ->join('seller_orders', 'seller_orders.id', '=', 'seller_order_items.order_id')
                ->join('solds', 'solds.id', '=', 'seller_orders.order_id')
                ->whereIn('seller_order_items.seller_id', $sellerScopeIds)
                ->whereNotNull('solds.completed_at')
                ->whereBetween('solds.completed_at', [$period['start'], $period['end']])
                ->groupBy('seller_order_items.product_id', 'seller_order_items.type');
            $this->applyCompletedPaidSoldFilter($topProducts);
            $this->applyBranchFilterToSellerOrders($topProducts, $selectedLocation, 'seller_orders');
            $topProducts = $topProducts
                ->orderByDesc('total_quantity')
                ->take(10)
                ->get();

            $bookIds = $topProducts->where('type', 'book')->pluck('product_id')->unique()->values();
            $stationeryIds = $topProducts->where('type', 'stationery')->pluck('product_id')->unique()->values();
            $giftIds = $topProducts->where('type', 'gift')->pluck('product_id')->unique()->values();

            $bookMap = Books::query()
                ->whereIn('id', $bookIds)
                ->get(['id', 'name', 'images'])
                ->keyBy('id');
            $stationeryMap = Stationery::query()
                ->whereIn('id', $stationeryIds)
                ->get(['id', 'name', 'images'])
                ->keyBy('id');
            $giftMap = Gifts::query()
                ->whereIn('id', $giftIds)
                ->get(['id', 'name', 'images'])
                ->keyBy('id');

            $topProductsPayload = $topProducts->values()->map(function ($row, $index) use ($bookMap, $stationeryMap, $giftMap) {
                $product = match ((string) $row->type) {
                    'stationery' => $stationeryMap->get($row->product_id),
                    'gift' => $giftMap->get($row->product_id),
                    default => $bookMap->get($row->product_id),
                };

                $images = is_array($product?->images) ? $product->images : [];

                return [
                    'product_id' => (int) $row->product_id,
                    'type' => (string) $row->type,
                    'name' => $product->name ?? 'Unknown',
                    'image' => $images[0] ?? null,
                    'quantity' => (int) $row->total_quantity,
                    'total_price' => (int) $row->total_price,
                    'rank' => $index + 1,
                ];
            })->all();

            $bookCategoryRows = SellerOrderItem::query()
                ->join('seller_orders', 'seller_orders.id', '=', 'seller_order_items.order_id')
                ->join('solds', 'solds.id', '=', 'seller_orders.order_id')
                ->join('books', 'books.id', '=', 'seller_order_items.product_id')
                ->leftJoin('book_categories', 'book_categories.id', '=', 'books.category_id')
                ->whereIn('seller_order_items.seller_id', $sellerScopeIds)
                ->where('seller_order_items.type', 'book')
                ->whereNotNull('solds.completed_at')
                ->whereBetween('solds.completed_at', [$period['start'], $period['end']])
                ->selectRaw("'book' as product_type")
                ->selectRaw('COALESCE(book_categories.id, 0) as category_id')
                ->selectRaw("COALESCE(book_categories.name_uz, 'Kitob') as name_uz")
                ->selectRaw("COALESCE(book_categories.name_ru, 'Книги') as name_ru")
                ->selectRaw("COALESCE(book_categories.name_en, 'Books') as name_en")
                ->selectRaw("COALESCE(book_categories.name_ja, '本') as name_ja")
                ->selectRaw('SUM(seller_order_items.quantity) as total_quantity')
                ->selectRaw('SUM(seller_order_items.price * seller_order_items.quantity) as total_revenue')
                ->groupBy([
                    'book_categories.id',
                    'book_categories.name_uz',
                    'book_categories.name_ru',
                    'book_categories.name_en',
                    'book_categories.name_ja',
                ]);
            $this->applyCompletedPaidSoldFilter($bookCategoryRows);
            $this->applyBranchFilterToSellerOrders($bookCategoryRows, $selectedLocation, 'seller_orders');

            $stationeryCategoryRows = SellerOrderItem::query()
                ->join('seller_orders', 'seller_orders.id', '=', 'seller_order_items.order_id')
                ->join('solds', 'solds.id', '=', 'seller_orders.order_id')
                ->join('stationeries', 'stationeries.id', '=', 'seller_order_items.product_id')
                ->leftJoin('stationery_categories', 'stationery_categories.id', '=', 'stationeries.category_id')
                ->whereIn('seller_order_items.seller_id', $sellerScopeIds)
                ->where('seller_order_items.type', 'stationery')
                ->whereNotNull('solds.completed_at')
                ->whereBetween('solds.completed_at', [$period['start'], $period['end']])
                ->selectRaw("'stationery' as product_type")
                ->selectRaw('COALESCE(stationery_categories.id, 0) as category_id')
                ->selectRaw("COALESCE(stationery_categories.name_uz, 'Kanselyariya') as name_uz")
                ->selectRaw("COALESCE(stationery_categories.name_ru, 'Канцелярия') as name_ru")
                ->selectRaw("COALESCE(stationery_categories.name_en, 'Stationery') as name_en")
                ->selectRaw("COALESCE(stationery_categories.name_ja, '文房具') as name_ja")
                ->selectRaw('SUM(seller_order_items.quantity) as total_quantity')
                ->selectRaw('SUM(seller_order_items.price * seller_order_items.quantity) as total_revenue')
                ->groupBy([
                    'stationery_categories.id',
                    'stationery_categories.name_uz',
                    'stationery_categories.name_ru',
                    'stationery_categories.name_en',
                    'stationery_categories.name_ja',
                ]);
            $this->applyCompletedPaidSoldFilter($stationeryCategoryRows);
            $this->applyBranchFilterToSellerOrders($stationeryCategoryRows, $selectedLocation, 'seller_orders');

            $giftCategoryRows = SellerOrderItem::query()
                ->join('seller_orders', 'seller_orders.id', '=', 'seller_order_items.order_id')
                ->join('solds', 'solds.id', '=', 'seller_orders.order_id')
                ->whereIn('seller_order_items.seller_id', $sellerScopeIds)
                ->where('seller_order_items.type', 'gift')
                ->whereNotNull('solds.completed_at')
                ->whereBetween('solds.completed_at', [$period['start'], $period['end']])
                ->selectRaw("'gift' as product_type")
                ->selectRaw('0 as category_id')
                ->selectRaw("'Sovg\\'a' as name_uz")
                ->selectRaw("'Подарки' as name_ru")
                ->selectRaw("'Gifts' as name_en")
                ->selectRaw("'ギフト' as name_ja")
                ->selectRaw('SUM(seller_order_items.quantity) as total_quantity')
                ->selectRaw('SUM(seller_order_items.price * seller_order_items.quantity) as total_revenue');
            $this->applyCompletedPaidSoldFilter($giftCategoryRows);
            $this->applyBranchFilterToSellerOrders($giftCategoryRows, $selectedLocation, 'seller_orders');

            $categoryRows = $bookCategoryRows->get()
                ->concat($stationeryCategoryRows->get())
                ->concat($giftCategoryRows->get())
                ->filter(fn ($row) => (int) ($row->total_revenue ?? 0) > 0)
                ->sortByDesc('total_revenue')
                ->values();

            $categoryRevenueTotal = (int) $categoryRows->sum('total_revenue');
            $categorySalesPayload = $categoryRows
                ->take(4)
                ->map(function ($row) use ($categoryRevenueTotal) {
                    $revenue = (int) ($row->total_revenue ?? 0);

                    return [
                        'product_type' => (string) ($row->product_type ?? 'book'),
                        'category_id' => (int) ($row->category_id ?? 0),
                        'name_uz' => (string) ($row->name_uz ?? 'Kategoriya'),
                        'name_ru' => (string) ($row->name_ru ?? 'Категория'),
                        'name_en' => (string) ($row->name_en ?? 'Category'),
                        'name_ja' => (string) ($row->name_ja ?? 'カテゴリ'),
                        'quantity' => (int) ($row->total_quantity ?? 0),
                        'revenue' => $revenue,
                        'share_percent' => $categoryRevenueTotal > 0
                            ? round(($revenue / $categoryRevenueTotal) * 100, 1)
                            : 0,
                    ];
                })
                ->values()
                ->all();

            return [
                'period' => $period['period'],
                'selected_location_id' => $selectedLocation?->id,
                'selected_location_address' => $selectedLocation?->fullAddress,
                'sales_count' => (int) $salesCount,
                'sales_price' => (int) $salesPrice,
                'previous_sales_price' => $previousSalesPrice,
                'sales_growth_percent' => $previousSalesPrice > 0
                    ? round((($salesPrice - $previousSalesPrice) / $previousSalesPrice) * 100, 1)
                    : null,
                'seller_balance' => (int) $sellerBalance,
                'seller_clients' => (int) $sellerClients,
                'kitobchi_clients' => (int) User::count(),
                'average_order_value' => $salesCount > 0 ? (int) round($salesPrice / $salesCount) : 0,
                'items_sold' => $itemsSold,
                'repeat_clients' => $repeatClients,
                'total_views' => $totalViews,
                'recommended_views' => $recommendedViews,
                'top_sellers_label' => $period['start']->format('d.m.Y')
                    .' – '
                    .$period['end']->format('d.m.Y'),
                'top_sellers' => $topSellersPayload,
                'top_products' => $topProductsPayload,
                'category_sales' => $categorySalesPayload,
            ];
        })();

        return response()->json([
            'success' => true,
            'data' => $payload,
        ], 200);
    }

    public function getSalesStats(Request $request)
    {
        $seller = Auth::guard('seller')->user();
        if (! $seller) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        if (! $this->hasDashboardAccess($seller)) {
            return response()->json(['success' => true, 'data' => []], 200);
        }

        $storeSellerId = $this->getStoreSellerId($seller);
        $storeSeller = Seller::query()->find($storeSellerId);
        $period = $this->resolvePeriod($request, $storeSeller);
        $selectedLocation = $this->resolveSellerLocation(
            $storeSellerId,
            $this->requestedLocationIdForSeller($seller, $request)
        );

        if ($seller->parent_id && ! $selectedLocation) {
            return response()->json([
                'success' => false,
                'message' => 'Xodimga faol filial biriktirilmagan.',
            ], 403);
        }
        $data = (function () use (
            $storeSellerId,
            $period,
            $selectedLocation
        ) {
            $sellerScopeIds = $this->resolveSellerScopeIds($storeSellerId);
            $data = [];

            $viewRows = ProductViewLog::query()
                ->selectRaw('DATE(created_at) as bucket_key')
                ->selectRaw('COUNT(*) as total_views')
                ->selectRaw('SUM(CASE WHEN recommendation_active = 1 THEN 1 ELSE 0 END) as total_recommended_views')
                ->whereIn('seller_id', $sellerScopeIds)
                ->whereBetween('created_at', [$period['start'], $period['end']])
                ->groupBy('bucket_key')
                ->get()
                ->keyBy('bucket_key');

            if ($period['group'] === 'month') {
                $rows = SellerOrder::query()
                    ->join('solds', 'solds.id', '=', 'seller_orders.order_id')
                    ->selectRaw("DATE_FORMAT(solds.completed_at, '%Y-%m') as bucket_key")
                    ->selectRaw('SUM(seller_orders.amount) as total_amount')
                    ->selectRaw('COUNT(*) as total_orders')
                    ->selectRaw('COUNT(DISTINCT seller_orders.client_id) as total_clients')
                    ->whereIn('seller_orders.seller_id', $sellerScopeIds)
                    ->whereNotNull('solds.completed_at')
                    ->whereBetween('solds.completed_at', [$period['start'], $period['end']])
                    ->groupBy('bucket_key');
                $this->applyCompletedPaidSoldFilter($rows);
                $this->applyBranchFilterToSellerOrders($rows, $selectedLocation);
                $rows = $rows->get()->keyBy('bucket_key');

                $cursor = $period['start']->copy();
                while ($cursor <= $period['end']) {
                    $bucketKey = $cursor->format('Y-m');
                    $row = $rows->get($bucketKey);
                    $viewStats = $viewRows
                        ->filter(fn ($_, $key) => str_starts_with((string) $key, $bucketKey))
                        ->values();

                    $data[] = [
                        'time' => $cursor->copy()->startOfMonth()->toDateString(),
                        'label' => $cursor->translatedFormat('M'),
                        'value' => (int) ($row->total_amount ?? 0),
                        'orders' => (int) ($row->total_orders ?? 0),
                        'clients' => (int) ($row->total_clients ?? 0),
                        'views' => (int) $viewStats->sum('total_views'),
                        'recommended_views' => (int) $viewStats->sum('total_recommended_views'),
                    ];

                    $cursor->addMonth();
                }
            } elseif ($period['group'] === 'week') {
                $rows = SellerOrder::query()
                    ->join('solds', 'solds.id', '=', 'seller_orders.order_id')
                    ->selectRaw('YEARWEEK(solds.completed_at, 1) as bucket_key')
                    ->selectRaw('SUM(seller_orders.amount) as total_amount')
                    ->selectRaw('COUNT(*) as total_orders')
                    ->selectRaw('COUNT(DISTINCT seller_orders.client_id) as total_clients')
                    ->whereIn('seller_orders.seller_id', $sellerScopeIds)
                    ->whereNotNull('solds.completed_at')
                    ->whereBetween('solds.completed_at', [$period['start'], $period['end']])
                    ->groupBy('bucket_key');
                $this->applyCompletedPaidSoldFilter($rows);
                $this->applyBranchFilterToSellerOrders($rows, $selectedLocation);
                $rows = $rows->get()->keyBy('bucket_key');

                $cursor = $period['start']->copy()->startOfWeek(Carbon::MONDAY);
                while ($cursor <= $period['end']) {
                    $bucketKey = (int) $cursor->format('oW');
                    $row = $rows->get($bucketKey);
                    $weekStart = $cursor->copy()->startOfWeek(Carbon::MONDAY)->toDateString();
                    $weekEnd = $cursor->copy()->endOfWeek(Carbon::SUNDAY)->toDateString();
                    $viewStats = $viewRows
                        ->filter(fn ($_, $key) => $key >= $weekStart && $key <= $weekEnd)
                        ->values();

                    $data[] = [
                        'time' => $cursor->toDateString(),
                        'label' => $cursor->translatedFormat('j M'),
                        'value' => (int) ($row->total_amount ?? 0),
                        'orders' => (int) ($row->total_orders ?? 0),
                        'clients' => (int) ($row->total_clients ?? 0),
                        'views' => (int) $viewStats->sum('total_views'),
                        'recommended_views' => (int) $viewStats->sum('total_recommended_views'),
                    ];

                    $cursor->addWeek();
                }
            } else {
                $rows = SellerOrder::query()
                    ->join('solds', 'solds.id', '=', 'seller_orders.order_id')
                    ->selectRaw('DATE(solds.completed_at) as bucket_key')
                    ->selectRaw('SUM(seller_orders.amount) as total_amount')
                    ->selectRaw('COUNT(*) as total_orders')
                    ->selectRaw('COUNT(DISTINCT seller_orders.client_id) as total_clients')
                    ->whereIn('seller_orders.seller_id', $sellerScopeIds)
                    ->whereNotNull('solds.completed_at')
                    ->whereBetween('solds.completed_at', [$period['start'], $period['end']])
                    ->groupBy('bucket_key');
                $this->applyCompletedPaidSoldFilter($rows);
                $this->applyBranchFilterToSellerOrders($rows, $selectedLocation);
                $rows = $rows->get()->keyBy('bucket_key');

                $cursor = $period['start']->copy();
                while ($cursor <= $period['end']) {
                    $bucketKey = $cursor->toDateString();
                    $row = $rows->get($bucketKey);
                    $viewRow = $viewRows->get($bucketKey);

                    $data[] = [
                        'time' => $bucketKey,
                        'label' => $cursor->translatedFormat('j M'),
                        'value' => (int) ($row->total_amount ?? 0),
                        'orders' => (int) ($row->total_orders ?? 0),
                        'clients' => (int) ($row->total_clients ?? 0),
                        'views' => (int) ($viewRow->total_views ?? 0),
                        'recommended_views' => (int) ($viewRow->total_recommended_views ?? 0),
                    ];

                    $cursor->addDay();
                }
            }

            return $data;
        })();

        return response()->json([
            'success' => true,
            'data' => $data,
        ], 200);
    }
}
