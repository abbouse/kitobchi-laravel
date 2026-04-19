<?php

use App\Http\Controllers\CareersController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\TelegramWebhookController;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

Route::get('/locale/{locale}', [LocaleController::class, 'switch'])->name('locale.switch');

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
            ->whereNotNull('kangaroo_post_star')
            ->whereIn('kangaroo_post_ugc_status', ['auto_scored', 'admin_scored'])
            ->whereNotNull('text')
            ->where('text', '!=', '')
            ->orderByDesc('kangaroo_post_star')
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
Route::get('/demo', function () {
    return view('demo');
})->name('demo');

// Payme checkout redirect
Route::get('/payment/order/{order_id}', [PaymentController::class, 'payWithPayme']);
Route::get('/payment/gift-cert/{cert_id}', [PaymentController::class, 'payGiftCert']);
Route::get('/payment/mystery-box/{sub_id}', [PaymentController::class, 'payMysteryBox']);

// Success callback (deep link)
Route::get('/payment/success/order/{id}', [PaymentController::class, 'successOrder']);
Route::get('/payment/success/gift-cert/{id}', [PaymentController::class, 'successGiftCert']);
Route::get('/payment/success/mystery-box/{id}', [PaymentController::class, 'successMysteryBox']);

Route::get('/api/v1/kitobchi/payment/{order_id}', [PaymentController::class, 'payWithPayme'])->name('payme.redirect');
Route::get('/payment/success/{order_id}', [PaymentController::class, 'redirectToApp'])->name('success.redirect');
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

require __DIR__.'/panel.php';
