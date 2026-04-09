<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\LegalController;

use App\Http\Controllers\TelegramWebhookController;



Route::get('/', function () {
    return view('welcome');
})->name('welcome');

Route::get('/share/product/{id}', function (int $id) {
    return view('share.redirect', [
        'type'       => 'product',
        'value'      => $id,
        'appScheme'  => "kitobchi://share/product/{$id}",
        'title'      => 'Kitobchi — Mahsulot',
        'description'=> 'Kitobchi ilovasida bu mahsulotni ko\'ring',
    ]);
})->where('id', '[0-9]+');
 
// ── Cart share ────────────────────────────────────────────────────────
Route::get('/share/cart/{slug}', function (string $slug) {
    return view('share.redirect', [
        'type'       => 'cart',
        'value'      => $slug,
        'appScheme'  => "kitobchi://share/cart/{$slug}",
        'title'      => 'Kitobchi — Ulashilgan savat',
        'description'=> 'Kitobchi ilovasida bu savatchani ko\'ring',
    ]);
})->where('slug', '[A-Za-z0-9]+');

// Payme checkout redirect
Route::get('/payment/order/{order_id}',      [PaymentController::class, 'payWithPayme']);
Route::get('/payment/gift-cert/{cert_id}',   [PaymentController::class, 'payGiftCert']);
Route::get('/payment/mystery-box/{sub_id}',  [PaymentController::class, 'payMysteryBox']);
 
// Success callback (deep link)
Route::get('/payment/success/order/{id}',       [PaymentController::class, 'successOrder']);
Route::get('/payment/success/gift-cert/{id}',   [PaymentController::class, 'successGiftCert']);
Route::get('/payment/success/mystery-box/{id}', [PaymentController::class, 'successMysteryBox']);

Route::get('/api/v1/kitobchi/payment/{order_id}', [PaymentController::class, 'payWithPayme'])->name('payme.redirect');
Route::get('/payment/success/{order_id}', [PaymentController::class, 'redirectToApp'])->name('success.redirect');
Route::post('/telegram/webhook', TelegramWebhookController::class)->withoutMiddleware(['web', \App\Http\Middleware\VerifyCsrfToken::class]);

Route::group(['prefix' => 'legal'], function () {
    Route::get('/terms', [LegalController::class, 'terms'])->name('legal.terms');
    Route::get('/privacy', [LegalController::class, 'privacy'])->name('legal.privacy');
    Route::get('/subscription', [LegalController::class, 'subscription'])->name('legal.subscription');
    Route::get('/all', [LegalController::class, 'index'])->name('legal.index');
});

require __DIR__.'/panel.php';