<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Courier\{CourierAuthController, CourierController, CourierOrderController, CourierTransactionController, CourierBanLogController};

// Kuryer Login
Route::post('auth', [CourierAuthController::class, 'auth']);
Route::post('contact-request', [CourierAuthController::class, 'contactRequest']);

// Kuryer funksiyalari
Route::middleware('auth:courier')->group(function () {
    Route::get('devices', [CourierController::class, 'getDevices']);
    Route::get('update/password', [CourierController::class, 'updatePassword']);
    Route::post('update/fcm', [CourierController::class, 'updateFcm']);
    Route::get('notifications', [CourierController::class, 'notifications']);
    Route::get('notifications/count', [CourierController::class, 'notificationsCount']);
    Route::post('notifications/read', [CourierController::class, 'markAsRead']);
    Route::post('notifications/read-all', [CourierController::class, 'markAllAsRead']);
    
    Route::get('transactions/latest', [CourierTransactionController::class, 'getTransactions']);
    Route::get('transactions/withdrawal', [CourierTransactionController::class, 'requestWithdrawal']);
    Route::get('transactions/count', [CourierTransactionController::class, 'getTotal']);
    Route::get('transactions/cancel/{transactionId}', [CourierTransactionController::class, 'cancelTransaction']);
    
    Route::get('ban-logs', [CourierBanLogController::class, 'index'])->name('index_banlog');
    Route::get('ban-logs/{id}/read', [CourierBanLogController::class, 'markAsRead'])->name('markAsRead_banlog');
    Route::get('ban-logs/counts', [CourierBanLogController::class, 'getCounts'])->name('counts_banlog');
    
    Route::get('orders/available', [CourierOrderController::class, 'getAvailableOrders']);
    Route::get('orders/confirm/{id}', [CourierOrderController::class, 'confirmOrder']);
    Route::get('orders/view/{id}', [CourierOrderController::class, 'showOrder']);
    Route::get('orders/my', [CourierOrderController::class, 'myOrders']);
    Route::get('orders/toCustomer/{qr}', [CourierOrderController::class, 'toCustomer']);
    
    Route::get('delivery/{col}', [CourierController::class, 'index']);
    Route::get('profile', [CourierController::class, 'my_data']);
    Route::get('scannedCode/{qr_code}', [CourierController::class, 'qr_code']);
    Route::get('takeOrder/{id}', [CourierController::class, 'product_taked']);
    Route::get('deliveredOrder/{qr_code}', [CourierController::class, 'product_delivered']);
});