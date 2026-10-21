<?php

use App\Http\Controllers\KitobchiId\NativeLoginController;
use App\Http\Controllers\KitobchiId\TokenController;
use Illuminate\Support\Facades\Route;

/*
| Kitobchi ID — stateless endpointlar (`api` guruhi, /api/id/...). CORS global
| HandleCors orqali (api/* yo'llari), cookie/sessiya ishlatilmaydi.
*/
Route::prefix('id')->name('kitobchi-id.')->group(function () {
    Route::get('.well-known/openid-configuration', [TokenController::class, 'discovery'])->name('discovery');
    Route::get('jwks', [TokenController::class, 'jwks'])->name('jwks');
    Route::post('token', [TokenController::class, 'token'])->middleware('throttle:60,1')->name('token');
    Route::post('revoke', [TokenController::class, 'revoke'])->middleware('throttle:60,1')->name('revoke');

    // Ekotizim (first-party) mobil ilovalari: brauzersiz kirish
    Route::post('native/start', [NativeLoginController::class, 'start'])->middleware('throttle:5,1')->name('native.start');
    Route::post('native/resend', [NativeLoginController::class, 'resend'])->middleware('throttle:5,1')->name('native.resend');
    Route::post('native/verify', [NativeLoginController::class, 'verify'])->middleware('throttle:10,1')->name('native.verify');

    Route::match(['get', 'post'], 'userinfo', [TokenController::class, 'userinfo'])->middleware('throttle:120,1')->name('userinfo');
});
