<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Courier\{ConversationController, CourierAuthController, CourierController, CourierOrderController, CourierTransactionController, CourierBanLogController};

// Kuryer Login
Route::post('auth', [CourierAuthController::class, 'auth']);
Route::post('forgot', [CourierAuthController::class, 'forgot']);
Route::post('contact-request', [CourierAuthController::class, 'contactRequest']);

// Kuryer funksiyalari
Route::middleware('auth:courier')->group(function () {
    Route::post('logout', [CourierAuthController::class, 'logout']);
    Route::get('devices', [CourierController::class, 'getDevices']);
    Route::post('devices/remove-device', [CourierController::class, 'removeDevice']);
    Route::post('update/password', [CourierController::class, 'updatePassword']);
    Route::post('update/fcm', [CourierController::class, 'updateFcm']);
    Route::get('notifications', [CourierController::class, 'notifications']);
    Route::get('notifications/count', [CourierController::class, 'notificationsCount']);
    Route::post('notifications/read', [CourierController::class, 'markAsRead']);
    Route::post('notifications/read-all', [CourierController::class, 'markAllAsRead']);

    Route::get('transactions/latest', [CourierTransactionController::class, 'getTransactions']);
    Route::post('transactions/withdrawal', [CourierTransactionController::class, 'requestWithdrawal']);
    Route::get('transactions/count', [CourierTransactionController::class, 'getTotal']);
    Route::post('transactions/cancel', [CourierTransactionController::class, 'cancelTransaction']);

    Route::get('ban-logs', [CourierBanLogController::class, 'index'])->name('courier.index_banlog');
    Route::get('ban-logs/counts', [CourierBanLogController::class, 'getCounts'])->name('courier.counts_banlog');
    Route::post('ban-logs/{id}/read', [CourierBanLogController::class, 'markAsRead'])->name('courier.markAsRead_banlog');

    Route::get('orders/available', [CourierOrderController::class, 'getAvailableOrders']);
    Route::post('orders/confirm/{id}', [CourierOrderController::class, 'confirmOrder']);
    Route::get('orders/view/{id}', [CourierOrderController::class, 'showOrder']);
    Route::get('orders/my', [CourierOrderController::class, 'myOrders']);
    Route::post('orders/toCustomer/{qr}', [CourierOrderController::class, 'toCustomer']);
    // Phase 3: Mijoz javob bermayapti — SLA timerini pauza/resume (toggle).
    Route::post('orders/{id}/customer-delay', [CourierOrderController::class, 'customerDelay']);
    Route::prefix('conversations')->group(function () {
        Route::get('', [ConversationController::class, 'index']);
        Route::post('order/{orderId}/start', [ConversationController::class, 'startForOrder']);
        Route::get('{id}/messages', [ConversationController::class, 'getMessages']);
        Route::post('{id}/send', [ConversationController::class, 'sendMessage']);
        Route::post('{id}/read', [ConversationController::class, 'markAsRead']);
    });

    Route::get('profile', [CourierController::class, 'my_data']);
});
