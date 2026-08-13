<?php

use App\Http\Controllers\CareersController;
use App\Http\Controllers\Developers\ApiDocsController;
use App\Http\Controllers\HubDesk\AuthController as HubDeskAuthController;
use App\Http\Controllers\HubDesk\DeskController as HubDeskDeskController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\TelegramWebhookController;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

require __DIR__.'/boshqaruv.php';

Route::get('/locale/{locale}', [LocaleController::class, 'switch'])->name('locale.switch');

Route::get('/developers/api/openapi.json', [ApiDocsController::class, 'openapi'])->name('developers.api-openapi');
Route::get('/developers/api/{page?}', ApiDocsController::class)->name('developers.api-docs');

Route::get('/', function () {
    try {
        $featuredBooks = \App\Models\Books::where('is_approved', 1)
            ->where('is_hidden', 0)
            ->where('status', 1)
            ->orderByDesc('totalSales')
            ->take(20)
            ->get();
    } catch (\Throwable) {
        $featuredBooks = collect();
    }

    $ttl = 300;
    try {
        $landingPartnerStoresCount = Cache::remember('welcome_stats_partners', $ttl, fn () => (int) \App\Models\Seller::query()
            ->where('status', 'approved')
            ->where('is_hidden', false)
            ->count());
        $landingSalesCount = Cache::remember('welcome_stats_sales', $ttl, fn () => (int) \App\Models\Sold::query()
            ->where('paymentStatus', 2)
            ->count());
        $landingCustomersCount = Cache::remember('welcome_stats_customers', $ttl, fn () => (int) \App\Models\User::query()
            ->whereNull('verifyCode')
            ->count());
    } catch (\Throwable) {
        $landingPartnerStoresCount = 0;
        $landingSalesCount = 0;
        $landingCustomersCount = 0;
    }

    try {
        $landingUgcReviews = Cache::remember('welcome_ugc_reviews', 600, function () {
            return \App\Models\BookClub::with('user')
                ->where('is_deleted', false)
                ->where(function ($query) {
                    $query->whereNull('is_hidden_by_ai')->orWhere('is_hidden_by_ai', false);
                })
                ->whereNotNull('ai_post_score')
                ->where('ai_post_status', 'scored')
                ->whereNotNull('text')
                ->where('text', '!=', '')
                ->orderByDesc('ai_post_score')
                ->take(30)
                ->get()
                ->shuffle()
                ->take(3)
                ->values();
        });
    } catch (\Throwable) {
        $landingUgcReviews = collect();
    }

    return view('welcome', compact(
        'featuredBooks',
        'landingPartnerStoresCount',
        'landingSalesCount',
        'landingCustomersCount',
        'landingUgcReviews',
    ));
})->name('welcome');

// ── Web SEO & Product Catalog Routes ─────────────────────────────────
Route::get('/catalog', [\App\Http\Controllers\Web\ProductCatalogController::class, 'catalog'])->name('web.catalog');
Route::get('/books/{id}-{slug?}', [\App\Http\Controllers\Web\ProductCatalogController::class, 'showBook'])->where('id', '[0-9]+')->name('web.books.show');
Route::get('/stationery/{id}-{slug?}', [\App\Http\Controllers\Web\ProductCatalogController::class, 'showStationery'])->where('id', '[0-9]+')->name('web.stationery.show');
Route::get('/p/{artikul}', [\App\Http\Controllers\Web\ProductCatalogController::class, 'byArtikul'])->where('artikul', '[A-Za-z0-9\-]+')->name('web.by_artikul');
Route::get('/sitemap.xml', [\App\Http\Controllers\Web\ProductCatalogController::class, 'sitemap'])->name('web.sitemap');
Route::get('/google-merchant.xml', [\App\Http\Controllers\Web\ProductCatalogController::class, 'googleMerchantFeed'])->name('web.google_merchant');
Route::get('/robots.txt', [\App\Http\Controllers\Web\ProductCatalogController::class, 'robots'])->name('web.robots');

Route::get('/share/product/{id}', function (int $id) {
    try {
        $book = \App\Models\Books::where('id', $id)->first();
        $stationery = ! $book ? \App\Models\Stationery::where('id', $id)->first() : null;
        $product = $book ?: $stationery;
    } catch (\Throwable $e) {
        $book = null;
        $stationery = null;
        $product = null;
    }

    $title = $product ? $product->name . ($book && $book->author ? " — {$book->author}" : '') : 'Kitobchi — Mahsulot';
    $description = $product && $product->description ? \Illuminate\Support\Str::limit(strip_tags($product->description), 150) : 'Kitobchi ilovasida bu mahsulotni ko\'ring';
    $image = $product && $product->first_image ? asset('storage/' . $product->first_image) : asset('images/logo/logo_blue.png');
    $webUrl = $product ? ($book ? route('web.books.show', $product->id) : route('web.stationery.show', $product->id)) : null;

    return view('share.redirect', [
        'type' => 'product',
        'value' => $id,
        'product' => $product,
        'productType' => $book ? 'book' : ($stationery ? 'stationery' : null),
        'appScheme' => "kitobchi://share/product/{$id}",
        'title' => $title,
        'description' => $description,
        'image' => $image,
        'webUrl' => $webUrl,
    ]);
})->where('id', '[0-9]+');

Route::get('/art/{artikul}', function (string $artikul) {
    try {
        $book = \App\Models\Books::where('artikul', $artikul)->first();
        $stationery = ! $book ? \App\Models\Stationery::where('artikul', $artikul)->first() : null;
        $product = $book ?: $stationery;
    } catch (\Throwable $e) {
        $book = null;
        $stationery = null;
        $product = null;
    }

    $title = $product ? $product->name . ($book && $book->author ? " — {$book->author}" : '') : 'Kitobchi — Mahsulot';
    $description = $product && $product->description ? \Illuminate\Support\Str::limit(strip_tags($product->description), 150) : 'Kitobchi ilovasida bu mahsulotni ko\'ring';
    $image = $product && $product->first_image ? asset('storage/' . $product->first_image) : asset('images/logo/logo_blue.png');
    $webUrl = $product ? ($book ? route('web.books.show', $product->id) : route('web.stationery.show', $product->id)) : null;

    return view('share.redirect', [
        'type' => 'artikul',
        'value' => $artikul,
        'product' => $product,
        'productType' => $book ? 'book' : ($stationery ? 'stationery' : null),
        'appScheme' => "kitobchi://art/{$artikul}",
        'title' => $title,
        'description' => $description,
        'image' => $image,
        'webUrl' => $webUrl,
    ]);
})->where('artikul', '[A-Za-z0-9\-]+');

// ── Cart share ────────────────────────────────────────────────────────
Route::get('/share/cart/{slug}', function (string $slug) {
    return view('share.redirect', [
        'type' => 'cart',
        'value' => $slug,
        'appScheme' => "kitobchi://share/cart/{$slug}",
        'title' => 'Kitobchi — Ulashilgan savat',
        'description' => 'Kitobchi ilovasida bu savatchani ko\'ring',
    ]);
})->where('slug', '[A-Za-z0-9]+');

Route::get('/share/collection/{slug}', function (string $slug) {
    return view('share.redirect', [
        'type' => 'collection',
        'value' => $slug,
        'appScheme' => "kitobchi://share/collection/{$slug}",
        'title' => 'Kitobchi — To\'plam',
        'description' => 'Kitobchi ilovasida bu to\'plamni ko\'ring',
    ]);
})->where('slug', '[A-Za-z0-9\-]+');

Route::get('/shared-cart/{slug}', function (string $slug) {
    return redirect("/share/cart/{$slug}", 301);
})->where('slug', '[A-Za-z0-9]+');

Route::get('/demo', function () {
    return view('demo');
})->name('demo');

Route::post('/telegram/webhook', TelegramWebhookController::class)->withoutMiddleware(['web', \App\Http\Middleware\VerifyCsrfToken::class]);

Route::get('/contact', [\App\Http\Controllers\ContactController::class, 'index'])->name('contact.index');
Route::post('/contact', [\App\Http\Controllers\ContactController::class, 'store'])
    ->middleware('throttle:6,1')
    ->name('contact.store');

Route::get('/careers', [CareersController::class, 'index'])->name('careers.index');
Route::middleware('throttle:12,1')->group(function () {
    Route::post('/careers/vacancy/{vacancy}/apply', [CareersController::class, 'storeVacancy'])->name('careers.apply');
    Route::post('/careers/inquiry', [CareersController::class, 'storeInquiry'])->name('careers.inquiry');
});

Route::group(['prefix' => 'legal'], function () {
    Route::get('/', [LegalController::class, 'index'])->name('legal.index');
    Route::get('/terms', [LegalController::class, 'terms'])->name('legal.terms');
    Route::get('/privacy', [LegalController::class, 'privacy'])->name('legal.privacy');
    Route::get('/subscription', [LegalController::class, 'subscription'])->name('legal.subscription');
    Route::get('/all', [LegalController::class, 'index']);
    Route::get('/{slug}', [LegalController::class, 'show'])->name('legal.policy')
        ->where('slug', '[a-z0-9\-]+');
});

Route::prefix('hub-desk')->name('hubdesk.')->group(function () {
    Route::middleware('guest:hub_web')->group(function () {
        Route::get('/login', [HubDeskAuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [HubDeskAuthController::class, 'login'])->middleware('throttle:auth-panel')->name('login.post');
    });

    Route::middleware('auth.hubdesk')->group(function () {
        Route::post('/logout', [HubDeskAuthController::class, 'logout'])->name('logout');
        Route::get('/', [HubDeskDeskController::class, 'index'])->name('index');
        Route::get('/fulfillments/{fulfillment}', [HubDeskDeskController::class, 'show'])->name('show');
        Route::get('/fulfillments/{fulfillment}/print/label', [HubDeskDeskController::class, 'printLabel'])->name('print.label');
        Route::get('/fulfillments/{fulfillment}/print/receipt', [HubDeskDeskController::class, 'printReceipt'])->name('print.receipt');
    });
});

require __DIR__.'/a122.php';
