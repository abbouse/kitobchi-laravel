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

Route::get('/developers/api/{page?}', ApiDocsController::class)->name('developers.api-docs');

Route::get('/', function () {
    $featuredBooks = \App\Models\Books::where('is_approved', 1)
        ->where('is_hidden', 0)
        ->where('status', 1)
        ->orderByDesc('totalSales')
        ->take(20)
        ->get();

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

    $landingUgcReviews = Cache::remember('welcome_ugc_reviews', 600, function () {
        return \App\Models\BookClub::with('user')
            ->where('is_deleted', false)
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

    return view('welcome', compact(
        'featuredBooks',
        'landingPartnerStoresCount',
        'landingSalesCount',
        'landingCustomersCount',
        'landingUgcReviews',
    ));
})->name('welcome');

Route::get('/share/product/{id}', function (int $id) {
    return view('share.redirect', [
        'type' => 'product',
        'value' => $id,
        'appScheme' => "kitobchi://share/product/{$id}",
        'title' => 'Kitobchi — Mahsulot',
        'description' => 'Kitobchi ilovasida bu mahsulotni ko\'ring',
    ]);
})->where('id', '[0-9]+');

Route::get('/art/{artikul}', function (string $artikul) {
    return view('share.redirect', [
        'type' => 'artikul',
        'value' => $artikul,
        'appScheme' => "kitobchi://art/{$artikul}",
        'title' => 'Kitobchi — Mahsulot',
        'description' => 'Kitobchi ilovasida bu mahsulotni ko\'ring',
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
