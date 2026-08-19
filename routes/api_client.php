<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\{ProductsController, PurchaseController, SearchController, CartController, GiftsController};
use App\Http\Controllers\Api\Client\SellerApiController;

// ── Seller-scoped WRITE API ──────────────────────────────────────────────
// Kalit bitta sellerga bog'langan + `stock:write` ability. Bu guruh READ
// guruhidan OLDIN turadi — aks holda GET products/mine "products/{col}" ga tushib
// ketardi.
Route::middleware('api.client:stock:write')->group(function () {
    Route::post('products/stock/by-code', [SellerApiController::class, 'updateStockByCode']);
    Route::get('products/mine', [SellerApiController::class, 'myProducts']);
});

// ── Public READ API ──────────────────────────────────────────────────────
Route::middleware('api.client:read')->group(function () {
    Route::prefix('products')->group(function () {
        // Individual Product Details
        Route::get('book/{id}', [ProductsController::class, 'showBook']);
        Route::get('stationery/{id}', [ProductsController::class, 'showStationery']);
        Route::get('by-author/{authorId}', [ProductsController::class, 'booksByAuthor']);
        Route::get('by-publisher/{publisherId}', [ProductsController::class, 'booksByPublisher']);
        Route::get('by-category', [ProductsController::class, 'booksByCategory']);

        // Sellers & Storefronts
        Route::get('sellers/list', [ProductsController::class, 'sellersWithLatestProducts']);
        Route::get('sellers/by-qr/{token}', [ProductsController::class, 'sellerByQr']);
        Route::get('sellers/profile/{id}/{page}', [ProductsController::class, 'seller']);
        Route::get('sellers/{sellerId}/isbn/{isbn}', [ProductsController::class, 'sellerProductByIsbn']);
        Route::get('sellers/{sellerId}/code/{code}', [ProductsController::class, 'sellerProductByCode']);

        // Recommendations & Catalog Listing
        Route::get('recommendation/{col}', [ProductsController::class, 'recommendation']);
        Route::get('{col}', [ProductsController::class, 'index']);
    });

    // Deep Linking Helper
    Route::get('deeplink', [ProductsController::class, 'deeplink']);

    Route::prefix('search')->group(function () {
        Route::get('/', [SearchController::class, 'search']);
        Route::get('suggestions', [SearchController::class, 'suggestions']);
        Route::get('trending', [SearchController::class, 'trendingSearches']);
        Route::get('categories', [SearchController::class, 'allCategories']);
        Route::get('category/{cat_id}/{type}', [SearchController::class, 'category']);
    });
});
