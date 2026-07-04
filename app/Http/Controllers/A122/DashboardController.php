<?php

namespace App\Http\Controllers\A122;

use App\Http\Controllers\Controller;
use App\Enums\CourierOrderStatusCode;
use App\Enums\OrderStatusCode;
use App\Enums\PaymentStatusCode;
use App\Enums\SellerOrderStatusCode;
use App\Models\BookClub;
use App\Models\BookClubComment;
use App\Models\Books;
use App\Models\BotTicket;
use App\Models\Couriers;
use App\Models\CourierTransaction;
use App\Models\GiftCertificate;
use App\Models\MysteryBoxDelivery;
use App\Models\MysteryBoxSubscription;
use App\Models\Report;
use App\Models\Seller;
use App\Models\SellerAd;
use App\Models\SellerOrder;
use App\Models\SellerTransaction;
use App\Models\Sold;
use App\Models\Stationery;
use App\Models\User;
use App\Models\CourierOrder;
use App\Services\SellerOrderSettlementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    private const TTL = 300;

    private const TTL_HOT = 60;

    private function sellerPayoutQuery()
    {
        return SellerTransaction::query()->where(function ($query) {
            $query->whereNull('category')
                ->orWhere('category', SellerOrderSettlementService::CATEGORY_WITHDRAWAL);
        });
    }

    private function sellerOrderIncomeQuery()
    {
        return SellerTransaction::query()
            ->where('type', 'income')
            ->where('category', SellerOrderSettlementService::CATEGORY_ORDER_SALE);
    }

    private function salesGeoCatalog(): array
    {
        return [
            'uzbekistan' => [
                'label' => "O'zbekiston",
                'aliases' => [
                    "o'zbekiston", 'ozbekiston', 'uzbekistan', 'узбекистан', 'uzbekiston',
                ],
                'regions' => [
                    'tashkent_city' => ['label' => 'Toshkent shahri', 'aliases' => ['toshkent shahri', 'tashkent city', 'город ташкент', 'г ташкент']],
                    'tashkent_region' => ['label' => 'Toshkent viloyati', 'aliases' => ['toshkent viloyati', 'tashkent region', 'ташкентская область', 'toshkent tumani emas']],
                    'andijan' => ['label' => 'Andijon viloyati', 'aliases' => ['andijon', 'andijan', 'андижан']],
                    'fergana' => ['label' => "Farg'ona viloyati", 'aliases' => ["farg'ona", 'fargona', 'fergana', 'фергана']],
                    'namangan' => ['label' => 'Namangan viloyati', 'aliases' => ['namangan', 'наманган']],
                    'samarqand' => ['label' => 'Samarqand viloyati', 'aliases' => ['samarqand', 'samarkand', 'самарканд']],
                    'bukhara' => ['label' => 'Buxoro viloyati', 'aliases' => ['buxoro', 'bukhara', 'бухара']],
                    'khorezm' => ['label' => 'Xorazm viloyati', 'aliases' => ['xorazm', 'khorezm', 'хорезм']],
                    'navoiy' => ['label' => 'Navoiy viloyati', 'aliases' => ['navoiy', 'navoi', 'навои']],
                    'jizzakh' => ['label' => 'Jizzax viloyati', 'aliases' => ['jizzax', 'jizzakh', 'джизак']],
                    'sirdaryo' => ['label' => 'Sirdaryo viloyati', 'aliases' => ['sirdaryo', 'syrdarya', 'сырдарья']],
                    'surxondaryo' => ['label' => 'Surxondaryo viloyati', 'aliases' => ['surxondaryo', 'surkhandarya', 'сурхандарья']],
                    'qashqadaryo' => ['label' => 'Qashqadaryo viloyati', 'aliases' => ['qashqadaryo', 'kashkadarya', 'кашкадарья']],
                    'qarakalpakstan' => ['label' => "Qoraqalpog'iston Respublikasi", 'aliases' => ["qoraqalpog'iston", 'qoraqalpogiston', 'karakalpakstan', 'каракалпакстан', 'nukus']],
                ],
            ],
        ];
    }

    private function normalizeGeoText(?string $value): string
    {
        $value = mb_strtolower(trim((string) $value));
        $value = str_replace(["’", "`", "ʻ", "ʼ"], "'", $value);
        $value = preg_replace('/[^\p{L}\p{N}\s\']+/u', ' ', $value) ?? '';
        $value = preg_replace('/\s+/u', ' ', $value) ?? '';

        return trim($value);
    }

    private function detectSalesGeoFromOrder(Sold $order): ?array
    {
        $catalog = $this->salesGeoCatalog();
        $texts = [];

        if (is_array($order->address)) {
            foreach ($order->address as $chunk) {
                if (!is_array($chunk)) {
                    continue;
                }
                foreach (['fullAddress', 'branch_address', 'address', 'region', 'city'] as $field) {
                    $value = $chunk[$field] ?? null;
                    if (is_string($value) && trim($value) !== '') {
                        $texts[] = $value;
                    }
                }
            }
        }

        foreach ([$order->recipient_region, $order->recipient_address] as $value) {
            if (is_string($value) && trim($value) !== '') {
                $texts[] = $value;
            }
        }

        $normalizedTexts = array_values(array_filter(array_map(
            fn ($text) => $this->normalizeGeoText($text),
            $texts
        )));

        if (empty($normalizedTexts)) {
            return null;
        }

        $countryKey = null;
        $countryLabel = null;
        $regionKey = null;
        $regionLabel = null;

        foreach ($catalog as $key => $country) {
            foreach ($country['regions'] as $rKey => $region) {
                foreach ($normalizedTexts as $text) {
                    foreach ($region['aliases'] as $alias) {
                        if (str_contains($text, $this->normalizeGeoText($alias))) {
                            $countryKey = $key;
                            $countryLabel = $country['label'];
                            $regionKey = $rKey;
                            $regionLabel = $region['label'];
                            break 3;
                        }
                    }
                }
            }

            foreach ($normalizedTexts as $text) {
                foreach ($country['aliases'] as $alias) {
                    if (str_contains($text, $this->normalizeGeoText($alias))) {
                        $countryKey = $key;
                        $countryLabel = $country['label'];
                        break 2;
                    }
                }
            }
        }

        if (!$countryKey) {
            return null;
        }

        return [
            'country_key' => $countryKey,
            'country_label' => $countryLabel,
            'region_key' => $regionKey,
            'region_label' => $regionLabel,
        ];
    }

    public function index(Request $request)
    {
        if ($request->has('clear_cache')) {
            foreach ($this->cacheKeys() as $key) {
                Cache::forget($key);
            }

            return redirect()->route('admin.dashboard')->with('success', 'Cache tozalandi.');
        }

        $admin = auth('panel')->user();
        $isSuperAdmin = $admin?->role === 'superadmin';

        return view('a122.dashboard', array_merge(
            $this->getData(),
            compact('admin', 'isSuperAdmin')
        ));
    }

    public function liveMonitor()
    {
        return view('a122.dashboard-live', [
            'snapshot' => $this->getLiveSnapshot(),
        ]);
    }

    public function liveMonitorData()
    {
        return response()->json($this->getLiveSnapshot());
    }

    private function cacheKeys(): array
    {
        return [
            'dash5_rev_total', 'dash5_rev_today', 'dash5_rev_month', 'dash5_rev_week',
            'dash5_ord_total', 'dash5_ord_today', 'dash5_ord_week',
            'dash5_ord_D', 'dash5_ord_A', 'dash5_ord_P', 'dash5_ord_B', 'dash5_ord_F',
            'dash5_daily_ord', 'dash5_daily_rev', 'dash5_monthly', 'dash5_monthly_fin',
            'dash5_sales_geo',
            'dash5_fin_delivery', 'dash5_fin_promo', 'dash5_fin_cashback',
            'dash5_fin_comm', 'dash5_fin_courier',
            'dash5_u_total', 'dash5_u_premium', 'dash5_u_online', 'dash5_u_today',
            'dash5_u_week', 'dash5_u_month', 'dash5_u_active', 'dash5_u_inactive',
            'dash5_u_isolated', 'dash5_u_verified',
            'dash5_u_list', 'dash5_u_daily',
            'dash5_s_total', 'dash5_s_pending', 'dash5_s_approved',
            'dash5_s_contract_expiring', 'dash5_s_contract_expired', 'dash5_s_contract_unsigned',
            'dash5_c_total', 'dash5_c_active', 'dash5_c_verification_pending',
            'dash5_gift_total', 'dash5_gift_pending', 'dash5_gift_used',
            'dash5_mystery_active', 'dash5_mystery_due', 'dash5_mystery_due_list',
            'dash5_top_mixed', 'dash5_top_buyers', 'dash5_recent',
            'dash5_fin_seller_pay', 'dash5_fin_courier_pay',
            'dash5_gmv_total', 'dash5_cancel_loss', 'dash5_repeat', 'dash5_new_buyers',
            'dash5_avg_comm_pct', 'dash5_aov_monthly', 'dash5_delivery_split',
            'dash5_type_rev', 'dash5_mystery_rev',
            'dash5_k_hreview_books', 'dash5_k_hreview_stat', 'dash5_k_ugc_admin',
        ];
    }

    private function getData(): array
    {
        $ttl = self::TTL;
        $hot = self::TTL_HOT;

        // ── USERS ─────────────────────────────────────────────
        $totalUsers = Cache::remember('dash5_u_total', $ttl, fn () => User::count());
        $premiumUsers = Cache::remember('dash5_u_premium', $ttl, fn () => User::where('is_premium', true)->count());
        $activeUsers = Cache::remember('dash5_u_active', $ttl, fn () => User::query()->phoneVerified()->count());
        $inactiveUsers = Cache::remember('dash5_u_inactive', $ttl, fn () => User::query()->phoneUnverified()->count());
        $onlineUsers = Cache::remember('dash5_u_online', $hot, fn () => User::where('last_seen_at', '>=', now()->subMinutes(5))->count());
        $newUsersToday = Cache::remember('dash5_u_today', $hot, fn () => User::whereDate('created_at', today())->count());
        $newUsersWeek = Cache::remember('dash5_u_week', $ttl, fn () => User::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count());
        $newUsersMonth = Cache::remember('dash5_u_month', $ttl, fn () => User::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count());
        $verifiedUsers = Cache::remember('dash5_u_verified', $ttl, fn () => User::where('isVerified', true)->count());
        $isolatedUsers = Cache::remember('dash5_u_isolated', $ttl, fn () => User::query()->phoneVerified()
            ->where(fn ($q) => $q->where('last_seen_at', '<=', now()->subDays(30))->orWhereNull('last_seen_at'))
            ->count()
        );
        $onlineUsersList = Cache::remember('dash5_u_list', $hot,
            fn () => User::where('last_seen_at', '>=', now()->subMinutes(5))
                ->select('id', 'name', 'lastname', 'avatar', 'last_seen_at')
                ->latest('last_seen_at')->take(8)->get()
        );
        $dailyNewUsers = Cache::remember('dash5_u_daily', $ttl, fn () => collect(range(6, 0))->map(fn ($i) => [
            'day' => now()->subDays($i)->format('D'),
            'count' => User::whereDate('created_at', now()->subDays($i))->count(),
        ])
        );

        // ── ORDERS ────────────────────────────────────────────
        $ordersByStatus = fn (OrderStatusCode $status) => Sold::query()
            ->where(function ($query) use ($status) {
                $query->where('status_code', $status->value)
                    ->orWhere(function ($fallback) use ($status) {
                        $fallback->whereNull('status_code')
                            ->where('status', $status->legacy());
                    });
            });
        $paidOrders = fn () => Sold::query()
            ->where(function ($query) {
                $query->where('payment_status_code', PaymentStatusCode::PAID->value)
                    ->orWhere(function ($fallback) {
                        $fallback->whereNull('payment_status_code')
                            ->where('paymentStatus', PaymentStatusCode::PAID->legacy());
                    });
            });
        $completedCourierOrders = fn () => DB::table('courier_orders')
            ->where(function ($query) {
                $query->where('status_code', CourierOrderStatusCode::CUSTOMER_RECEIVED->value)
                    ->orWhere(function ($fallback) {
                        $fallback->whereNull('status_code')
                            ->where('status', CourierOrderStatusCode::CUSTOMER_RECEIVED->legacy());
                    });
            });

        $totalOrders = Cache::remember('dash5_ord_total', $ttl, fn () => Sold::count());
        $todayOrders = Cache::remember('dash5_ord_today', $hot, fn () => Sold::whereDate('created_at', today())->count());
        $weekOrders = Cache::remember('dash5_ord_week', $ttl, fn () => Sold::where('created_at', '>=', now()->startOfWeek())->count());
        $completedOrders = Cache::remember('dash5_ord_D', $ttl, fn () => Sold::whereNotNull('completed_at')->count());
        $pendingOrders   = Cache::remember('dash5_ord_A', $hot, fn () => $ordersByStatus(OrderStatusCode::PENDING)->count());
        $packingOrders   = Cache::remember('dash5_ord_P', $ttl, fn () => $ordersByStatus(OrderStatusCode::PACKING)->count());
        $onwayOrders     = Cache::remember('dash5_ord_B', $ttl, fn () => $ordersByStatus(OrderStatusCode::IN_DELIVERY)->count());
        $cancelledOrders = Cache::remember('dash5_ord_F', $ttl, fn () => $ordersByStatus(OrderStatusCode::CANCELLED)->count());
        $completionRate  = $totalOrders > 0 ? round($completedOrders / $totalOrders * 100, 1) : 0;
        $cancellationRate = $totalOrders > 0 ? round($cancelledOrders / $totalOrders * 100, 1) : 0;
        $dailyOrders = Cache::remember('dash5_daily_ord', $ttl, fn () => collect(range(6, 0))->map(fn ($i) => [
            'day' => now()->subDays($i)->format('D'),
            'count' => Sold::whereDate('created_at', now()->subDays($i))->count(),
        ])
        );
        $dailyRevenue = Cache::remember('dash5_daily_rev', $ttl, fn () => collect(range(6, 0))->map(fn ($i) => [
            'day' => now()->subDays($i)->format('D'),
            'total' => (float) $paidOrders()->whereDate('created_at', now()->subDays($i))->sum('amount'),
        ])
        );

        // ── REVENUE ───────────────────────────────────────────
        $totalRevenue = Cache::remember('dash5_rev_total', $ttl, fn () => (float) $paidOrders()->sum('amount'));
        $todayRevenue = Cache::remember('dash5_rev_today', $hot, fn () => (float) $paidOrders()->whereDate('created_at', today())->sum('amount'));
        $weekRevenue = Cache::remember('dash5_rev_week', $ttl, fn () => (float) $paidOrders()->where('created_at', '>=', now()->startOfWeek())->sum('amount'));
        $monthRevenue = Cache::remember('dash5_rev_month', $ttl, fn () => (float) $paidOrders()->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->sum('amount'));
        $monthlyRevenue = Cache::remember('dash5_monthly', $ttl, fn () => collect(range(5, 0))->map(fn ($i) => [
            'month' => now()->subMonths($i)->format('M Y'),
            'total' => (int) $paidOrders()->whereMonth('created_at', now()->subMonths($i)->month)->whereYear('created_at', now()->subMonths($i)->year)->sum('amount'),
            'count' => $paidOrders()->whereMonth('created_at', now()->subMonths($i)->month)->whereYear('created_at', now()->subMonths($i)->year)->count(),
        ])
        );
        $avgOrderValue = $totalOrders > 0 ? (int) round($totalRevenue / max(1, $paidOrders()->count())) : 0;

        // ── FINANCIAL ─────────────────────────────────────────
        $totalDeliveryIncome = Cache::remember('dash5_fin_delivery', $ttl, fn () => (int) $paidOrders()->sum('deliveryPrice'));
        $monthDeliveryIncome = (int) $paidOrders()->whereMonth('created_at', now()->month)->sum('deliveryPrice');
        $hasCollectionDiscountAmount = Schema::hasColumn('solds', 'collectionDiscountAmount');
        $totalPromoDiscount = Cache::remember('dash5_fin_promo', $ttl, fn () => (int) $paidOrders()->where('discountAmount', '>', 0)->sum('discountAmount'));
        $monthPromoDiscount = (int) $paidOrders()->whereMonth('created_at', now()->month)->where('discountAmount', '>', 0)->sum('discountAmount');
        $promoOrdersCount = $paidOrders()->where('discountAmount', '>', 0)->count();
        $totalCollectionDiscount = $hasCollectionDiscountAmount
            ? Cache::remember('dash5_fin_collection_discount', $ttl, fn () => (int) $paidOrders()->where('collectionDiscountAmount', '>', 0)->sum('collectionDiscountAmount'))
            : 0;
        $monthCollectionDiscount = $hasCollectionDiscountAmount
            ? (int) $paidOrders()->whereMonth('created_at', now()->month)->where('collectionDiscountAmount', '>', 0)->sum('collectionDiscountAmount')
            : 0;
        $totalCashbackPaid = Cache::remember('dash5_fin_cashback', $ttl, fn () => (int) $paidOrders()->where('cashbackAmount', '>', 0)->sum('cashbackAmount'));
        $monthCashbackPaid = (int) $paidOrders()->whereMonth('created_at', now()->month)->where('cashbackAmount', '>', 0)->sum('cashbackAmount');
        $totalCommissionEarned = Cache::remember('dash5_fin_comm', $ttl, fn () => (int) $this->sellerOrderIncomeQuery()->where('status', 'approved')->sum('commissionPrice'));
        $monthCommissionEarned = (int) $this->sellerOrderIncomeQuery()->where('status', 'approved')->whereMonth('created_at', now()->month)->sum('commissionPrice');
        $pendingCommission = (int) $this->sellerOrderIncomeQuery()->where('status', 'pending')->sum('commissionPrice');
        $totalCourierPayout = Cache::remember('dash5_fin_courier', $ttl, fn () => (int) $completedCourierOrders()->sum('courierPrice'));
        $monthCourierPayout = (int) $completedCourierOrders()->whereMonth('created_at', now()->month)->sum('courierPrice');
        $totalSellerPayout = Cache::remember('dash5_fin_seller_pay', $ttl, fn () => (int) $this->sellerPayoutQuery()->where('status', 'approved')->sum('netAmount'));
        $monthSellerPayout = (int) $this->sellerPayoutQuery()->where('status', 'approved')->whereMonth('created_at', now()->month)->sum('netAmount');
        $pendingSellerPayout = (int) $this->sellerPayoutQuery()->where('status', 'pending')->sum('netAmount');
        $pendingCourierPayout = (int) CourierTransaction::where('status', 'pending')->sum('netAmount');
        $pendingSellerTxCount = $this->sellerPayoutQuery()->where('status', 'pending')->count();
        $pendingCourierTxCount = CourierTransaction::where('status', 'pending')->count();
        $netRevenue = $totalRevenue - $totalPromoDiscount - $totalCollectionDiscount - $totalCashbackPaid - $totalCourierPayout + $totalDeliveryIncome;
        $monthNetRevenue = $monthRevenue - $monthPromoDiscount - $monthCollectionDiscount - $monthCashbackPaid - $monthCourierPayout + $monthDeliveryIncome;

        $monthlyFinancial = Cache::remember('dash5_monthly_fin', $ttl, fn () => collect(range(5, 0))->map(function ($i) use ($paidOrders, $completedCourierOrders, $hasCollectionDiscountAmount) {
            $m = now()->subMonths($i);
            $mo = $m->month;
            $yr = $m->year;
            $rev = (int) $paidOrders()->whereMonth('created_at', $mo)->whereYear('created_at', $yr)->sum('amount');
            $promo = (int) $paidOrders()->whereMonth('created_at', $mo)->whereYear('created_at', $yr)->sum('discountAmount');
            $collectionDiscount = $hasCollectionDiscountAmount
                ? (int) $paidOrders()->whereMonth('created_at', $mo)->whereYear('created_at', $yr)->sum('collectionDiscountAmount')
                : 0;
            $cash = (int) $paidOrders()->whereMonth('created_at', $mo)->whereYear('created_at', $yr)->sum('cashbackAmount');
            $del = (int) $paidOrders()->whereMonth('created_at', $mo)->whereYear('created_at', $yr)->sum('deliveryPrice');
            $comm = (int) SellerTransaction::where('status', 'approved')->whereMonth('created_at', $mo)->whereYear('created_at', $yr)->sum('commissionPrice');
            $cour = (int) $completedCourierOrders()->whereMonth('created_at', $mo)->whereYear('created_at', $yr)->sum('courierPrice');

            return ['month' => $m->format('M'), 'revenue' => $rev, 'cost' => $promo + $collectionDiscount + $cash + $cour, 'commission' => $comm + $del, 'net' => $rev - $promo - $collectionDiscount - $cash - $cour + $del];
        })
        );

        // ── EXTENDED FINANCIAL (try/catch — jadvallar yo'q bo'lsa xato chiqmassin) ──
        $gmvTotal = $gmvMonth = 0.0;
        $cancelledRevLoss = $cancelledMonthLoss = 0;
        $mysteryRevTotal = $mysteryRevMonth = 0;
        $giftUsedInOrders = $giftUsedMonthOrders = 0;
        $repeatBuyersMonth = $newBuyersMonth = 0;
        $avgCommissionPct = 0.0;
        $aovMonthly = collect();
        $deliveryTypeSplit = collect();
        $revenueByType = ['book' => 0, 'stationery' => 0];
        $platformProfit = $platformProfitMonth = 0;

        try {
            $gmvTotal = Cache::remember('dash5_gmv_total', $ttl,
                fn () => (float) Sold::sum('amount'));
            $gmvMonth = (float) Sold::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)->sum('amount');

            $cancelledRevLoss = Cache::remember('dash5_cancel_loss', $ttl,
                fn () => (int) $ordersByStatus(OrderStatusCode::CANCELLED)->sum('amount'));
            $cancelledMonthLoss = (int) $ordersByStatus(OrderStatusCode::CANCELLED)
                ->whereMonth('created_at', now()->month)->sum('amount');

            $giftUsedInOrders = (int) $paidOrders()
                ->where('gift_certificate_discount', '>', 0)->sum('gift_certificate_discount');
            $giftUsedMonthOrders = (int) $paidOrders()
                ->where('gift_certificate_discount', '>', 0)
                ->whereMonth('created_at', now()->month)->sum('gift_certificate_discount');

            $repeatBuyersMonth = Cache::remember('dash5_repeat', $ttl, fn () => $paidOrders()
                ->whereMonth('created_at', now()->month)
                ->select('user_id')
                ->groupBy('user_id')
                ->havingRaw('COUNT(*) > 1')
                ->get()
                ->count()
            );
            $newBuyersMonth = Cache::remember('dash5_new_buyers', $ttl, fn () => $paidOrders()
                ->whereMonth('created_at', now()->month)
                ->select('user_id')
                ->groupBy('user_id')
                ->havingRaw('COUNT(*) = 1')
                ->get()
                ->count()
            );

            $avgCommissionPct = Cache::remember('dash5_avg_comm_pct', $ttl,
                fn () => round((float) $this->sellerOrderIncomeQuery()->where('status', 'approved')->avg('commissionPercent'), 1)
            );

            $aovMonthly = Cache::remember('dash5_aov_monthly', $ttl, fn () => collect(range(5, 0))->map(function ($i) use ($paidOrders) {
                $m = now()->subMonths($i);
                $cnt = $paidOrders()->whereMonth('created_at', $m->month)->whereYear('created_at', $m->year)->count();
                $sum = (float) $paidOrders()->whereMonth('created_at', $m->month)->whereYear('created_at', $m->year)->sum('amount');

                return ['month' => $m->format('M'), 'aov' => $cnt > 0 ? (int) ($sum / $cnt) : 0];
            })
            );

            $deliveryTypeSplit = Cache::remember('dash5_delivery_split', $ttl, fn () => $paidOrders()->whereNotNull('deliveryType')
                ->selectRaw('deliveryType, COUNT(*) as cnt, SUM(amount) as total')
                ->groupBy('deliveryType')->orderByDesc('cnt')->get()
            );

            $revenueByType = Cache::remember('dash5_type_rev', $ttl, function () use ($paidOrders) {
                $bookRev = 0;
                $statRev = 0;
                $paidOrders()->whereNotNull('items')->pluck('items')
                    ->each(function ($raw) use (&$bookRev, &$statRev) {
                        $items = is_array($raw) ? $raw : json_decode($raw, true);
                        if (! is_array($items)) {
                            return;
                        }
                        foreach ($items as $item) {
                            $p = (float) data_get($item, 'item_price', 0);
                            $q = (int) data_get($item, 'count_item', 1);
                            data_get($item, 'type') === 'stationery' ? $statRev += $p * $q : $bookRev += $p * $q;
                        }
                    });

                return ['book' => (int) $bookRev, 'stationery' => (int) $statRev];
            });

            $platformProfit = $totalCommissionEarned + $totalDeliveryIncome
                                 - $totalPromoDiscount - $totalCashbackPaid - $totalCourierPayout;
            $platformProfitMonth = $monthCommissionEarned + $monthDeliveryIncome
                                 - $monthPromoDiscount - $monthCashbackPaid - $monthCourierPayout;
        } catch (\Throwable $e) {
            // Jadvallar hali migrate qilinmagan bo'lsa default qiymatlar ishlatiladi
        }

        try {
            $mysteryRevTotal = Cache::remember('dash5_mystery_rev', $ttl,
                fn () => (int) MysteryBoxSubscription::whereIn('status', ['active', 'completed'])->sum('price_uzs'));
            $mysteryRevMonth = (int) MysteryBoxSubscription::whereIn('status', ['active', 'completed'])
                ->whereMonth('created_at', now()->month)->sum('price_uzs');
        } catch (\Throwable) {
        }

        // ── SALES GEO ANALYTICS ─────────────────────────────────
        $salesGeoCountries = [];
        $salesGeoRegionsByCountry = [];
        $salesGeoDefaultCountry = null;
        try {
            $salesGeo = Cache::remember('dash5_sales_geo', $ttl, function () {
                $countries = [];
                $regionsByCountry = [];

                Sold::query()
                    ->where(function ($query) {
                        $query->where('payment_status_code', PaymentStatusCode::PAID->value)
                            ->orWhere(function ($fallback) {
                                $fallback->whereNull('payment_status_code')
                                    ->where('paymentStatus', PaymentStatusCode::PAID->legacy());
                            });
                    })
                    ->select('id', 'amount', 'address', 'recipient_region', 'recipient_address')
                    ->orderBy('id')
                    ->chunk(300, function ($orders) use (&$countries, &$regionsByCountry) {
                        foreach ($orders as $order) {
                            $geo = $this->detectSalesGeoFromOrder($order);
                            if (!$geo) {
                                continue;
                            }

                            $countryKey = $geo['country_key'];
                            $regionKey = $geo['region_key'];
                            $amount = (float) ($order->amount ?? 0);

                            if (!isset($countries[$countryKey])) {
                                $countries[$countryKey] = [
                                    'key' => $countryKey,
                                    'label' => $geo['country_label'],
                                    'orders' => 0,
                                    'revenue' => 0,
                                    'regions_count' => 0,
                                ];
                            }

                            $countries[$countryKey]['orders']++;
                            $countries[$countryKey]['revenue'] += $amount;

                            if ($regionKey && $geo['region_label']) {
                                if (!isset($regionsByCountry[$countryKey][$regionKey])) {
                                    $regionsByCountry[$countryKey][$regionKey] = [
                                        'key' => $regionKey,
                                        'label' => $geo['region_label'],
                                        'orders' => 0,
                                        'revenue' => 0,
                                    ];
                                }

                                $regionsByCountry[$countryKey][$regionKey]['orders']++;
                                $regionsByCountry[$countryKey][$regionKey]['revenue'] += $amount;
                            }
                        }
                    });

                foreach ($regionsByCountry as $countryKey => &$regions) {
                    uasort($regions, fn ($a, $b) => $b['revenue'] <=> $a['revenue']);
                    $countries[$countryKey]['regions_count'] = count($regions);
                    $regions = array_values($regions);
                }
                unset($regions);

                uasort($countries, fn ($a, $b) => $b['revenue'] <=> $a['revenue']);

                return [
                    'countries' => array_values($countries),
                    'regions' => $regionsByCountry,
                ];
            });

            $salesGeoCountries = $salesGeo['countries'] ?? [];
            $salesGeoRegionsByCountry = $salesGeo['regions'] ?? [];
            $salesGeoDefaultCountry = $salesGeoCountries[0]['key'] ?? null;
        } catch (\Throwable) {
        }

        // ── BUSINESS ──────────────────────────────────────────
        $totalSellers = Cache::remember('dash5_s_total', $ttl, fn () => Seller::count());
        $pendingSellers = Cache::remember('dash5_s_pending', $ttl, fn () => Seller::where('status', 'pending')->count());
        $approvedSellers = Cache::remember('dash5_s_approved', $ttl, fn () => Seller::where('status', 'approved')->count());
        $totalCouriers = Cache::remember('dash5_c_total', $ttl, fn () => Couriers::count());
        $activeCouriers = Cache::remember('dash5_c_active', $ttl, fn () => Couriers::where('status', 'approved')->count());

        // Verifikatsiyasi kutilayotgan kuryerlar — admin ko'rib chiqishi kerak
        $couriersPendingVerification = 0;
        try {
            $couriersPendingVerification = Cache::remember('dash5_c_verification_pending', $ttl, fn () => Couriers::query()
                ->where('verification_status', 'pending')
                ->count()
            );
        } catch (\Throwable) {
            // Migration tushmagan bo'lsa default 0
        }

        // ── SELLER CONTRACTS ──────────────────────────────────
        // Yaqinda tugaydigan va allaqachon tugagan shartnomalar — alert va widget
        // uchun. Migration hali ishga tushmagan bo'lsa ham xato chiqarmaydi.
        $contractsExpiringCount = 0;
        $contractsExpiredCount  = 0;
        $contractsUnsignedCount = 0;
        try {
            $contractsExpiringCount = Cache::remember('dash5_s_contract_expiring', $ttl, fn () => Seller::query()
                ->whereNotNull('contract_expires_at')
                ->whereDate('contract_expires_at', '>=', now()->toDateString())
                ->whereDate('contract_expires_at', '<=', now()->addDays(30)->toDateString())
                ->where('contract_status', '!=', 'terminated')
                ->count()
            );
            $contractsExpiredCount = Cache::remember('dash5_s_contract_expired', $ttl, fn () => Seller::query()
                ->whereNotNull('contract_expires_at')
                ->whereDate('contract_expires_at', '<', now()->toDateString())
                ->where('contract_status', '!=', 'terminated')
                ->count()
            );
            // Tasdiqlangan, lekin shartnomasi imzolanmagan sellerlar — admin
            // tomonidan qog'oz jarayonini boshlash kerak.
            $contractsUnsignedCount = Cache::remember('dash5_s_contract_unsigned', $ttl, fn () => Seller::query()
                ->where('status', 'approved')
                ->where('contract_signed', false)
                ->count()
            );
        } catch (\Throwable) {
            // Migration tushmagan bo'lsa default 0
        }

        // ── GIFT CERTIFICATES ─────────────────────────────────
        $giftTotal = $giftPending = $giftUsed = $giftSent = $giftRevenue = $giftExpiringSoon = 0;
        try {
            $giftTotal = Cache::remember('dash5_gift_total', $ttl, fn () => GiftCertificate::count());
            $giftPending = Cache::remember('dash5_gift_pending', $hot, fn () => GiftCertificate::where('status', 'pending_payment')->count());
            $giftUsed = Cache::remember('dash5_gift_used', $ttl, fn () => GiftCertificate::where('status', 'used')->count());
            $giftSent = GiftCertificate::where('status', 'active')->count();
            $giftRevenue = (int) GiftCertificate::where('status', 'used')->sum('nominal_uzs');
            $giftExpiringSoon = GiftCertificate::where('status', 'active')
                ->where('expires_at', '<=', now()->addDays(7))
                ->where('expires_at', '>=', now())->count();
        } catch (\Throwable) {
        }

        // ── MYSTERY BOX ───────────────────────────────────────
        $mysteryActive = $mysteryDueCount = $mysteryOverdue = $mysteryPending = $stalePreparing = 0;
        $mysteryDueSoon = $mysteryDueToday = collect();
        try {
            $mysteryActive = Cache::remember('dash5_mystery_active', $ttl, fn () => MysteryBoxSubscription::where('status', 'active')->count());
            $mysteryDueCount = Cache::remember('dash5_mystery_due', $hot, fn () => MysteryBoxDelivery::query()
                ->whereNotIn('status', MysteryBoxDelivery::FINAL_STATUSES)
                ->whereDate('planned_for_date', '<=', now()->toDateString())
                ->count());
            $mysteryOverdue = MysteryBoxDelivery::query()
                ->whereNotIn('status', MysteryBoxDelivery::FINAL_STATUSES)
                ->whereDate('planned_for_date', '<=', now()->subDays(3)->toDateString())
                ->count();
            $mysteryPending = MysteryBoxSubscription::where('status', 'pending_payment')->count();
            $stalePreparing = MysteryBoxDelivery::whereIn('status', [MysteryBoxDelivery::STATUS_PREPARING, MysteryBoxDelivery::STATUS_READY_TO_SHIP])
                ->where('prepared_at', '<=', now()->subDays(2))
                ->count();
            $mysteryDueSoon = MysteryBoxDelivery::with(['subscription.user:id,name,lastname,phone_number', 'subscription.plan:id,name_uz'])
                ->whereNotIn('status', MysteryBoxDelivery::FINAL_STATUSES)
                ->whereBetween('planned_for_date', [now()->toDateString(), now()->addDays(7)->toDateString()])
                ->orderBy('planned_for_date')->take(6)->get();
            $mysteryDueToday = Cache::remember('dash5_mystery_due_list', $hot,
                fn () => MysteryBoxDelivery::with(['subscription.user:id,name,lastname,phone_number', 'subscription.plan:id,name_uz'])
                    ->whereNotIn('status', MysteryBoxDelivery::FINAL_STATUSES)
                    ->whereDate('planned_for_date', '<=', now()->toDateString())
                    ->orderBy('planned_for_date')->take(8)->get()
            );
        } catch (\Throwable) {
        }

        // ── TOP PRODUCTS ──────────────────────────────────────
        $topMixedProducts = Cache::remember('dash5_top_mixed', $ttl, function () use ($paidOrders) {
            $stats = [];
            $paidOrders()->whereNotNull('items')->pluck('items')
                ->each(function ($raw) use (&$stats) {
                    $items = is_array($raw) ? $raw : json_decode($raw, true);
                    if (! is_array($items)) {
                        return;
                    }
                    foreach ($items as $item) {
                        $id = (int) data_get($item, 'item_id');
                        $type = data_get($item, 'type', 'book');
                        $qty = (int) data_get($item, 'count_item', 1);
                        $pr = (float) data_get($item, 'item_price', 0);
                        if (! $id) {
                            continue;
                        }
                        $key = $type.':'.$id;
                        $stats[$key]['id'] = $id;
                        $stats[$key]['type'] = $type;
                        $stats[$key]['sold_count'] = ($stats[$key]['sold_count'] ?? 0) + $qty;
                        $stats[$key]['total_revenue'] = ($stats[$key]['total_revenue'] ?? 0) + $pr * $qty;
                    }
                });
            uasort($stats, fn ($a, $b) => $b['sold_count'] <=> $a['sold_count']);
            $top = array_slice($stats, 0, 8, true);
            $books = Books::whereIn('id', collect($top)->where('type', 'book')->pluck('id'))->select('id', 'name', 'images', 'author')->get()->keyBy('id');
            $stats2 = Stationery::whereIn('id', collect($top)->where('type', 'stationery')->pluck('id'))->select('id', 'name', 'images')->get()->keyBy('id');

            return collect($top)->map(function ($s) use ($books, $stats2) {
                $model = $s['type'] === 'book' ? $books->get($s['id']) : $stats2->get($s['id']);
                if (! $model) {
                    return null;
                }
                $model->_type = $s['type'];
                $model->sold_count = $s['sold_count'];
                $model->total_revenue = (int) $s['total_revenue'];

                return $model;
            })->filter()->values();
        });

        // ── TOP BUYERS ────────────────────────────────────────
        $topBuyers = Cache::remember('dash5_top_buyers', $ttl, function () {
            $rows = Sold::query()
                ->where(function ($query) {
                    $query->where('payment_status_code', PaymentStatusCode::PAID->value)
                        ->orWhere(function ($fallback) {
                            $fallback->whereNull('payment_status_code')
                                ->where('paymentStatus', PaymentStatusCode::PAID->legacy());
                        });
                })
                ->select('user_id')
                ->selectRaw('COUNT(*) as order_count')
                ->selectRaw('SUM(amount) as total_spent')
                ->groupBy('user_id')
                ->orderByDesc('total_spent')
                ->take(5)
                ->get();
            $users = User::whereIn('id', $rows->pluck('user_id'))->select('id', 'name', 'lastname', 'avatar')->get()->keyBy('id');

            return $rows->map(fn ($r) => (object) ['user_id' => $r->user_id, 'order_count' => $r->order_count, 'total_spent' => $r->total_spent, 'user' => $users->get($r->user_id)]);
        });

        // ── RECENT ORDERS ─────────────────────────────────────
        $recentOrders = Cache::remember('dash5_recent', $hot, fn () => Sold::with('user:id,name,lastname,avatar')->latest()->take(10)->get()->map(fn ($o) => [
            'id' => $o->id,
            'customer' => $o->user ? trim($o->user->name.' '.$o->user->lastname) : 'Mehmon',
            'avatar' => $o->user?->avatar,
            'amount' => number_format($o->amount),
            'status' => match ($o->status_code ?? $o->status) {
                'delivered', 'C' => 'Yetib bordi',
                'customer_received', 'D' => 'Mijoz qabul qildi',
                'pending', 'A' => 'Kutilmoqda',
                'packing', 'P' => 'Qadoqlanmoqda',
                'in_delivery', 'B' => "Yo'lda",
                'returned' => 'Qaytgan',
                'cancelled', 'F' => 'Bekor qilindi',
                default => ($o->status_code ?? $o->status)
            },
            'date' => $o->created_at->format('d.m H:i'),
            'gift' => (bool) $o->gift,
        ])
        );

        $kangarooHumanReviewBooks = 0;
        $kangarooHumanReviewStationery = 0;

        // ── ALERTS ────────────────────────────────────────────
        // [color, icon, title, description, url]
        $alerts = [];

        if ($pendingOrders > 0) {
            $alerts[] = ['danger', 'bi-bag-x', 'Kutilayotgan buyurtmalar',
                "{$pendingOrders} ta buyurtma qabul qilinmagan", route('admin.orders.index', ['status' => 'A'])];
        }

        if ($pendingSellers > 0) {
            $alerts[] = ['warning', 'bi-shop-window', 'Yangi sotuvchi arizasi',
                "{$pendingSellers} ta sotuvchi tasdiqlash kutmoqda", route('admin.sellers.index', ['status' => 'pending'])];
        }

        if ($contractsExpiredCount > 0) {
            $alerts[] = ['danger', 'bi-file-x', 'Shartnomasi tugagan sellerlar',
                "{$contractsExpiredCount} ta sellerning shartnomasi tugagan — uzaytirish lozim",
                route('admin.sellers.index', ['contract' => 'expired'])];
        }
        if ($contractsExpiringCount > 0) {
            $alerts[] = ['warning', 'bi-calendar-event', 'Shartnoma tugashi yaqin',
                "{$contractsExpiringCount} ta seller shartnomasi 30 kun ichida tugaydi",
                route('admin.sellers.index', ['contract' => 'expiring'])];
        }
        if ($contractsUnsignedCount > 0) {
            $alerts[] = ['warning', 'bi-pencil-square', 'Shartnoma imzolanmagan sellerlar',
                "{$contractsUnsignedCount} ta tasdiqlangan seller bilan shartnoma hali imzolanmagan",
                route('admin.sellers.index', ['contract' => 'unsigned'])];
        }
        if ($couriersPendingVerification > 0) {
            $alerts[] = ['warning', 'bi-shield-check', 'Kuryer hujjatlari tekshirilmoqda',
                "{$couriersPendingVerification} ta kuryerning hujjatlari ko'rib chiqilishi kerak",
                route('admin.couriers.index', ['verification' => 'pending'])];
        }

        if ($pendingSellerTxCount > 0) {
            $alerts[] = ['warning', 'bi-arrow-left-right', 'Seller to\'lov arizasi',
                "{$pendingSellerTxCount} ta — ".number_format($pendingSellerPayout).' UZS', route('admin.transactions.index', ['tab' => 'pending'])];
        }

        if ($pendingCourierTxCount > 0) {
            $alerts[] = ['warning', 'bi-bicycle', 'Kuryer to\'lov arizasi',
                "{$pendingCourierTxCount} ta — ".number_format($pendingCourierPayout).' UZS', route('admin.transactions.index', ['tab' => 'pending', 'segment' => 'courier'])];
        }

        if ($mysteryDueCount > 0) {
            $alerts[] = ['danger', 'bi-box-seam', 'Mystery Box navbati',
                "{$mysteryDueCount} ta obunachi jo'natish kutmoqda", route('admin.mystery-box.subscriptions', ['tab' => 'active'])];
        }

        if ($giftExpiringSoon > 0) {
            $alerts[] = ['warning', 'bi-gift', 'Sertifikat muddati tugaydi',
                "{$giftExpiringSoon} ta sertifikat 7 kun ichida bekor bo'ladi", route('admin.gift-certificates.index', ['tab' => 'sent'])];
        }

        if ($stalePreparing > 0) {
            $alerts[] = ['warning', 'bi-hourglass-split', 'Mystery Box kechikdi',
                "{$stalePreparing} ta yetkazish 2+ kun tayyorlanmagan", route('admin.mystery-box.subscriptions')];
        }

        try {
            $pr = Report::where('status', 'pending')->count();
            if ($pr > 0) {
                $alerts[] = ['danger', 'bi-flag-fill', 'Ko\'rib chiqilmagan shikoyat',
                    "{$pr} ta shikoyat javob kutmoqda", route('admin.complaints.index')];
            }
        } catch (\Throwable) {
        }

        try {
            $qt = BotTicket::where('status', 'queue')->count();
            if ($qt > 0) {
                $alerts[] = ['warning', 'bi-headset', 'Support navbati',
                    "{$qt} ta murojaat javob kutmoqda", route('admin.support.index')];
            }
        } catch (\Throwable) {
        }

        try {
            $pa = SellerAd::where('moderation', 'pending')->count();
            if ($pa > 0) {
                $alerts[] = ['warning', 'bi-megaphone', 'Reklama moderatsiya',
                    "{$pa} ta reklama tasdiqlash kutmoqda", route('admin.ads.index')];
            }
        } catch (\Throwable) {
        }

        return compact(
            'totalUsers', 'premiumUsers', 'activeUsers', 'inactiveUsers',
            'onlineUsers', 'newUsersToday', 'newUsersWeek', 'newUsersMonth',
            'verifiedUsers', 'isolatedUsers', 'onlineUsersList', 'dailyNewUsers',
            'totalOrders', 'todayOrders', 'weekOrders',
            'completedOrders', 'pendingOrders', 'packingOrders', 'onwayOrders', 'cancelledOrders',
            'completionRate', 'cancellationRate', 'dailyOrders', 'dailyRevenue', 'monthlyRevenue',
            'totalRevenue', 'todayRevenue', 'weekRevenue', 'monthRevenue', 'avgOrderValue',
            'totalDeliveryIncome', 'monthDeliveryIncome',
            'totalPromoDiscount', 'monthPromoDiscount', 'promoOrdersCount',
            'totalCashbackPaid', 'monthCashbackPaid',
            'totalCommissionEarned', 'monthCommissionEarned', 'pendingCommission',
            'totalCourierPayout', 'monthCourierPayout',
            'totalSellerPayout', 'monthSellerPayout', 'pendingSellerPayout',
            'pendingCourierPayout', 'pendingSellerTxCount', 'pendingCourierTxCount',
            'netRevenue', 'monthNetRevenue', 'monthlyFinancial',
            'totalSellers', 'pendingSellers', 'approvedSellers',
            'contractsExpiringCount', 'contractsExpiredCount', 'contractsUnsignedCount',
            'totalCouriers', 'activeCouriers', 'couriersPendingVerification',
            'giftTotal', 'giftPending', 'giftUsed', 'giftSent', 'giftRevenue', 'giftExpiringSoon',
            'mysteryActive', 'mysteryDueCount', 'mysteryOverdue', 'mysteryPending',
            'mysteryDueSoon', 'mysteryDueToday', 'stalePreparing',
            'topMixedProducts', 'topBuyers', 'recentOrders',
            'alerts',
            'gmvTotal', 'gmvMonth', 'cancelledRevLoss', 'cancelledMonthLoss',
            'mysteryRevTotal', 'mysteryRevMonth',
            'giftUsedInOrders', 'giftUsedMonthOrders',
            'repeatBuyersMonth', 'newBuyersMonth', 'avgCommissionPct',
            'aovMonthly', 'deliveryTypeSplit', 'revenueByType',
            'salesGeoCountries', 'salesGeoRegionsByCountry', 'salesGeoDefaultCountry',
            'platformProfit', 'platformProfitMonth'
        );
    }

    private function getLiveSnapshot(): array
    {
        $onlineThreshold = now()->subMinutes(5);
        $mainStatusCount = fn (OrderStatusCode $status) => Sold::query()
            ->where(function ($query) use ($status) {
                $query->where('status_code', $status->value)
                    ->orWhere(function ($fallback) use ($status) {
                        $fallback->whereNull('status_code')
                            ->where('status', $status->legacy());
                    });
            })->count();
        $sellerStatusCount = fn (SellerOrderStatusCode $status) => SellerOrder::query()
            ->where(function ($query) use ($status) {
                $query->where('status_code', $status->value)
                    ->orWhere(function ($fallback) use ($status) {
                        $fallback->whereNull('status_code')
                            ->where('status', $status->legacy());
                    });
            })->count();
        $courierStatusCount = fn (CourierOrderStatusCode $status) => CourierOrder::query()
            ->where(function ($query) use ($status) {
                $query->where('status_code', $status->value)
                    ->orWhere(function ($fallback) use ($status) {
                        $fallback->whereNull('status_code')
                            ->where('status', $status->legacy());
                    });
            })->count();

        $mainCounts = [
            'all' => Sold::count(),
            'new' => $mainStatusCount(OrderStatusCode::PENDING),
            'packing' => $mainStatusCount(OrderStatusCode::PACKING),
            'onway' => $mainStatusCount(OrderStatusCode::IN_DELIVERY),
            'arrived' => $mainStatusCount(OrderStatusCode::DELIVERED),
            'done' => Sold::whereNotNull('completed_at')->count(),
            'cancelled' => $mainStatusCount(OrderStatusCode::CANCELLED),
        ];

        $sellerCounts = [
            'all' => SellerOrder::count(),
            'payment_pending' => $sellerStatusCount(SellerOrderStatusCode::PAYMENT_PENDING),
            'new' => $sellerStatusCount(SellerOrderStatusCode::NEW),
            'accepted' => $sellerStatusCount(SellerOrderStatusCode::ACCEPTED),
            'handover' => $sellerStatusCount(SellerOrderStatusCode::HANDED_TO_COURIER),
            'cancelled' => $sellerStatusCount(SellerOrderStatusCode::CANCELLED),
        ];

        $courierCounts = [
            'all' => CourierOrder::count(),
            'pay_process' => $courierStatusCount(CourierOrderStatusCode::PAYMENT_PENDING),
            'pending' => $courierStatusCount(CourierOrderStatusCode::PENDING),
            'in_delivery' => $courierStatusCount(CourierOrderStatusCode::IN_DELIVERY),
            'delivered' => $courierStatusCount(CourierOrderStatusCode::DELIVERED),
            'customer_received' => $courierStatusCount(CourierOrderStatusCode::CUSTOMER_RECEIVED),
            'rejected' => $courierStatusCount(CourierOrderStatusCode::CANCELLED),
        ];

        $onlineUsers = User::query()
            ->where('last_seen_at', '>=', $onlineThreshold)
            ->select('id', 'name', 'lastname', 'avatar', 'last_seen_at')
            ->latest('last_seen_at')
            ->take(16)
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => trim(($user->name ?? '').' '.($user->lastname ?? '')) ?: 'Foydalanuvchi',
                'avatar' => $this->assetFromStorage($user->avatar),
                'last_seen' => optional($user->last_seen_at)?->diffForHumans(),
            ])
            ->values();

        $recentOrders = Sold::with('user:id,name,lastname,avatar')
            ->latest('updated_at')
            ->take(12)
            ->get()
            ->map(fn (Sold $order) => [
                'id' => $order->id,
                'customer' => trim(($order->user?->name ?? 'Mehmon').' '.($order->user?->lastname ?? '')) ?: 'Mehmon',
                'avatar' => $this->assetFromStorage($order->user?->avatar),
                'amount' => number_format((float) $order->amount, 0, '.', ' '),
                'status' => match ((string) $order->status_code) {
                    'pending' => 'Yangi',
                    'packing' => 'Qadoqlanmoqda',
                    'in_delivery' => "Yo'lda",
                    'delivered' => 'Yetib bordi',
                    'customer_received' => 'Mijoz qabul qildi',
                    'cancelled' => 'Bekor qilindi',
                    'returned' => 'Qaytgan',
                    default => (string) ($order->status_code ?? $order->status),
                },
                'updated_at' => optional($order->updated_at)->diffForHumans(),
            ])
            ->values();

        $recentSellerOrders = SellerOrder::with([
            'seller:id,shop_name,firstname,lastname,photo',
            'client:id,name,lastname,avatar',
        ])
            ->latest('updated_at')
            ->take(12)
            ->get()
            ->map(fn (SellerOrder $order) => [
                'id' => $order->id,
                'seller' => $order->seller?->shop_name ?? 'Sotuvchi',
                'customer' => trim(($order->client?->name ?? '').' '.($order->client?->lastname ?? '')) ?: 'Mijoz',
                'avatar' => $this->assetFromStorage($order->seller?->photo),
                'amount' => number_format((float) ($order->amount ?? 0), 0, '.', ' '),
                'status' => match ((string) $order->status_code) {
                    'payment_pending' => "To'lov jarayonida",
                    'new' => 'Yangi',
                    'accepted' => "Do'kon qabul qildi",
                    'handed_to_courier' => "Kuryerga berildi",
                    'cancelled' => 'Bekor qilindi',
                    default => (string) ($order->status_code ?? $order->status),
                },
                'updated_at' => optional($order->updated_at)->diffForHumans(),
            ])
            ->values();

        $recentCourierOrders = CourierOrder::with([
            'courier:id,first_name,last_name,photo',
            'user:id,name,lastname,avatar',
        ])
            ->latest('updated_at')
            ->take(12)
            ->get()
            ->map(fn (CourierOrder $order) => [
                'id' => $order->id,
                'courier' => trim(($order->courier?->first_name ?? '').' '.($order->courier?->last_name ?? '')) ?: 'Tayinlanmagan',
                'customer' => trim(($order->user?->name ?? '').' '.($order->user?->lastname ?? '')) ?: 'Mijoz',
                'avatar' => $this->assetFromStorage($order->courier?->photo),
                'amount' => number_format((float) ($order->amount ?? 0), 0, '.', ' '),
                'status' => match ((string) $order->status_code) {
                    'payment_pending' => "To'lov jarayonida",
                    'pending' => 'Kutilmoqda',
                    'in_delivery' => "Yo'lda",
                    'delivered' => 'Yetib bordi',
                    'customer_received' => 'Mijoz qabul qildi',
                    'cancelled' => 'Bekor qilindi',
                    'returned' => 'Qaytgan',
                    default => (string) ($order->status_code ?? $order->status),
                },
                'updated_at' => optional($order->updated_at)->diffForHumans(),
            ])
            ->values();

        return [
            'generated_at' => now()->format('H:i:s'),
            'online_users_count' => $onlineUsers->count(),
            'main_counts' => $mainCounts,
            'seller_counts' => $sellerCounts,
            'courier_counts' => $courierCounts,
            'online_users' => $onlineUsers,
            'recent_orders' => $recentOrders,
            'recent_seller_orders' => $recentSellerOrders,
            'recent_courier_orders' => $recentCourierOrders,
        ];
    }

    private function assetFromStorage(?string $path): ?string
    {
        $value = trim((string) $path);
        if ($value === '') {
            return null;
        }

        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://') || str_starts_with($value, '/')) {
            return $value;
        }

        return asset('storage/' . ltrim($value, '/'));
    }
}
