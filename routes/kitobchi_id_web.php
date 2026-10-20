<?php

use App\Http\Controllers\KitobchiId\AuthorizeController;
use Illuminate\Support\Facades\Route;

/*
| Kitobchi ID — interaktiv sahifalar (sessiya + CSRF, `web` guruhi).
| Stateless endpointlar (token, userinfo, jwks, discovery, revoke) — routes/kitobchi_id_api.php.
*/
Route::prefix('api/id')->name('kitobchi-id.')->group(function () {
    Route::get('authorize', [AuthorizeController::class, 'handleAuthorize'])->middleware('throttle:60,1')->name('authorize');
    Route::get('authorize/resume', [AuthorizeController::class, 'resume'])->middleware('throttle:60,1')->name('resume');
    Route::post('login/send', [AuthorizeController::class, 'loginSend'])->middleware('throttle:5,1')->name('login.send');
    Route::post('login/verify', [AuthorizeController::class, 'loginVerify'])->middleware('throttle:10,1')->name('login.verify');
    Route::post('login/switch', [AuthorizeController::class, 'switchAccount'])->middleware('throttle:20,1')->name('login.switch');
    Route::post('consent', [AuthorizeController::class, 'consent'])->middleware('throttle:30,1')->name('consent');
    Route::get('logout', [AuthorizeController::class, 'endSession'])->middleware('throttle:30,1')->name('logout');
});
