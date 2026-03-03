<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Models\SellerOrder;
use App\Models\User;
use App\Models\SellerOrderItem;
use App\Models\Seller;
use App\Models\Books; // ✅ Books import qo'shildi
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HisobotController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:seller');
    }

    /**
     * ✅ OWNER DO'KON ID QAYTARADI
     */
    private function getStoreSellerId($seller)
    {
        return $seller->parent_id ?: $seller->id;
    }

    public function index()
    {
        $seller = Auth::guard('seller')->user();
        if (!$seller) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $storeSellerId = $this->getStoreSellerId($seller); // ✅ OWNER ID
        
        $access = true;
        if ($seller->parent_id) {
            $access = in_array($seller->role, [1, 4]);
        }

        if ($access) {
            // ✅ BARCHA QUERYLARDA $storeSellerId
            $salesCount = SellerOrder::where('seller_id', $storeSellerId)
                ->where('status', '2')
                ->count();

            $salesPrice = SellerOrder::where('seller_id', $storeSellerId)
                ->where('status', '2')
                ->sum('amount');

            $sellerBalance = Seller::find($storeSellerId)->balance; // ✅ OWNER BALANCE

            $sellerClients = SellerOrder::where('seller_id', $storeSellerId)
                ->where('status', '2')
                ->distinct('client_id')
                ->count('client_id');

            $kitobchiClients = User::count();

            $topSellers = [];
            if (!$seller->parent_id || $seller->role == 1) {
                $topSellers = SellerOrder::select(
                    'seller_id',
                    DB::raw('SUM(amount) as total_sales')
                )
                    ->where('status', '2')
                    ->groupBy('seller_id')
                    ->orderByDesc('total_sales')
                    ->take(5)
                    ->get()
                    ->map(function ($item) {
                        $sellerInfo = Seller::find($item->seller_id);
                        return [
                            'seller_id' => $item->seller_id,
                            'name' => $sellerInfo->shop_name ?? 'Unknown',
                            'sales_amount' => (int)$item->total_sales,
                        ];
                    });
            }

            $topProducts = [];
            if (!$seller->parent_id || in_array($seller->role, [1, 2])) {
                $topProducts = SellerOrderItem::select(
                    'product_id',
                    DB::raw('SUM(quantity) as total_quantity'),
                    DB::raw('SUM(price * quantity) as total_price')
                )
                    ->whereIn('order_id', SellerOrder::where('status', '2')->pluck('id'))
                    ->groupBy('product_id')
                    ->orderByDesc('total_quantity')
                    ->take(5)
                    ->get()
                    ->map(function ($product) {
                        $book = Books::find($product->product_id);
                        return [
                            'product_id' => $product->product_id,
                            'name' => $book->name ?? 'Unknown',
                            'quantity' => (int)$product->total_quantity,
                            'total_price' => (int)$product->total_price,
                        ];
                    });
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'sales_count' => (int)$salesCount,
                    'sales_price' => (int)$salesPrice,
                    'seller_balance' => (int)$sellerBalance,
                    'seller_clients' => (int)$sellerClients,
                    'kitobchi_clients' => (int)$kitobchiClients,
                    'top_sellers' => $topSellers,
                    'top_products' => $topProducts,
                ]
            ], 200);
        } else {
            return response()->json([
                'success' => true,
                'data' => [
                    'sales_count' => 0,
                    'sales_price' => 0,
                    'seller_balance' => 0,
                    'seller_clients' => 0,
                    'kitobchi_clients' => User::count(),
                    'top_sellers' => [],
                    'top_products' => [],
                ]
            ], 200);
        }
    }

    public function getSalesStats(Request $request)
    {
        $seller = Auth::guard('seller')->user();
        if (!$seller) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $storeSellerId = $this->getStoreSellerId($seller); // ✅ OWNER ID
        
        // ✅ TUZATILDI: Role 1,2,4 ko'radi
        $access = true;
        if ($seller->parent_id) {
            $access = in_array($seller->role, [1, 2, 4]); // Admin, Product Manager, Accountant
        }

        if ($access) {
            $year = $request->query('year', date('Y'));

            $sales = SellerOrder::select(
                DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month"),
                DB::raw('SUM(amount) as total_amount')
            )
                ->where('status', '2')
                ->where('seller_id', $storeSellerId) // ✅ OWNER ID
                ->whereYear('created_at', $year)
                ->groupBy(DB::raw("DATE_FORMAT(created_at, '%Y-%m')"))
                ->orderBy('month')
                ->get();

            $months = [
                '01' => 'Jan', '02' => 'Feb', '03' => 'Mar', '04' => 'Apr',
                '05' => 'May', '06' => 'Jun', '07' => 'Jul', '08' => 'Aug',
                '09' => 'Sep', '10' => 'Oct', '11' => 'Nov', '12' => 'Dec'
            ];

            $salesData = [];
            foreach (range(1, 12) as $month) {
                $monthKey = sprintf('%02d', $month);
                $monthData = $sales->firstWhere('month', "$year-$monthKey");
                $salesData[] = [
                    'time' => "$year-$monthKey-01",
                    'value' => $monthData ? (int)$monthData->total_amount : 0,
                    'label' => $months[$monthKey]
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $salesData
            ], 200);
        } else {
            return response()->json([
                'success' => true,
                'data' => []
            ], 200);
        }
    }
}