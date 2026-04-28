<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\{ProductsController, PurchaseController, SearchController, CartController, GiftsController};

Route::middleware('api.client:read')->group(function () {
    Route::prefix('products')->group(function () {
        Route::get('sellers/list', [ProductsController::class, 'sellersWithLatestProducts']);
        Route::get('sellers/by-qr/{token}', [ProductsController::class, 'sellerByQr']);
        Route::get('sellers/profile/{id}/{page}', [ProductsController::class, 'seller']);
        Route::get('recommendation/{col}', [ProductsController::class, 'recommendation']);
        Route::get('{col}', [ProductsController::class, 'index']);
    });
    Route::prefix('search')->group(function () {
        Route::get('/', [SearchController::class, 'search']);
        Route::get('categories', [SearchController::class, 'allCategories']);
        Route::get('category/{cat_id}/{type}', [SearchController::class, 'category']);
    });
});
