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
        Route::get('/live/data', [AdminController::class, 'liveData'])->name('live.data');

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

        Route::patch('/books/{book}/moderate', [\App\Http\Controllers\A122\BookController::class, 'moderate'])->name('books.moderate');
        Route::patch('/stationery/{id}/moderate', [\App\Http\Controllers\A122\StationeryController::class, 'moderate'])->name('stationery.moderate');
        Route::post('/authors/{author}/generate-image-prompt', [\App\Http\Controllers\A122\AuthorController::class, 'generateImagePrompt'])->name('authors.generate-image-prompt');
        Route::delete('/authors/{author}', [\App\Http\Controllers\A122\AuthorController::class, 'destroy'])->name('authors.destroy');
        Route::delete('/publishers/{publisher}', [\App\Http\Controllers\A122\PublisherController::class, 'destroy'])->name('publishers.destroy');
        Route::patch('/book-categories/{bookCategory}/toggle', [\App\Http\Controllers\A122\BookCategoryController::class, 'toggle'])->name('book-categories.toggle');
        Route::delete('/book-categories/{bookCategory}', [\App\Http\Controllers\A122\BookCategoryController::class, 'destroy'])->name('book-categories.destroy');
        Route::patch('/stationery-categories/{stationeryCategory}/toggle', [\App\Http\Controllers\A122\StationeryCategoryController::class, 'toggle'])->name('stationery-categories.toggle');
        Route::delete('/stationery-categories/{stationeryCategory}', [\App\Http\Controllers\A122\StationeryCategoryController::class, 'destroy'])->name('stationery-categories.destroy');
        Route::patch('/orders/{order}/status', [\App\Http\Controllers\A122\OrderController::class, 'updateStatus'])->name('orders.status');
        Route::post('/orders/{order}/cancel', [\App\Http\Controllers\A122\OrderController::class, 'adminCancel'])->name('orders.cancel');
        Route::patch('/sellers/{seller}/approve', [\App\Http\Controllers\A122\SellerController::class, 'approve'])->name('sellers.approve');
        Route::patch('/sellers/{seller}/reject', [\App\Http\Controllers\A122\SellerController::class, 'reject'])->name('sellers.reject');
        Route::patch('/sellers/{seller}/unblock', [\App\Http\Controllers\A122\SellerController::class, 'unblock'])->name('sellers.unblock');
        Route::post('/sellers/{seller}/warn', [\App\Http\Controllers\A122\SellerController::class, 'warn'])->name('sellers.warn');
        Route::post('/sellers/{seller}/reset-password', [\App\Http\Controllers\A122\SellerController::class, 'resetPassword'])->name('sellers.reset-password');
        Route::patch('/seller-orders/{sellerOrder}/status', [\App\Http\Controllers\A122\SellerOrderController::class, 'updateStatus'])->name('seller-orders.status');
        Route::patch('/courier-orders/{courierOrder}/status', [\App\Http\Controllers\A122\CourierOrderController::class, 'updateStatus'])->name('courier-orders.status');
        Route::patch('/ads/{ad}/moderate', [\App\Http\Controllers\A122\SellerAdController::class, 'moderate'])->name('ads.moderate');

        Route::put('/settings/versions', [\App\Http\Controllers\A122\SettingsController::class, 'updateVersions'])->name('settings.versions');
        Route::put('/settings/contacts', [\App\Http\Controllers\A122\SettingsController::class, 'updateContacts'])->name('settings.contacts');
        Route::put('/settings/app-flags', [\App\Http\Controllers\A122\SettingsController::class, 'updateAppFlags'])->name('settings.app-flags');
        Route::put('/settings/courier-bonus', [\App\Http\Controllers\A122\SettingsController::class, 'updateCourierBonus'])->name('settings.courier-bonus');
        Route::put('/settings/telegram', [\App\Http\Controllers\A122\SettingsController::class, 'updateTelegram'])->name('settings.telegram');
        Route::post('/settings/commission', [\App\Http\Controllers\A122\SettingsController::class, 'storeCommission'])->name('settings.commission.store');
        Route::put('/settings/commission/{commissionSetting}', [\App\Http\Controllers\A122\SettingsController::class, 'updateCommission'])->name('settings.commission.update');
        Route::delete('/settings/commission/{commissionSetting}', [\App\Http\Controllers\A122\SettingsController::class, 'destroyCommission'])->name('settings.commission.destroy');
        Route::post('/settings/cashback', [\App\Http\Controllers\A122\SettingsController::class, 'storeCashback'])->name('settings.cashback.store');
        Route::put('/settings/cashback/{cashbackSetting}', [\App\Http\Controllers\A122\SettingsController::class, 'updateCashback'])->name('settings.cashback.update');
        Route::delete('/settings/cashback/{cashbackSetting}', [\App\Http\Controllers\A122\SettingsController::class, 'destroyCashback'])->name('settings.cashback.destroy');
        Route::post('/settings/delivery', [\App\Http\Controllers\A122\SettingsController::class, 'storeDelivery'])->name('settings.delivery.store');
        Route::put('/settings/delivery/{deliveryService}', [\App\Http\Controllers\A122\SettingsController::class, 'updateDelivery'])->name('settings.delivery.update');
        Route::delete('/settings/delivery/{deliveryService}', [\App\Http\Controllers\A122\SettingsController::class, 'destroyDelivery'])->name('settings.delivery.destroy');
    });
});
