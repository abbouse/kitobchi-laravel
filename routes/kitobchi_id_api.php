<?php

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
    Route::match(['get', 'post'], 'userinfo', [TokenController::class, 'userinfo'])->middleware('throttle:120,1')->name('userinfo');
});
