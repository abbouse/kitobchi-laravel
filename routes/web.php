<?php
use App\Http\Controllers\ForgotController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RegisterController;
use App\MoonShine\Pages\ResetPasswordPage;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\Seller\ProductController;
use App\Http\Controllers\LegalController;

use App\Http\Controllers\Admin\DashboardController;


Route::get('/', function () {
    return view('welcome');
})->name('welcome');

Route::get('/payment/{order_id}', [PaymentController::class, 'payWithPayme'])->name('payme.redirect');
Route::get('/payment/success/{order_id}', [PaymentController::class, 'redirectToApp'])->name('success.redirect');

Route::group(['prefix' => 'legal'], function () {
    Route::get('/terms', [LegalController::class, 'terms'])->name('legal.terms');
    Route::get('/privacy', [LegalController::class, 'privacy'])->name('legal.privacy');
    Route::get('/subscription', [LegalController::class, 'subscription'])->name('legal.subscription');
    Route::get('/all', [LegalController::class, 'index'])->name('legal.index');
});

Route::group(['prefix' => 'boshqaruv'], function () {
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

// calender pages
Route::get('/calendar', function () {
    return view('pages.calender', ['title' => 'Calendar']);
})->name('calendar');

// profile pages
Route::get('/profile', function () {
    return view('pages.profile', ['title' => 'Profile']);
})->name('profile');

// form pages
Route::get('/form-elements', function () {
    return view('pages.form.form-elements', ['title' => 'Form Elements']);
})->name('form-elements');

// tables pages
Route::get('/basic-tables', function () {
    return view('pages.tables.basic-tables', ['title' => 'Basic Tables']);
})->name('basic-tables');

// pages

Route::get('/blank', function () {
    return view('pages.blank', ['title' => 'Blank']);
})->name('blank');

// error pages
Route::get('/error-404', function () {
    return view('pages.errors.error-404', ['title' => 'Error 404']);
})->name('error-404');

// chart pages
Route::get('/line-chart', function () {
    return view('pages.chart.line-chart', ['title' => 'Line Chart']);
})->name('line-chart');

Route::get('/bar-chart', function () {
    return view('pages.chart.bar-chart', ['title' => 'Bar Chart']);
})->name('bar-chart');


// authentication pages
Route::get('/signin', function () {
    return view('pages.auth.signin', ['title' => 'Sign In']);
})->name('signin');

Route::get('/signup', function () {
    return view('pages.auth.signup', ['title' => 'Sign Up']);
})->name('signup');

// ui elements pages
Route::get('/alerts', function () {
    return view('pages.ui-elements.alerts', ['title' => 'Alerts']);
})->name('alerts');

Route::get('/avatars', function () {
    return view('pages.ui-elements.avatars', ['title' => 'Avatars']);
})->name('avatars');

Route::get('/badge', function () {
    return view('pages.ui-elements.badges', ['title' => 'Badges']);
})->name('badges');

Route::get('/buttons', function () {
    return view('pages.ui-elements.buttons', ['title' => 'Buttons']);
})->name('buttons');

Route::get('/image', function () {
    return view('pages.ui-elements.images', ['title' => 'Images']);
})->name('images');

Route::get('/videos', function () {
    return view('pages.ui-elements.videos', ['title' => 'Videos']);
})->name('videos');
});