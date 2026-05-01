<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Models\Books;
use App\Models\Seller;
use App\Models\SellerLocation;
use App\Models\SellerOrder;
use App\Models\SellerOrderItem;
use App\Models\Stationery;
use App\Models\User;
use App\Models\ProductViewLog;
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
        if (!$seller->parent_id) {
            return true;
        }

        return in_array((int) $seller->role, [1, 4], true);
    }

    private function resolvePeriod(Request $request): array
    {
        $period = (string) $request->query('period', '30d');
        $now = now();

        switch ($period) {
            case '7d':
                $start = $now->copy()->startOfDay()->subDays(6);
                $group = 'day';
                break;
            case '90d':
                $start = $now->copy()->startOfDay()->subDays(89);
                $group = 'week';
                break;
            case '1y':
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

        $end = $now->copy()->endOfDay();
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

    private function baseCompletedOrders(int $storeSellerId, Carbon $start, Carbon $end)
    {
        return SellerOrder::query()
            ->where('seller_id', $storeSellerId)
            ->where('status', '3')
            ->whereBetween('created_at', [$start, $end]);
    }

    private function resolveSellerLocation(int $storeSellerId, ?int $locationId): ?SellerLocation
    {
        if (!$locationId) {
            return null;
        }

        return SellerLocation::query()
            ->where('seller_id', $storeSellerId)
            ->where('is_deleted', false)
            ->where('id', $locationId)
            ->first();
    }

    private function applyBranchFilterToSellerOrders($query, ?SellerLocation $location, string $table = 'seller_orders')
    {
        if (!$location) {
            return $query;
        }

        $jsonLocationExpr = "CAST(JSON_UNQUOTE(JSON_EXTRACT({$table}.address, '$[0].location_id')) AS UNSIGNED)";

        if ($location->is_main) {
            return $query->where(function ($inner) use ($table, $jsonLocationExpr, $location) {
                $inner->where("{$table}.delivery_type", '!=', 'pickup')
                    ->orWhereNull("{$table}.delivery_type")
                    ->orWhere(function ($pickup) use ($table, $jsonLocationExpr, $location) {
                        $pickup->where("{$table}.delivery_type", 'pickup')
                            ->whereRaw("{$jsonLocationExpr} = ?", [$location->id]);
                    });
            });
        }

        return $query->where("{$table}.delivery_type", 'pickup')
            ->whereRaw("{$jsonLocationExpr} = ?", [$location->id]);
    }

    public function index(Request $request)
    {
        $seller = Auth::guard('seller')->user();
        if (!$seller) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $storeSellerId = $this->getStoreSellerId($seller);
        $period = $this->resolvePeriod($request);
        $selectedLocation = $this->resolveSellerLocation(
            $storeSellerId,
            $request->filled('location_id') ? (int) $request->query('location_id') : null
        );

        if (!$this->hasDashboardAccess($seller)) {
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
                    'top_sellers_label' => now()->translatedFormat('F Y'),
                    'top_sellers' => [],
                    'top_products' => [],
                ],
            ], 200);
        }

        $ordersQuery = $this->baseCompletedOrders($storeSellerId, $period['start'], $period['end']);
        $this->applyBranchFilterToSellerOrders($ordersQuery, $selectedLocation);
        $salesCount = (clone $ordersQuery)->count();
        $salesPrice = (int) ((clone $ordersQuery)->sum('amount') ?? 0);
        $sellerBalance = (int) optional(Seller::find($storeSellerId))->balance;
        $sellerClients = (clone $ordersQuery)->distinct('client_id')->count('client_id');

        $itemsSold = (int) SellerOrderItem::query()
            ->join('seller_orders', 'seller_orders.id', '=', 'seller_order_items.order_id')
            ->where('seller_order_items.seller_id', $storeSellerId)
            ->where('seller_orders.status', '3')
            ->whereBetween('seller_orders.created_at', [$period['start'], $period['end']])
            ->sum('seller_order_items.quantity');

        $repeatClientsQuery = SellerOrder::query()
            ->where('seller_id', $storeSellerId)
            ->where('status', '3')
            ->whereBetween('created_at', [$period['start'], $period['end']])
            ->select('client_id', DB::raw('COUNT(*) as orders_count'))
            ->groupBy('client_id');
        $this->applyBranchFilterToSellerOrders($repeatClientsQuery, $selectedLocation);
        $repeatClients = (int) $repeatClientsQuery
            ->having('orders_count', '>', 1)
            ->get()
            ->count();

        $viewLogsQuery = ProductViewLog::query()
            ->where('seller_id', $storeSellerId)
            ->whereBetween('created_at', [$period['start'], $period['end']]);

        $totalViews = (int) (clone $viewLogsQuery)->count();
        $recommendedViews = (int) (clone $viewLogsQuery)
            ->where('recommendation_active', true)
            ->count();

        $monthStart = now()->copy()->startOfMonth();
        $monthEnd = now()->copy()->endOfDay();
        $topSellers = SellerOrder::query()
            ->select('seller_id', DB::raw('SUM(amount) as total_sales'), DB::raw('COUNT(*) as orders_count'))
            ->where('status', '3')
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->groupBy('seller_id')
            ->orderByDesc('total_sales')
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
            ->where('seller_order_items.seller_id', $storeSellerId)
            ->where('seller_orders.status', '3')
            ->whereBetween('seller_orders.created_at', [$period['start'], $period['end']])
            ->groupBy('seller_order_items.product_id', 'seller_order_items.type');
        $this->applyBranchFilterToSellerOrders($topProducts, $selectedLocation, 'seller_orders');
        $topProducts = $topProducts
            ->orderByDesc('total_quantity')
            ->take(10)
            ->get();

        $bookIds = $topProducts->where('type', 'book')->pluck('product_id')->unique()->values();
        $stationeryIds = $topProducts->where('type', 'stationery')->pluck('product_id')->unique()->values();

        $bookMap = Books::query()
            ->whereIn('id', $bookIds)
            ->get(['id', 'name', 'images'])
            ->keyBy('id');
        $stationeryMap = Stationery::query()
            ->whereIn('id', $stationeryIds)
            ->get(['id', 'name', 'images'])
            ->keyBy('id');

        $topProductsPayload = $topProducts->values()->map(function ($row, $index) use ($bookMap, $stationeryMap) {
            $product = $row->type === 'stationery'
                ? $stationeryMap->get($row->product_id)
                : $bookMap->get($row->product_id);

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

        return response()->json([
            'success' => true,
            'data' => [
                'period' => $period['period'],
                'selected_location_id' => $selectedLocation?->id,
                'selected_location_address' => $selectedLocation?->fullAddress,
                'sales_count' => (int) $salesCount,
                'sales_price' => (int) $salesPrice,
                'seller_balance' => (int) $sellerBalance,
                'seller_clients' => (int) $sellerClients,
                'kitobchi_clients' => (int) User::count(),
                'average_order_value' => $salesCount > 0 ? (int) round($salesPrice / $salesCount) : 0,
                'items_sold' => $itemsSold,
                'repeat_clients' => $repeatClients,
                'total_views' => $totalViews,
                'recommended_views' => $recommendedViews,
                'top_sellers_label' => $monthStart->translatedFormat('F Y'),
                'top_sellers' => $topSellersPayload,
                'top_products' => $topProductsPayload,
            ],
        ], 200);
    }

    public function getSalesStats(Request $request)
    {
        $seller = Auth::guard('seller')->user();
        if (!$seller) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        if (!$this->hasDashboardAccess($seller)) {
            return response()->json(['success' => true, 'data' => []], 200);
        }

        $storeSellerId = $this->getStoreSellerId($seller);
        $period = $this->resolvePeriod($request);
        $selectedLocation = $this->resolveSellerLocation(
            $storeSellerId,
            $request->filled('location_id') ? (int) $request->query('location_id') : null
        );
        $data = [];

        $viewRows = ProductViewLog::query()
            ->selectRaw('DATE(created_at) as bucket_key')
            ->selectRaw('COUNT(*) as total_views')
            ->selectRaw('SUM(CASE WHEN recommendation_active = 1 THEN 1 ELSE 0 END) as total_recommended_views')
            ->where('seller_id', $storeSellerId)
            ->whereBetween('created_at', [$period['start'], $period['end']])
            ->groupBy('bucket_key')
            ->get()
            ->keyBy('bucket_key');

        if ($period['group'] === 'month') {
            $rows = SellerOrder::query()
                ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as bucket_key")
                ->selectRaw('SUM(amount) as total_amount')
                ->selectRaw('COUNT(*) as total_orders')
                ->selectRaw('COUNT(DISTINCT client_id) as total_clients')
                ->where('seller_id', $storeSellerId)
                ->where('status', '3')
                ->whereBetween('created_at', [$period['start'], $period['end']])
                ->groupBy('bucket_key');
            $this->applyBranchFilterToSellerOrders($rows, $selectedLocation);
            $rows = $rows->get()
                ->keyBy('bucket_key');

            $cursor = $period['start']->copy();
            while ($cursor <= $period['end']) {
                $bucketKey = $cursor->format('Y-m');
                $row = $rows->get($bucketKey);
                $viewStats = $viewRows
                    ->filter(fn($_, $key) => str_starts_with((string) $key, $bucketKey))
                    ->values();

                $data[] = [
                    'time' => $cursor->copy()->startOfMonth()->toDateString(),
                    'label' => $cursor->format('M'),
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
                ->selectRaw('YEARWEEK(created_at, 1) as bucket_key')
                ->selectRaw('SUM(amount) as total_amount')
                ->selectRaw('COUNT(*) as total_orders')
                ->selectRaw('COUNT(DISTINCT client_id) as total_clients')
                ->where('seller_id', $storeSellerId)
                ->where('status', '3')
                ->whereBetween('created_at', [$period['start'], $period['end']])
                ->groupBy('bucket_key');
            $this->applyBranchFilterToSellerOrders($rows, $selectedLocation);
            $rows = $rows->get()
                ->keyBy('bucket_key');

            $cursor = $period['start']->copy()->startOfWeek(Carbon::MONDAY);
            while ($cursor <= $period['end']) {
                $bucketKey = (int) $cursor->format('oW');
                $row = $rows->get($bucketKey);
                $weekStart = $cursor->copy()->startOfWeek(Carbon::MONDAY)->toDateString();
                $weekEnd = $cursor->copy()->endOfWeek(Carbon::SUNDAY)->toDateString();
                $viewStats = $viewRows
                    ->filter(fn($_, $key) => $key >= $weekStart && $key <= $weekEnd)
                    ->values();

                $data[] = [
                    'time' => $cursor->toDateString(),
                    'label' => $cursor->format('d M'),
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
                ->selectRaw('DATE(created_at) as bucket_key')
                ->selectRaw('SUM(amount) as total_amount')
                ->selectRaw('COUNT(*) as total_orders')
                ->selectRaw('COUNT(DISTINCT client_id) as total_clients')
                ->where('seller_id', $storeSellerId)
                ->where('status', '3')
                ->whereBetween('created_at', [$period['start'], $period['end']])
                ->groupBy('bucket_key');
            $this->applyBranchFilterToSellerOrders($rows, $selectedLocation);
            $rows = $rows->get()
                ->keyBy('bucket_key');

            $cursor = $period['start']->copy();
            while ($cursor <= $period['end']) {
                $bucketKey = $cursor->toDateString();
                $row = $rows->get($bucketKey);
                $viewRow = $viewRows->get($bucketKey);

                $data[] = [
                    'time' => $bucketKey,
                    'label' => $cursor->format('d M'),
                    'value' => (int) ($row->total_amount ?? 0),
                    'orders' => (int) ($row->total_orders ?? 0),
                    'clients' => (int) ($row->total_clients ?? 0),
                    'views' => (int) ($viewRow->total_views ?? 0),
                    'recommended_views' => (int) ($viewRow->total_recommended_views ?? 0),
                ];

                $cursor->addDay();
            }
        }

        return response()->json([
            'success' => true,
            'data' => $data,
        ], 200);
    }
}
