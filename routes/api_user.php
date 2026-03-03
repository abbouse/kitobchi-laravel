<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\{UserController, NewsController, ProductsController, PurchaseController, SearchController, CartController, BookClubController, BookClubCommentController, GiftsController, ContestController, ReelController, PremiumController, CardController, ChatController, ReportController, ChatBotController};

// --- Ochiq qismlar ---
Route::post('payme', [App\Http\Controllers\Api\PaymeController::class, 'index'])->middleware('payme');
//Route::get('checkToken/{token}', [AuthController::class, 'checkToken']);
Route::get('user/premium/plans',     [PremiumController::class, 'plans']);
Route::get('user/update_locale/{locale}', [UserController::class, 'updateLocale']);
Route::get('counts', [UserController::class, 'getGlobalCounts']);
Route::get('news', [NewsController::class, 'index']);
Route::get('blog', [NewsController::class, 'blog']);
Route::prefix('products')->group(function () {
    Route::get('{col}', [ProductsController::class, 'index']);
    Route::get('sellers/list', [ProductsController::class, 'sellersWithLatestProducts']);
    Route::get('sellers/profile/{id}/{page}', [ProductsController::class, 'seller']);
    Route::get('recommendation/{col}', [ProductsController::class, 'recommendation']);
});
Route::prefix('search')->group(function () {
    Route::get('/', [SearchController::class, 'search']);
    Route::get('categories', [SearchController::class, 'allCategories']);
    Route::get('category/{cat_id}/{type}', [SearchController::class, 'category']);
});
Route::get('product_comments/{productId}/{type}', [BookClubController::class, 'getProductPosts']);
Route::get('book_club', [BookClubController::class, 'index']);
Route::get('book_club/comments/{post_id}', [BookClubCommentController::class, 'index']);
Route::get('book_club/comments/{post_id}/replies', [BookClubCommentController::class, 'replies']);
Route::get('cart_user', [CartController::class, 'index']);
Route::get('cart_user/count', [CartController::class, 'count']);
Route::get('contest/{sellerId}', [ContestController::class, 'getContest']);
Route::get('reels', [ReelController::class, 'index']);

// --- Faqat Token bilan kiriladigan qismlar ---
Route::middleware('auth:user')->group(function () {
    Route::get('user/premium-status',   [PremiumController::class, 'status']);
    Route::post('user/subscribe-premium', [PremiumController::class, 'subscribe']);
    Route::post('user/cancel-premium',  [PremiumController::class, 'cancel']);
    
    // To'lov tasdiqlash uchun (webhook yoki callback)
    //Route::post('payment/confirm-premium', [UserPremiumController::class, 'confirmPayment']);
    Route::get('cards', [CardController::class, 'index']);
    Route::post('cards', [CardController::class, 'store']);
    Route::post('cards/verify', [CardController::class, 'verify']);
    Route::delete('cards/{id}', [CardController::class, 'destroy']);
    
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
        Route::get('get-profile', [BookClubController::class, 'get_profile']);
        Route::post('like', [BookClubController::class, 'like']);
        Route::get('vote/{option}', [BookClubController::class, 'vote']);
        Route::post('comments', [BookClubCommentController::class, 'store']);
        Route::post('like/comment', [BookClubCommentController::class, 'likeComment']);
        Route::post('comments/{comment_id}/reply', [BookClubCommentController::class, 'reply']);
        Route::delete('comments/delete/{id}', [BookClubCommentController::class, 'destroy']);
        Route::post('follow', [BookClubController::class, 'followUser']);
    Route::get('notifications', [BookClubController::class, 'getNotifications']);
    Route::get('notifications/mark-read-bulk', [BookClubController::class, 'readNotifications']);
    });
    // Sovg'alar
    Route::prefix('gifts')->group(function () {
        Route::post('select', [GiftsController::class, 'select_gift']);
        Route::get('{col}', [GiftsController::class, 'index']);
    });
    // Sozlamalar va foydalanuvchi yo'llari
    Route::post('settings', [UserController::class, 'settings']);
    Route::get('user/logout', [UserController::class, 'logout']);
    Route::get('user/devices', [UserController::class, 'devices']);
    Route::get('user/devices/{id}/delete', [UserController::class, 'logoutDevice']);
    Route::post('user/update_avatar', [UserController::class, 'upload_avatar']);
    Route::post('user/update_fcm', [UserController::class, 'updateFcm']);
    Route::post('user/update-status', [UserController::class, 'updateStatus']);
    Route::get('favourite_products', [UserController::class, 'favouriteProducts']);
    Route::get('favourite_products/{id}/add', [UserController::class, 'addFavourite']);

    // Xaridlar
    Route::prefix('purchase')->group(function () {
        Route::post('make', [PurchaseController::class, 'buy_book']);
        Route::get('make/final/{order_id}', [PurchaseController::class, 'finalStep']);
        Route::get('list', [PurchaseController::class, 'purchaseList']);
        Route::get('details/{order_id}', [PurchaseController::class, 'purchaseDetails']);
        Route::get('cancel/{orderId}', [PurchaseController::class, 'cancelOrder']);
        Route::get('checkPromo', [PurchaseController::class, 'checkPromo']);
        Route::get('cashback_balance', [UserController::class, 'getCashbackCount']); 
        Route::get('getCartCheckoutInfo', [PurchaseController::class, 'getCartCheckoutInfo']); 
    });

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
        Route::post('plus', [CartController::class, 'plus']);
        Route::get('{cartId}/minus', [CartController::class, 'minus']);
    });
    Route::prefix('conversations')->group(function () {
    Route::get('/', [ChatController::class, 'getConversations']);
    Route::get('{id}/messages', [ChatController::class, 'getMessages']);
    Route::post('{id}/send', [ChatController::class, 'sendMessage']);
    Route::post('{id}/edit', [ChatController::class, 'editMessage']);
    Route::post('{id}/delete', [ChatController::class, 'deleteMessage']);
    Route::post('{id}/hide', [ChatController::class, 'hideConversation']);
    Route::post('start', [ChatController::class, 'startConversation']);
    Route::post('{conversationId}/read', [ChatController::class, 'markAsRead']);
    Route::get('recent-contacts', [ChatController::class, 'getRecentContacts']);
    Route::get('search', [ChatController::class, 'globalSearch']);
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
    Route::get('contest/{contest}/join', [ContestController::class, 'joinToContest']);
    Route::get('contest/my/list', [ContestController::class, 'getUserContests']);
});