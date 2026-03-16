<?php
use App\Http\Controllers\ForgotController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RegisterController;
use App\MoonShine\Pages\ResetPasswordPage;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\Seller\ProductController;
use App\Http\Controllers\LegalController;

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserController;

use App\Http\Controllers\TelegramWebhookController;



Route::get('/', function () {
    return view('welcome');
})->name('welcome');

Route::get('/api/v1/kitobchi/payment/{order_id}', [PaymentController::class, 'payWithPayme'])->name('payme.redirect');
Route::get('/payment/success/{order_id}', [PaymentController::class, 'redirectToApp'])->name('success.redirect');
Route::post('/telegram/webhook', TelegramWebhookController::class)->withoutMiddleware(['web', \App\Http\Middleware\VerifyCsrfToken::class]);

Route::group(['prefix' => 'legal'], function () {
    Route::get('/terms', [LegalController::class, 'terms'])->name('legal.terms');
    Route::get('/privacy', [LegalController::class, 'privacy'])->name('legal.privacy');
    Route::get('/subscription', [LegalController::class, 'subscription'])->name('legal.subscription');
    Route::get('/all', [LegalController::class, 'index'])->name('legal.index');
});

Route::prefix('boshqaruv')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::resource('users', UserController::class)->only([
        'index',
        'show',
        'edit',
        'update',
    ]);
});