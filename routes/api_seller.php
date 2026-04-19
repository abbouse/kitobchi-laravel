<?php

use App\Http\Controllers\Api\Seller\BanLogController;
use App\Http\Controllers\Api\Seller\ContestsController;
use App\Http\Controllers\Api\Seller\ConversationController;
use App\Http\Controllers\Api\Seller\GiftController;
use App\Http\Controllers\Api\Seller\HisobotController;
use App\Http\Controllers\Api\Seller\OrderController;
use App\Http\Controllers\Api\Seller\ProductController;
use App\Http\Controllers\Api\Seller\SellerAuthController;
use App\Http\Controllers\Api\Seller\SellerController;
use App\Http\Controllers\Api\Seller\SellerLocationController;
use App\Http\Controllers\Api\Seller\SellerMarketInsightController;
use App\Http\Controllers\Api\Seller\SellerStaffController;
use App\Http\Controllers\Api\Seller\TargetController;
use App\Http\Controllers\Api\Seller\TransactionController;
use Illuminate\Support\Facades\Route;

// Ochiq yo'llar (Login/Registratsiya)
Route::post('login', [SellerAuthController::class, 'login']);
Route::post('contact', [SellerAuthController::class, 'register']);
Route::post('forgot', [SellerAuthController::class, 'forgot']);

// Avtorizatsiyadan o'tgan sotuvchilar
Route::middleware('auth:seller')->group(function () {
    Route::post('update/fcm', [SellerController::class, 'updateFcm']);
    Route::post('update/profile', [SellerController::class, 'updateProfile']);
    Route::get('update/password', [SellerController::class, 'updatePassword']);
    Route::post('update/status', [SellerController::class, 'updateStatus']);
    Route::get('devices', [SellerController::class, 'getDevices']);
    Route::post('devices/remove-device', [SellerController::class, 'removeDevice']);
    Route::get('notifications', [SellerController::class, 'notifications']);
    Route::get('notifications/count', [SellerController::class, 'notificationsCount']);
    Route::post('notifications/read', [SellerController::class, 'markAsRead']);
    Route::post('notifications/read-all', [SellerController::class, 'markAllAsRead']);

    Route::get('products/last-products', [ProductController::class, 'lastProducts']); // vaqtincha
    Route::get('products/last', [ProductController::class, 'lastProductsBS']);
    Route::get('products/products-count', [ProductController::class, 'productsCount']);
    Route::post('products/product-status', [ProductController::class, 'updateProductStatus']);
    Route::post('products/remove-product', [ProductController::class, 'removeProduct']);
    Route::post('products/create', [ProductController::class, 'createProduct']);
    Route::post('products/update', [ProductController::class, 'updateProduct']);
    Route::get('products/categories', [ProductController::class, 'getCategories']);
    Route::post('products/stationery/create', [ProductController::class, 'createStationery']);
    Route::post('products/stationery/update', [ProductController::class, 'updateStationery']);
    Route::get('products/stationery/categories', [ProductController::class, 'getStationeryCategories']);
    Route::get('products/stationery/categories/{category_id}/tags', [ProductController::class, 'getStationeryTagsForCategory']);
    Route::get('products/categories/{category_id}/tags', [ProductController::class, 'getTagsForCategory']);
    Route::get('products/stat/{product}', [ProductController::class, 'productStatistics']);
    Route::get('products/{type}/{id}/market-insight', [SellerMarketInsightController::class, 'show'])
        ->whereIn('type', ['book', 'stationery']);
    Route::get('analytics/bundle-signals', [SellerMarketInsightController::class, 'bundleSignals']);
    Route::post('products/generate-description', [ProductController::class, 'generateDescription']);
    Route::post('products/set-recommended', [ProductController::class, 'setRecommended']);
    Route::get('gifts/list', [GiftController::class, 'list']);
    Route::post('gifts/create', [GiftController::class, 'create']);
    Route::post('gifts/update/{gift_id}', [GiftController::class, 'update']);
    Route::get('orders/last-orders', [OrderController::class, 'lastOrders']);
    Route::post('orders/view-order', [OrderController::class, 'viewOrder']);
    Route::get('orders/orders-count', [OrderController::class, 'ordersCount']);
    Route::get('orders/toCourier/{qr}', [OrderController::class, 'toCourier']);

    Route::get('transactions/latest', [TransactionController::class, 'getTransactions']);
    Route::get('transactions/withdrawal', [TransactionController::class, 'requestWithdrawal']);
    Route::get('transactions/count', [TransactionController::class, 'getTotal']);
    Route::get('transactions/cancel/{transactionId}', [TransactionController::class, 'cancelTransaction']);

    Route::get('target/initial', [TargetController::class, 'getInitialTargetData']);
    Route::get('target/products', [TargetController::class, 'getProductList']);
    Route::post('target/banner', [TargetController::class, 'storeHomePageBannerAd']);
    Route::get('target/product-stats/{id}', [TargetController::class, 'getProductStats']);

    Route::get('contests', [ContestsController::class, 'getContests']);
    Route::get('contests/{contestId}', [ContestsController::class, 'getContestDetails']);
    Route::get('contests/{contestId}/download-participants', [ContestsController::class, 'downloadParticipants']);
    Route::post('contests', [ContestsController::class, 'createContest']);
    Route::post('contests/{contestId}/end', [ContestsController::class, 'endContest']);
    Route::get('contests/{contestId}', [ContestsController::class, 'deleteContest']);

    Route::get('ban-logs', [BanLogController::class, 'index'])->name('index_banlog');
    Route::get('ban-logs/{id}/read', [BanLogController::class, 'markAsRead'])->name('markAsRead_banlog');
    Route::get('ban-logs/counts', [BanLogController::class, 'getCounts'])->name('counts_banlog');

    Route::get('statistics/data', [HisobotController::class, 'index'])->name('sales_data_index');
    Route::get('statistics/sales_chart_data', [HisobotController::class, 'getSalesStats'])->name('saleschart_data');

    Route::get('staff', [SellerStaffController::class, 'index']);
    Route::post('staff', [SellerStaffController::class, 'store']);
    Route::get('staff/{id}', [SellerStaffController::class, 'show']);
    Route::post('staff/{id}', [SellerStaffController::class, 'update']);
    Route::get('staff/{id}/delete', [SellerStaffController::class, 'destroy']);
    Route::get('staff/{id}/getpassword', [SellerStaffController::class, 'getPasswordSms']);

    Route::get('locations', [SellerLocationController::class, 'index']);
    Route::post('locations', [SellerLocationController::class, 'store']);
    Route::post('locations/{id}', [SellerLocationController::class, 'update']);
    Route::get('locations/{id}/delete', [SellerLocationController::class, 'destroy']);
    Route::get('locations/{id}/main', [SellerLocationController::class, 'makeMainLocation']);

    Route::prefix('conversations')->group(function () {
        Route::get('/', [ConversationController::class, 'getConversations']);
        Route::get('{id}/messages', [ConversationController::class, 'getMessages']);
        Route::post('{id}/send', [ConversationController::class, 'sendMessage']);
        Route::post('{id}/edit', [ConversationController::class, 'editMessage']);
        Route::post('{id}/delete', [ConversationController::class, 'deleteMessage']);
        Route::post('{id}/hide', [ConversationController::class, 'hideConversation']);
        Route::post('{conversationId}/read', [ConversationController::class, 'markAsRead']);
    });
});
