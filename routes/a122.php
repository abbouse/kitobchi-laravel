<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\A122\AuthController;
use App\Http\Controllers\A122\DashboardController;
use App\Http\Controllers\A122\UserController;
use App\Http\Controllers\A122\BookController;
use App\Http\Controllers\A122\OrderController;
use App\Http\Controllers\A122\StationeryController;
use App\Http\Controllers\A122\BookCategoryController;
use App\Http\Controllers\A122\StationeryCategoryController;
use App\Http\Controllers\A122\ReelController;
use App\Http\Controllers\A122\MarketNewsController;
use App\Http\Controllers\A122\SellerController;
use App\Http\Controllers\A122\SellerOrderController;
use App\Http\Controllers\A122\CourierController;
use App\Http\Controllers\A122\CourierOrderController;
use App\Http\Controllers\A122\TransactionController;
use App\Http\Controllers\A122\PromocodeController;
use App\Http\Controllers\A122\SellerAdController;
use App\Http\Controllers\A122\GiftCertificateController;
use App\Http\Controllers\A122\MysteryBoxController;
use App\Http\Controllers\A122\BookClubController;
use App\Http\Controllers\A122\ChatController;
use App\Http\Controllers\A122\ComplaintController;
use App\Http\Controllers\A122\SupportController;
use App\Http\Controllers\A122\PushNotificationController;
use App\Http\Controllers\A122\PolicyController;
use App\Http\Controllers\A122\VacancyController;
use App\Http\Controllers\A122\CareerApplicationController;
use App\Http\Controllers\A122\AdminController;
use App\Http\Controllers\A122\ApiClientController;
use App\Http\Controllers\A122\SettingsController;

Route::prefix('a122')->name('admin.')->group(function () {

    // ── Auth ───────────────────────────────────────────────────────
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
    Route::post('/theme', function (\Illuminate\Http\Request $req) {
        $theme = $req->input('theme', 'dark');
        session(['theme' => in_array($theme, ['light', 'dark'], true) ? $theme : 'dark']);

        return back();
    })->name('theme');

    Route::middleware('auth.panel')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('/profile', [AuthController::class, 'profile'])->name('profile');
        Route::post('/profile', [AuthController::class, 'updateProfile'])->name('profile.update');

        // ── Dashboard ──────────────────────────────────────────────────
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard/live', [DashboardController::class, 'liveMonitor'])->name('dashboard.live');
        Route::get('/dashboard/live/data', [DashboardController::class, 'liveMonitorData'])->name('dashboard.live.data');

    // ── Users ──────────────────────────────────────────────────────
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/',              [UserController::class, 'index'])->name('index');
        Route::get('/create',        [UserController::class, 'create'])->name('create');
        Route::post('/',             [UserController::class, 'store'])->name('store');
        Route::get('/{user}',        [UserController::class, 'show'])->name('show');
        Route::get('/{user}/edit',   [UserController::class, 'edit'])->name('edit');
        Route::put('/{user}',        [UserController::class, 'update'])->name('update');
        Route::post('/{user}/block', [UserController::class, 'block'])->name('block');
        Route::post('/{user}/unblock', [UserController::class, 'unblock'])->name('unblock');
        Route::patch('/{user}/verify', [UserController::class, 'toggleVerify'])->name('verify');
        Route::patch('/{user}/premium', [UserController::class, 'togglePremium'])->name('premium');
        Route::delete('/{user}',     [UserController::class, 'destroy'])->name('destroy');
    });

    // ── Books ──────────────────────────────────────────────────────
    Route::prefix('books')->name('books.')->group(function () {
        Route::get('/',                      [BookController::class, 'index'])->name('index');
        Route::get('/create',                [BookController::class, 'create'])->name('create');
        Route::post('/',                     [BookController::class, 'store'])->name('store');
        Route::get('/{book}',                [BookController::class, 'show'])->name('show');
        Route::get('/{book}/edit',           [BookController::class, 'edit'])->name('edit');
        Route::put('/{book}',                [BookController::class, 'update'])->name('update');
        Route::patch('/{book}/moderate',     [BookController::class, 'moderate'])->name('moderate');
    });

    // ── Stationery ─────────────────────────────────────────────────
    Route::prefix('stationery')->name('stationery.')->group(function () {
        Route::get('/',            [StationeryController::class, 'index'])->name('index');
        Route::get('/create',      [StationeryController::class, 'create'])->name('create');
        Route::post('/',           [StationeryController::class, 'store'])->name('store');
        Route::get('/{id}',        [StationeryController::class, 'show'])->name('show');
        Route::get('/{id}/edit',   [StationeryController::class, 'edit'])->name('edit');
        Route::put('/{id}',        [StationeryController::class, 'update'])->name('update');
        Route::patch('/{id}/moderate', [StationeryController::class, 'moderate'])->name('moderate');
    });

    // ── Book Categories ────────────────────────────────────────────
    Route::prefix('book-categories')->name('book-categories.')->group(function () {
        Route::get('/',                          [BookCategoryController::class, 'index'])->name('index');
        Route::get('/create',                    [BookCategoryController::class, 'create'])->name('create');
        Route::post('/',                         [BookCategoryController::class, 'store'])->name('store');
        Route::get('/{bookCategory}/edit',       [BookCategoryController::class, 'edit'])->name('edit');
        Route::put('/{bookCategory}',            [BookCategoryController::class, 'update'])->name('update');
        Route::patch('/{bookCategory}/toggle',   [BookCategoryController::class, 'toggle'])->name('toggle');
        Route::delete('/{bookCategory}',         [BookCategoryController::class, 'destroy'])->name('destroy');
    });

    // ── Stationery Categories ──────────────────────────────────────
    Route::prefix('stationery-categories')->name('stationery-categories.')->group(function () {
        Route::get('/',                                  [StationeryCategoryController::class, 'index'])->name('index');
        Route::get('/create',                            [StationeryCategoryController::class, 'create'])->name('create');
        Route::post('/',                                 [StationeryCategoryController::class, 'store'])->name('store');
        Route::get('/{stationeryCategory}/edit',         [StationeryCategoryController::class, 'edit'])->name('edit');
        Route::put('/{stationeryCategory}',              [StationeryCategoryController::class, 'update'])->name('update');
        Route::patch('/{stationeryCategory}/toggle',     [StationeryCategoryController::class, 'toggle'])->name('toggle');
        Route::delete('/{stationeryCategory}',           [StationeryCategoryController::class, 'destroy'])->name('destroy');
    });

    // ── Reels ──────────────────────────────────────────────────────
    Route::prefix('reels')->name('reels.')->group(function () {
        Route::get('/',                          [ReelController::class, 'index'])->name('index');
        Route::get('/create',                    [ReelController::class, 'create'])->name('create');
        Route::post('/',                         [ReelController::class, 'store'])->name('store');
        Route::get('/{reel}',                    [ReelController::class, 'show'])->name('show');
        Route::get('/{reel}/edit',               [ReelController::class, 'edit'])->name('edit');
        Route::put('/{reel}',                    [ReelController::class, 'update'])->name('update');
        Route::delete('/{reel}',                 [ReelController::class, 'destroy'])->name('destroy');
        Route::post('/{reel}/items',             [ReelController::class, 'storeItem'])->name('items.store');
        Route::delete('/{reel}/items/{item}',    [ReelController::class, 'destroyItem'])->name('items.destroy');
        Route::post('/{reel}/reorder',           [ReelController::class, 'reorderItems'])->name('items.reorder');
    });

    // ── Market News ────────────────────────────────────────────────
    Route::prefix('news')->name('news.')->group(function () {
        Route::get('/',              [MarketNewsController::class, 'index'])->name('index');
        Route::get('/create',        [MarketNewsController::class, 'create'])->name('create');
        Route::post('/',             [MarketNewsController::class, 'store'])->name('store');
        Route::get('/{news}',        [MarketNewsController::class, 'show'])->name('show');
        Route::get('/{news}/edit',   [MarketNewsController::class, 'edit'])->name('edit');
        Route::put('/{news}',        [MarketNewsController::class, 'update'])->name('update');
        Route::patch('/{news}/toggle', [MarketNewsController::class, 'toggle'])->name('toggle');
        Route::delete('/{news}',     [MarketNewsController::class, 'destroy'])->name('destroy');
    });

    // ── Orders ─────────────────────────────────────────────────────
    Route::prefix('orders')->name('orders.')->group(function () {
        Route::get('/',                  [OrderController::class, 'index'])->name('index');
        Route::get('/{order}',           [OrderController::class, 'show'])->name('show');
        Route::patch('/{order}/status',  [OrderController::class, 'updateStatus'])->name('status');
        Route::post('/{order}/cancel',   [OrderController::class, 'adminCancel'])->name('cancel');
        Route::get('/export',            [OrderController::class, 'export'])->name('export');
    });

    // ── Seller Orders ──────────────────────────────────────────────
    Route::prefix('seller-orders')->name('seller-orders.')->group(function () {
        Route::get('/',                          [SellerOrderController::class, 'index'])->name('index');
        Route::get('/{sellerOrder}',             [SellerOrderController::class, 'show'])->name('show');
        Route::patch('/{sellerOrder}/status',    [SellerOrderController::class, 'updateStatus'])->name('status');
    });

    // ── Courier Orders ─────────────────────────────────────────────
    Route::prefix('courier-orders')->name('courier-orders.')->group(function () {
        Route::get('/',              [CourierOrderController::class, 'index'])->name('index');
        Route::get('/{courierOrder}', [CourierOrderController::class, 'show'])->name('show');
        Route::patch('/{courierOrder}/status', [CourierOrderController::class, 'updateStatus'])->name('status');
    });

    // ── Sellers ────────────────────────────────────────────────────
    Route::prefix('sellers')->name('sellers.')->group(function () {
        Route::get('/',              [SellerController::class, 'index'])->name('index');
        Route::get('/{seller}',      [SellerController::class, 'show'])->name('show');
        Route::get('/{seller}/edit', [SellerController::class, 'edit'])->name('edit');
        Route::put('/{seller}',      [SellerController::class, 'update'])->name('update');
        Route::post('/{seller}/reset-password', [SellerController::class, 'resetPassword'])->name('reset-password');
        Route::post('/{seller}/warn', [SellerController::class, 'warn'])->name('warn');
        Route::patch('/{seller}/unblock', [SellerController::class, 'unblock'])->name('unblock');
        Route::patch('/{seller}/approve', [SellerController::class, 'approve'])->name('approve');
        Route::patch('/{seller}/reject',  [SellerController::class, 'reject'])->name('reject');

        // Do'kon QR — rotate
        Route::post('/{seller}/qr/rotate', [SellerController::class, 'rotateQr'])->name('qr.rotate');

        // Shartnoma va hujjatlar
        Route::patch('/{seller}/contract/extend',  [SellerController::class, 'extendContract'])->name('contract.extend');
        Route::post('/{seller}/documents',          [SellerController::class, 'uploadDocument'])->name('documents.store');
        Route::delete('/{seller}/documents/{document}', [SellerController::class, 'deleteDocument'])->name('documents.destroy');
    });

    // ── Transactions ───────────────────────────────────────────────
    Route::prefix('transactions')->name('transactions.')->group(function () {
        Route::get('/',                          [TransactionController::class, 'index'])->name('index');
        Route::get('/{transaction}',             [TransactionController::class, 'show'])->name('show');
        Route::patch('/{transaction}/approve',   [TransactionController::class, 'approve'])->name('approve');
        Route::patch('/{transaction}/reject',    [TransactionController::class, 'reject'])->name('reject');
    });

    // ── Couriers ───────────────────────────────────────────────────
    Route::prefix('couriers')->name('couriers.')->group(function () {
        Route::get('/',              [CourierController::class, 'index'])->name('index');
        Route::get('/create',        [CourierController::class, 'create'])->name('create');
        Route::post('/',             [CourierController::class, 'store'])->name('store');
        Route::get('/{courier}',     [CourierController::class, 'show'])->name('show');
        Route::get('/{courier}/edit', [CourierController::class, 'edit'])->name('edit');
        Route::put('/{courier}',     [CourierController::class, 'update'])->name('update');
        Route::post('/{courier}/reset-password', [CourierController::class, 'resetPassword'])->name('reset-password');
        Route::patch('/{courier}/approve', [CourierController::class, 'approve'])->name('approve');
        Route::patch('/{courier}/reject',  [CourierController::class, 'reject'])->name('reject');

        // ── Ogohlantirish va blok ──────────────────────────────────
        Route::post('/{courier}/warn',     [CourierController::class, 'warn'])->name('warn');
        Route::patch('/{courier}/unblock', [CourierController::class, 'unblock'])->name('unblock');

        Route::delete('/{courier}',  [CourierController::class, 'destroy'])->name('destroy');

        // ── Hujjatlar ──────────────────────────────────────────────
        Route::post('/{courier}/documents',                [CourierController::class, 'uploadDocument'])->name('documents.store');
        Route::delete('/{courier}/documents/{document}',   [CourierController::class, 'deleteDocument'])->name('documents.destroy');
    });

    // ── Promocodes ─────────────────────────────────────────────────
    Route::prefix('promocodes')->name('promocodes.')->group(function () {
        Route::get('/',              [PromocodeController::class, 'index'])->name('index');
        Route::get('/create',        [PromocodeController::class, 'create'])->name('create');
        Route::post('/',             [PromocodeController::class, 'store'])->name('store');
        Route::get('/generate',      [PromocodeController::class, 'generate'])->name('generate');
        Route::get('/{promocode}',   [PromocodeController::class, 'show'])->name('show');
        Route::get('/{promocode}/edit', [PromocodeController::class, 'edit'])->name('edit');
        Route::put('/{promocode}',   [PromocodeController::class, 'update'])->name('update');
        Route::delete('/{promocode}', [PromocodeController::class, 'destroy'])->name('destroy');
    });

    // ── Ads ────────────────────────────────────────────────────────
    Route::prefix('ads')->name('ads.')->group(function () {
        Route::get('/',           [SellerAdController::class, 'index'])->name('index');
        Route::get('/{ad}',       [SellerAdController::class, 'show'])->name('show');
        Route::patch('/{ad}/moderate', [SellerAdController::class, 'moderate'])->name('moderate');
        Route::delete('/{ad}',    [SellerAdController::class, 'destroy'])->name('destroy');
    });

    // ── Gifts placeholder (Gifts model — separate from GiftCertificate) ──
    Route::prefix('gifts')->name('gifts.')->group(function () {
        Route::view('/', 'a122.placeholder', ['title' => 'Sovg\'alar'])->name('index');
    });

    // ── Gift Certificates ──────────────────────────────────────────
    Route::prefix('gift-certificates')->name('gift-certificates.')->group(function () {
        Route::get('/',                          [GiftCertificateController::class, 'index'])->name('index');
        Route::get('/{giftCertificate}',         [GiftCertificateController::class, 'show'])->name('show');
        Route::patch('/{giftCertificate}/status',[GiftCertificateController::class, 'updateStatus'])->name('status');
        Route::post('/{giftCertificate}/cancel', [GiftCertificateController::class, 'cancel'])->name('cancel');
    });

    // ── Mystery Box ────────────────────────────────────────────────
    Route::prefix('mystery-box')->name('mystery-box.')->group(function () {
        Route::get('/',                                  [MysteryBoxController::class, 'index'])->name('index');
        Route::get('/books/search',                      [MysteryBoxController::class, 'searchBooks'])->name('books.search');
        Route::get('/subscriptions',                     [MysteryBoxController::class, 'subscriptions'])->name('subscriptions');
        Route::get('/plans',                             [MysteryBoxController::class, 'plans'])->name('plans');
        Route::post('/plans',                            [MysteryBoxController::class, 'storePlan'])->name('plans.store');
        Route::put('/plans/{plan}',                      [MysteryBoxController::class, 'updatePlan'])->name('plans.update');
        Route::delete('/plans/{plan}',                   [MysteryBoxController::class, 'destroyPlan'])->name('plans.destroy');
        Route::get('/{subscription}',                    [MysteryBoxController::class, 'show'])->name('show');
        Route::get('/subscription/{subscription}',       [MysteryBoxController::class, 'subscription'])->name('subscription');
        Route::patch('/{subscription}/pause',            [MysteryBoxController::class, 'pauseSubscription'])->name('pause');
        Route::patch('/{subscription}/resume',           [MysteryBoxController::class, 'resumeSubscription'])->name('resume');
        Route::patch('/{subscription}/cancel',           [MysteryBoxController::class, 'cancelSubscription'])->name('cancel');
        Route::patch('/deliveries/{delivery}/prepare',   [MysteryBoxController::class, 'prepareDelivery'])->name('prepare');
        Route::patch('/deliveries/{delivery}/ship',      [MysteryBoxController::class, 'shipDelivery'])->name('ship');
        Route::patch('/deliveries/{delivery}/deliver',   [MysteryBoxController::class, 'deliverDelivery'])->name('deliver');
    });

    // ── Book Club ──────────────────────────────────────────────────
    Route::prefix('book-club')->name('book-club.')->group(function () {
        Route::get('/',                       [BookClubController::class, 'index'])->name('index');
        Route::get('/moderation',             [BookClubController::class, 'moderationQueue'])->name('moderation');
        Route::get('/moderation-queue',       [BookClubController::class, 'moderationQueue'])->name('moderation-queue');
        Route::post('/{bookClub}/post-ugc-score', [BookClubController::class, 'savePostUgcScore'])->name('post-ugc-score');
        Route::post('/comments/{comment}/ugc-score', [BookClubController::class, 'saveCommentUgcScore'])->name('comment.ugc-score');
        Route::delete('/images/{image}',      [BookClubController::class, 'deleteImage'])->name('image.delete');
        Route::patch('/comments/{comment}',   [BookClubController::class, 'updateComment'])->name('comment.update');
        Route::delete('/comments/{comment}',  [BookClubController::class, 'deleteComment'])->name('comment.delete');
        Route::get('/{bookClub}/edit',        [BookClubController::class, 'edit'])->name('edit');
        Route::put('/{bookClub}',             [BookClubController::class, 'update'])->name('update');
        Route::post('/{bookClub}/warn',       [BookClubController::class, 'warn'])->name('warn');
        Route::get('/{bookClub}',             [BookClubController::class, 'show'])->name('show');
        Route::delete('/{bookClub}',          [BookClubController::class, 'destroy'])->name('destroy');
    });

    // ── UGC (Book Club alias) ──────────────────────────────────────
    Route::prefix('ugc')->name('ugc.')->group(function () {
        Route::get('/', [BookClubController::class, 'moderationQueue'])->name('index');
    });

    // ── Chats ──────────────────────────────────────────────────────
    Route::prefix('chats')->name('chats.')->group(function () {
        Route::get('/',              [ChatController::class, 'index'])->name('index');
        Route::get('/ai/{userId}',   [ChatController::class, 'showAi'])->name('show-ai');
        Route::get('/{conversation}', [ChatController::class, 'show'])->name('show');
    });

    // ── Complaints ─────────────────────────────────────────────────
    Route::prefix('complaints')->name('complaints.')->group(function () {
        Route::get('/',                          [ComplaintController::class, 'index'])->name('index');
        Route::get('/{complaint}',               [ComplaintController::class, 'show'])->name('show');
        Route::patch('/{complaint}/status',      [ComplaintController::class, 'updateStatus'])->name('status');
        Route::delete('/{complaint}',            [ComplaintController::class, 'destroy'])->name('destroy');
    });

    // ── Support ────────────────────────────────────────────────────
    Route::prefix('support')->name('support.')->group(function () {
        Route::get('/',          [SupportController::class, 'index'])->name('index');
        Route::get('/{ticket}',  [SupportController::class, 'show'])->name('show');
        Route::patch('/{ticket}/assign', [SupportController::class, 'assign'])->name('assign');
        Route::patch('/{ticket}/close', [SupportController::class, 'close'])->name('close');
    });

    // ── Push Notifications ─────────────────────────────────────────
    Route::prefix('push')->name('push.')->group(function () {
        Route::get('/',                  [PushNotificationController::class, 'index'])->name('index');
        Route::get('/create',            [PushNotificationController::class, 'create'])->name('create');
        Route::post('/',                 [PushNotificationController::class, 'store'])->name('store');
        Route::delete('/{notification}', [PushNotificationController::class, 'destroy'])->name('destroy');
    });

    // ── Policies ───────────────────────────────────────────────────
    Route::prefix('policies')->name('policies.')->group(function () {
        Route::get('/',              [PolicyController::class, 'index'])->name('index');
        Route::get('/create',        [PolicyController::class, 'create'])->name('create');
        Route::post('/',             [PolicyController::class, 'store'])->name('store');
        Route::get('/{policy}/edit', [PolicyController::class, 'edit'])->name('edit');
        Route::put('/{policy}',      [PolicyController::class, 'update'])->name('update');
        Route::patch('/{policy}/toggle', [PolicyController::class, 'toggle'])->name('toggle');
        Route::delete('/{policy}',   [PolicyController::class, 'destroy'])->name('destroy');
    });

    // ── Jobs (Vacancies) ───────────────────────────────────────────
    Route::prefix('jobs')->name('jobs.')->group(function () {
        Route::get('/',              [VacancyController::class, 'index'])->name('index');
        Route::get('/create',        [VacancyController::class, 'create'])->name('create');
        Route::post('/',             [VacancyController::class, 'store'])->name('store');
        Route::get('/{vacancy}/edit', [VacancyController::class, 'edit'])->name('edit');
        Route::put('/{vacancy}',     [VacancyController::class, 'update'])->name('update');
        Route::patch('/{vacancy}/toggle', [VacancyController::class, 'toggle'])->name('toggle');
        Route::delete('/{vacancy}',  [VacancyController::class, 'destroy'])->name('destroy');
    });

    // ── Job Applications ────────────────────────────────────────────
    Route::prefix('job-applications')->name('job-applications.')->group(function () {
        Route::get('/',                          [CareerApplicationController::class, 'index'])->name('index');
        Route::get('/{application}',             [CareerApplicationController::class, 'show'])->name('show');
        Route::patch('/{application}/status',    [CareerApplicationController::class, 'updateStatus'])->name('status');
        Route::post('/{application}/reply',      [CareerApplicationController::class, 'sendReply'])->name('reply');
        Route::get('/{application}/cv',          [CareerApplicationController::class, 'downloadCv'])->name('cv');
    });

    // ── Admins ─────────────────────────────────────────────────────
    Route::prefix('admins')->name('admins.')->group(function () {
        Route::get('/',            [AdminController::class, 'index'])->name('index');
        Route::get('/create',      [AdminController::class, 'create'])->name('create');
        Route::post('/',           [AdminController::class, 'store'])->name('store');
        Route::get('/{admin}/edit', [AdminController::class, 'edit'])->name('edit');
        Route::put('/{admin}',     [AdminController::class, 'update'])->name('update');
        Route::patch('/{admin}/toggle', [AdminController::class, 'toggle'])->name('toggle');
        Route::delete('/{admin}',  [AdminController::class, 'destroy'])->name('destroy');
    });

    // ── API Clients ────────────────────────────────────────────────
    Route::prefix('api-clients')->name('api-clients.')->group(function () {
        Route::get('/',                      [ApiClientController::class, 'index'])->name('index');
        Route::get('/docs',                  [ApiClientController::class, 'docs'])->name('docs');
        Route::get('/logs',                  [ApiClientController::class, 'logs'])->name('logs');
        Route::get('/logs/export',           [ApiClientController::class, 'exportLogs'])->name('logs.export');
        Route::get('/create',                [ApiClientController::class, 'create'])->name('create');
        Route::post('/',                     [ApiClientController::class, 'store'])->name('store');
        Route::get('/{apiClient}/edit',      [ApiClientController::class, 'edit'])->name('edit');
        Route::put('/{apiClient}',           [ApiClientController::class, 'update'])->name('update');
        Route::patch('/{apiClient}/toggle',  [ApiClientController::class, 'toggle'])->name('toggle');
        Route::patch('/{apiClient}/regenerate', [ApiClientController::class, 'regenerate'])->name('regenerate');
        Route::delete('/{apiClient}',        [ApiClientController::class, 'destroy'])->name('destroy');
    });

        // ── Settings ───────────────────────────────────────────────────
        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('/',                                    [SettingsController::class, 'index'])->name('index');
            Route::put('/versions',                            [SettingsController::class, 'updateVersions'])->name('versions');
            Route::post('/commission',                         [SettingsController::class, 'storeCommission'])->name('commission.store');
            Route::put('/commission/{commissionSetting}',      [SettingsController::class, 'updateCommission'])->name('commission.update');
            Route::delete('/commission/{commissionSetting}',   [SettingsController::class, 'destroyCommission'])->name('commission.destroy');
            Route::post('/cashback',                           [SettingsController::class, 'storeCashback'])->name('cashback.store');
            Route::put('/cashback/{cashbackSetting}',          [SettingsController::class, 'updateCashback'])->name('cashback.update');
            Route::delete('/cashback/{cashbackSetting}',       [SettingsController::class, 'destroyCashback'])->name('cashback.destroy');
            Route::post('/delivery',                           [SettingsController::class, 'storeDelivery'])->name('delivery.store');
            Route::put('/delivery/{deliveryService}',          [SettingsController::class, 'updateDelivery'])->name('delivery.update');
            Route::delete('/delivery/{deliveryService}',       [SettingsController::class, 'destroyDelivery'])->name('delivery.destroy');
            Route::put('/contacts',                            [SettingsController::class, 'updateContacts'])->name('contacts');
            Route::put('/app-flags',                           [SettingsController::class, 'updateAppFlags'])->name('app-flags');
            Route::put('/telegram',                            [SettingsController::class, 'updateTelegram'])->name('telegram');
        });
    });
});
