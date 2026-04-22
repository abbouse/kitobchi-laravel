<?php

namespace App\Http\Controllers\A122;

use App\Http\Controllers\Controller;
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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    private const TTL = 300;

    private const TTL_HOT = 60;

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
            'dash5_ord_C', 'dash5_ord_A', 'dash5_ord_B', 'dash5_ord_F',
            'dash5_daily_ord', 'dash5_daily_rev', 'dash5_monthly', 'dash5_monthly_fin',
            'dash5_fin_delivery', 'dash5_fin_promo', 'dash5_fin_cashback',
            'dash5_fin_comm', 'dash5_fin_courier',
            'dash5_u_total', 'dash5_u_premium', 'dash5_u_online', 'dash5_u_today',
            'dash5_u_week', 'dash5_u_active', 'dash5_u_isolated', 'dash5_u_verified',
            'dash5_u_list', 'dash5_u_daily',
            'dash5_s_total', 'dash5_s_pending', 'dash5_s_approved',
            'dash5_c_total', 'dash5_c_active',
            'dash5_gift_total', 'dash5_gift_pending', 'dash5_gift_used',
            'dash5_mystery_active', 'dash5_mystery_due', 'dash5_mystery_due_list',
            'dash5_top_mixed', 'dash5_top_buyers', 'dash5_recent',
            'dash5_fin_seller_pay', 'dash5_fin_courier_pay',
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
        $activeUsers = Cache::remember('dash5_u_active', $ttl, fn () => User::whereNull('verifyCode')->count());
        $inactiveUsers = Cache::remember('dash5_u_inactive', $ttl, fn () => User::whereNotNull('verifyCode')->count());
        $onlineUsers = Cache::remember('dash5_u_online', $hot, fn () => User::where('last_seen_at', '>=', now()->subMinutes(5))->count());
        $newUsersToday = Cache::remember('dash5_u_today', $hot, fn () => User::whereDate('created_at', today())->count());
        $newUsersWeek = Cache::remember('dash5_u_week', $ttl, fn () => User::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count());
        $newUsersMonth = Cache::remember('dash5_u_month', $ttl, fn () => User::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count());
        $verifiedUsers = Cache::remember('dash5_u_verified', $ttl, fn () => User::where('isVerified', true)->count());
        $isolatedUsers = Cache::remember('dash5_u_isolated', $ttl, fn () => User::whereNull('verifyCode')
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
        $totalOrders = Cache::remember('dash5_ord_total', $ttl, fn () => Sold::count());
        $todayOrders = Cache::remember('dash5_ord_today', $hot, fn () => Sold::whereDate('created_at', today())->count());
        $weekOrders = Cache::remember('dash5_ord_week', $ttl, fn () => Sold::where('created_at', '>=', now()->startOfWeek())->count());
        $completedOrders = Cache::remember('dash5_ord_C', $ttl, fn () => Sold::where('status', 'C')->count());
        $pendingOrders   = Cache::remember('dash5_ord_A', $hot, fn () => Sold::where('status', 'A')->count());
        $packingOrders   = Cache::remember('dash5_ord_P', $ttl, fn () => Sold::where('status', 'P')->count());
        $onwayOrders     = Cache::remember('dash5_ord_B', $ttl, fn () => Sold::where('status', 'B')->count());
        $cancelledOrders = Cache::remember('dash5_ord_F', $ttl, fn () => Sold::where('status', 'F')->count());
        $completionRate  = $totalOrders > 0 ? round($completedOrders / $totalOrders * 100, 1) : 0;
        $cancellationRate = $totalOrders > 0 ? round($cancelledOrders / $totalOrders * 100, 1) : 0;
        $dailyOrders = Cache::remember('dash5_daily_ord', $ttl, fn () => collect(range(6, 0))->map(fn ($i) => [
            'day' => now()->subDays($i)->format('D'),
            'count' => Sold::whereDate('created_at', now()->subDays($i))->count(),
        ])
        );
        $dailyRevenue = Cache::remember('dash5_daily_rev', $ttl, fn () => collect(range(6, 0))->map(fn ($i) => [
            'day' => now()->subDays($i)->format('D'),
            'total' => (float) Sold::where('paymentStatus', 2)->whereDate('created_at', now()->subDays($i))->sum('amount'),
        ])
        );

        // ── REVENUE ───────────────────────────────────────────
        $totalRevenue = Cache::remember('dash5_rev_total', $ttl, fn () => (float) Sold::where('paymentStatus', 2)->sum('amount'));
        $todayRevenue = Cache::remember('dash5_rev_today', $hot, fn () => (float) Sold::where('paymentStatus', 2)->whereDate('created_at', today())->sum('amount'));
        $weekRevenue = Cache::remember('dash5_rev_week', $ttl, fn () => (float) Sold::where('paymentStatus', 2)->where('created_at', '>=', now()->startOfWeek())->sum('amount'));
        $monthRevenue = Cache::remember('dash5_rev_month', $ttl, fn () => (float) Sold::where('paymentStatus', 2)->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->sum('amount'));
        $monthlyRevenue = Cache::remember('dash5_monthly', $ttl, fn () => collect(range(5, 0))->map(fn ($i) => [
            'month' => now()->subMonths($i)->format('M Y'),
            'total' => (int) Sold::where('paymentStatus', 2)->whereMonth('created_at', now()->subMonths($i)->month)->whereYear('created_at', now()->subMonths($i)->year)->sum('amount'),
            'count' => Sold::where('paymentStatus', 2)->whereMonth('created_at', now()->subMonths($i)->month)->whereYear('created_at', now()->subMonths($i)->year)->count(),
        ])
        );
        $avgOrderValue = $totalOrders > 0 ? (int) round($totalRevenue / max(1, Sold::where('paymentStatus', 2)->count())) : 0;

        // ── FINANCIAL ─────────────────────────────────────────
        $totalDeliveryIncome = Cache::remember('dash5_fin_delivery', $ttl, fn () => (int) Sold::where('paymentStatus', 2)->sum('deliveryPrice'));
        $monthDeliveryIncome = (int) Sold::where('paymentStatus', 2)->whereMonth('created_at', now()->month)->sum('deliveryPrice');
        $totalPromoDiscount = Cache::remember('dash5_fin_promo', $ttl, fn () => (int) Sold::where('paymentStatus', 2)->where('discountAmount', '>', 0)->sum('discountAmount'));
        $monthPromoDiscount = (int) Sold::where('paymentStatus', 2)->whereMonth('created_at', now()->month)->where('discountAmount', '>', 0)->sum('discountAmount');
        $promoOrdersCount = Sold::where('paymentStatus', 2)->where('discountAmount', '>', 0)->count();
        $totalCashbackPaid = Cache::remember('dash5_fin_cashback', $ttl, fn () => (int) Sold::where('paymentStatus', 2)->where('cashbackAmount', '>', 0)->sum('cashbackAmount'));
        $monthCashbackPaid = (int) Sold::where('paymentStatus', 2)->whereMonth('created_at', now()->month)->where('cashbackAmount', '>', 0)->sum('cashbackAmount');
        $totalCommissionEarned = Cache::remember('dash5_fin_comm', $ttl, fn () => (int) SellerTransaction::where('status', 'approved')->sum('commissionPrice'));
        $monthCommissionEarned = (int) SellerTransaction::where('status', 'approved')->whereMonth('created_at', now()->month)->sum('commissionPrice');
        $pendingCommission = (int) SellerTransaction::where('status', 'pending')->sum('commissionPrice');
        $totalCourierPayout = Cache::remember('dash5_fin_courier', $ttl, fn () => (int) DB::table('courier_orders')->where('status', 'delivered')->sum('courierPrice'));
        $monthCourierPayout = (int) DB::table('courier_orders')->where('status', 'delivered')->whereMonth('created_at', now()->month)->sum('courierPrice');
        $totalSellerPayout = Cache::remember('dash5_fin_seller_pay', $ttl, fn () => (int) SellerTransaction::where('status', 'approved')->sum('netAmount'));
        $monthSellerPayout = (int) SellerTransaction::where('status', 'approved')->whereMonth('created_at', now()->month)->sum('netAmount');
        $pendingSellerPayout = (int) SellerTransaction::where('status', 'pending')->sum('amount');
        $pendingCourierPayout = (int) CourierTransaction::where('status', 'pending')->sum('amount');
        $pendingSellerTxCount = SellerTransaction::where('status', 'pending')->count();
        $pendingCourierTxCount = CourierTransaction::where('status', 'pending')->count();
        $netRevenue = $totalRevenue - $totalPromoDiscount - $totalCashbackPaid - $totalCourierPayout + $totalDeliveryIncome;
        $monthNetRevenue = $monthRevenue - $monthPromoDiscount - $monthCashbackPaid - $monthCourierPayout + $monthDeliveryIncome;

        $monthlyFinancial = Cache::remember('dash5_monthly_fin', $ttl, fn () => collect(range(5, 0))->map(function ($i) {
            $m = now()->subMonths($i);
            $mo = $m->month;
            $yr = $m->year;
            $rev = (int) Sold::where('paymentStatus', 2)->whereMonth('created_at', $mo)->whereYear('created_at', $yr)->sum('amount');
            $promo = (int) Sold::where('paymentStatus', 2)->whereMonth('created_at', $mo)->whereYear('created_at', $yr)->sum('discountAmount');
            $cash = (int) Sold::where('paymentStatus', 2)->whereMonth('created_at', $mo)->whereYear('created_at', $yr)->sum('cashbackAmount');
            $del = (int) Sold::where('paymentStatus', 2)->whereMonth('created_at', $mo)->whereYear('created_at', $yr)->sum('deliveryPrice');
            $comm = (int) SellerTransaction::where('status', 'approved')->whereMonth('created_at', $mo)->whereYear('created_at', $yr)->sum('commissionPrice');
            $cour = (int) DB::table('courier_orders')->where('status', 'delivered')->whereMonth('created_at', $mo)->whereYear('created_at', $yr)->sum('courierPrice');

            return ['month' => $m->format('M'), 'revenue' => $rev, 'cost' => $promo + $cash + $cour, 'commission' => $comm + $del, 'net' => $rev - $promo - $cash - $cour + $del];
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
                fn () => (int) Sold::where('status', 'F')->sum('amount'));
            $cancelledMonthLoss = (int) Sold::where('status', 'F')
                ->whereMonth('created_at', now()->month)->sum('amount');

            $giftUsedInOrders = (int) Sold::where('paymentStatus', 2)
                ->where('gift_certificate_discount', '>', 0)->sum('gift_certificate_discount');
            $giftUsedMonthOrders = (int) Sold::where('paymentStatus', 2)
                ->where('gift_certificate_discount', '>', 0)
                ->whereMonth('created_at', now()->month)->sum('gift_certificate_discount');

            $repeatBuyersMonth = Cache::remember('dash5_repeat', $ttl, fn () => DB::table('solds')->select('user_id')->where('paymentStatus', 2)
                ->whereMonth('created_at', now()->month)->groupBy('user_id')
                ->havingRaw('COUNT(*) > 1')->get()->count()
            );
            $newBuyersMonth = Cache::remember('dash5_new_buyers', $ttl, fn () => DB::table('solds')->select('user_id')->where('paymentStatus', 2)
                ->whereMonth('created_at', now()->month)->groupBy('user_id')
                ->havingRaw('COUNT(*) = 1')->get()->count()
            );

            $avgCommissionPct = Cache::remember('dash5_avg_comm_pct', $ttl,
                fn () => round((float) SellerTransaction::where('status', 'approved')->avg('commissionPercent'), 1)
            );

            $aovMonthly = Cache::remember('dash5_aov_monthly', $ttl, fn () => collect(range(5, 0))->map(function ($i) {
                $m = now()->subMonths($i);
                $cnt = Sold::where('paymentStatus', 2)->whereMonth('created_at', $m->month)->whereYear('created_at', $m->year)->count();
                $sum = (float) Sold::where('paymentStatus', 2)->whereMonth('created_at', $m->month)->whereYear('created_at', $m->year)->sum('amount');

                return ['month' => $m->format('M'), 'aov' => $cnt > 0 ? (int) ($sum / $cnt) : 0];
            })
            );

            $deliveryTypeSplit = Cache::remember('dash5_delivery_split', $ttl, fn () => Sold::where('paymentStatus', 2)->whereNotNull('deliveryType')
                ->selectRaw('deliveryType, COUNT(*) as cnt, SUM(amount) as total')
                ->groupBy('deliveryType')->orderByDesc('cnt')->get()
            );

            $revenueByType = Cache::remember('dash5_type_rev', $ttl, function () {
                $bookRev = 0;
                $statRev = 0;
                Sold::where('paymentStatus', 2)->whereNotNull('items')->pluck('items')
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
                                 - $totalCashbackPaid - $totalSellerPayout - $totalCourierPayout;
            $platformProfitMonth = $monthCommissionEarned + $monthDeliveryIncome
                                 - $monthCashbackPaid - $monthSellerPayout - $monthCourierPayout;
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

        // ── BUSINESS ──────────────────────────────────────────
        $totalSellers = Cache::remember('dash5_s_total', $ttl, fn () => Seller::count());
        $pendingSellers = Cache::remember('dash5_s_pending', $ttl, fn () => Seller::where('status', 'pending')->count());
        $approvedSellers = Cache::remember('dash5_s_approved', $ttl, fn () => Seller::where('status', 'approved')->count());
        $totalCouriers = Cache::remember('dash5_c_total', $ttl, fn () => Couriers::count());
        $activeCouriers = Cache::remember('dash5_c_active', $ttl, fn () => Couriers::where('status', 'approved')->count());

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
            $mysteryDueCount = Cache::remember('dash5_mystery_due', $hot, fn () => MysteryBoxSubscription::where('status', 'active')->where('next_delivery_at', '<=', now())->count());
            $mysteryOverdue = MysteryBoxSubscription::where('status', 'active')->where('next_delivery_at', '<=', now()->subDays(3))->count();
            $mysteryPending = MysteryBoxSubscription::where('status', 'pending_payment')->count();
            $stalePreparing = MysteryBoxDelivery::where('status', 'preparing')->where('prepared_at', '<=', now()->subDays(2))->count();
            $mysteryDueSoon = MysteryBoxSubscription::with('user:id,name,lastname,phone_number')
                ->where('status', 'active')
                ->whereBetween('next_delivery_at', [now(), now()->addDays(7)])
                ->orderBy('next_delivery_at')->take(6)->get();
            $mysteryDueToday = Cache::remember('dash5_mystery_due_list', $hot,
                fn () => MysteryBoxSubscription::with(['user:id,name,lastname,phone_number', 'plan:id,name_uz'])
                    ->where('status', 'active')
                    ->where('next_delivery_at', '<=', now())
                    ->orderBy('next_delivery_at')->take(8)->get()
            );
        } catch (\Throwable) {
        }

        // ── TOP PRODUCTS ──────────────────────────────────────
        $topMixedProducts = Cache::remember('dash5_top_mixed', $ttl, function () {
            $stats = [];
            Sold::where('paymentStatus', 2)->whereNotNull('items')->pluck('items')
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
            $rows = DB::table('solds')->select('user_id')->selectRaw('COUNT(*) as order_count')->selectRaw('SUM(amount) as total_spent')
                ->where('paymentStatus', 2)->groupBy('user_id')->orderByDesc('total_spent')->take(5)->get();
            $users = User::whereIn('id', $rows->pluck('user_id'))->select('id', 'name', 'lastname', 'avatar')->get()->keyBy('id');

            return $rows->map(fn ($r) => (object) ['user_id' => $r->user_id, 'order_count' => $r->order_count, 'total_spent' => $r->total_spent, 'user' => $users->get($r->user_id)]);
        });

        // ── RECENT ORDERS ─────────────────────────────────────
        $recentOrders = Cache::remember('dash5_recent', $hot, fn () => Sold::with('user:id,name,lastname,avatar')->latest()->take(10)->get()->map(fn ($o) => [
            'id' => $o->id,
            'customer' => $o->user ? trim($o->user->name.' '.$o->user->lastname) : 'Mehmon',
            'avatar' => $o->user?->avatar,
            'amount' => number_format($o->amount),
            'status' => match ($o->status) {
                'C' => 'Yetkazildi','A' => 'Kutilmoqda','P' => 'Qadoqlanmoqda',
                'B' => "Yo'lda",'F' => 'Bekor qilindi', default => $o->status
            },
            'date' => $o->created_at->format('d.m H:i'),
            'gift' => (bool) $o->gift,
        ])
        );

        // ── Kangaroo (mahsulot + UGC inson navbati) ───────────
        try {
            $kangarooHumanReviewBooks = Cache::remember('dash5_k_hreview_books', $hot, fn () => Books::query()
                ->where('is_approved', 0)
                ->where('is_hidden', 0)
                ->where('status', 1)
                ->where('kangaroo_listing_decision', 'human_review')
                ->count());
            $kangarooHumanReviewStationery = Cache::remember('dash5_k_hreview_stat', $hot, fn () => Stationery::query()
                ->where('is_approved', 0)
                ->where('is_hidden', 0)
                ->where('status', 1)
                ->where('kangaroo_listing_decision', 'human_review')
                ->count());
            $kangarooUgcAdminQueue = Cache::remember('dash5_k_ugc_admin', $hot, fn () => BookClubComment::query()
                ->whereNull('parent_id')
                ->where('kangaroo_ugc_status', 'pending_admin')
                ->count()
                + BookClub::query()
                    ->where('is_deleted', 0)
                    ->where('kangaroo_post_ugc_status', 'pending_admin')
                    ->count());
        } catch (\Throwable) {
            $kangarooHumanReviewBooks = 0;
            $kangarooHumanReviewStationery = 0;
            $kangarooUgcAdminQueue = 0;
        }

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

        if ($kangarooHumanReviewBooks > 0) {
            $alerts[] = ['warning', 'bi-book', 'Kangaroo: kitoblar (inson)',
                "{$kangarooHumanReviewBooks} ta kitob Kangaroo human_review — qo‘lda tasdiqlash", route('admin.books.index')];
        }
        if ($kangarooHumanReviewStationery > 0) {
            $alerts[] = ['warning', 'bi-pencil-square', 'Kangaroo: kanstovar (inson)',
                "{$kangarooHumanReviewStationery} ta mahsulot Kangaroo human_review — qo‘lda tasdiqlash", route('admin.stationery.index')];
        }
        if ($kangarooUgcAdminQueue > 0) {
            $alerts[] = ['warning', 'bi-stars', 'Kangaroo: UGC navbati',
                "{$kangarooUgcAdminQueue} ta post yoki izoh admin bahosini kutmoqda", route('admin.book-club.moderation-queue')];
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
            'totalCouriers', 'activeCouriers',
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
            'platformProfit', 'platformProfitMonth',
            'kangarooHumanReviewBooks', 'kangarooHumanReviewStationery', 'kangarooUgcAdminQueue'
        );
    }

    private function getLiveSnapshot(): array
    {
        $onlineThreshold = now()->subMinutes(5);

        $mainCounts = [
            'all' => Sold::count(),
            'new' => Sold::where('status', 'A')->count(),
            'packing' => Sold::where('status', 'P')->count(),
            'onway' => Sold::where('status', 'B')->count(),
            'done' => Sold::where('status', 'C')->count(),
            'cancelled' => Sold::where('status', 'F')->count(),
        ];

        $sellerCounts = [
            'all' => SellerOrder::count(),
            'payment_pending' => SellerOrder::where('status', 0)->count(),
            'new' => SellerOrder::where('status', 1)->count(),
            'handover' => SellerOrder::whereIn('status', [2, 3])->count(),
            'legacy' => SellerOrder::where('status', 3)->count(),
            'cancelled' => SellerOrder::where('status', 4)->count(),
        ];

        $courierCounts = [
            'all' => CourierOrder::count(),
            'pay_process' => CourierOrder::where('status', 'pay_process')->count(),
            'pending' => CourierOrder::where('status', 'pending')->count(),
            'in_delivery' => CourierOrder::where('status', 'in_delivery')->count(),
            'delivered' => CourierOrder::where('status', 'delivered')->count(),
            'rejected' => CourierOrder::where('status', 'rejected')->count(),
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
                'status' => match ((string) $order->status) {
                    'A' => 'Yangi',
                    'P' => 'Qadoqlanmoqda',
                    'B' => "Yo'lda",
                    'C' => 'Yetkazildi',
                    'F' => 'Bekor qilindi',
                    default => (string) $order->status,
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
                'status' => match ((int) $order->status) {
                    0 => "To'lov jarayonida",
                    1 => 'Yangi',
                    2 => 'Kuryerga berildi',
                    3 => "Kuryerga berildi (legacy)",
                    4 => 'Bekor qilindi',
                    default => (string) $order->status,
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
                'status' => match ((string) $order->status) {
                    'pay_process' => "To'lov jarayonida",
                    'pending' => 'Kutilmoqda',
                    'in_delivery' => "Yo'lda",
                    'delivered' => 'Yetkazildi',
                    'rejected' => 'Bekor qilindi',
                    default => (string) $order->status,
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
