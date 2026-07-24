<?php

use App\Http\Controllers\Api\Seller\BanLogController;
use App\Http\Controllers\Api\Seller\ConversationController;
use App\Http\Controllers\Api\Seller\GiftController;
use App\Http\Controllers\Api\Seller\HisobotController;
use App\Http\Controllers\Api\Seller\OrderController;
use App\Http\Controllers\Api\Seller\PremiumController;
use App\Http\Controllers\Api\Seller\ProductController;
use App\Http\Controllers\Api\Seller\SellerAiController;
use App\Http\Controllers\Api\Seller\SellerAuthController;
use App\Http\Controllers\Api\Seller\SellerController;
use App\Http\Controllers\Api\Seller\SellerLocationController;
use App\Http\Controllers\Api\Seller\SellerStaffController;
use App\Http\Controllers\Api\Seller\SellerCourierController;
use App\Http\Controllers\Api\Seller\SupportTicketController;
use App\Http\Controllers\Api\Seller\TargetController;
use App\Http\Controllers\Api\Seller\TransactionController;
use Illuminate\Support\Facades\Route;

// Ochiq yo'llar (Login/Registratsiya)
Route::post('login', [SellerAuthController::class, 'login'])->middleware('throttle:auth-seller');
Route::post('contact', [SellerAuthController::class, 'register'])->middleware('throttle:registration-light');
Route::post('forgot', [SellerAuthController::class, 'forgot'])->middleware('throttle:password-recovery');

// Avtorizatsiyadan o'tgan sotuvchilar
Route::middleware('auth:seller')->group(function () {
    Route::get('session/config', [SellerController::class, 'sessionConfig']);
    Route::post('update/fcm', [SellerController::class, 'updateFcm']);
    Route::post('update/profile', [SellerController::class, 'updateProfile']);
    Route::get('update/password', [SellerController::class, 'updatePassword']);
    Route::post('update/status', [SellerController::class, 'updateStatus']);
    Route::post('ai/chat', [SellerAiController::class, 'chat']);
    Route::post('ai/parse-document', [SellerAiController::class, 'parseDocument']);
    Route::post('ai/stock-preview', [SellerAiController::class, 'previewStockUpdate']);
    Route::get('ai/actions', [SellerAiController::class, 'actionsHistory']);
    Route::post('ai/actions/{token}/apply', [SellerAiController::class, 'applyAction']);
    Route::post('ai/actions/{token}/rollback', [SellerAiController::class, 'rollbackAction']);
    Route::get('devices', [SellerController::class, 'getDevices']);
    Route::post('devices/remove-device', [SellerController::class, 'removeDevice']);
    Route::get('notifications', [SellerController::class, 'notifications']);
    Route::get('notifications/count', [SellerController::class, 'notificationsCount']);
    Route::get('menu/badge-summary', [SellerController::class, 'menuBadgeSummary']);
    Route::get('menu/reputation-summary', [SellerController::class, 'menuReputationSummary']);
    Route::post('notifications/read', [SellerController::class, 'markAsRead']);
    Route::post('notifications/read-all', [SellerController::class, 'markAllAsRead']);

    Route::get('products/last-products', [ProductController::class, 'lastProducts']); // vaqtincha
    Route::get('products/last', [ProductController::class, 'lastProductsBS']);
    Route::get('products/products-count', [ProductController::class, 'productsCount']);
    Route::post('products/product-status', [ProductController::class, 'updateProductStatus']);
    Route::post('products/remove-product', [ProductController::class, 'removeProduct']);
    Route::post('products/create', [ProductController::class, 'createProduct']);
    Route::post('products/update', [ProductController::class, 'updateProduct']);
    Route::get('products/authors/suggestions', [ProductController::class, 'getAuthorSuggestions']);
    Route::get('products/publishers/suggestions', [ProductController::class, 'getPublisherSuggestions']);
    Route::get('products/by-isbn/{isbn}', [ProductController::class, 'lookupByIsbn']);
    Route::get('products/stationery/by-barcode/{barcode}', [ProductController::class, 'lookupStationeryByBarcode']);
    Route::post('products/stock/by-code', [ProductController::class, 'updateStockByCode']);
    Route::get('products/categories', [ProductController::class, 'getCategories']);
    Route::post('products/stationery/create', [ProductController::class, 'createStationery']);
    Route::post('products/stationery/update', [ProductController::class, 'updateStationery']);
    Route::get('products/stationery/categories', [ProductController::class, 'getStationeryCategories']);
    Route::get('products/stationery/categories/{category_id}/tags', [ProductController::class, 'getStationeryTagsForCategory']);
    Route::get('products/categories/{category_id}/tags', [ProductController::class, 'getTagsForCategory']);
    Route::get('products/stat/{product}', [ProductController::class, 'productStatistics']);
    Route::post('products/generate-description', [ProductController::class, 'generateDescription']);
    Route::post('products/set-recommended', [ProductController::class, 'setRecommended']);
    Route::get('gifts/list', [GiftController::class, 'list']);
    Route::post('gifts/create', [GiftController::class, 'create']);
    Route::post('gifts/update/{gift_id}', [GiftController::class, 'update']);
    Route::post('gifts/delete/{gift_id}', [GiftController::class, 'delete']);
    Route::get('orders/last-orders', [OrderController::class, 'lastOrders']);
    Route::post('orders/view-order', [OrderController::class, 'viewOrder']);
    Route::get('orders/orders-count', [OrderController::class, 'ordersCount']);
    Route::get('orders/cancel-reasons', [OrderController::class, 'cancelReasonCatalog']);
    Route::get('orders/accept/{id}', [OrderController::class, 'acceptOrder']);
    Route::get('orders/scan-qr/{qr}', [OrderController::class, 'scanQR']);
    Route::post('orders/toCourier/{qr}', [OrderController::class, 'toCourier']);
    Route::post('orders/{id}/cancel', [OrderController::class, 'cancelSellerOrder']);
    Route::post('orders/items/{itemId}/cancel', [OrderController::class, 'cancelItem']);
    Route::post('orders/items/{itemId}/restore-cancel', [OrderController::class, 'restoreCancelledItem']);
    Route::get('premium/info', [PremiumController::class, 'info']);
    Route::post('premium/buy', [PremiumController::class, 'buy']);
    Route::post('premium/cancel', [PremiumController::class, 'cancel']);

    Route::get('transactions/latest', [TransactionController::class, 'getTransactions']);
    Route::get('transactions/withdrawal', [TransactionController::class, 'requestWithdrawal']);
    Route::get('transactions/count', [TransactionController::class, 'getTotal']);
    Route::get('transactions/cancel/{transactionId}', [TransactionController::class, 'cancelTransaction']);

    Route::get('target/initial', [TargetController::class, 'getInitialTargetData']);
    Route::get('target/products', [TargetController::class, 'getProductList']);
    Route::post('target/banner', [TargetController::class, 'storeHomePageBannerAd']);
    Route::get('target/product-stats/{id}', [TargetController::class, 'getProductStats']);

    Route::prefix('support-tickets')->group(function () {
        Route::get('/', [SupportTicketController::class, 'index']);
        Route::post('/', [SupportTicketController::class, 'store']);
        Route::get('{ticket}', [SupportTicketController::class, 'show']);
        Route::post('{ticket}/reply', [SupportTicketController::class, 'reply']);
        Route::post('{ticket}/close', [SupportTicketController::class, 'close']);
    });

    Route::get('ban-logs', [BanLogController::class, 'index'])->name('seller.index_banlog');
    Route::get('ban-logs/{id}/read', [BanLogController::class, 'markAsRead'])->name('seller.markAsRead_banlog');
    Route::get('ban-logs/counts', [BanLogController::class, 'getCounts'])->name('seller.counts_banlog');

    Route::get('statistics/data', [HisobotController::class, 'index'])->name('sales_data_index');
    Route::get('statistics/sales_chart_data', [HisobotController::class, 'getSalesStats'])->name('saleschart_data');

    Route::get('staff', [SellerStaffController::class, 'index']);
    Route::post('staff', [SellerStaffController::class, 'store']);
    Route::get('staff/{id}', [SellerStaffController::class, 'show']);
    Route::post('staff/{id}', [SellerStaffController::class, 'update']);
    Route::get('staff/{id}/delete', [SellerStaffController::class, 'destroy']);
    Route::get('staff/{id}/getpassword', [SellerStaffController::class, 'getPasswordSms']);

    // ── Do'kon kuryerlari (store couriers) ──────────────────────────
    Route::get('couriers', [SellerCourierController::class, 'index']);
    Route::post('couriers', [SellerCourierController::class, 'store']);
    Route::post('couriers/settings', [SellerCourierController::class, 'settings']); // {id}dan oldin
    Route::post('couriers/{id}', [SellerCourierController::class, 'update']);
    Route::get('couriers/{id}/delete', [SellerCourierController::class, 'destroy']);

    // FILIAL-DARAJALI STOCK (branch_stocks)
    Route::get('branch-stocks', [\App\Http\Controllers\Api\Seller\BranchStockController::class, 'show']);
    Route::post('branch-stocks/set', [\App\Http\Controllers\Api\Seller\BranchStockController::class, 'set']);
    Route::post('branch-stocks/transfer', [\App\Http\Controllers\Api\Seller\BranchStockController::class, 'transfer']);
    Route::get('branch-stocks/movements', [\App\Http\Controllers\Api\Seller\BranchStockController::class, 'movements']);

    Route::get('locations', [SellerLocationController::class, 'index']);
    Route::post('locations', [SellerLocationController::class, 'store']);
    Route::post('locations/{id}', [SellerLocationController::class, 'update']);
    Route::get('locations/{id}/delete', [SellerLocationController::class, 'destroy']);
    Route::get('locations/{id}/main', [SellerLocationController::class, 'makeMainLocation']);
    Route::post('locations/{id}/qr/rotate', [SellerLocationController::class, 'rotateQr']);

    Route::prefix('conversations')->group(function () {
        Route::get('/', [ConversationController::class, 'getConversations']);
        Route::get('order/{orderId}/start', [ConversationController::class, 'startForOrder']);
        Route::get('{id}/messages', [ConversationController::class, 'getMessages']);
        Route::post('{id}/send', [ConversationController::class, 'sendMessage']);
        Route::post('{id}/edit', [ConversationController::class, 'editMessage']);
        Route::post('{id}/delete', [ConversationController::class, 'deleteMessage']);
        Route::post('{id}/hide', [ConversationController::class, 'hideConversation']);
        Route::post('{conversationId}/read', [ConversationController::class, 'markAsRead']);
    });
});
