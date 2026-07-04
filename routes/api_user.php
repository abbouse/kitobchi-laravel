<?php

use App\Http\Controllers\Api\BookClubCommentController;
use App\Http\Controllers\Api\BookClubController;
use App\Http\Controllers\Api\BookClubThemeController;
use App\Http\Controllers\Api\CardController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\ChatBotController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\GiftCertificateController;
use App\Http\Controllers\Api\GiftsController;
use App\Http\Controllers\Api\GuestSyncController;
use App\Http\Controllers\Api\NewsController;
use App\Http\Controllers\Api\PremiumController;
use App\Http\Controllers\Api\ProductsController;
use App\Http\Controllers\Api\PurchaseController;
use App\Http\Controllers\Api\ReelController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\ShareController;
use App\Http\Controllers\Api\SharedCartController;
use App\Http\Controllers\Api\ShopApiController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

// --- Ochiq qismlar ---
Route::get('user/premium/plans', [PremiumController::class, 'plans']);
Route::get('user/update_locale/{locale}', [UserController::class, 'updateLocale']);
Route::get('user/by-username/{username}', [UserController::class, 'byUsername']);
Route::get('counts', [UserController::class, 'getGlobalCounts']);
Route::get('news', [NewsController::class, 'index']);
Route::get('blog', [NewsController::class, 'blog']);
Route::prefix('products')->group(function () {
    Route::get('sellers/list', [ProductsController::class, 'sellersWithLatestProducts']);
    Route::get('sellers/by-qr/{token}', [ProductsController::class, 'sellerByQr']);
    Route::get('sellers/{sellerId}/by-code/{code}', [ProductsController::class, 'sellerProductByCode']);
    Route::get('sellers/{sellerId}/by-isbn/{isbn}', [ProductsController::class, 'sellerProductByIsbn']);
    Route::get('sellers/profile/{id}', [ProductsController::class, 'seller']);
    Route::get('sellers/profile/{id}/category-products', [ProductsController::class, 'sellerCategoryProducts']);
    Route::get('books-by-category', [ProductsController::class, 'booksByCategory']);
    Route::get('recommendation/{col}', [ProductsController::class, 'recommendation']);
    Route::get('cart-recommendation', [ProductsController::class, 'cartRecommendation']);
    Route::post('{type}/{id}/view', [ProductsController::class, 'trackView']);
    Route::get('{col}', [ProductsController::class, 'index']);
});
Route::prefix('search')->group(function () {
    Route::get('/', [SearchController::class, 'search']);
    Route::get('history', [SearchController::class, 'history']);
    Route::delete('history', [SearchController::class, 'clearHistory']);
    Route::get('suggestions', [SearchController::class, 'suggestions']);
    Route::get('trending', [SearchController::class, 'trendingSearches']);
    Route::get('recommendations', [SearchController::class, 'recommendations']);
    Route::get('categories', [SearchController::class, 'allCategories']);
    Route::get('category-sellers', [SearchController::class, 'categoryBySellers']);
});
Route::get('product_comments/{productId}/{type}', [BookClubController::class, 'getProductPosts']);
Route::get('book_club', [BookClubController::class, 'index']);
Route::get('book_club/themes', [BookClubThemeController::class, 'index']);
Route::get('book_club/themes/{slug}/posts', [BookClubController::class, 'postsByTheme']);
Route::get('book_club/comments/{post_id}', [BookClubCommentController::class, 'index']);
Route::get('book_club/comments/{post_id}/replies', [BookClubCommentController::class, 'replies']);
Route::get('book_club/get-profile', [BookClubController::class, 'get_profile']);
Route::get('book_club/profile-posts', [BookClubController::class, 'getProfilePosts']);
Route::get('cart_user', [CartController::class, 'index']);
Route::get('cart_user/count', [CartController::class, 'count']);
Route::get('shared-order/{orderId}', [SharedCartController::class, 'orderItems']);
Route::get('reels', [ReelController::class, 'index']);
Route::get('shop/info', [ShopApiController::class, 'info']);
Route::get('shop/collections', [ShopApiController::class, 'collections']);
Route::get('shop/collections/{collection}', [ShopApiController::class, 'collectionDetail']);

Route::prefix('share')->group(function () {
    Route::get('product/{id}', [ShareController::class, 'product']);
    Route::get('cart/{slug}', [SharedCartController::class, 'show']);
});

// --- Faqat Token bilan kiriladigan qismlar ---
Route::middleware('auth:user')->group(function () {
    Route::post('shared-cart/create', [SharedCartController::class, 'create']);
    Route::post('shared-cart/{slug}/add-to-cart', [SharedCartController::class, 'addToCart']);
    Route::get('shared-cart/my', [SharedCartController::class, 'myLinks']);
    Route::delete('shared-cart/{slug}', [SharedCartController::class, 'destroy']);
    Route::post('cart_user/guest-sync', [GuestSyncController::class, 'syncCart']);
    Route::post('user/favorites/guest-sync', [GuestSyncController::class, 'syncFavorites']);

    Route::get('user/premium-status', [PremiumController::class, 'status']);
    Route::post('user/subscribe-premium', [PremiumController::class, 'subscribe']);
    Route::post('user/cancel-premium', [PremiumController::class, 'cancel']);

    // To'lov tasdiqlash uchun (webhook yoki callback)
    // Route::post('payment/confirm-premium', [UserPremiumController::class, 'confirmPayment']);
    Route::get('cards', [CardController::class, 'index']);
    Route::post('cards', [CardController::class, 'store']);
    Route::post('cards/verify', [CardController::class, 'verify']);
    Route::delete('cards/{id}', [CardController::class, 'destroy']);

    Route::post('shop/mystery-box/subscribe', [ShopApiController::class, 'subscribeMysteryBox']);
    Route::post('shop/mystery-box/{id}/pay-with-card', [ShopApiController::class, 'payMysteryBoxWithSavedCard']);
    Route::get('shop/mystery-box/subscription/{id}', [ShopApiController::class, 'subscriptionDetail']);
    Route::post('shop/mystery-box/update-address', [ShopApiController::class, 'updateSubscriptionAddress']);
    Route::post('shop/collections/{collection}/checkout', [ShopApiController::class, 'checkoutCollection']);

    Route::post('products/{type}/{id}/stock-alert', [ProductsController::class, 'subscribeStockAlert']);

    Route::post('shop/gift-certificate/buy', [ShopApiController::class, 'buyCertificate']);
    Route::post('shop/gift-certificate/{id}/pay-with-card', [ShopApiController::class, 'payGiftCertificateWithSavedCard']);
    Route::post('shop/gift-certificate/activate', [ShopApiController::class, 'activateCertificate']);
    Route::get('gift-certificates', [GiftCertificateController::class, 'index']);

    Route::post('report/send', [ReportController::class, 'sendReport']);
    Route::post('/bot/ask', [ChatBotController::class, 'ask']);
    Route::get('/bot/history', [ChatBotController::class, 'history']);
    Route::post('/bot/add-to-cart', [ChatBotController::class, 'addAIToCart']);

    // Book Club yo'llari
    Route::prefix('book_club')->group(function () {
        Route::get('repost/{id}', [BookClubController::class, 'repost']);
        Route::get('delete/{id}', [BookClubController::class, 'deleteClubPost']);
        Route::post('update_post', [BookClubController::class, 'update_post']);
        Route::post('new', [BookClubController::class, 'new_post']);
        Route::post('new_comment', [BookClubController::class, 'new_book_post']);
        Route::post('like', [BookClubController::class, 'like']);
        Route::get('vote/{option}', [BookClubController::class, 'vote']);
        Route::post('{postId}/moderate/warn', [BookClubController::class, 'moderateWarn']);
        Route::post('{postId}/moderate/edit', [BookClubController::class, 'moderateEdit']);
        Route::post('{postId}/moderate/ban-user', [BookClubController::class, 'moderateBanUser']);
        Route::post('comments', [BookClubCommentController::class, 'store']);
        Route::post('like/comment', [BookClubCommentController::class, 'likeComment']);
        Route::post('comments/{comment_id}/reply', [BookClubCommentController::class, 'reply']);
        Route::delete('comments/delete/{id}', [BookClubCommentController::class, 'destroy']);
        Route::post('follow', [BookClubController::class, 'followUser']);
        Route::get('suggested-users', [BookClubController::class, 'suggestedUsers']);
        Route::get('notifications', [BookClubController::class, 'getNotifications']);
        Route::get('notifications/mark-read-bulk', [BookClubController::class, 'readNotifications']);
    });
    // Sovg'alar
    Route::prefix('gifts')->group(function () {
        Route::get('/', [GiftsController::class, 'index']);
        Route::get('seller/{sellerId}', [GiftsController::class, 'bySeller']);
        Route::post('select', [GiftsController::class, 'select_gift']);
    });
    // Sozlamalar va foydalanuvchi yo'llari
    Route::post('settings', [UserController::class, 'settings']);
    Route::get('settings/role-presets', [UserController::class, 'rolePresets']);
    Route::get('user/logout', [UserController::class, 'logout']);
    Route::get('user/devices', [UserController::class, 'devices']);
    Route::get('user/devices/{id}/delete', [UserController::class, 'logoutDevice']);
    Route::post('user/update_avatar', [UserController::class, 'upload_avatar']);
    Route::post('user/update_fcm', [UserController::class, 'updateFcm']);
    Route::post('user/update-status', [UserController::class, 'updateStatus']);
    Route::get('favourite_products', [UserController::class, 'favouriteProducts']);
    Route::get('favourite_products/{id}/add', [UserController::class, 'addFavourite']);
    Route::delete('favourite_products/clear', [UserController::class, 'clearFavorites']);

    // Xaridlar
    Route::prefix('purchase')->group(function () {
        Route::post('make', [PurchaseController::class, 'buy_book']);
        Route::get('make/final/{order_id}', [PurchaseController::class, 'finalStep']);
        Route::get('list', [PurchaseController::class, 'purchaseList']);
        Route::get('details/{order_id}', [PurchaseController::class, 'purchaseDetails']);
        Route::post('details/{order_id}/pay-with-card', [PurchaseController::class, 'payPendingOrderWithSavedCard']);
        Route::post('details/{order_id}/pay-with-split', [PurchaseController::class, 'payPendingOrderWithSplit']);
        Route::get('split-offers', [PurchaseController::class, 'splitOffers']);
        Route::post('details/{order_id}/postal-resend', [PurchaseController::class, 'createPostalResend']);
        Route::get('cancel/{orderId}', [PurchaseController::class, 'cancelOrder']);
        Route::get('cashback-history', [PurchaseController::class, 'cashbackHistory']);
        Route::get('checkPromo', [PurchaseController::class, 'checkPromo']);
        Route::get('cashback_balance', [UserController::class, 'getCashbackCount']);
        Route::get('getCartCheckoutInfo', [PurchaseController::class, 'getCartCheckoutInfo']);
    });

    // "Do'kon ichida" — QR scanlab kelgan mijoz uchun pickup xaridi
    Route::post('in-store/buy', [PurchaseController::class, 'inStoreBuy']);

    // Savatcha
    Route::prefix('cart')->group(function () {
        Route::get('check_book/{book_id}', [UserController::class, 'book_in_cart']);
        Route::post('delete', [UserController::class, 'cart_delete']);
        Route::post('plus', [UserController::class, 'cart_plus']);
        Route::get('{id}/minus', [UserController::class, 'cart_minus']);
    });
    Route::prefix('cart_user')->group(function () {
        Route::get('check/{product_id}', [CartController::class, 'check']);
        Route::get('delete/{cartId}', [CartController::class, 'remove']);
        Route::delete('delete', [CartController::class, 'batchDelete']);
        Route::post('plus', [CartController::class, 'plus']);
        Route::get('{cartId}/minus', [CartController::class, 'minus']);
    });
    Route::prefix('conversations')->group(function () {
        Route::get('/', [ChatController::class, 'getConversations']);
        Route::get('recent-contacts', [ChatController::class, 'getRecentContacts']);
        Route::get('search', [ChatController::class, 'globalSearch']);
        Route::post('start', [ChatController::class, 'startConversation']);
        Route::post('groups', [ChatController::class, 'createGroup']);
        Route::get('{id}', [ChatController::class, 'getConversationDetails']);
        Route::get('{id}/messages', [ChatController::class, 'getMessages']);
        Route::post('{id}/send', [ChatController::class, 'sendMessage']);
        Route::post('{id}/edit', [ChatController::class, 'editMessage']);
        Route::post('{id}/delete', [ChatController::class, 'deleteMessage']);
        Route::post('{id}/hide', [ChatController::class, 'hideConversation']);
        Route::post('{conversationId}/mute', [ChatController::class, 'updateGroupMute']);
        Route::post('{conversationId}/group/update', [ChatController::class, 'updateGroup']);
        Route::post('{conversationId}/read', [ChatController::class, 'markAsRead']);
        Route::post('{conversationId}/typing', [ChatController::class, 'typing']);
    });

    // Boshqa foydalanuvchi yo'llari
    Route::get('notifications', [UserController::class, 'notifications']);
    Route::get('notifications/read/{id}', [UserController::class, 'markAsRead']);
    Route::prefix('locations')->group(function () {
        Route::get('/', [UserController::class, 'my_locations']);
        Route::post('new', [UserController::class, 'new_location']);
        Route::get('select/{id}', [UserController::class, 'select_location']);
        Route::get('delete/{location}', [UserController::class, 'delete_location']);
    });
});
