<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\ChatBotController;
    
use App\Http\Controllers\Api\ProjectSettingController;
use App\Http\Controllers\PushController;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SendSmsController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\WebhookController;

// Userni autentifikatsiya qilish
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
   return $request->user();
});

// Autentifikatsiya yo'llari
Route::post('auth', [AuthController::class, 'store'])->middleware('throttle:auth-user');
Route::get('auth/telegram/config', [AuthController::class, 'telegramConfig']);
Route::post('auth/telegram/login', [AuthController::class, 'telegramLogin'])->middleware('throttle:auth-telegram');
Route::post('push-notify/send/keywbudcegvc36247c2bc012389ds', [PushController::class, 'sendPush']);
Route::post('sendSms', [SendSmsController::class, 'sendSms'])->middleware('throttle:send-sms')->name('api.sendSms');
Route::get('appversion/check', [ProjectSettingController::class, 'getVersions']);
Route::post('hook', WebhookController::class);
Route::post('payme', [App\Http\Controllers\Api\PaymeController::class, 'index'])->middleware('payme');
Route::get('update_locale', [UserController::class, 'updateLocale']);
Route::get('counts', [UserController::class, 'getGlobalCounts']);
Route::prefix('v1')->group(function () {
    Route::prefix('client')->group(base_path('routes/api_client.php')); 
    Route::prefix('kitobchi')->group(base_path('routes/api_user.php')); 
    Route::prefix('seller')->group(base_path('routes/api_seller.php'));
    Route::prefix('courier')->group(base_path('routes/api_courier.php'));
    Route::prefix('hub')->group(base_path('routes/api_hub.php'));

});
