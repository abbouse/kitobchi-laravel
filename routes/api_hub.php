<?php

use App\Http\Controllers\Api\Hub\HubApplicationController;
use App\Http\Controllers\Api\Hub\HubAuthController;
use App\Http\Controllers\Api\Hub\HubFulfillmentController;
use App\Http\Controllers\Api\Hub\HubStaffController;
use Illuminate\Support\Facades\Route;

Route::post('login', [HubAuthController::class, 'login'])->middleware('throttle:auth-courier');

// Ochiq: ish o'rniga ariza qoldirish (login talab qilinmaydi).
Route::post('applications', [HubApplicationController::class, 'store'])->middleware('throttle:registration-light');

Route::middleware('auth:hub')->group(function () {
    Route::post('logout', [HubAuthController::class, 'logout']);
    Route::get('me', [HubAuthController::class, 'me']);

    Route::get('dashboard', [HubFulfillmentController::class, 'dashboard']);
    Route::get('queues/{queue}', [HubFulfillmentController::class, 'queue'])
        ->whereIn('queue', ['inbound', 'qc', 'packing', 'dispatch']);
    Route::get('exceptions', [HubFulfillmentController::class, 'exceptions']);
    Route::get('activity', [HubFulfillmentController::class, 'activity']);
    Route::get('staff', [HubStaffController::class, 'index']);
    Route::patch('staff/{staff}/role', [HubStaffController::class, 'updateRole']);
    Route::get('scan', [HubFulfillmentController::class, 'scan']);
    Route::get('fulfillments/{fulfillment}', [HubFulfillmentController::class, 'show']);
    Route::get('fulfillments/{fulfillment}/handoff-qr', [HubFulfillmentController::class, 'handoffQr']);
    Route::get('fulfillments/{fulfillment}/print-payload', [HubFulfillmentController::class, 'printPayload']);
    Route::post('fulfillments/{fulfillment}/mark-print', [HubFulfillmentController::class, 'markPrint']);
    Route::post('fulfillments/{fulfillment}/arrive', [HubFulfillmentController::class, 'arrive']);
    Route::post('fulfillments/{fulfillment}/qc', [HubFulfillmentController::class, 'qc']);
    Route::post('fulfillments/{fulfillment}/pack', [HubFulfillmentController::class, 'pack']);
    Route::post('fulfillments/{fulfillment}/label', [HubFulfillmentController::class, 'label']);
    Route::post('fulfillments/{fulfillment}/dispatch', [HubFulfillmentController::class, 'dispatch']);
    Route::post('fulfillments/{fulfillment}/exception', [HubFulfillmentController::class, 'reportException']);
    Route::post('fulfillments/{fulfillment}/resolve-exception', [HubFulfillmentController::class, 'resolveException']);
});
