<?php

use App\Http\Controllers\Boshqaruv\AdminController;
use Illuminate\Support\Facades\Route;

Route::prefix('boshqaruv')->name('boshqaruv.')->group(function () {
    Route::middleware('guest:panel')->group(function () {
        Route::get('/login', [AdminController::class, 'login'])->name('login');
        Route::post('/login', [AdminController::class, 'authenticate'])->middleware('throttle:auth-panel')->name('login.post');
    });

    Route::middleware('auth.panel')->group(function () {
        Route::post('/logout', [AdminController::class, 'logout'])->name('logout');

        Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
        Route::get('/live', [AdminController::class, 'live'])->name('live');

        Route::get('/products', fn (AdminController $controller) => $controller->page('Products'))->name('products');
        Route::get('/books', fn (AdminController $controller) => $controller->page('Books'))->name('books');
        Route::get('/book-categories', fn (AdminController $controller) => $controller->page('BookCategories'))->name('book-categories');
        Route::get('/stationeries', fn (AdminController $controller) => $controller->page('Stationeries'))->name('stationeries');
        Route::get('/stationery-categories', fn (AdminController $controller) => $controller->page('stationery-categories'))->name('stationery-categories');
        Route::get('/authors', fn (AdminController $controller) => $controller->page('Authors'))->name('authors');
        Route::get('/publishers', fn (AdminController $controller) => $controller->page('Publishers'))->name('publishers');
        Route::get('/parser', fn (AdminController $controller) => $controller->page('Parser'))->name('parser');
        Route::get('/users', fn (AdminController $controller) => $controller->page('Users'))->name('users');
        Route::get('/orders', fn (AdminController $controller) => $controller->page('Orders'))->name('orders');
        Route::get('/sellers', fn (AdminController $controller) => $controller->page('SellerOrders'))->name('sellers');
        Route::get('/seller-orders', fn (AdminController $controller) => $controller->page('SellerOrders'))->name('seller-orders');
        Route::get('/couriers', fn (AdminController $controller) => $controller->page('CourierOrders'))->name('couriers');
        Route::get('/courier-orders', fn (AdminController $controller) => $controller->page('CourierOrders'))->name('courier-orders');
        Route::get('/hubs', fn (AdminController $controller) => $controller->page('Hubs'))->name('hubs');
        Route::get('/transactions', fn (AdminController $controller) => $controller->page('Transaksiyalar'))->name('transactions');
        Route::get('/logistika', fn (AdminController $controller) => $controller->page('LogistikaPage'))->name('logistika');
        Route::get('/reklamalar', fn (AdminController $controller) => $controller->page('Reklamalar'))->name('reklamalar');
        Route::get('/promokodlar', fn (AdminController $controller) => $controller->page('Promokodlar'))->name('promokodlar');
        Route::get('/blogerlar', fn (AdminController $controller) => $controller->page('Blogerlar'))->name('blogerlar');
        Route::get('/gift-sertifikatlar', fn (AdminController $controller) => $controller->page('GiftSertifikatlar'))->name('gift-sertifikatlar');
        Route::get('/market-news', fn (AdminController $controller) => $controller->page('MarketNewsPage'))->name('market-news');
        Route::get('/reels', fn (AdminController $controller) => $controller->page('ReelsPage'))->name('reels');
        Route::get('/book-club', fn (AdminController $controller) => $controller->page('BookClub'))->name('book-club');
        Route::get('/tickets', fn (AdminController $controller) => $controller->page('Tickets'))->name('tickets');
        Route::get('/shikoyatlar', fn (AdminController $controller) => $controller->page('Shikoyatlar'))->name('shikoyatlar');
        Route::get('/chat', fn (AdminController $controller) => $controller->page('ChatKuzatuv'))->name('chat');
        Route::get('/push', fn (AdminController $controller) => $controller->page('PushNotifications'))->name('push');
        Route::get('/vakansiyalar', fn (AdminController $controller) => $controller->page('Vakansiyalar'))->name('vakansiyalar');
        Route::get('/karyera-arizalari', fn (AdminController $controller) => $controller->page('KaryeraArizalari'))->name('karyera-arizalari');
        Route::get('/adminlar', fn (AdminController $controller) => $controller->page('Adminlar'))->name('adminlar');
        Route::get('/mystery-box', fn (AdminController $controller) => $controller->page('MysteryBoxPage'))->name('mystery-box');
        Route::get('/sovgalar', fn (AdminController $controller) => $controller->page('Sovgalar'))->name('sovgalar');
        Route::get('/siyosatlar', fn (AdminController $controller) => $controller->page('Siyosatlar'))->name('siyosatlar');
        Route::get('/api-clients', fn (AdminController $controller) => $controller->page('ApiClients'))->name('api-clients');
        Route::get('/search-history', fn (AdminController $controller) => $controller->page('SearchHistory'))->name('search-history');
        Route::get('/settings', fn (AdminController $controller) => $controller->page('Settings'))->name('settings');
    });
});
