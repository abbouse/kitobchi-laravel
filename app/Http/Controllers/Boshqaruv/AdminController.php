<?php

namespace App\Http\Controllers\Boshqaruv;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\ApiClient;
use App\Models\ApiClientRequestLog;
use App\Models\Author;
use App\Models\Blogger;
use App\Models\BookClub;
use App\Models\BotTicket;
use App\Models\BookCategories;
use App\Models\Books;
use App\Models\CashbackSetting;
use App\Models\CareerApplication;
use App\Models\CommissionSetting;
use App\Models\CourierOrder;
use App\Models\Couriers;
use App\Models\DeliveryService;
use App\Models\FcmNotifications;
use App\Models\GiftCertificate;
use App\Models\Gifts;
use App\Models\Hub;
use App\Models\MarketNews;
use App\Models\MysteryBoxPlan;
use App\Models\MysteryBoxSubscription;
use App\Models\Policy;
use App\Models\ProjectSetting;
use App\Models\Publisher;
use App\Models\Report;
use App\Models\Promocode;
use App\Models\Reel;
use App\Models\SearchHistory;
use App\Models\SellerAd;
use App\Models\Seller;
use App\Models\SellerBanLog;
use App\Models\SellerOrder;
use App\Models\SellerTransaction;
use App\Models\Sold;
use App\Models\Stationery;
use App\Models\StationeryCategory;
use App\Models\User;
use App\Models\Vacancy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Support\ProductImageUrls;
use App\Enums\SellerOrderStatusCode;
use App\Services\AdminOrderStatusSyncService;
use Inertia\Inertia;
use Inertia\Response;

class AdminController extends Controller
{
    public function login(): Response|\Illuminate\Http\RedirectResponse
    {
        if (Auth::guard('panel')->check()) {
            return redirect()->route('boshqaruv.dashboard');
        }

        return Inertia::render('Login');
    }

    public function authenticate(Request $request): \Illuminate\Http\RedirectResponse
    {
        $creds = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if (! Auth::guard('panel')->attempt(
            ['email' => $creds['email'], 'password' => $creds['password'], 'is_active' => true],
            $request->boolean('remember')
        )) {
            return back()
                ->withInput($request->only('email'))
                ->with('error', "Email yoki parol noto'g'ri, yoki akkaunt bloklangan.");
        }

        $admin = Auth::guard('panel')->user();
        $admin->update([
            'last_login_at' => now(),
            'last_ip' => $request->ip(),
        ]);

        $request->session()->regenerate();

        return redirect()->intended(route('boshqaruv.dashboard'));
    }

    public function logout(Request $request): \Illuminate\Http\RedirectResponse
    {
        Auth::guard('panel')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('boshqaruv.login')
            ->with('success', "Tizimdan chiqildi.");
    }

    public function dashboard(): Response
    {
        return Inertia::render('Dashboard', [
            'dashboard' => $this->dashboardPayload(),
        ]);
    }

    public function live(): Response
    {
        return Inertia::render('LiveDashboard', [
            'metrics' => [
                'orders' => $this->tableCount('solds'),
                'users' => $this->tableCount('users'),
                'books' => $this->tableCount('books'),
                'sellers' => $this->tableCount('sellers'),
            ],
            'snapshot' => $this->liveSnapshot(),
            'liveEndpoint' => route('boshqaruv.live.data'),
        ]);
    }

    public function liveData(): JsonResponse
    {
        return response()->json($this->liveSnapshot());
    }

    public function page(string $component): Response
    {
        return Inertia::render($component, $this->pagePayload($component));
    }

    private function pagePayload(string $component): array
    {
        return match ($component) {
            'Products' => ['products' => $this->productsPayload()],
            'Books' => ['books' => $this->booksPayload()],
            'BookCategories' => ['categories' => $this->bookCategoriesPayload()],
            'stationery-categories' => ['categories' => $this->stationeryCategoriesPayload()],
            'Stationeries' => ['stationeries' => $this->stationeriesPayload()],
            'Authors' => ['authors' => $this->authorsPayload()],
            'Publishers' => ['publishers' => $this->publishersPayload()],
            'Users' => ['users' => $this->usersPayload()],
            'Orders' => ['orders' => $this->ordersPayload()],
            'SellerOrders' => [
                'sellers' => $this->sellersPayload(),
                'sellerCounts' => $this->sellerStatusCounts(),
                'sellerOrders' => $this->sellerOrdersPayload(),
                'sellerOrderCounts' => $this->sellerOrderStatusCounts(),
                'sellerOrderStatuses' => AdminOrderStatusSyncService::SELLER_STATUSES,
            ],
            'CourierOrders' => ['couriers' => $this->couriersPayload(), 'courierOrders' => $this->courierOrdersPayload()],
            'Hubs' => ['hubs' => $this->hubsPayload()],
            'Transaksiyalar' => ['transactions' => $this->transactionsPayload()],
            'Promokodlar' => ['promocodes' => $this->promocodesPayload()],
            'Reklamalar' => ['ads' => $this->adsPayload()],
            'Blogerlar' => ['bloggers' => $this->bloggersPayload()],
            'GiftSertifikatlar' => ['giftCertificates' => $this->giftCertificatesPayload()],
            'MarketNewsPage' => ['news' => $this->marketNewsPayload()],
            'ReelsPage' => ['reels' => $this->reelsPayload()],
            'Siyosatlar' => ['policies' => $this->policiesPayload()],
            'PushNotifications' => ['notifications' => $this->pushNotificationsPayload()],
            'SearchHistory' => ['searchHistory' => $this->searchHistoryPayload()],
            'Sovgalar' => ['gifts' => $this->giftsPayload()],
            'Tickets' => ['tickets' => $this->ticketsPayload()],
            'Shikoyatlar' => ['complaints' => $this->complaintsPayload()],
            'Vakansiyalar' => ['vacancies' => $this->vacanciesPayload()],
            'KaryeraArizalari' => ['applications' => $this->careerApplicationsPayload()],
            'Adminlar' => ['admins' => $this->adminsPayload()],
            'ApiClients' => ['apiClients' => $this->apiClientsPayload(), 'apiLogs' => $this->apiLogsPayload()],
            'Settings' => ['settings' => $this->settingsPayload()],
            'LogistikaPage' => ['deliveryServices' => $this->deliveryServicesPayload()],
            'MysteryBoxPage' => ['mysteryBox' => $this->mysteryBoxPayload()],
            'BookClub' => ['bookClubPosts' => $this->bookClubPayload()],
            default => [],
        };
    }

    private function dashboardPayload(): array
    {
        $today = now()->startOfDay();
        $yesterday = now()->subDay()->startOfDay();
        $week = now()->startOfWeek();
        $lastWeek = now()->subWeek()->startOfWeek();
        $month = now()->startOfMonth();
        $lastMonth = now()->subMonth()->startOfMonth();

        $paid = fn () => $this->paidOrdersQuery();
        $period = fn ($start, $end = null) => [
            'revenue' => (float) $paid()->where('created_at', '>=', $start)->when($end, fn ($q) => $q->where('created_at', '<', $end))->sum('amount'),
            'orders' => (int) Sold::query()->where('created_at', '>=', $start)->when($end, fn ($q) => $q->where('created_at', '<', $end))->count(),
            'users' => Schema::hasTable('users') ? (int) User::query()->where('created_at', '>=', $start)->when($end, fn ($q) => $q->where('created_at', '<', $end))->count() : 0,
        ];

        $todayStats = $period($today);
        $yesterdayStats = $period($yesterday, $today);
        $weekStats = $period($week);
        $lastWeekStats = $period($lastWeek, $week);
        $monthStats = $period($month);
        $lastMonthStats = $period($lastMonth, $month);

        $totalOrders = $this->tableCount('solds');
        $paidCount = (int) $paid()->count();
        $grossRevenue = (float) $paid()->sum('amount');
        $deliveryIncome = (float) $paid()->sum('deliveryPrice');
        $promoDiscount = (float) $paid()->sum('discountAmount');
        $cashback = (float) $paid()->sum('cashbackAmount');
        $giftDiscount = Schema::hasColumn('solds', 'gift_certificate_discount') ? (float) $paid()->sum('gift_certificate_discount') : 0;
        $commission = Schema::hasTable('seller_transactions') ? (float) SellerTransaction::query()->where('status', 'approved')->sum('commissionPrice') : 0;
        $courierPayout = Schema::hasTable('courier_orders') ? (float) CourierOrder::query()
            ->where(fn ($query) => $query->whereIn('status_code', ['customer_received', 'delivered', 'C'])->orWhereIn('status', ['customer_received', 'delivered', 'C']))
            ->sum('courierPrice') : 0;
        $sellerPayout = Schema::hasTable('seller_transactions') ? (float) SellerTransaction::query()->where('status', 'approved')->sum('netAmount') : 0;
        $platformProfit = $commission + $deliveryIncome - $promoDiscount - $cashback - $courierPayout;

        $mainCounts = [
            'all' => $totalOrders,
            'new' => $this->countStatuses(Sold::query(), ['pending', 'A']),
            'packing' => $this->countStatuses(Sold::query(), ['packing', 'P', 'B']),
            'onway' => $this->countStatuses(Sold::query(), ['in_delivery', 'D']),
            'done' => $this->countStatuses(Sold::query(), ['delivered', 'customer_received', 'C']),
            'cancelled' => $this->countStatuses(Sold::query(), ['cancelled', 'returned', 'F']),
        ];

        $sellerCounts = [
            'all' => $this->tableCount('seller_orders'),
            'payment_pending' => Schema::hasTable('seller_orders') ? $this->countStatuses(SellerOrder::query(), ['payment_pending']) : 0,
            'new' => Schema::hasTable('seller_orders') ? $this->countStatuses(SellerOrder::query(), ['new']) : 0,
            'accepted' => Schema::hasTable('seller_orders') ? $this->countStatuses(SellerOrder::query(), ['accepted']) : 0,
            'handover' => Schema::hasTable('seller_orders') ? $this->countStatuses(SellerOrder::query(), ['handed_to_courier']) : 0,
            'cancelled' => Schema::hasTable('seller_orders') ? $this->countStatuses(SellerOrder::query(), ['cancelled']) : 0,
        ];

        $courierCounts = [
            'all' => $this->tableCount('courier_orders'),
            'pending' => Schema::hasTable('courier_orders') ? $this->countStatuses(CourierOrder::query(), ['pending']) : 0,
            'in_delivery' => Schema::hasTable('courier_orders') ? $this->countStatuses(CourierOrder::query(), ['in_delivery']) : 0,
            'delivered' => Schema::hasTable('courier_orders') ? $this->countStatuses(CourierOrder::query(), ['delivered']) : 0,
            'customer_received' => Schema::hasTable('courier_orders') ? $this->countStatuses(CourierOrder::query(), ['customer_received']) : 0,
            'rejected' => Schema::hasTable('courier_orders') ? $this->countStatuses(CourierOrder::query(), ['cancelled', 'returned']) : 0,
        ];

        return [
            'generatedAt' => now()->format('Y-m-d H:i:s'),
            'metrics' => [
                'orders' => $totalOrders,
                'paidOrders' => $paidCount,
                'users' => $this->tableCount('users'),
                'premiumUsers' => Schema::hasColumn('users', 'is_premium') ? (int) User::query()->where('is_premium', true)->count() : 0,
                'onlineUsers' => Schema::hasColumn('users', 'last_seen_at') ? (int) User::query()->where('last_seen_at', '>=', now()->subMinutes(5))->count() : 0,
                'books' => $this->tableCount('books'),
                'stationeries' => $this->tableCount('stationeries'),
                'sellers' => $this->tableCount('sellers'),
                'pendingSellers' => Schema::hasTable('sellers') ? (int) Seller::query()->where('status', 'pending')->count() : 0,
                'couriers' => $this->tableCount('couriers'),
                'tickets' => $this->tableCount('bot_tickets'),
                'complaints' => $this->tableCount('reports'),
            ],
            'periods' => [
                'today' => $this->periodCard($todayStats),
                'yesterday' => $this->periodCard($yesterdayStats),
                'week' => $this->periodCard($weekStats),
                'lastWeek' => $this->periodCard($lastWeekStats),
                'month' => $this->periodCard($monthStats),
                'lastMonth' => $this->periodCard($lastMonthStats),
            ],
            'financial' => [
                'grossRevenue' => $grossRevenue,
                'monthRevenue' => $monthStats['revenue'],
                'deliveryIncome' => $deliveryIncome,
                'commission' => $commission,
                'promoDiscount' => $promoDiscount,
                'cashback' => $cashback,
                'giftDiscount' => $giftDiscount,
                'courierPayout' => $courierPayout,
                'sellerPayout' => $sellerPayout,
                'platformProfit' => $platformProfit,
                'avgOrderValue' => $paidCount > 0 ? round($grossRevenue / $paidCount) : 0,
                'netMargin' => $grossRevenue > 0 ? round($platformProfit / $grossRevenue * 100, 1) : 0,
            ],
            'status' => [
                'main' => $mainCounts,
                'seller' => $sellerCounts,
                'courier' => $courierCounts,
            ],
            'salesByMonth' => $this->dashboardMonthlySales(),
            'categoryShare' => $this->dashboardCategoryShare(),
            'topProducts' => $this->liveTopProducts(),
            'recentOrders' => $this->liveRecentOrders(),
            'paymentSplit' => $this->paymentSplit([]),
            'deliverySplit' => $this->deliverySplit(),
            'regions' => $this->liveRegionStats(),
            'alerts' => $this->liveAlerts($mainCounts, $sellerCounts, $courierCounts),
        ];
    }

    private function periodCard(array $stats): array
    {
        return [
            'revenue' => (float) ($stats['revenue'] ?? 0),
            'orders' => (int) ($stats['orders'] ?? 0),
            'users' => (int) ($stats['users'] ?? 0),
            'aov' => ((int) ($stats['orders'] ?? 0)) > 0 ? round(((float) ($stats['revenue'] ?? 0)) / (int) $stats['orders']) : 0,
        ];
    }

    private function booksPayload(): array
    {
        return Books::query()
            ->with(['authorProfile:id,name', 'category:id,name_uz', 'publisher:id,name', 'seller:id,shop_name,firstname,lastname,phone_number,status,isVerified,is_hidden'])
            ->latest()
            ->take(48)
            ->get()
            ->map(function (Books $book) {
                $images = collect($book->images ?? [])
                    ->filter(fn ($image) => is_string($image) && trim($image) !== '')
                    ->map(fn (string $image) => ProductImageUrls::originalUrl($image))
                    ->filter()
                    ->values();
                $recentOrders = $this->recentBookOrders($book);
                $sellerOrders = $this->recentSellerOrdersForBook($book);

                return [
                    'id' => $book->id,
                    'title' => $book->name,
                    'author' => $book->authorProfile?->name ?: ($book->author ?: 'Noma\'lum'),
                    'translator' => $book->translator,
                    'isbn' => $book->isbn,
                    'category' => $book->category?->name_uz ?: 'Kitob',
                    'publisher' => $book->publisher?->name,
                    'price' => (float) ($book->price ?? 0),
                    'discountPrice' => $book->discountPrice !== null ? (float) $book->discountPrice : null,
                    'discountExpiresAt' => $this->dateTime($book->discountExpiresAt),
                    'stock' => (int) ($book->count ?? 0),
                    'sold' => (int) ($book->totalSales ?? 0),
                    'totalClients' => (int) ($book->totalClients ?? 0),
                    'totalRevenue' => (float) ($book->totalRevenue ?? 0),
                    'totalSalesWeek' => (int) ($book->totalSalesWeek ?? 0),
                    'views' => (int) ($book->views ?? 0),
                    'cover' => $images->first(),
                    'images' => $images->all(),
                    'status' => (int) ($book->is_approved ?? 0),
                    'active' => (bool) ($book->status ?? false),
                    'hidden' => (bool) ($book->is_hidden ?? false),
                    'recommended' => (bool) ($book->recommended ?? false),
                    'recommendedExpiresAt' => $this->dateTime($book->recommendedExpiresAt),
                    'seller' => $book->seller ? [
                        'id' => $book->seller->id,
                        'name' => $book->seller->shop_name ?: trim(($book->seller->firstname ?? '').' '.($book->seller->lastname ?? '')),
                        'phone' => $book->seller->phone_number,
                        'status' => $book->seller->status,
                        'verified' => (bool) $book->seller->isVerified,
                        'hidden' => (bool) $book->seller->is_hidden,
                        'url' => route('boshqaruv.sellers'),
                    ] : null,
                    'lang' => $book->lang,
                    'langType' => $book->langType,
                    'coverType' => $book->coverType,
                    'pages' => $book->pages,
                    'year' => $book->year,
                    'description' => $book->description,
                    'mediaCount' => $images->count(),
                    'recentOrders' => $recentOrders,
                    'sellerOrders' => $sellerOrders,
                    'createdAt' => optional($book->created_at)->format('Y-m-d H:i'),
                    'updatedAt' => optional($book->updated_at)->format('Y-m-d H:i'),
                    'showUrl' => route('admin.books.show', $book),
                    'editUrl' => route('admin.books.edit', $book),
                    'moderateUrl' => route('boshqaruv.books.moderate', $book),
                ];
            })
            ->values()
            ->all();
    }

    private function productsPayload(): array
    {
        $items = collect();

        if (Schema::hasTable('books')) {
            Books::query()
                ->with(['category:id,name_uz', 'seller:id,shop_name'])
                ->latest()
                ->take(50)
                ->get()
                ->each(function (Books $book) use ($items) {
                    $items->push([
                        'id' => 'book-'.$book->id,
                        'rawId' => $book->id,
                        'title' => $book->name,
                        'type' => 'Kitob',
                        'category' => $book->category?->name_uz ?: 'Kitob',
                        'seller' => $book->seller?->shop_name,
                        'price' => (float) ($book->discountPrice ?? $book->price ?? 0),
                        'stock' => (int) ($book->count ?? 0),
                        'sold' => (int) ($book->totalSales ?? 0),
                        'revenue' => (float) ($book->totalRevenue ?? 0),
                        'status' => $book->status ? 'Active' : 'Inactive',
                        'approved' => (bool) $book->is_approved,
                        'image' => ProductImageUrls::originalUrl(collect($book->images ?? [])->first()),
                        'showUrl' => route('admin.books.show', $book),
                        'editUrl' => route('admin.books.edit', $book),
                        'moderateUrl' => route('boshqaruv.books.moderate', $book),
                    ]);
                });
        }

        if (Schema::hasTable('stationeries')) {
            Stationery::query()
                ->with(['category', 'seller:id,shop_name'])
                ->latest()
                ->take(50)
                ->get()
                ->each(function (Stationery $stationery) use ($items) {
                    $items->push([
                        'id' => 'stationery-'.$stationery->id,
                        'rawId' => $stationery->id,
                        'title' => $stationery->name,
                        'type' => 'Kanselyariya',
                        'category' => $stationery->category?->name_uz ?: $stationery->category?->name_ru ?: $stationery->category?->slug ?: 'Kanselyariya',
                        'seller' => $stationery->seller?->shop_name,
                        'price' => (float) ($stationery->price ?? 0),
                        'stock' => (int) ($stationery->stock ?? 0),
                        'sold' => (int) ($stationery->totalSales ?? 0),
                        'revenue' => (float) ($stationery->totalRevenue ?? 0),
                        'status' => $stationery->status ? 'Active' : 'Inactive',
                        'approved' => (bool) $stationery->is_approved,
                        'image' => $this->assetFromStorage(collect($stationery->images ?? [])->first()),
                        'showUrl' => route('admin.stationery.show', $stationery->id),
                        'editUrl' => route('admin.stationery.edit', $stationery->id),
                        'moderateUrl' => route('boshqaruv.stationery.moderate', $stationery->id),
                    ]);
                });
        }

        if (Schema::hasTable('gifts')) {
            Gifts::query()
                ->with('seller:id,shop_name')
                ->latest()
                ->take(50)
                ->get()
                ->each(function (Gifts $gift) use ($items) {
                    $items->push([
                        'id' => 'gift-'.$gift->id,
                        'rawId' => $gift->id,
                        'title' => $gift->name,
                        'type' => "Sovg'a",
                        'category' => "Sovg'a",
                        'seller' => $gift->seller?->shop_name,
                        'price' => (float) ($gift->priceFrom ?? 0),
                        'stock' => (int) ($gift->stock ?? 0),
                        'sold' => (int) ($gift->totalSales ?? 0),
                        'revenue' => (float) ($gift->totalRevenue ?? 0),
                        'status' => $gift->status ? 'Active' : 'Inactive',
                        'approved' => (bool) $gift->is_approved,
                        'image' => $this->assetFromStorage(collect($gift->images ?? [])->first()),
                        'showUrl' => route('admin.gifts.index'),
                        'editUrl' => route('admin.gifts.index'),
                    ]);
                });
        }

        return $items->sortByDesc('rawId')->take(120)->values()->all();
    }

    private function usersPayload(): array
    {
        return User::query()
            ->latest()
            ->take(50)
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => trim(($user->name ?? '').' '.($user->lastname ?? '')) ?: 'Foydalanuvchi',
                'email' => $user->email ?? '',
                'phone' => $user->phone_number ?? $user->phone ?? '',
                'orders' => (int) ($user->purchases_count ?? 0),
                'spent' => (float) ($user->total_purchase_amount ?? 0),
                'status' => $user->is_blocked ?? false ? 'Blocked' : (($user->is_premium ?? false) ? 'VIP' : 'Active'),
                'role' => 'Customer',
                'showUrl' => route('admin.users.show', $user),
                'editUrl' => route('admin.users.edit', $user),
                'blockUrl' => route('admin.users.block', $user),
                'unblockUrl' => route('admin.users.unblock', $user),
                'destroyUrl' => route('admin.users.destroy', $user),
            ])
            ->values()
            ->all();
    }

    private function authorsPayload(): array
    {
        if (! Schema::hasTable('authors')) {
            return [];
        }

        return Author::query()
            ->withCount('books')
            ->orderBy('name')
            ->take(80)
            ->get()
            ->map(fn (Author $author) => [
                'id' => $author->id,
                'name' => $author->name,
                'country' => '—',
                'books' => (int) ($author->books_count ?? 0),
                'followers' => 0,
                'bio' => $author->source_url ?: ($author->external_id ? 'External ID: '.$author->external_id : "Muallif katalogi"),
                'image' => $author->display_image_url,
                'needsAiPortrait' => (bool) $author->needs_ai_portrait,
                'destroyUrl' => route('boshqaruv.authors.destroy', $author),
                'generateImagePromptUrl' => route('boshqaruv.authors.generate-image-prompt', $author),
            ])
            ->values()
            ->all();
    }

    private function publishersPayload(): array
    {
        if (! Schema::hasTable('publishers')) {
            return [];
        }

        return Publisher::query()
            ->withCount('books')
            ->orderBy('name')
            ->take(80)
            ->get()
            ->map(fn (Publisher $publisher) => [
                'id' => $publisher->id,
                'name' => $publisher->name,
                'city' => '—',
                'books' => (int) ($publisher->books_count ?? 0),
                'contact' => '—',
                'rating' => 0,
                'image' => $publisher->image_url,
                'destroyUrl' => route('boshqaruv.publishers.destroy', $publisher),
            ])
            ->values()
            ->all();
    }

    private function bookCategoriesPayload(): array
    {
        if (! Schema::hasTable('book_categories')) {
            return [];
        }

        return BookCategories::query()
            ->withCount('books')
            ->orderBy('name_uz')
            ->take(100)
            ->get()
            ->map(fn (BookCategories $category) => [
                'id' => $category->id,
                'name' => $category->name_uz ?: $category->name_ru ?: 'Kategoriya',
                'nameRu' => $category->name_ru,
                'nameEn' => $category->name_en,
                'slug' => $category->slug,
                'active' => (bool) $category->is_active,
                'itemsCount' => (int) ($category->books_count ?? 0),
                'toggleUrl' => route('boshqaruv.book-categories.toggle', $category),
                'destroyUrl' => route('boshqaruv.book-categories.destroy', $category),
            ])
            ->values()
            ->all();
    }

    private function stationeryCategoriesPayload(): array
    {
        if (! Schema::hasTable('stationery_categories')) {
            return [];
        }

        return StationeryCategory::query()
            ->withCount('stationeries')
            ->orderBy('name_uz')
            ->take(100)
            ->get()
            ->map(fn (StationeryCategory $category) => [
                'id' => $category->id,
                'name' => $category->name_uz ?: $category->name_ru ?: 'Kategoriya',
                'nameRu' => $category->name_ru,
                'nameEn' => $category->name_en,
                'slug' => $category->slug,
                'active' => (bool) $category->is_active,
                'itemsCount' => (int) ($category->stationeries_count ?? 0),
                'toggleUrl' => route('boshqaruv.stationery-categories.toggle', $category),
                'destroyUrl' => route('boshqaruv.stationery-categories.destroy', $category),
            ])
            ->values()
            ->all();
    }

    private function stationeriesPayload(): array
    {
        if (! Schema::hasTable('stationeries')) {
            return [];
        }

        return Stationery::query()
            ->with(['category:id,name_uz', 'seller:id,shop_name'])
            ->latest()
            ->take(80)
            ->get()
            ->map(function (Stationery $item) {
                $image = collect($item->images ?? [])->first();

                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'category' => $item->category?->name_uz ?: '—',
                    'seller' => $item->seller?->shop_name,
                    'price' => (float) ($item->price ?? 0),
                    'discountPrice' => $item->discount_price !== null ? (float) $item->discount_price : null,
                    'stock' => (int) ($item->stock ?? 0),
                    'sold' => (int) ($item->totalSales ?? 0),
                    'revenue' => (float) ($item->totalRevenue ?? 0),
                    'views' => (int) ($item->views ?? 0),
                    'status' => (int) ($item->is_approved ?? 0),
                    'active' => (bool) ($item->status ?? false),
                    'hidden' => (bool) ($item->is_hidden ?? false),
                    'recommended' => (bool) ($item->recommended ?? false),
                    'icon' => $image ? ProductImageUrls::originalUrl((string) $image) : null,
                    'createUrl' => route('admin.stationery.create'),
                    'showUrl' => route('admin.stationery.show', $item->id),
                    'editUrl' => route('admin.stationery.edit', $item->id),
                    'moderateUrl' => route('boshqaruv.stationery.moderate', $item->id),
                ];
            })
            ->values()
            ->all();
    }

    private function ordersPayload(): array
    {
        return Sold::query()
            ->with(['user:id,name,lastname,phone_number,email', 'fulfillment.hub'])
            ->latest()
            ->take(50)
            ->get()
            ->map(fn (Sold $order) => $this->orderPayload($order))
            ->values()
            ->all();
    }

    private function sellersPayload(): array
    {
        if (! Schema::hasTable('sellers')) {
            return [];
        }

        return Seller::query()
            ->withCount(['books', 'stationeries', 'orders', 'premiumSubscriptions'])
            ->with(['locations' => fn ($query) => $query->orderByDesc('is_main')->orderBy('id')->take(4)])
            ->where(fn ($query) => $query->whereNull('parent_id')->orWhere('parent_id', 0))
            ->latest()
            ->take(240)
            ->get()
            ->map(fn (Seller $seller) => $this->sellerPayload($seller))
            ->values()
            ->all();
    }

    private function sellerPayload(Seller $seller): array
    {
        $sellerIds = Seller::query()
            ->where('id', $seller->id)
            ->orWhere('parent_id', $seller->id)
            ->pluck('id');
        $warningCount = Schema::hasTable('seller_ban_logs') ? SellerBanLog::getWarningCount($seller->id) : 0;
        $transactions = Schema::hasTable('seller_transactions')
            ? SellerTransaction::query()->whereIn('seller_id', $sellerIds)->latest()->take(6)->get()
            : collect();
        $recentOrders = Schema::hasTable('seller_orders')
            ? SellerOrder::query()->with(['client:id,name,lastname,phone_number'])->whereIn('seller_id', $sellerIds)->latest()->take(6)->get()
            : collect();
        $banLogs = Schema::hasTable('seller_ban_logs')
            ? SellerBanLog::query()->where('seller_id', $seller->id)->latest()->take(6)->get()
            : collect();
        $totalRevenue = Schema::hasTable('seller_transactions')
            ? (float) SellerTransaction::query()
                ->whereIn('seller_id', $sellerIds)
                ->where('status', 'approved')
                ->where('type', 'income')
                ->sum('netAmount')
            : 0;

        return [
            'id' => $seller->id,
            'name' => $seller->shop_name ?: trim(($seller->firstname ?? '').' '.($seller->lastname ?? '')) ?: 'Seller',
            'ownerName' => trim(($seller->firstname ?? '').' '.($seller->lastname ?? '')) ?: '—',
            'legalName' => $seller->legal_type ?: '—',
            'phone' => $seller->phone_number,
            'photo' => $this->assetFromStorage($seller->photo),
            'region' => $seller->region,
            'district' => $seller->district ?? null,
            'address' => $seller->address ?? $seller->legal_address,
            'status' => $seller->status,
            'verified' => (bool) $seller->isVerified,
            'hidden' => (bool) $seller->is_hidden,
            'premium' => (bool) $seller->isPremiumShop,
            'premiumExpiresAt' => optional($seller->isPremiumExpiresAt)->format('Y-m-d'),
            'rating' => (float) ($seller->rating ?? 0),
            'ratingReviewsCount' => (int) ($seller->rating_reviews_count ?? 0),
            'reputationScore' => (float) ($seller->reputation_score ?? 0),
            'balance' => (float) ($seller->balance ?? 0),
            'totalRevenue' => $totalRevenue,
            'commissionRate' => (float) ($seller->commission_percent ?? 0),
            'products' => (int) (($seller->books_count ?? 0) + ($seller->stationeries_count ?? 0)),
            'books' => (int) ($seller->books_count ?? 0),
            'stationeries' => (int) ($seller->stationeries_count ?? 0),
            'orders' => (int) ($seller->orders_count ?? 0),
            'warningCount' => (int) $warningCount,
            'legal' => [
                'type' => $seller->legal_type,
                'inn' => $seller->inn,
                'passport' => trim(($seller->passport_series ?? '').' '.($seller->passport_number ?? '')) ?: null,
                'passportIssuedBy' => $seller->passport_issued_by,
                'passportIssuedAt' => optional($seller->passport_issued_at)->format('Y-m-d'),
                'legalAddress' => $seller->legal_address,
            ],
            'bank' => [
                'name' => $seller->bank_name,
                'account' => $seller->masked_bank_account,
                'mfo' => $seller->bank_mfo,
                'swift' => $seller->bank_swift,
                'card' => $seller->masked_card,
                'cardHolder' => $seller->card_holder,
            ],
            'contract' => [
                'number' => $seller->contract_number,
                'signed' => (bool) $seller->contract_signed,
                'signedAt' => optional($seller->contract_signed_at)->format('Y-m-d'),
                'status' => $seller->contract_computed_status,
                'rawStatus' => $seller->contract_status,
                'expiresAt' => optional($seller->contract_expires_at)->format('Y-m-d'),
                'daysRemaining' => $seller->contract_days_remaining,
                'notes' => $seller->contract_notes,
            ],
            'locations' => $seller->locations->map(fn ($location) => [
                'id' => $location->id,
                'address' => $location->fullAddress,
                'description' => $location->description,
                'main' => (bool) $location->is_main,
                'qrUrl' => $location->qr_url,
                'rotatedAt' => optional($location->qr_rotated_at)->format('Y-m-d H:i'),
            ])->values()->all(),
            'recentOrders' => $recentOrders->map(fn (SellerOrder $order) => [
                'id' => $order->id,
                'customer' => trim(($order->client?->name ?? '').' '.($order->client?->lastname ?? '')) ?: 'Mijoz',
                'phone' => $order->client?->phone_number,
                'amount' => (float) ($order->amount ?? 0),
                'status' => (string) ($order->status_code ?? $order->status ?? ''),
                'date' => optional($order->created_at)->format('Y-m-d H:i'),
                'url' => route('boshqaruv.seller-orders'),
            ])->values()->all(),
            'transactions' => $transactions->map(fn (SellerTransaction $transaction) => [
                'id' => $transaction->id,
                'type' => $transaction->type,
                'category' => $transaction->category,
                'amount' => (float) ($transaction->amount ?? 0),
                'commission' => (float) ($transaction->commissionPrice ?? 0),
                'net' => (float) ($transaction->netAmount ?? 0),
                'status' => $transaction->status,
                'date' => optional($transaction->created_at)->format('Y-m-d H:i'),
            ])->values()->all(),
            'banLogs' => $banLogs->map(fn (SellerBanLog $log) => [
                'id' => $log->id,
                'title' => $log->title,
                'message' => $log->message,
                'type' => $log->type,
                'read' => (bool) $log->is_read,
                'date' => optional($log->created_at)->format('Y-m-d H:i'),
            ])->values()->all(),
            'actions' => [
                'approveUrl' => route('boshqaruv.sellers.approve', $seller),
                'rejectUrl' => route('boshqaruv.sellers.reject', $seller),
                'unblockUrl' => route('boshqaruv.sellers.unblock', $seller),
                'warnUrl' => route('boshqaruv.sellers.warn', $seller),
                'resetPasswordUrl' => route('boshqaruv.sellers.reset-password', $seller),
            ],
        ];
    }

    private function sellerStatusCounts(): array
    {
        if (! Schema::hasTable('sellers')) {
            return ['pending' => 0, 'approved' => 0, 'rejected' => 0, 'blocked' => 0, 'all' => 0];
        }

        $base = fn () => Seller::query()->where(fn ($query) => $query->whereNull('parent_id')->orWhere('parent_id', 0));

        return [
            'pending' => (int) $base()->where('status', 'pending')->count(),
            'approved' => (int) $base()->where('status', 'approved')->count(),
            'rejected' => (int) $base()->where('status', 'rejected')->count(),
            'blocked' => (int) $base()->where('status', 'blocked')->count(),
            'all' => (int) $base()->count(),
        ];
    }

    private function sellerOrdersPayload(): array
    {
        if (! Schema::hasTable('seller_orders')) {
            return [];
        }

        return SellerOrder::query()
            ->with(['seller:id,shop_name,firstname,lastname,phone_number,photo', 'client:id,name,lastname,phone_number', 'courier:id,first_name,last_name,phone_number', 'order:id,user_id,amount,status,paymentStatus,deliveryPrice,deliveryType,items,address,created_at'])
            ->latest()
            ->take(240)
            ->get()
            ->map(fn (SellerOrder $order) => $this->sellerOrderPayload($order))
            ->values()
            ->all();
    }

    private function sellerOrderPayload(SellerOrder $order): array
    {
        $address = collect($order->order?->address ?? $order->address ?? [])->first() ?: [];
        $items = collect($order->order?->items ?? [])
            ->filter(fn ($item) => (int) ($item['seller_id'] ?? 0) === (int) $order->seller_id)
            ->values()
            ->map(fn ($item) => [
                'name' => $item['name'] ?? 'Mahsulot',
                'type' => $item['type'] ?? 'book',
                'quantity' => (int) ($item['count_item'] ?? $item['quantity'] ?? 1),
                'price' => (float) ($item['item_price'] ?? $item['price'] ?? 0),
                'cover' => $item['cover'] ?? null,
                'author' => $item['author'] ?? null,
            ]);
        $statusCode = (string) ($order->status_code ?? SellerOrderStatusCode::fromLegacy($order->status ?? null)->value);
        $statusMeta = AdminOrderStatusSyncService::SELLER_STATUSES[$statusCode] ?? ['label' => $statusCode, 'badge' => 'badge-muted'];

        return [
            'id' => $order->id,
            'orderId' => $order->order_id,
            'sellerId' => $order->seller_id,
            'seller' => $order->seller?->shop_name ?: 'Seller',
            'sellerOwner' => trim(($order->seller?->firstname ?? '').' '.($order->seller?->lastname ?? '')) ?: 'Sotuvchi',
            'sellerPhone' => $order->seller?->phone_number,
            'sellerPhoto' => $this->assetFromStorage($order->seller?->photo),
            'customer' => trim(($order->client?->name ?? '').' '.($order->client?->lastname ?? '')) ?: ($address['fullName'] ?? 'Mijoz'),
            'customerPhone' => $order->client?->phone_number ?? ($address['phoneNumber'] ?? null),
            'courier' => trim(($order->courier?->first_name ?? '').' '.($order->courier?->last_name ?? '')) ?: ($order->courierName ?? '—'),
            'courierPhone' => $order->courier?->phone_number,
            'amount' => (float) ($order->amount ?? 0),
            'mainOrderAmount' => (float) ($order->order?->amount ?? 0),
            'deliveryPrice' => (float) ($order->order?->deliveryPrice ?? 0),
            'deliveryType' => $order->delivery_type ?: ($order->order?->deliveryType ?? '—'),
            'status' => $statusCode,
            'statusLabel' => $statusMeta['label'],
            'statusBadge' => $statusMeta['badge'],
            'acceptedAt' => optional($order->accepted_at)->format('Y-m-d H:i'),
            'date' => optional($order->created_at)->format('Y-m-d H:i'),
            'address' => [
                'fullName' => $address['fullName'] ?? null,
                'phone' => $address['phoneNumber'] ?? null,
                'region' => $address['region'] ?? $address['province'] ?? null,
                'district' => $address['district'] ?? null,
                'street' => $address['street'] ?? $address['address'] ?? null,
                'home' => $address['home'] ?? null,
            ],
            'summary' => [
                'itemsCount' => (int) $items->sum('quantity'),
                'itemsTotal' => (float) $items->sum(fn ($item) => (float) $item['price'] * (int) $item['quantity']),
            ],
            'items' => $items->all(),
            'showUrl' => route('boshqaruv.seller-orders'),
            'statusUrl' => route('boshqaruv.seller-orders.status', $order),
        ];
    }

    private function sellerOrderStatusCounts(): array
    {
        if (! Schema::hasTable('seller_orders')) {
            return ['all' => 0];
        }

        $counts = ['all' => (int) SellerOrder::query()->count()];
        foreach (array_keys(AdminOrderStatusSyncService::SELLER_STATUSES) as $status) {
            $counts[$status] = (int) SellerOrder::query()
                ->where(fn ($query) => $query
                    ->where('status_code', $status)
                    ->orWhere(fn ($fallback) => $fallback
                        ->whereNull('status_code')
                        ->where('status', SellerOrderStatusCode::fromLegacy($status)->legacy())
                    )
                )
                ->count();
        }

        return $counts;
    }

    private function couriersPayload(): array
    {
        if (! Schema::hasTable('couriers')) {
            return [];
        }

        return Couriers::query()
            ->withCount('orders')
            ->latest()
            ->take(60)
            ->get()
            ->map(fn (Couriers $courier) => [
                'id' => $courier->id,
                'name' => trim(($courier->first_name ?? '').' '.($courier->last_name ?? '')) ?: 'Kuryer',
                'phone' => $courier->phone_number,
                'region' => $courier->region,
                'status' => $courier->status,
                'verificationStatus' => $courier->verification_status,
                'transport' => $courier->transport_type,
                'vehicle' => trim(($courier->vehicle_brand ?? '').' '.($courier->vehicle_model ?? '')),
                'plate' => $courier->vehicle_plate_number,
                'balance' => (float) ($courier->balance ?? 0),
                'orders' => (int) ($courier->orders_count ?? 0),
                'photo' => $this->assetFromStorage($courier->photo),
                'createUrl' => route('admin.couriers.create'),
                'showUrl' => route('admin.couriers.show', $courier),
                'editUrl' => route('admin.couriers.edit', $courier),
                'approveUrl' => route('admin.couriers.approve', $courier),
                'rejectUrl' => route('admin.couriers.reject', $courier),
                'unblockUrl' => route('admin.couriers.unblock', $courier),
            ])
            ->values()
            ->all();
    }

    private function courierOrdersPayload(): array
    {
        if (! Schema::hasTable('courier_orders')) {
            return [];
        }

        return CourierOrder::query()
            ->with(['courier:id,first_name,last_name,phone_number', 'user:id,name,lastname,phone_number'])
            ->latest()
            ->take(80)
            ->get()
            ->map(fn (CourierOrder $order) => [
                'id' => $order->id,
                'orderId' => $order->order_id,
                'courier' => trim(($order->courier?->first_name ?? '').' '.($order->courier?->last_name ?? '')) ?: 'Tayinlanmagan',
                'courierPhone' => $order->courier?->phone_number,
                'customer' => trim(($order->user?->name ?? '').' '.($order->user?->lastname ?? '')) ?: 'Mijoz',
                'customerPhone' => $order->user?->phone_number,
                'amount' => (float) ($order->amount ?? 0),
                'courierPrice' => (float) ($order->courierPrice ?? 0),
                'bonus' => (float) ($order->final_bonus ?? $order->locked_bonus ?? $order->courierBonus ?? 0),
                'status' => (string) ($order->status_code ?? $order->status ?? ''),
                'date' => optional($order->created_at)->format('Y-m-d H:i'),
                'showUrl' => route('admin.courier-orders.show', $order),
                'statusUrl' => route('boshqaruv.courier-orders.status', $order),
            ])
            ->values()
            ->all();
    }

    private function hubsPayload(): array
    {
        if (! Schema::hasTable('hubs')) {
            return [];
        }

        return Hub::query()
            ->withCount(['staff', 'fulfillments', 'courierTasks'])
            ->orderBy('priority')
            ->take(80)
            ->get()
            ->map(fn (Hub $hub) => [
                'id' => $hub->id,
                'name' => $hub->name,
                'code' => $hub->code,
                'country' => $hub->country_code,
                'region' => $hub->region_name,
                'city' => $hub->city_name,
                'address' => $hub->address,
                'active' => (bool) $hub->is_active,
                'primary' => (bool) $hub->is_primary,
                'priority' => (int) $hub->priority,
                'staff' => (int) ($hub->staff_count ?? 0),
                'fulfillments' => (int) ($hub->fulfillments_count ?? 0),
                'courierTasks' => (int) ($hub->courier_tasks_count ?? 0),
                'supportsFirstMile' => (bool) $hub->supports_first_mile,
                'supportsLastMile' => (bool) $hub->supports_last_mile,
                'supportsPostal' => (bool) $hub->supports_postal_dispatch,
                'indexUrl' => route('admin.hubs.index'),
            ])
            ->values()
            ->all();
    }

    private function transactionsPayload(): array
    {
        if (! Schema::hasTable('seller_transactions')) {
            return [];
        }

        return SellerTransaction::query()
            ->with('seller:id,shop_name,phone_number')
            ->where(fn ($query) => $query->whereNull('category')->orWhere('category', 'withdrawal')->orWhere('category', 'seller_withdrawal'))
            ->latest()
            ->take(100)
            ->get()
            ->map(fn (SellerTransaction $transaction) => [
                'id' => $transaction->id,
                'user' => $transaction->seller?->shop_name ?: 'Seller',
                'phone' => $transaction->seller?->phone_number,
                'type' => $transaction->type ?: ($transaction->category ?: 'payout'),
                'amount' => (float) ($transaction->amount ?? 0),
                'commission' => (float) ($transaction->commissionPrice ?? 0),
                'netAmount' => (float) ($transaction->netAmount ?? $transaction->amount ?? 0),
                'status' => (string) ($transaction->status ?? 'pending'),
                'date' => optional($transaction->created_at)->format('Y-m-d H:i'),
                'method' => $transaction->card ?: '—',
                'note' => $transaction->description ?: $transaction->rejected_desc,
                'showUrl' => route('admin.transactions.show', $transaction),
                'approveUrl' => route('admin.transactions.approve', $transaction),
                'rejectUrl' => route('admin.transactions.reject', $transaction),
            ])
            ->values()
            ->all();
    }

    private function promocodesPayload(): array
    {
        if (! Schema::hasTable('promocodes')) {
            return [];
        }

        return Promocode::query()
            ->withCount('histories')
            ->latest()
            ->take(80)
            ->get()
            ->map(fn (Promocode $promocode) => [
                'id' => $promocode->id,
                'code' => $promocode->code,
                'type' => $promocode->type,
                'discount' => (int) ($promocode->amount ?? 0),
                'maxDiscount' => (int) ($promocode->max_discount_amount ?? 0),
                'minOrder' => (int) ($promocode->min_order_amount ?? 0),
                'used' => (int) ($promocode->usedCount ?? $promocode->histories_count ?? 0),
                'max' => (int) ($promocode->usesLimit ?? 0),
                'status' => $promocode->is_active ? 'Active' : 'Inactive',
                'expiresAt' => optional($promocode->expires_at)->format('Y-m-d'),
                'createUrl' => route('admin.promocodes.create'),
                'generateUrl' => route('admin.promocodes.generate'),
                'showUrl' => route('admin.promocodes.show', $promocode),
                'editUrl' => route('admin.promocodes.edit', $promocode),
                'destroyUrl' => route('admin.promocodes.destroy', $promocode),
            ])
            ->values()
            ->all();
    }

    private function adsPayload(): array
    {
        if (! Schema::hasTable('seller_ads')) {
            return [];
        }

        return SellerAd::query()
            ->with('seller:id,shop_name,phone_number')
            ->latest()
            ->take(80)
            ->get()
            ->map(fn (SellerAd $ad) => [
                'id' => $ad->id,
                'name' => $ad->seller?->shop_name ?: ($ad->description ?: 'Reklama'),
                'sellerPhone' => $ad->seller?->phone_number,
                'type' => $ad->type,
                'budget' => (float) ($ad->amount ?? 0),
                'spent' => 0,
                'clicks' => 0,
                'days' => (int) ($ad->days ?? 0),
                'status' => $ad->moderation ?: ($ad->paymentStatus ?: 'pending'),
                'paymentStatus' => $ad->paymentStatus,
                'expiresAt' => optional($ad->expire_at)->format('Y-m-d'),
                'image' => $this->assetFromStorage($ad->banner_img),
                'showUrl' => route('admin.ads.show', $ad),
                'moderateUrl' => route('boshqaruv.ads.moderate', $ad),
                'destroyUrl' => route('admin.ads.destroy', $ad),
            ])
            ->values()
            ->all();
    }

    private function bloggersPayload(): array
    {
        if (! Schema::hasTable('bloggers')) {
            return [];
        }

        return Blogger::query()
            ->withCount('shipments')
            ->latest()
            ->take(80)
            ->get()
            ->map(fn (Blogger $blogger) => [
                'id' => $blogger->id,
                'name' => $blogger->full_name,
                'phone' => $blogger->phone_number,
                'address' => $blogger->address,
                'platforms' => array_keys($blogger->socialLinks()),
                'followers' => 0,
                'shipments' => (int) ($blogger->shipments_count ?? 0),
                'status' => $blogger->status_label,
                'activeUntil' => optional($blogger->active_until)->format('Y-m-d'),
                'createUrl' => route('admin.bloggers.create'),
                'showUrl' => route('admin.bloggers.show', $blogger),
                'editUrl' => route('admin.bloggers.edit', $blogger),
                'destroyUrl' => route('admin.bloggers.destroy', $blogger),
            ])
            ->values()
            ->all();
    }

    private function giftCertificatesPayload(): array
    {
        if (! Schema::hasTable('gift_certificates')) {
            return [];
        }

        return GiftCertificate::query()
            ->with(['buyer:id,name,lastname,phone_number', 'recipient:id,name,lastname,phone_number'])
            ->latest()
            ->take(80)
            ->get()
            ->map(fn (GiftCertificate $certificate) => [
                'id' => $certificate->id,
                'code' => $certificate->code,
                'amount' => (int) ($certificate->nominal_uzs ?? 0),
                'buyer' => trim(($certificate->buyer?->name ?? '').' '.($certificate->buyer?->lastname ?? '')) ?: '—',
                'recipient' => trim(($certificate->recipient?->name ?? '').' '.($certificate->recipient?->lastname ?? '')) ?: '—',
                'status' => $certificate->status,
                'statusLabel' => $certificate->status_label,
                'expires' => optional($certificate->expires_at)->format('Y-m-d'),
                'paidAt' => optional($certificate->paid_at)->format('Y-m-d H:i'),
                'showUrl' => route('admin.gift-certificates.show', $certificate),
                'statusUrl' => route('admin.gift-certificates.status', $certificate),
                'cancelUrl' => route('admin.gift-certificates.cancel', $certificate),
            ])
            ->values()
            ->all();
    }

    private function marketNewsPayload(): array
    {
        if (! Schema::hasTable('market_news')) {
            return [];
        }

        return MarketNews::query()
            ->latest()
            ->take(80)
            ->get()
            ->map(fn (MarketNews $news) => [
                'id' => $news->id,
                'title' => $news->title,
                'description' => $news->description,
                'status' => $news->status ? 'Active' : 'Inactive',
                'action' => $news->action_label,
                'image' => $this->assetFromStorage($news->imgUrl),
                'date' => optional($news->created_at)->format('Y-m-d'),
                'createUrl' => route('admin.news.create'),
                'showUrl' => route('admin.news.show', $news),
                'editUrl' => route('admin.news.edit', $news),
                'toggleUrl' => route('admin.news.toggle', $news),
                'destroyUrl' => route('admin.news.destroy', $news),
            ])
            ->values()
            ->all();
    }

    private function reelsPayload(): array
    {
        if (! Schema::hasTable('reels')) {
            return [];
        }

        return Reel::query()
            ->withCount('items')
            ->orderBy('order')
            ->latest()
            ->take(80)
            ->get()
            ->map(fn (Reel $reel) => [
                'id' => $reel->id,
                'title' => $reel->title,
                'description' => $reel->description,
                'order' => (int) ($reel->order ?? 0),
                'items' => (int) ($reel->items_count ?? 0),
                'status' => 'Active',
                'createUrl' => route('admin.reels.create'),
                'showUrl' => route('admin.reels.show', $reel),
                'editUrl' => route('admin.reels.edit', $reel),
                'destroyUrl' => route('admin.reels.destroy', $reel),
            ])
            ->values()
            ->all();
    }

    private function policiesPayload(): array
    {
        if (! Schema::hasTable('policies')) {
            return [];
        }

        return Policy::query()
            ->orderBy('sort_order')
            ->latest()
            ->take(80)
            ->get()
            ->map(fn (Policy $policy) => [
                'id' => $policy->id,
                'title' => $policy->title,
                'slug' => $policy->slug,
                'status' => $policy->is_active ? 'Active' : 'Inactive',
                'showInApp' => (bool) $policy->show_in_app,
                'sortOrder' => (int) ($policy->sort_order ?? 0),
                'createUrl' => route('admin.policies.create'),
                'editUrl' => route('admin.policies.edit', $policy),
                'toggleUrl' => route('admin.policies.toggle', $policy),
                'destroyUrl' => route('admin.policies.destroy', $policy),
            ])
            ->values()
            ->all();
    }

    private function pushNotificationsPayload(): array
    {
        if (! Schema::hasTable('fcm_notifications')) {
            return [];
        }

        return FcmNotifications::query()
            ->latest()
            ->take(80)
            ->get()
            ->map(fn (FcmNotifications $notification) => [
                'id' => $notification->id,
                'title' => $notification->name,
                'body' => $notification->description,
                'who' => $notification->who,
                'status' => $notification->is_read ? 'Read' : 'Sent',
                'date' => optional($notification->created_at)->format('Y-m-d H:i'),
                'createUrl' => route('admin.push.create'),
                'destroyUrl' => route('admin.push.destroy', $notification),
            ])
            ->values()
            ->all();
    }

    private function searchHistoryPayload(): array
    {
        if (! Schema::hasTable('search_histories')) {
            return [];
        }

        return SearchHistory::query()
            ->with('user:id,name,lastname,phone_number')
            ->latest()
            ->take(120)
            ->get()
            ->map(fn (SearchHistory $history) => [
                'id' => $history->id,
                'text' => $history->text,
                'user' => trim(($history->user?->name ?? '').' '.($history->user?->lastname ?? '')) ?: ($history->session_id ?: 'Mehmon'),
                'resultCount' => (int) ($history->result_count ?? 0),
                'resultName' => $history->result_name,
                'resultType' => $history->result_type,
                'searchCount' => (int) ($history->search_count ?? 0),
                'draft' => (bool) $history->is_draft,
                'date' => optional($history->created_at)->format('Y-m-d H:i'),
            ])
            ->values()
            ->all();
    }

    private function giftsPayload(): array
    {
        if (! Schema::hasTable('gifts')) {
            return [];
        }

        return Gifts::query()
            ->with('seller:id,shop_name')
            ->latest()
            ->take(80)
            ->get()
            ->map(fn (Gifts $gift) => [
                'id' => $gift->id,
                'name' => $gift->name,
                'seller' => $gift->seller?->shop_name,
                'stock' => (int) ($gift->stock ?? 0),
                'priceFrom' => (float) ($gift->priceFrom ?? 0),
                'priceTo' => (float) ($gift->priceTo ?? 0),
                'status' => $gift->status ? 'Active' : 'Inactive',
                'approved' => (bool) $gift->is_approved,
                'sold' => (int) ($gift->totalSales ?? 0),
                'revenue' => (float) ($gift->totalRevenue ?? 0),
                'image' => $this->assetFromStorage(collect($gift->images ?? [])->first()),
                'indexUrl' => route('admin.gifts.index'),
            ])
            ->values()
            ->all();
    }

    private function ticketsPayload(): array
    {
        if (! Schema::hasTable('bot_tickets')) {
            return [];
        }

        return BotTicket::query()
            ->with(['operator', 'latestMessage'])
            ->withCount('messages')
            ->latest()
            ->take(80)
            ->get()
            ->map(fn (BotTicket $ticket) => [
                'id' => $ticket->id,
                'user' => $ticket->name ?: ($ticket->username ?: 'Mijoz'),
                'subject' => $ticket->first_msg ?: $ticket->latestMessage?->message ?: 'Support ticket',
                'operator' => $ticket->operator?->name,
                'messages' => (int) ($ticket->messages_count ?? 0),
                'rating' => $ticket->rating,
                'status' => $ticket->status,
                'date' => optional($ticket->created_at)->format('Y-m-d H:i'),
                'showUrl' => route('admin.support.show', $ticket),
                'assignUrl' => route('admin.support.assign', $ticket),
                'closeUrl' => route('admin.support.close', $ticket),
                'replyUrl' => route('admin.support.reply', $ticket),
            ])
            ->values()
            ->all();
    }

    private function complaintsPayload(): array
    {
        if (! Schema::hasTable('reports')) {
            return [];
        }

        return Report::query()
            ->with('user:id,name,lastname,phone_number')
            ->latest()
            ->take(80)
            ->get()
            ->map(fn (Report $report) => [
                'id' => $report->id,
                'user' => trim(($report->user?->name ?? '').' '.($report->user?->lastname ?? '')) ?: 'Mijoz',
                'phone' => $report->user?->phone_number,
                'reason' => $report->reason,
                'comment' => $report->comment,
                'type' => class_basename($report->reportable_type ?: 'Report'),
                'status' => $report->status,
                'date' => optional($report->created_at)->format('Y-m-d H:i'),
                'showUrl' => route('admin.complaints.show', $report),
                'statusUrl' => route('admin.complaints.status', $report),
                'destroyUrl' => route('admin.complaints.destroy', $report),
            ])
            ->values()
            ->all();
    }

    private function vacanciesPayload(): array
    {
        if (! Schema::hasTable('vacancies')) {
            return [];
        }

        return Vacancy::query()
            ->withCount('careerApplications')
            ->ordered()
            ->take(80)
            ->get()
            ->map(fn (Vacancy $vacancy) => [
                'id' => $vacancy->id,
                'title' => $vacancy->title,
                'contractType' => $vacancy->contract_type,
                'location' => $vacancy->location,
                'status' => $vacancy->is_active ? 'Active' : 'Inactive',
                'applicants' => (int) ($vacancy->career_applications_count ?? 0),
                'createUrl' => route('admin.jobs.create'),
                'editUrl' => route('admin.jobs.edit', $vacancy),
                'toggleUrl' => route('admin.jobs.toggle', $vacancy),
                'destroyUrl' => route('admin.jobs.destroy', $vacancy),
            ])
            ->values()
            ->all();
    }

    private function careerApplicationsPayload(): array
    {
        if (! Schema::hasTable('career_applications')) {
            return [];
        }

        return CareerApplication::query()
            ->with('vacancy:id,title')
            ->latest()
            ->take(80)
            ->get()
            ->map(fn (CareerApplication $application) => [
                'id' => $application->id,
                'name' => $application->full_name,
                'email' => $application->email,
                'telegram' => $application->telegram_username,
                'vacancy' => $application->vacancy?->title ?: $application->type,
                'status' => $application->status,
                'message' => $application->cover_message,
                'date' => optional($application->created_at)->format('Y-m-d H:i'),
                'showUrl' => route('admin.job-applications.show', $application),
                'cvUrl' => route('admin.job-applications.cv', $application),
                'replyUrl' => route('admin.job-applications.reply', $application),
                'statusUrl' => route('admin.job-applications.status', $application),
            ])
            ->values()
            ->all();
    }

    private function adminsPayload(): array
    {
        if (! Schema::hasTable('admins')) {
            return [];
        }

        return Admin::query()
            ->latest()
            ->take(80)
            ->get()
            ->map(fn (Admin $admin) => [
                'id' => $admin->id,
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => $admin->role_label,
                'active' => (bool) $admin->is_active,
                'lastLogin' => optional($admin->last_login_at)->format('Y-m-d H:i'),
                'createUrl' => route('admin.admins.create'),
                'showUrl' => route('admin.admins.show', $admin),
                'editUrl' => route('admin.admins.edit', $admin),
                'toggleUrl' => route('admin.admins.toggle', $admin),
                'destroyUrl' => route('admin.admins.destroy', $admin),
            ])
            ->values()
            ->all();
    }

    private function apiClientsPayload(): array
    {
        if (! Schema::hasTable('api_clients')) {
            return [];
        }

        return ApiClient::query()
            ->withCount('requestLogs')
            ->latest()
            ->take(80)
            ->get()
            ->map(fn (ApiClient $client) => [
                'id' => $client->id,
                'name' => $client->name,
                'key' => $client->app_id,
                'active' => (bool) $client->is_active,
                'requests' => (int) ($client->request_logs_count ?? 0),
                'rateLimitSecond' => (int) ($client->rate_limit_per_second ?? 0),
                'rateLimitMinute' => (int) ($client->rate_limit_per_minute ?? 0),
                'createUrl' => route('admin.api-clients.create'),
                'docsUrl' => route('admin.api-clients.docs'),
                'logsUrl' => route('admin.api-clients.logs'),
                'editUrl' => route('admin.api-clients.edit', $client),
                'toggleUrl' => route('admin.api-clients.toggle', $client),
                'regenerateUrl' => route('admin.api-clients.regenerate', $client),
                'destroyUrl' => route('admin.api-clients.destroy', $client),
            ])
            ->values()
            ->all();
    }

    private function apiLogsPayload(): array
    {
        if (! Schema::hasTable('api_client_request_logs')) {
            return [];
        }

        return ApiClientRequestLog::query()
            ->with('client:id,name')
            ->latest()
            ->take(50)
            ->get()
            ->map(fn (ApiClientRequestLog $log) => [
                'id' => $log->id,
                'client' => $log->client?->name,
                'method' => $log->method,
                'path' => $log->path,
                'status' => (int) ($log->status_code ?? 0),
                'date' => optional($log->created_at)->format('Y-m-d H:i:s'),
            ])
            ->values()
            ->all();
    }

    private function settingsPayload(): array
    {
        if (! Schema::hasTable('project_settings')) {
            return [];
        }

        $settings = ProjectSetting::query()->firstOrCreate([]);

        return [
            'project' => [
                'business_version_ios' => $settings->business_version_ios,
                'business_version_android' => $settings->business_version_android,
                'courier_version_ios' => $settings->courier_version_ios,
                'courier_version_android' => $settings->courier_version_android,
                'market_version_ios' => $settings->market_version_ios,
                'market_version_android' => $settings->market_version_android,
                'kitobchi_phone' => $settings->kitobchi_phone,
                'kitobchi_email' => $settings->kitobchi_email,
                'business_phone' => $settings->business_phone,
                'business_email' => $settings->business_email,
                'courier_phone' => $settings->courier_phone,
                'courier_email' => $settings->courier_email,
                'on_premium' => (bool) $settings->on_premium,
                'on_reels' => (bool) $settings->on_reels,
                'ramadan' => (bool) $settings->ramadan,
                'stop_sales' => (bool) $settings->stop_sales,
                'packaging_price_small' => (int) ($settings->packaging_price_small ?? 25000),
                'packaging_price_large' => (int) ($settings->packaging_price_large ?? 40000),
                'packaging_threshold' => (int) ($settings->packaging_threshold ?? 4),
                'courier_surge_step' => (int) ($settings->courier_surge_step ?? 500),
                'courier_surge_max' => (int) ($settings->courier_surge_max ?? 10000),
                'courier_surge_threshold' => (int) ($settings->courier_surge_threshold ?? 5000),
                'courier_sla_minutes' => (int) ($settings->courier_sla_minutes ?? 45),
                'courier_penalty_step' => (int) ($settings->courier_penalty_step ?? 300),
                'telegram_login_enabled' => (bool) $settings->telegram_login_enabled,
                'telegram_client_id' => $settings->telegram_client_id,
                'telegram_redirect_uri_ios' => $settings->telegram_redirect_uri_ios ?: 'https://app3206985527-login.tg.dev',
                'telegram_redirect_uri_android' => $settings->telegram_redirect_uri_android ?: 'https://app2854400165-login.tg.dev/tglogin',
                'telegram_scopes' => $settings->telegram_scopes ?: 'openid profile phone',
            ],
            'commission' => $this->settingsCommissionPayload(),
            'cashback' => $this->settingsCashbackPayload(),
            'cashbackDelivery' => $this->settingsCashbackPayload(CashbackSetting::TYPE_DELIVERY),
            'cashbackPickup' => $this->settingsCashbackPayload(CashbackSetting::TYPE_PICKUP),
            'delivery' => $this->settingsDeliveryPayload(),
            'actions' => [
                'versions' => route('boshqaruv.settings.versions'),
                'contacts' => route('boshqaruv.settings.contacts'),
                'appFlags' => route('boshqaruv.settings.app-flags'),
                'courierBonus' => route('boshqaruv.settings.courier-bonus'),
                'telegram' => route('boshqaruv.settings.telegram'),
                'commissionStore' => route('boshqaruv.settings.commission.store'),
                'cashbackStore' => route('boshqaruv.settings.cashback.store'),
                'deliveryStore' => route('boshqaruv.settings.delivery.store'),
            ],
            'indexUrl' => route('boshqaruv.settings'),
        ];
    }

    private function settingsCommissionPayload(): array
    {
        if (! Schema::hasTable('commission_settings')) {
            return [];
        }

        return CommissionSetting::query()
            ->orderBy('priceFrom')
            ->get()
            ->map(fn (CommissionSetting $setting) => [
                'id' => $setting->id,
                'priceFrom' => (int) ($setting->priceFrom ?? 0),
                'priceTo' => (int) ($setting->priceTo ?? 0),
                'percent' => (int) ($setting->percent ?? 0),
                'updateUrl' => route('boshqaruv.settings.commission.update', $setting),
                'destroyUrl' => route('boshqaruv.settings.commission.destroy', $setting),
            ])
            ->values()
            ->all();
    }

    private function settingsCashbackPayload(?string $type = null): array
    {
        if (! Schema::hasTable('cashback_settings')) {
            return [];
        }

        return CashbackSetting::query()
            ->when($type, fn ($query) => $query->where('type', $type))
            ->orderBy('type')
            ->orderBy('fromUzs')
            ->get()
            ->map(fn (CashbackSetting $setting) => [
                'id' => $setting->id,
                'fromUzs' => (int) ($setting->fromUzs ?? 0),
                'toUzs' => (int) ($setting->toUzs ?? 0),
                'cashback' => (int) ($setting->cashback ?? 0),
                'type' => $setting->type ?: CashbackSetting::TYPE_DELIVERY,
                'updateUrl' => route('boshqaruv.settings.cashback.update', $setting),
                'destroyUrl' => route('boshqaruv.settings.cashback.destroy', $setting),
            ])
            ->values()
            ->all();
    }

    private function settingsDeliveryPayload(): array
    {
        if (! Schema::hasTable('delivery_services')) {
            return [];
        }

        return DeliveryService::query()
            ->orderBy('name')
            ->get()
            ->map(fn (DeliveryService $service) => [
                'id' => $service->id,
                'name' => $service->name,
                'type' => $service->type,
                'priceKg' => (int) ($service->priceKg ?? 0),
                'muddat' => (int) ($service->muddat ?? 0),
                'forCountry' => $service->forCountry,
                'capital' => (bool) $service->capital,
                'freePriceFrom' => (int) ($service->freePriceFrom ?? 0),
                'status' => (bool) $service->status,
                'updateUrl' => route('boshqaruv.settings.delivery.update', $service),
                'destroyUrl' => route('boshqaruv.settings.delivery.destroy', $service),
            ])
            ->values()
            ->all();
    }

    private function deliveryServicesPayload(): array
    {
        if (! Schema::hasTable('delivery_services')) {
            return [];
        }

        return DeliveryService::query()
            ->orderBy('name')
            ->take(80)
            ->get()
            ->map(fn (DeliveryService $service) => [
                'id' => $service->id,
                'name' => $service->name,
                'type' => $service->type,
                'price' => (int) ($service->priceKg ?? 0),
                'days' => (int) ($service->muddat ?? 0),
                'country' => $service->forCountry,
                'capital' => (bool) $service->capital,
                'freeFrom' => (int) ($service->freePriceFrom ?? 0),
                'active' => (bool) $service->status,
                'indexUrl' => route('admin.logistics.index'),
            ])
            ->values()
            ->all();
    }

    private function mysteryBoxPayload(): array
    {
        $plans = [];
        $subscriptions = [];

        if (Schema::hasTable('mystery_box_plans')) {
            $plans = MysteryBoxPlan::query()
                ->withCount('subscriptions')
                ->orderBy('sort_order')
                ->get()
                ->map(fn (MysteryBoxPlan $plan) => [
                    'id' => $plan->id,
                    'name' => $plan->name_uz,
                    'months' => (int) ($plan->months ?? 0),
                    'price' => (int) ($plan->price_uzs ?? 0),
                    'booksPerMonth' => (int) ($plan->books_per_month ?? 0),
                    'active' => (bool) $plan->is_active,
                    'subscribers' => (int) ($plan->subscriptions_count ?? 0),
                    'plansUrl' => route('admin.mystery-box.plans'),
                    'destroyUrl' => route('admin.mystery-box.plans.destroy', $plan),
                ])
                ->values()
                ->all();
        }

        if (Schema::hasTable('mystery_box_subscriptions')) {
            $subscriptions = MysteryBoxSubscription::query()
                ->with(['user:id,name,lastname,phone_number', 'plan:id,name_uz'])
                ->latest()
                ->take(80)
                ->get()
                ->map(fn (MysteryBoxSubscription $subscription) => [
                    'id' => $subscription->id,
                    'user' => trim(($subscription->user?->name ?? '').' '.($subscription->user?->lastname ?? '')) ?: 'Mijoz',
                    'phone' => $subscription->user?->phone_number,
                    'plan' => $subscription->plan?->name_uz ?: '—',
                    'status' => $subscription->status,
                    'statusLabel' => $subscription->status_label,
                    'nextDelivery' => optional($subscription->next_delivery_at)->format('Y-m-d'),
                    'progress' => $subscription->progress_pct,
                    'showUrl' => route('admin.mystery-box.show', $subscription),
                    'pauseUrl' => route('admin.mystery-box.pause', $subscription),
                    'resumeUrl' => route('admin.mystery-box.resume', $subscription),
                    'cancelUrl' => route('admin.mystery-box.cancel', $subscription),
                ])
                ->values()
                ->all();
        }

        return [
            'plans' => $plans,
            'subscriptions' => $subscriptions,
            'indexUrl' => route('admin.mystery-box.index'),
            'plansUrl' => route('admin.mystery-box.plans'),
        ];
    }

    private function bookClubPayload(): array
    {
        if (! Schema::hasTable('book_club')) {
            return [];
        }

        return BookClub::query()
            ->with(['user:id,name,lastname,avatar', 'activeWarning'])
            ->withCount(['likes', 'comments'])
            ->where('is_deleted', false)
            ->latest()
            ->take(80)
            ->get()
            ->map(fn (BookClub $post) => [
                'id' => $post->id,
                'author' => trim(($post->user?->name ?? '').' '.($post->user?->lastname ?? '')) ?: 'Kitobxon',
                'text' => $post->text,
                'productType' => $post->product_type,
                'repost' => (bool) $post->repost,
                'likes' => (int) ($post->likes_count ?? 0),
                'comments' => (int) ($post->comments_count ?? 0),
                'aiStatus' => $post->ai_post_status,
                'aiScore' => $post->ai_post_score,
                'warning' => (bool) $post->activeWarning,
                'date' => optional($post->created_at)->format('Y-m-d H:i'),
                'showUrl' => route('admin.book-club.show', $post),
                'editUrl' => route('admin.book-club.edit', $post),
                'warnUrl' => route('admin.book-club.warn', $post),
                'destroyUrl' => route('admin.book-club.destroy', $post),
            ])
            ->values()
            ->all();
    }

    private function liveSnapshot(): array
    {
        $now = now();
        $today = $now->copy()->startOfDay();
        $week = $now->copy()->startOfWeek();
        $month = $now->copy()->startOfMonth();
        $activeOrderStatuses = ['pending', 'packing', 'in_delivery', 'A', 'P', 'B', 'D'];

        $orders = Sold::query();
        $todayOrdersQuery = Sold::query()->where('created_at', '>=', $today);
        $monthOrdersQuery = Sold::query()->where('created_at', '>=', $month);
        $totalRevenue = (float) $this->paidOrdersQuery()->sum('amount');
        $todayRevenue = (float) $this->paidOrdersQuery()->where('created_at', '>=', $today)->sum('amount');
        $monthRevenue = (float) $this->paidOrdersQuery()->where('created_at', '>=', $month)->sum('amount');
        $totalOrders = (int) $orders->count();
        $todayOrders = (int) (clone $todayOrdersQuery)->count();
        $weekOrders = (int) Sold::query()->where('created_at', '>=', $week)->count();
        $avgOrderValue = $totalOrders > 0 ? round($totalRevenue / $totalOrders) : 0;
        $activeOrders = $this->countStatuses(Sold::query(), $activeOrderStatuses);
        $cancelledOrders = $this->countStatuses(Sold::query(), ['cancelled', 'returned', 'F']);
        $completedOrders = $this->countStatuses(Sold::query(), ['delivered', 'customer_received', 'C']);
        $deliveryIncome = (float) Sold::query()->sum('deliveryPrice');
        $promoDiscount = (float) Sold::query()->sum('discountAmount');
        $cashback = (float) Sold::query()->sum('cashbackAmount');
        $platformProfit = max(0, $deliveryIncome - $promoDiscount - $cashback);
        $onlineUsers = User::query()->where('last_seen_at', '>=', $now->copy()->subMinutes(5))->count();

        $mainCounts = [
            'all' => $totalOrders,
            'new' => $this->countStatuses(Sold::query(), ['pending', 'A']),
            'packing' => $this->countStatuses(Sold::query(), ['packing', 'P', 'B']),
            'onway' => $this->countStatuses(Sold::query(), ['in_delivery', 'D']),
            'arrived' => $this->countStatuses(Sold::query(), ['delivered', 'C']),
            'done' => (int) Sold::query()->whereNotNull('completed_at')->count(),
            'cancelled' => $cancelledOrders,
        ];

        $sellerCounts = [
            'all' => $this->tableCount('seller_orders'),
            'payment_pending' => $this->countStatuses(SellerOrder::query(), ['payment_pending']),
            'new' => $this->countStatuses(SellerOrder::query(), ['new']),
            'accepted' => $this->countStatuses(SellerOrder::query(), ['accepted']),
            'handover' => $this->countStatuses(SellerOrder::query(), ['handed_to_courier']),
            'cancelled' => $this->countStatuses(SellerOrder::query(), ['cancelled']),
        ];

        $courierCounts = [
            'all' => $this->tableCount('courier_orders'),
            'pay_process' => $this->countStatuses(CourierOrder::query(), ['payment_pending']),
            'pending' => $this->countStatuses(CourierOrder::query(), ['pending']),
            'in_delivery' => $this->countStatuses(CourierOrder::query(), ['in_delivery']),
            'delivered' => $this->countStatuses(CourierOrder::query(), ['delivered']),
            'customer_received' => $this->countStatuses(CourierOrder::query(), ['customer_received']),
            'rejected' => $this->countStatuses(CourierOrder::query(), ['cancelled', 'returned']),
        ];

        return [
            'generated_at' => $now->format('H:i:s'),
            'endpoint' => route('boshqaruv.live.data'),
            'kpis' => [
                'total_revenue' => $totalRevenue,
                'today_revenue' => $todayRevenue,
                'month_revenue' => $monthRevenue,
                'platform_profit' => $platformProfit,
                'total_orders' => $totalOrders,
                'today_orders' => $todayOrders,
                'week_orders' => $weekOrders,
                'active_orders' => $activeOrders,
                'completed_orders' => $completedOrders,
                'cancelled_orders' => $cancelledOrders,
                'avg_order_value' => $avgOrderValue,
                'online_users' => (int) $onlineUsers,
                'delivery_income' => $deliveryIncome,
                'promo_discount' => $promoDiscount,
                'cashback' => $cashback,
            ],
            'main_counts' => $mainCounts,
            'seller_counts' => $sellerCounts,
            'courier_counts' => $courierCounts,
            'chart' => $this->liveHourlyChart($today),
            'regions' => $this->liveRegionStats(),
            'recent_orders' => $this->liveRecentOrders(),
            'recent_seller_orders' => $this->liveRecentSellerOrders(),
            'recent_courier_orders' => $this->liveRecentCourierOrders(),
            'online_users' => $this->liveOnlineUsers(),
            'top_products' => $this->liveTopProducts(),
            'alerts' => $this->liveAlerts($mainCounts, $sellerCounts, $courierCounts),
            'payment_split' => $this->paymentSplit([]),
            'delivery_split' => $this->deliverySplit(),
        ];
    }

    private function paidOrdersQuery()
    {
        return Sold::query()->where(function ($query) {
            $query->whereIn('payment_status_code', ['paid', 'success', 'completed'])
                ->orWhereIn('paymentStatus', ['paid', 'success', 'completed', 'C', 'c'])
                ->orWhereNotNull('completed_at');
        });
    }

    private function dashboardMonthlySales(): array
    {
        return collect(range(11, 0))->map(function ($i) {
            $date = now()->subMonths($i);
            $start = $date->copy()->startOfMonth();
            $end = $date->copy()->endOfMonth();
            $revenue = (float) $this->paidOrdersQuery()->whereBetween('created_at', [$start, $end])->sum('amount');
            $orders = (int) Sold::query()->whereBetween('created_at', [$start, $end])->count();
            $promo = (float) $this->paidOrdersQuery()->whereBetween('created_at', [$start, $end])->sum('discountAmount');
            $cashback = (float) $this->paidOrdersQuery()->whereBetween('created_at', [$start, $end])->sum('cashbackAmount');
            $delivery = (float) $this->paidOrdersQuery()->whereBetween('created_at', [$start, $end])->sum('deliveryPrice');
            $commission = Schema::hasTable('seller_transactions')
                ? (float) SellerTransaction::query()->where('status', 'approved')->whereBetween('created_at', [$start, $end])->sum('commissionPrice')
                : 0;

            return [
                'month' => $date->format('M'),
                'revenue' => $revenue,
                'profit' => $commission + $delivery - $promo - $cashback,
                'orders' => $orders,
            ];
        })->values()->all();
    }

    private function dashboardCategoryShare(): array
    {
        $colors = ['book' => '#4f46e5', 'stationery' => '#10b981', 'gift' => '#f59e0b', 'other' => '#ec4899'];
        $labels = ['book' => 'Kitoblar', 'stationery' => 'Kanselyariya', 'gift' => 'Sovg\'alar', 'other' => 'Boshqa'];
        $totals = ['book' => 0.0, 'stationery' => 0.0, 'gift' => 0.0, 'other' => 0.0];

        $this->paidOrdersQuery()
            ->whereNotNull('items')
            ->latest()
            ->take(1200)
            ->get(['items'])
            ->each(function (Sold $order) use (&$totals) {
                foreach (collect($order->items ?? []) as $item) {
                    $type = (string) ($item['type'] ?? 'book');
                    $key = array_key_exists($type, $totals) ? $type : 'other';
                    $quantity = (int) ($item['count_item'] ?? $item['count'] ?? $item['quantity'] ?? 1);
                    $price = (float) ($item['item_price'] ?? $item['price'] ?? 0);
                    $totals[$key] += $quantity * $price;
                }
            });

        $sum = array_sum($totals);
        if ($sum <= 0) {
            return [];
        }

        return collect($totals)
            ->filter(fn ($value) => $value > 0)
            ->map(fn ($value, $key) => [
                'name' => $labels[$key] ?? $key,
                'value' => round($value / $sum * 100, 1),
                'revenue' => $value,
                'color' => $colors[$key] ?? '#64748b',
            ])
            ->values()
            ->all();
    }

    private function countStatuses($query, array $statuses): int
    {
        return (int) $query
            ->where(function ($inner) use ($statuses) {
                $inner->whereIn('status_code', $statuses)
                    ->orWhere(function ($fallback) use ($statuses) {
                        $fallback->whereNull('status_code')->whereIn('status', $statuses);
                    });
            })
            ->count();
    }

    private function liveHourlyChart($today): array
    {
        return $this->paidOrdersQuery()
            ->where('created_at', '>=', $today)
            ->selectRaw('HOUR(created_at) as hour, COUNT(*) as orders, COALESCE(SUM(amount), 0) as revenue')
            ->groupBy(DB::raw('HOUR(created_at)'))
            ->orderBy('hour')
            ->get()
            ->keyBy('hour')
            ->pipe(fn ($rows) => collect(range(0, 23))->map(fn ($hour) => [
                'hour' => str_pad((string) $hour, 2, '0', STR_PAD_LEFT).':00',
                'orders' => (int) ($rows[$hour]->orders ?? 0),
                'revenue' => (float) ($rows[$hour]->revenue ?? 0),
            ]))
            ->values()
            ->all();
    }

    private function liveRegionStats(): array
    {
        $colors = ['#a855f7', '#6366f1', '#3b82f6', '#10b981', '#f59e0b', '#ec4899', '#06b6d4', '#f43f5e'];
        $coords = [
            ['x' => 72, 'y' => 35], ['x' => 50, 'y' => 55], ['x' => 35, 'y' => 60], ['x' => 85, 'y' => 45],
            ['x' => 92, 'y' => 40], ['x' => 82, 'y' => 32], ['x' => 20, 'y' => 45], ['x' => 45, 'y' => 70],
        ];

        $rows = $this->paidOrdersQuery()
            ->whereNotNull('address')
            ->latest()
            ->take(500)
            ->get(['address', 'amount'])
            ->map(function (Sold $order) {
                $address = collect($order->address ?? [])->first() ?? [];
                $region = data_get($address, 'region')
                    ?: data_get($address, 'region_name')
                    ?: data_get($address, 'city')
                    ?: data_get($address, 'district')
                    ?: 'Noma\'lum';

                return [
                    'region' => is_scalar($region) ? (string) $region : 'Noma\'lum',
                    'amount' => (float) ($order->amount ?? 0),
                ];
            })
            ->groupBy('region')
            ->map(fn ($items, $region) => [
                'name' => (string) $region,
                'value' => $items->count(),
                'revenue' => (float) $items->sum('amount'),
            ])
            ->sortByDesc('value')
            ->take(8)
            ->values();

        return $rows->map(fn ($row, $index) => [
            'name' => $row['name'],
            'value' => $row['value'],
            'revenue' => $row['revenue'],
            'profit' => max(0, round($row['revenue'] * 0.12)),
            'color' => $colors[$index % count($colors)],
            'coords' => $coords[$index % count($coords)],
        ])->all();
    }

    private function liveRecentOrders(): array
    {
        return Sold::query()
            ->with('user:id,name,lastname,avatar,phone_number')
            ->latest('updated_at')
            ->take(12)
            ->get()
            ->map(fn (Sold $order) => [
                'id' => $order->id,
                'title' => '#'.$order->id.' buyurtma',
                'customer' => trim(($order->user?->name ?? 'Mehmon').' '.($order->user?->lastname ?? '')) ?: 'Mehmon',
                'phone' => $order->user?->phone_number,
                'avatar' => $this->assetFromStorage($order->user?->avatar),
                'amount' => (float) ($order->amount ?? 0),
                'status' => $this->orderStatusLabel((string) ($order->status_code ?? $order->status ?? '')),
                'status_code' => (string) ($order->status_code ?? $order->status ?? ''),
                'updated_at' => optional($order->updated_at)->diffForHumans(),
                'url' => route('boshqaruv.orders'),
            ])
            ->values()
            ->all();
    }

    private function liveRecentSellerOrders(): array
    {
        if (! Schema::hasTable('seller_orders')) {
            return [];
        }

        return SellerOrder::query()
            ->with(['seller:id,shop_name,firstname,lastname,photo', 'client:id,name,lastname,avatar'])
            ->latest('updated_at')
            ->take(12)
            ->get()
            ->map(fn (SellerOrder $order) => [
                'id' => $order->id,
                'seller' => $order->seller?->shop_name ?: trim(($order->seller?->firstname ?? '').' '.($order->seller?->lastname ?? '')) ?: 'Sotuvchi',
                'customer' => trim(($order->client?->name ?? '').' '.($order->client?->lastname ?? '')) ?: 'Mijoz',
                'avatar' => $this->assetFromStorage($order->seller?->photo),
                'amount' => (float) ($order->amount ?? 0),
                'status' => $this->sellerStatusLabel((string) ($order->status_code ?? $order->status ?? '')),
                'status_code' => (string) ($order->status_code ?? $order->status ?? ''),
                'updated_at' => optional($order->updated_at)->diffForHumans(),
                'url' => route('boshqaruv.seller-orders'),
            ])
            ->values()
            ->all();
    }

    private function liveRecentCourierOrders(): array
    {
        if (! Schema::hasTable('courier_orders')) {
            return [];
        }

        return CourierOrder::query()
            ->with(['courier:id,first_name,last_name,photo', 'user:id,name,lastname,avatar'])
            ->latest('updated_at')
            ->take(12)
            ->get()
            ->map(fn (CourierOrder $order) => [
                'id' => $order->id,
                'courier' => trim(($order->courier?->first_name ?? '').' '.($order->courier?->last_name ?? '')) ?: 'Tayinlanmagan',
                'customer' => trim(($order->user?->name ?? '').' '.($order->user?->lastname ?? '')) ?: 'Mijoz',
                'avatar' => $this->assetFromStorage($order->courier?->photo),
                'amount' => (float) ($order->amount ?? 0),
                'status' => $this->orderStatusLabel((string) ($order->status_code ?? $order->status ?? '')),
                'status_code' => (string) ($order->status_code ?? $order->status ?? ''),
                'updated_at' => optional($order->updated_at)->diffForHumans(),
                'url' => route('boshqaruv.courier-orders'),
            ])
            ->values()
            ->all();
    }

    private function liveOnlineUsers(): array
    {
        return User::query()
            ->where('last_seen_at', '>=', now()->subMinutes(5))
            ->select('id', 'name', 'lastname', 'avatar', 'last_seen_at')
            ->latest('last_seen_at')
            ->take(12)
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => trim(($user->name ?? '').' '.($user->lastname ?? '')) ?: 'Foydalanuvchi',
                'avatar' => $this->assetFromStorage($user->avatar),
                'last_seen' => optional($user->last_seen_at)->diffForHumans(),
            ])
            ->values()
            ->all();
    }

    private function liveTopProducts(): array
    {
        $products = $this->paidOrdersQuery()
            ->latest()
            ->take(300)
            ->get(['items'])
            ->flatMap(fn (Sold $order) => collect($order->items ?? []))
            ->map(function ($item) {
                $quantity = (int) ($item['count_item'] ?? $item['count'] ?? $item['quantity'] ?? 1);
                $price = (float) ($item['item_price'] ?? $item['price'] ?? 0);
                return [
                    'id' => (int) ($item['item_id'] ?? $item['product_id'] ?? 0),
                    'type' => (string) ($item['type'] ?? 'book'),
                    'name' => (string) ($item['name'] ?? 'Mahsulot'),
                    'quantity' => $quantity,
                    'revenue' => $quantity * $price,
                ];
            })
            ->groupBy(fn ($item) => $item['type'].':'.$item['id'].':'.$item['name'])
            ->map(fn ($items) => [
                'name' => $items->first()['name'],
                'quantity' => (int) $items->sum('quantity'),
                'revenue' => (float) $items->sum('revenue'),
            ])
            ->sortByDesc('quantity')
            ->take(5)
            ->values();

        return $products->all();
    }

    private function liveAlerts(array $mainCounts, array $sellerCounts, array $courierCounts): array
    {
        $alerts = [];
        if (($mainCounts['new'] ?? 0) > 0) {
            $alerts[] = ['level' => 'warning', 'icon' => 'bi-bag-check', 'title' => 'Yangi buyurtmalar', 'text' => $mainCounts['new']." ta buyurtma ishlov kutmoqda", 'url' => route('boshqaruv.orders')];
        }
        if (($sellerCounts['new'] ?? 0) > 0) {
            $alerts[] = ['level' => 'info', 'icon' => 'bi-shop-window', 'title' => 'Seller navbati', 'text' => $sellerCounts['new']." ta seller order qabul kutmoqda", 'url' => route('boshqaruv.seller-orders')];
        }
        if (($courierCounts['pending'] ?? 0) > 0) {
            $alerts[] = ['level' => 'warning', 'icon' => 'bi-bicycle', 'title' => 'Kuryer navbati', 'text' => $courierCounts['pending']." ta kuryer order kutilmoqda", 'url' => route('boshqaruv.courier-orders')];
        }
        if ($this->tableCount('bot_tickets') > 0) {
            $queued = Schema::hasColumn('bot_tickets', 'status') ? DB::table('bot_tickets')->where('status', 'queue')->count() : 0;
            if ($queued > 0) {
                $alerts[] = ['level' => 'danger', 'icon' => 'bi-headset', 'title' => 'Support navbati', 'text' => $queued.' ta murojaat javob kutmoqda', 'url' => route('boshqaruv.tickets')];
            }
        }

        return array_slice($alerts, 0, 6);
    }

    private function paymentSplit(array $paidStatuses): array
    {
        $total = max(1, Sold::query()->count());
        $paid = $paidStatuses
            ? Sold::query()->where(fn ($query) => $query->whereIn('payment_status_code', $paidStatuses)->orWhereIn('paymentStatus', $paidStatuses))->count()
            : $this->paidOrdersQuery()->count();

        return [
            ['name' => 'To\'langan', 'count' => (int) $paid, 'share' => round($paid / $total * 100, 1), 'color' => '#10b981'],
            ['name' => 'Kutilmoqda', 'count' => (int) ($total - $paid), 'share' => round(($total - $paid) / $total * 100, 1), 'color' => '#f59e0b'],
        ];
    }

    private function deliverySplit(): array
    {
        return $this->paidOrdersQuery()
            ->selectRaw('deliveryType, COUNT(*) as count, COALESCE(SUM(amount), 0) as revenue')
            ->groupBy('deliveryType')
            ->orderByDesc('count')
            ->take(5)
            ->get()
            ->map(fn ($row) => [
                'name' => $row->deliveryType ?: 'delivery',
                'count' => (int) $row->count,
                'revenue' => (float) $row->revenue,
            ])
            ->values()
            ->all();
    }

    private function recentBookOrders(Books $book): array
    {
        return Sold::query()
            ->with('user:id,name,lastname,phone_number')
            ->latest()
            ->take(80)
            ->get()
            ->filter(fn (Sold $order) => collect($order->items ?? [])->contains(
                fn ($item) => (int) ($item['item_id'] ?? $item['product_id'] ?? 0) === (int) $book->id
                    && ($item['type'] ?? 'book') === 'book'
            ))
            ->take(6)
            ->map(fn (Sold $order) => [
                'id' => $order->id,
                'customer' => trim(($order->user?->name ?? '').' '.($order->user?->lastname ?? '')) ?: 'Mijoz',
                'phone' => $order->user?->phone_number,
                'amount' => (float) ($order->amount ?? 0),
                'status' => (string) ($order->status_code ?? $order->status ?? ''),
                'payment' => (string) ($order->payment_status_code ?? $order->paymentStatus ?? ''),
                'date' => optional($order->created_at)->format('Y-m-d H:i'),
                'url' => route('admin.orders.show', $order),
            ])
            ->values()
            ->all();
    }

    private function recentSellerOrdersForBook(Books $book): array
    {
        if (! $book->seller_id || ! Schema::hasTable('seller_orders')) {
            return [];
        }

        return SellerOrder::query()
            ->with(['client:id,name,lastname,phone_number', 'seller:id,shop_name'])
            ->where('seller_id', $book->seller_id)
            ->latest()
            ->take(6)
            ->get()
            ->map(fn (SellerOrder $order) => [
                'id' => $order->id,
                'seller' => $order->seller?->shop_name,
                'customer' => trim(($order->client?->name ?? '').' '.($order->client?->lastname ?? '')) ?: 'Mijoz',
                'phone' => $order->client?->phone_number,
                'amount' => (float) ($order->amount ?? 0),
                'status' => (string) ($order->status_code ?? $order->status ?? ''),
                'date' => optional($order->created_at)->format('Y-m-d H:i'),
                'url' => route('boshqaruv.seller-orders'),
            ])
            ->values()
            ->all();
    }

    private function orderPayload(Sold $order): array
    {
        $items = collect($order->items ?? [])->map(fn ($item) => $this->orderItemPayload((array) $item))->values();
        $sellerOrders = Schema::hasTable('seller_orders')
            ? SellerOrder::query()
                ->with(['seller:id,shop_name', 'courier:id,first_name,last_name,phone_number'])
                ->where('order_id', $order->id)
                ->latest('id')
                ->get()
                ->map(fn (SellerOrder $sellerOrder) => [
                    'id' => $sellerOrder->id,
                    'seller' => $sellerOrder->seller?->shop_name,
                    'courier' => trim(($sellerOrder->courier?->first_name ?? '').' '.($sellerOrder->courier?->last_name ?? '')) ?: ($sellerOrder->courierName ?? null),
                    'courierPhone' => $sellerOrder->courier?->phone_number,
                    'amount' => (float) ($sellerOrder->amount ?? 0),
                    'deliveryType' => $sellerOrder->delivery_type,
                    'status' => (string) ($sellerOrder->status_code ?? $sellerOrder->status ?? ''),
                    'acceptedAt' => optional($sellerOrder->accepted_at)->format('Y-m-d H:i'),
                    'url' => route('boshqaruv.seller-orders'),
                ])
                ->values()
                ->all()
            : [];
        $address = collect($order->address ?? [])->values();
        $primaryAddress = (array) ($address->first() ?? []);
        $fulfillment = $order->fulfillment;

        return [
            'id' => '#'.$order->id,
            'rawId' => $order->id,
            'customer' => trim(($order->user?->name ?? '').' '.($order->user?->lastname ?? '')) ?: 'Mijoz',
            'user' => $order->user ? [
                'id' => $order->user->id,
                'name' => trim(($order->user->name ?? '').' '.($order->user->lastname ?? '')),
                'phone' => $order->user->phone_number,
                'email' => $order->user->email,
                'url' => route('admin.users.show', $order->user),
            ] : null,
            'items' => $items->count(),
            'itemsList' => $items->all(),
            'total' => (float) ($order->amount ?? 0),
            'subtotal' => (float) $items->sum(fn ($item) => ((float) ($item['price'] ?? 0)) * (int) ($item['quantity'] ?? 1)),
            'deliveryPrice' => (float) ($order->deliveryPrice ?? 0),
            'discountAmount' => (float) ($order->discountAmount ?? 0),
            'cashbackAmount' => (float) ($order->cashbackAmount ?? 0),
            'giftCertAmount' => (float) ($order->giftCertAmount ?? 0),
            'packagingPrice' => (float) ($order->packaging_price ?? 0),
            'status' => (string) ($order->status_code ?? $order->status ?? 'pending'),
            'legacyStatus' => (string) ($order->status ?? ''),
            'date' => optional($order->created_at)->format('Y-m-d H:i'),
            'completedAt' => optional($order->completed_at)->format('Y-m-d H:i'),
            'payment' => (string) ($order->paymentStatus ?? $order->payment_status_code ?? '—'),
            'paymentStatus' => (string) ($order->payment_status_code ?? $order->paymentStatus ?? '—'),
            'deliveryType' => $order->deliveryType,
            'orderKind' => $order->order_kind,
            'postalReturnStatus' => $order->postal_return_status,
            'postalReturnFee' => (float) ($order->postal_return_fee ?? 0),
            'postalReturnNote' => $order->postal_return_note,
            'address' => $primaryAddress,
            'addresses' => $address->all(),
            'isInstore' => (bool) ($order->is_instore ?? false),
            'withPackaging' => (bool) ($order->with_packaging ?? false),
            'isGiftToOther' => (bool) ($order->is_gift_to_other ?? false),
            'recipient' => [
                'name' => $order->recipient_name,
                'phone' => $order->recipient_phone,
                'region' => $order->recipient_region,
                'address' => $order->recipient_address,
            ],
            'buyerWish' => $order->buyerWish,
            'promocode' => $order->promocode,
            'courierName' => $order->courierName,
            'fulfillment' => $fulfillment ? [
                'mode' => $fulfillment->fulfillment_mode,
                'status' => $fulfillment->status_code,
                'hub' => $fulfillment->hub?->name ?? $fulfillment->hub?->code,
                'firstMile' => $fulfillment->first_mile_mode,
                'lastMile' => $fulfillment->last_mile_mode,
                'isCod' => (bool) $fulfillment->is_cod,
                'cashCollectAmount' => (float) ($fulfillment->cash_collect_amount ?? 0),
                'tracking' => $fulfillment->postal_tracking_number,
                'labelCode' => $fulfillment->label_code,
            ] : null,
            'sellerOrders' => $sellerOrders,
            'showUrl' => route('admin.orders.show', $order),
            'labelUrl' => route('admin.orders.print.label', $order),
            'receiptUrl' => route('admin.orders.print.receipt', $order),
            'statusUrl' => route('boshqaruv.orders.status', $order),
            'cancelUrl' => route('boshqaruv.orders.cancel', $order),
            'switchModeUrl' => route('admin.orders.switch-mode', $order),
            'rerouteHubUrl' => route('admin.orders.reroute-hub', $order),
            'postalReturnUrl' => route('admin.orders.postal-return', $order),
        ];
    }

    private function orderItemPayload(array $item): array
    {
        $type = $item['type'] ?? 'book';
        $productId = (int) ($item['item_id'] ?? $item['product_id'] ?? 0);
        $product = match ($type) {
            'stationery' => $productId ? Stationery::find($productId) : null,
            'gift' => $productId ? Gifts::find($productId) : null,
            default => $productId ? Books::with('seller:id,shop_name')->find($productId) : null,
        };
        $sellerId = (int) ($item['seller_id'] ?? 0);
        $seller = $sellerId > 0 ? Seller::select('id', 'shop_name')->find($sellerId) : null;
        $quantity = (int) ($item['count_item'] ?? $item['count'] ?? $item['quantity'] ?? 1);
        $price = (float) ($item['item_price'] ?? $item['price'] ?? 0);

        return [
            'id' => $productId,
            'type' => $type,
            'typeLabel' => match ($type) {
                'stationery' => 'Kanselyariya',
                'gift' => "Sovg'a",
                default => 'Kitob',
            },
            'name' => $product?->name ?? $product?->title ?? ($item['name'] ?? 'Mahsulot'),
            'quantity' => $quantity,
            'price' => $price,
            'total' => $price * $quantity,
            'seller' => $seller?->shop_name ?? $product?->seller?->shop_name ?? null,
            'image' => $this->productImageUrl($product),
        ];
    }

    private function productImageUrl($product): ?string
    {
        if (! $product) {
            return null;
        }

        $image = $product->first_image ?? collect($product->images ?? [])->first();

        return is_string($image) && $image !== ''
            ? ProductImageUrls::originalUrl($image)
            : null;
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

        return asset('storage/'.ltrim($value, '/'));
    }

    private function orderStatusLabel(string $status): string
    {
        return match ($status) {
            'pending', 'A' => 'Yangi',
            'payment_pending' => "To'lov jarayonida",
            'packing', 'P', 'B' => 'Qadoqlanmoqda',
            'in_delivery', 'D' => "Yo'lda",
            'delivered', 'C' => 'Yetib bordi',
            'customer_received' => 'Mijoz qabul qildi',
            'cancelled', 'F' => 'Bekor qilindi',
            'returned' => 'Qaytgan',
            default => $status !== '' ? $status : 'Noma\'lum',
        };
    }

    private function sellerStatusLabel(string $status): string
    {
        return match ($status) {
            'payment_pending' => "To'lov jarayonida",
            'new' => 'Yangi',
            'accepted' => "Do'kon qabul qildi",
            'handed_to_courier' => 'Kuryerga berildi',
            'cancelled' => 'Bekor qilindi',
            default => $status !== '' ? $status : 'Noma\'lum',
        };
    }

    private function dateTime(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        try {
            return \Illuminate\Support\Carbon::parse($value)->format('Y-m-d H:i');
        } catch (\Throwable) {
            return is_scalar($value) ? (string) $value : null;
        }
    }

    private function tableCount(string $table): int
    {
        if (! Schema::hasTable($table)) {
            return 0;
        }

        return (int) DB::table($table)->count();
    }

    private function legacyUrl(string $component): ?string
    {
        return [
            'Products' => route('admin.books.index'),
            'Books' => route('admin.books.index'),
            'BookCategories' => route('admin.book-categories.index'),
            'Stationeries' => route('admin.stationery.index'),
            'stationery-categories' => route('admin.stationery-categories.index'),
            'Authors' => route('admin.authors.index'),
            'Publishers' => route('admin.publishers.index'),
            'Parser' => route('admin.parsers.index'),
            'Users' => route('admin.users.index'),
            'Orders' => route('admin.orders.index'),
            'SellerOrders' => request()->is('boshqaruv/sellers*')
                ? route('boshqaruv.sellers')
                : route('boshqaruv.seller-orders'),
            'CourierOrders' => request()->is('boshqaruv/couriers*')
                ? route('admin.couriers.index')
                : route('admin.courier-orders.index'),
            'Hubs' => route('admin.hubs.index'),
            'Transaksiyalar' => route('admin.transactions.index'),
            'LogistikaPage' => route('admin.logistics.index'),
            'Reklamalar' => route('admin.ads.index'),
            'Promokodlar' => route('admin.promocodes.index'),
            'Blogerlar' => route('admin.bloggers.index'),
            'GiftSertifikatlar' => route('admin.gift-certificates.index'),
            'MarketNewsPage' => route('admin.news.index'),
            'ReelsPage' => route('admin.reels.index'),
            'BookClub' => route('admin.book-club.index'),
            'Tickets' => route('admin.support.index'),
            'Shikoyatlar' => route('admin.complaints.index'),
            'ChatKuzatuv' => route('admin.chats.index'),
            'PushNotifications' => route('admin.push.index'),
            'Vakansiyalar' => route('admin.jobs.index'),
            'KaryeraArizalari' => route('admin.job-applications.index'),
            'Adminlar' => route('admin.admins.index'),
            'MysteryBoxPage' => route('admin.mystery-box.index'),
            'Sovgalar' => route('admin.gifts.index'),
            'Siyosatlar' => route('admin.policies.index'),
            'ApiClients' => route('admin.api-clients.index'),
            'SearchHistory' => route('admin.search-history.index'),
            'Settings' => route('boshqaruv.settings'),
        ][$component] ?? null;
    }
}
