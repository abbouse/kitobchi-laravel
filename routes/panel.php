<?php

use App\Http\Controllers\Panel\AdminController;
use App\Http\Controllers\Panel\ApiClientController;
use App\Http\Controllers\Panel\AuthController;
use App\Http\Controllers\Panel\BookCategoryController;
use App\Http\Controllers\Panel\BookClubController;
use App\Http\Controllers\Panel\BookController;
use App\Http\Controllers\Panel\BotTicketController;
use App\Http\Controllers\Panel\CareerApplicationController;
use App\Http\Controllers\Panel\ChatController;
use App\Http\Controllers\Panel\CourierController;
use App\Http\Controllers\Panel\CourierOrderController;
use App\Http\Controllers\Panel\DashboardController;
use App\Http\Controllers\Panel\FcmNotificationController;
use App\Http\Controllers\Panel\GiftCertificateController;
use App\Http\Controllers\Panel\MarketNewsController;
use App\Http\Controllers\Panel\MysteryBoxController;
use App\Http\Controllers\Panel\OrderController;
use App\Http\Controllers\Panel\PolicyController;
use App\Http\Controllers\Panel\PromocodeController;
use App\Http\Controllers\Panel\ReelController;
use App\Http\Controllers\Panel\ReportController;
use App\Http\Controllers\Panel\SellerAdController;
use App\Http\Controllers\Panel\SellerController;
use App\Http\Controllers\Panel\SellerOrderController;
use App\Http\Controllers\Panel\SellerTransactionController;
use App\Http\Controllers\Panel\SettingsController;
use App\Http\Controllers\Panel\StationeryCategoryController;
use App\Http\Controllers\Panel\StationeryController;
use App\Http\Controllers\Panel\UserController;
use App\Http\Controllers\Panel\VacancyController;
use Illuminate\Support\Facades\Route;

Route::prefix('panel')->name('panel.')->group(function () {

    // Auth
    Route::get('login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login'])->name('login.post');

    // Tema — kirish sahifasidan ham ishlaydi (sessiya)
    Route::post('theme', function (\Illuminate\Http\Request $req) {
        $theme = $req->input('theme', 'dark');
        session(['theme' => in_array($theme, ['light', 'dark'], true) ? $theme : 'dark']);

        return back();
    })->name('theme');

    Route::middleware('auth.panel')->group(function () {

        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('profile', [AuthController::class, 'profile'])->name('profile');
        Route::post('profile', [AuthController::class, 'updateProfile'])->name('profile.update');

        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        // Users
        Route::middleware('panel.permission:users')
            ->prefix('users')->name('users.')->group(function () {
                Route::get('/', [UserController::class, 'index'])->name('index');
                Route::get('/create', [UserController::class, 'create'])->name('create');
                Route::post('/', [UserController::class, 'store'])->name('store');
                Route::get('/export', [UserController::class, 'export'])->name('export');
                Route::get('/import', [UserController::class, 'importView'])->name('import');
                Route::post('/import', [UserController::class, 'import'])->name('import.post');
                Route::get('/{user}', [UserController::class, 'show'])->name('show');
                Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit');
                Route::put('/{user}', [UserController::class, 'update'])->name('update');
                Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
                Route::patch('/{user}/premium', [UserController::class, 'togglePremium'])->name('toggle-premium');
                Route::delete('/{user}/cards/{card}', [UserController::class, 'destroyCard'])->name('card.destroy');
            });

        // Books
        Route::middleware('panel.permission:books')
            ->prefix('books')->name('books.')->group(function () {
                Route::get('/', [BookController::class, 'index'])->name('index');
                Route::get('/create', [BookController::class, 'create'])->name('create');
                Route::post('/', [BookController::class, 'store'])->name('store');
                Route::get('/export', [BookController::class, 'export'])->name('export');
                Route::get('/import', [BookController::class, 'importView'])->name('import');
                Route::post('/import', [BookController::class, 'import'])->name('import.post');
                Route::get('/{book}', [BookController::class, 'show'])->name('show');
                Route::get('/{book}/edit', [BookController::class, 'edit'])->name('edit');
                Route::put('/{book}', [BookController::class, 'update'])->name('update');
                Route::patch('/{book}/moderate', [BookController::class, 'moderate'])->name('moderate');
            });

        // Stationery
        Route::middleware('panel.permission:stationery')
            ->prefix('stationery')->name('stationery.')->group(function () {
                Route::get('/', [StationeryController::class, 'index'])->name('index');
                Route::get('/export', [StationeryController::class, 'export'])->name('export');
                Route::get('/{stationery}', [StationeryController::class, 'show'])->name('show');
                Route::get('/{stationery}/edit', [StationeryController::class, 'edit'])->name('edit');
                Route::put('/{stationery}', [StationeryController::class, 'update'])->name('update');
                Route::patch('/{stationery}/moderate', [StationeryController::class, 'moderate'])->name('moderate');
            });

        // Categories
        Route::middleware('panel.permission:settings')->group(function () {
            Route::resource('book-categories', BookCategoryController::class)
                ->parameters(['book-categories' => 'bookCategory'])
                ->names('book-categories');
            Route::patch('book-categories/{bookCategory}/toggle',
                [BookCategoryController::class, 'toggle']
            )->name('book-categories.toggle');

            Route::resource('stationery-categories', StationeryCategoryController::class)
                ->parameters(['stationery-categories' => 'stationeryCategory'])
                ->names('stationery-categories');
            Route::patch('stationery-categories/{stationeryCategory}/toggle',
                [StationeryCategoryController::class, 'toggle']
            )->name('stationery-categories.toggle');
        });

        // Orders
        Route::middleware('panel.permission:orders')
            ->prefix('orders')->name('orders.')->group(function () {
                Route::get('/', [OrderController::class, 'index'])->name('index');
                Route::get('/export', [OrderController::class, 'export'])->name('export');
                Route::get('/{order}', [OrderController::class, 'show'])->name('show');
                Route::patch('/{order}/status', [OrderController::class, 'updateStatus'])->name('status');
                Route::patch('/{order}/cancel', [OrderController::class, 'adminCancel'])->name('cancel');
            });

        // Seller orders
        Route::middleware('panel.permission:orders')
            ->prefix('seller-orders')->name('seller-orders.')->group(function () {
                Route::get('/', [SellerOrderController::class, 'index'])->name('index');
                Route::get('/{sellerOrder}', [SellerOrderController::class, 'show'])->name('show');
                Route::patch('/{sellerOrder}/status', [SellerOrderController::class, 'updateStatus'])->name('status');
            });

        // Courier orders
        Route::middleware('panel.permission:orders')
            ->prefix('courier-orders')->name('courier-orders.')->group(function () {
                Route::get('/', [CourierOrderController::class, 'index'])->name('index');
                Route::get('/{courierOrder}', [CourierOrderController::class, 'show'])->name('show');
                Route::patch('/{courierOrder}/status', [CourierOrderController::class, 'updateStatus'])->name('status');
                Route::patch('/{courierOrder}/assign', [CourierOrderController::class, 'assignCourier'])->name('assign');
            });

        // Sellers + Staff
        Route::middleware('panel.permission:sellers')
            ->prefix('sellers')->name('sellers.')->group(function () {
                Route::get('/', [SellerController::class, 'index'])->name('index');
                Route::get('/export', [SellerController::class, 'export'])->name('export');
                Route::get('/{seller}', [SellerController::class, 'show'])->name('show');
                Route::get('/{seller}/edit', [SellerController::class, 'edit'])->name('edit');
                Route::put('/{seller}', [SellerController::class, 'update'])->name('update');
                Route::patch('/{seller}/approve', [SellerController::class, 'approve'])->name('approve');
                Route::patch('/{seller}/reject', [SellerController::class, 'reject'])->name('reject');
                Route::get('/{seller}/staff/create', [SellerController::class, 'createStaff'])->name('staff.create');
                Route::post('/{seller}/staff', [SellerController::class, 'storeStaff'])->name('staff.store');
                Route::patch('/{seller}/staff/toggle', [SellerController::class, 'toggleStaff'])->name('staff.toggle');
            });

        // Couriers
        Route::middleware('panel.permission:couriers')
            ->prefix('couriers')->name('couriers.')->group(function () {
                Route::get('/', [CourierController::class, 'index'])->name('index');
                Route::get('/create', [CourierController::class, 'create'])->name('create');
                Route::post('/', [CourierController::class, 'store'])->name('store');
                Route::get('/export', [CourierController::class, 'export'])->name('export');
                Route::get('/{courier}', [CourierController::class, 'show'])->name('show');
                Route::get('/{courier}/edit', [CourierController::class, 'edit'])->name('edit');
                Route::put('/{courier}', [CourierController::class, 'update'])->name('update');
                Route::patch('/{courier}/approve', [CourierController::class, 'approve'])->name('approve');
                Route::patch('/{courier}/reject', [CourierController::class, 'reject'])->name('reject');
                Route::delete('/{courier}', [CourierController::class, 'destroy'])->name('destroy');
            });

        // Promocodes
        Route::middleware('panel.permission:promocodes')
            ->resource('promocodes', PromocodeController::class)
            ->names('promocodes');

        // Seller ads
        Route::middleware('panel.permission:settings')
            ->prefix('seller-ads')->name('seller-ads.')->group(function () {
                Route::get('/settings', [SellerAdController::class, 'settings'])->name('settings');
                Route::put('/settings', [SellerAdController::class, 'updateSettings'])->name('settings.update');
                Route::get('/', [SellerAdController::class, 'index'])->name('index');
                Route::get('/{sellerAd}', [SellerAdController::class, 'show'])->name('show');
                Route::patch('/{sellerAd}/moderate', [SellerAdController::class, 'moderate'])->name('moderate');
                Route::delete('/{sellerAd}', [SellerAdController::class, 'destroy'])->name('destroy');
            });

        // Bot tickets
        Route::middleware('panel.permission:settings')
            ->prefix('bot-tickets')->name('bot-tickets.')->group(function () {
                Route::get('/operators', [BotTicketController::class, 'operators'])->name('operators');
                Route::patch('/operators/{botOperator}/toggle', [BotTicketController::class, 'toggleOperator'])->name('operator.toggle');
                Route::get('/', [BotTicketController::class, 'index'])->name('index');
                Route::get('/{botTicket}', [BotTicketController::class, 'show'])->name('show');
                Route::patch('/{botTicket}/assign', [BotTicketController::class, 'assign'])->name('assign');
                Route::patch('/{botTicket}/close', [BotTicketController::class, 'close'])->name('close');
            });

        // Transactions
        Route::middleware('panel.permission:settings')
            ->prefix('seller-transactions')->name('seller-transactions.')->group(function () {
                Route::get('/', [SellerTransactionController::class, 'index'])->name('index');
                Route::get('/{id}', [SellerTransactionController::class, 'show'])->name('show');
                Route::patch('/{id}/approve', [SellerTransactionController::class, 'approve'])->name('approve');
                Route::patch('/{id}/reject', [SellerTransactionController::class, 'reject'])->name('reject');
            });

        // Book Club
        Route::middleware('panel.permission:settings')
            ->prefix('book-club')->name('book-club.')->group(function () {
                Route::get('/moderation-queue', [BookClubController::class, 'moderationQueue'])->name('moderation-queue');
                Route::post('/comments/{comment}/ugc-score', [BookClubController::class, 'saveCommentUgcScore'])->name('comment.ugc-score');
                Route::delete('/images/{image}', [BookClubController::class, 'deleteImage'])->name('image.delete');
                Route::put('/comments/{comment}', [BookClubController::class, 'updateComment'])->name('comment.update');
                Route::delete('/comments/{comment}', [BookClubController::class, 'deleteComment'])->name('comment.delete');
                Route::get('/', [BookClubController::class, 'index'])->name('index');
                Route::post('/{bookClub}/post-ugc-score', [BookClubController::class, 'savePostUgcScore'])->name('post-ugc-score');
                Route::get('/{bookClub}', [BookClubController::class, 'show'])->name('show');
                Route::get('/{bookClub}/edit', [BookClubController::class, 'edit'])->name('edit');
                Route::put('/{bookClub}', [BookClubController::class, 'update'])->name('update');
                Route::delete('/{bookClub}', [BookClubController::class, 'destroy'])->name('destroy');
            });

        // FCM
        Route::middleware('panel.permission:settings')
            ->prefix('fcm-notifications')->name('fcm-notifications.')->group(function () {
                Route::get('/', [FcmNotificationController::class, 'index'])->name('index');
                Route::get('/create', [FcmNotificationController::class, 'create'])->name('create');
                Route::post('/', [FcmNotificationController::class, 'store'])->name('store');
                Route::delete('/{fcmNotification}', [FcmNotificationController::class, 'destroy'])->name('destroy');
            });

        // Reels (reorder OLDIN)
        Route::middleware('panel.permission:settings')
            ->prefix('reels')->name('reels.')->group(function () {
                Route::get('/', [ReelController::class, 'index'])->name('index');
                Route::get('/create', [ReelController::class, 'create'])->name('create');
                Route::post('/', [ReelController::class, 'store'])->name('store');
                Route::get('/{reel}', [ReelController::class, 'show'])->name('show');
                Route::get('/{reel}/edit', [ReelController::class, 'edit'])->name('edit');
                Route::put('/{reel}', [ReelController::class, 'update'])->name('update');
                Route::delete('/{reel}', [ReelController::class, 'destroy'])->name('destroy');
                Route::post('/{reel}/items/reorder', [ReelController::class, 'reorderItems'])->name('items.reorder');
                Route::post('/{reel}/items', [ReelController::class, 'storeItem'])->name('items.store');
                Route::delete('/{reel}/items/{item}', [ReelController::class, 'destroyItem'])->name('items.destroy');
            });

        // Market news (preview-action OLDIN)
        Route::middleware('panel.permission:settings')
            ->prefix('market-news')->name('market-news.')->group(function () {
                Route::get('/preview-action', [MarketNewsController::class, 'previewAction'])->name('preview-action');
                Route::get('/', [MarketNewsController::class, 'index'])->name('index');
                Route::get('/create', [MarketNewsController::class, 'create'])->name('create');
                Route::post('/', [MarketNewsController::class, 'store'])->name('store');
                Route::get('/{marketNews}', [MarketNewsController::class, 'show'])->name('show');
                Route::get('/{marketNews}/edit', [MarketNewsController::class, 'edit'])->name('edit');
                Route::put('/{marketNews}', [MarketNewsController::class, 'update'])->name('update');
                Route::patch('/{marketNews}/toggle', [MarketNewsController::class, 'toggle'])->name('toggle');
                Route::delete('/{marketNews}', [MarketNewsController::class, 'destroy'])->name('destroy');
            });

        // Reports
        Route::middleware('panel.permission:settings')
            ->prefix('reports')->name('reports.')->group(function () {
                Route::get('/', [ReportController::class, 'index'])->name('index');
                Route::get('/{report}', [ReportController::class, 'show'])->name('show');
                Route::patch('/{report}/status', [ReportController::class, 'updateStatus'])->name('status');
                Route::delete('/{report}', [ReportController::class, 'destroy'])->name('destroy');
            });

        // Chats (ai/{id} OLDIN)
        Route::middleware('panel.permission:settings')
            ->prefix('chats')->name('chats.')->group(function () {
                Route::get('/', [ChatController::class, 'index'])->name('index');
                Route::get('/ai/{id}', [ChatController::class, 'showAi'])->name('show-ai');
                Route::get('/{id}', [ChatController::class, 'show'])->name('show');
            });

        // API clients
        Route::middleware('panel.permission:admins')
            ->prefix('api-clients')->name('api-clients.')->group(function () {
                Route::get('/', [ApiClientController::class, 'index'])->name('index');
                Route::get('/create', [ApiClientController::class, 'create'])->name('create');
                Route::post('/', [ApiClientController::class, 'store'])->name('store');
                Route::get('/{apiClient}/edit', [ApiClientController::class, 'edit'])->name('edit');
                Route::put('/{apiClient}', [ApiClientController::class, 'update'])->name('update');
                Route::patch('/{apiClient}/toggle', [ApiClientController::class, 'toggle'])->name('toggle');
                Route::patch('/{apiClient}/regenerate', [ApiClientController::class, 'regenerate'])->name('regenerate');
                Route::delete('/{apiClient}', [ApiClientController::class, 'destroy'])->name('destroy');
            });

        // Admins
        Route::middleware('panel.permission:admins')
            ->prefix('admins')->name('admins.')->group(function () {
                Route::get('/', [AdminController::class, 'index'])->name('index');
                Route::get('/create', [AdminController::class, 'create'])->name('create');
                Route::post('/', [AdminController::class, 'store'])->name('store');
                Route::get('/{admin}', [AdminController::class, 'show'])->name('show');
                Route::get('/{admin}/edit', [AdminController::class, 'edit'])->name('edit');
                Route::put('/{admin}', [AdminController::class, 'update'])->name('update');
                Route::delete('/{admin}', [AdminController::class, 'destroy'])->name('destroy');
                Route::patch('/{admin}/toggle', [AdminController::class, 'toggle'])->name('toggle');
            });

        // Gift certificates
        Route::middleware('panel.permission:settings')
            ->prefix('gift-certificates')->name('gift-certificates.')->group(function () {
                Route::get('/', [GiftCertificateController::class, 'index'])->name('index');
                Route::get('/{giftCertificate}', [GiftCertificateController::class, 'show'])->name('show');
                Route::patch('/{cert}/status', [GiftCertificateController::class, 'updateStatus'])->name('status');
                Route::patch('/{cert}/cancel', [GiftCertificateController::class, 'cancel'])->name('cancel');
            });

        // Mystery Box
        Route::middleware('panel.permission:settings')
            ->prefix('mystery-box')->name('mystery-box.')->group(function () {
                Route::get('/plans', [MysteryBoxController::class, 'plans'])->name('plans');
                Route::post('/plans', [MysteryBoxController::class, 'planStore'])->name('plans.store');
                Route::put('/plans/{plan}', [MysteryBoxController::class, 'planUpdate'])->name('plans.update');
                Route::delete('/plans/{plan}', [MysteryBoxController::class, 'planDestroy'])->name('plans.destroy');
                Route::get('/', [MysteryBoxController::class, 'subscriptions'])->name('subscriptions');
                Route::get('/{subscription}', [MysteryBoxController::class, 'showSubscription'])->name('subscription');
                Route::patch('/{subscription}/pause', [MysteryBoxController::class, 'pauseSubscription'])->name('pause');
                Route::patch('/{subscription}/resume', [MysteryBoxController::class, 'resumeSubscription'])->name('resume');
                Route::patch('/{subscription}/cancel', [MysteryBoxController::class, 'cancelSubscription'])->name('cancel');
                Route::patch('/delivery/{delivery}/prepare', [MysteryBoxController::class, 'prepareDelivery'])->name('delivery.prepare');
                Route::patch('/delivery/{delivery}/ship', [MysteryBoxController::class, 'shipDelivery'])->name('delivery.ship');
                Route::patch('/delivery/{delivery}/deliver', [MysteryBoxController::class, 'deliverDelivery'])->name('delivery.deliver');
            });

        // Settings
        Route::middleware('panel.permission:settings')
            ->prefix('settings')->name('settings.')->group(function () {
                Route::get('/', [SettingsController::class, 'index'])->name('index');
                Route::put('/versions', [SettingsController::class, 'updateVersions'])->name('versions');
                Route::post('/commission', [SettingsController::class, 'storeCommission'])->name('commission.store');
                Route::put('/commission/{commissionSetting}', [SettingsController::class, 'updateCommission'])->name('commission.update');
                Route::delete('/commission/{commissionSetting}', [SettingsController::class, 'destroyCommission'])->name('commission.destroy');
                Route::post('/cashback', [SettingsController::class, 'storeCashback'])->name('cashback.store');
                Route::put('/cashback/{cashbackSetting}', [SettingsController::class, 'updateCashback'])->name('cashback.update');
                Route::delete('/cashback/{cashbackSetting}', [SettingsController::class, 'destroyCashback'])->name('cashback.destroy');
                Route::post('/delivery', [SettingsController::class, 'storeDelivery'])->name('delivery.store');
                Route::put('/delivery/{deliveryService}', [SettingsController::class, 'updateDelivery'])->name('delivery.update');
                Route::delete('/delivery/{deliveryService}', [SettingsController::class, 'destroyDelivery'])->name('delivery.destroy');
            });

        // Policies
        Route::middleware('panel.permission:settings')
            ->prefix('policies')->name('policies.')->group(function () {
                Route::get('/', [PolicyController::class, 'index'])->name('index');
                Route::post('/', [PolicyController::class, 'store'])->name('store');
                Route::put('/{policy}', [PolicyController::class, 'update'])->name('update');
                Route::patch('/{policy}/toggle', [PolicyController::class, 'toggle'])->name('toggle');
                Route::delete('/{policy}', [PolicyController::class, 'destroy'])->name('destroy');
            });

        // Vakansiyalar (sayt /careers)
        Route::middleware('panel.permission:settings')
            ->prefix('vacancies')->name('vacancies.')->group(function () {
                Route::get('/', [VacancyController::class, 'index'])->name('index');
                Route::get('/create', [VacancyController::class, 'create'])->name('create');
                Route::post('/', [VacancyController::class, 'store'])->name('store');
                Route::get('/{vacancy}/edit', [VacancyController::class, 'edit'])->name('edit');
                Route::put('/{vacancy}', [VacancyController::class, 'update'])->name('update');
                Route::patch('/{vacancy}/toggle', [VacancyController::class, 'toggle'])->name('toggle');
                Route::delete('/{vacancy}', [VacancyController::class, 'destroy'])->name('destroy');
            });

        // Karyera arizalari (sayt /careers)
        Route::middleware('panel.permission:settings')
            ->prefix('career-applications')->name('career-applications.')->group(function () {
                Route::get('/', [CareerApplicationController::class, 'index'])->name('index');
                Route::get('/{career_application}/cv', [CareerApplicationController::class, 'downloadCv'])->name('cv');
                Route::get('/{career_application}', [CareerApplicationController::class, 'show'])->name('show');
                Route::patch('/{career_application}/status', [CareerApplicationController::class, 'updateStatus'])->name('status');
                Route::post('/{career_application}/reply', [CareerApplicationController::class, 'sendReply'])->name('reply');
            });

    });
});
