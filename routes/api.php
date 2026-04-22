<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\ChatBotController;
    
use App\Http\Controllers\Api\ProjectSettingController;
use App\Http\Controllers\PushController;

use App\Http\Controllers\Api\Seller\SellerAuthController;
use App\Http\Controllers\Api\Seller\SellerController;
use App\Http\Controllers\Api\Seller\ProductController;
use App\Http\Controllers\Api\Seller\OrderController;
use App\Http\Controllers\Api\Seller\TransactionController;
use App\Http\Controllers\Api\Seller\ContestsController;
use App\Http\Controllers\Api\Seller\BanLogController;
use App\Http\Controllers\Api\Seller\HisobotController;
use App\Http\Controllers\Api\Seller\SellerStaffController;
use App\Http\Controllers\Api\Seller\SellerLocationController;
use App\Http\Controllers\Api\Seller\TargetController;
use App\Http\Controllers\Api\Seller\GiftController;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SendSmsController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\NewsController;
use App\Http\Controllers\Api\StoriesController;
use App\Http\Controllers\Api\BookClubController;
use App\Http\Controllers\Api\BookClubCommentController;
use App\Http\Controllers\Api\GiftsController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\ProductsController;
use App\Http\Controllers\Api\PurchaseController;
use App\Http\Controllers\Api\ContestController;
use App\Http\Controllers\Api\WebhookController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\PremiumController;
use App\Http\Controllers\Api\ReelController;
use App\Http\Controllers\Api\CardController;

use App\Http\Controllers\Api\Courier\CourierController;
use App\Http\Controllers\Api\Courier\CourierAuthController;
use App\Http\Controllers\Api\Courier\CourierOrderController;
use App\Http\Controllers\Api\Courier\CourierTransactionController;
use App\Http\Controllers\Api\Courier\CourierBanLogController;

// Userni autentifikatsiya qilish
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
   return $request->user();
});

// Autentifikatsiya yo'llari
Route::post('auth', [AuthController::class, 'store']);
Route::post('push-notify/send/keywbudcegvc36247c2bc012389ds', [PushController::class, 'sendPush']);
Route::post('sendSms', [SendSmsController::class, 'sendSms'])->name('api.sendSms');
Route::get('appversion/check', [ProjectSettingController::class, 'getVersions']);
Route::get('checkToken/{token}', [AuthController::class, 'checkToken']);
Route::post('hook', WebhookController::class);
Route::post('payme', [App\Http\Controllers\Api\PaymeController::class, 'index'])->middleware('payme');
Route::get('update_locale', [UserController::class, 'updateLocale']);
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


Route::prefix('seller')->group(function () {
    Route::post('login', [SellerAuthController::class, 'login']);
    Route::post('contact', [SellerAuthController::class, 'register']);
    Route::post('forgot', [SellerAuthController::class, 'forgot']);
    Route::middleware('auth:seller')->group(function () {
        Route::post('update/fcm', [SellerController::class, 'updateFcm']);
        Route::post('update/profile', [SellerController::class, 'updateProfile']);
        Route::get('update/password', [SellerController::class, 'updatePassword']);
        Route::post('update/status', [SellerController::class, 'updateStatus']);
        Route::get('devices', [SellerController::class, 'getDevices']);
        Route::post('devices/remove-device', [SellerController::class, 'removeDevice']);
        Route::get('notifications', [SellerController::class, 'notifications']);
        Route::get('notifications/count', [SellerController::class, 'notificationsCount']);
        Route::post('notifications/read', [SellerController::class, 'markAsRead']);
        Route::post('notifications/read-all', [SellerController::class, 'markAllAsRead']);

        Route::get('products/last-products', [ProductController::class, 'lastProducts']); //vaqtincha
        Route::get('products/last', [ProductController::class, 'lastProductsBS']);
        Route::get('products/products-count', [ProductController::class, 'productsCount']);
        Route::post('products/product-status', [ProductController::class, 'updateProductStatus']);
        Route::post('products/remove-product', [ProductController::class, 'removeProduct']);
        Route::post('products/create', [ProductController::class, 'createProduct']);
        Route::post('products/update', [ProductController::class, 'updateProduct']);
        Route::get('products/categories', [ProductController::class, 'getCategories']);
        Route::post('products/stationery/create', [ProductController::class, 'createStationery']);
        Route::post('products/stationery/update', [ProductController::class, 'updateStationery']);
        Route::get('products/stationery/categories', [ProductController::class, 'getStationeryCategories']);
        Route::get('products/stationery/categories/{category_id}/tags', [ProductController::class, 'getStationeryTagsForCategory']);
        Route::get('products/categories/{category_id}/tags', [ProductController::class, 'getTagsForCategory']);
        Route::get('products/stat/{product}', [ProductController::class, 'productStatistics']);
        Route::get('gifts/list', [GiftController::class, 'list']);
        Route::post('gifts/create', [GiftController::class, 'create']);
        Route::post('gifts/update/{gift_id}', [GiftController::class, 'update']);
        Route::get('orders/last-orders', [OrderController::class, 'lastOrders']);
        Route::post('orders/view-order', [OrderController::class, 'viewOrder']);
        Route::get('orders/orders-count', [OrderController::class, 'ordersCount']);
        Route::get('orders/accept/{id}', [OrderController::class, 'acceptOrder']);
        Route::get('orders/toCourier/{qr}', [OrderController::class, 'toCourier']);
        
        Route::get('transactions/latest', [TransactionController::class, 'getTransactions']);
        Route::get('transactions/withdrawal', [TransactionController::class, 'requestWithdrawal']);
        Route::get('transactions/count', [TransactionController::class, 'getTotal']);
        Route::get('transactions/cancel/{transactionId}', [TransactionController::class, 'cancelTransaction']);
        
    Route::get('target/initial-data', [TargetController::class, 'getInitialTargetData']);
    Route::post('target/store-forum-post', [TargetController::class, 'storeForumPostAd']);
    Route::post('target/store-recommendation', [TargetController::class, 'storeRecommendationAd']);
    Route::post('target/store-home-page-banner', [TargetController::class, 'storeHomePageBannerAd']);
        
        Route::get('contests', [ContestsController::class, 'getContests']);
        Route::get('contests/{contestId}', [ContestsController::class, 'getContestDetails']);
        Route::get('contests/{contestId}/download-participants', [ContestsController::class, 'downloadParticipants']);
        Route::post('contests', [ContestsController::class, 'createContest']);
        Route::post('contests/{contestId}/end', [ContestsController::class, 'endContest']);
        Route::get('contests/{contestId}', [ContestsController::class, 'deleteContest']);
    
        Route::get('ban-logs', [BanLogController::class, 'index'])->name('index_banlog');
        Route::get('ban-logs/{id}/read', [BanLogController::class, 'markAsRead'])->name('markAsRead_banlog');
        Route::get('ban-logs/counts', [BanLogController::class, 'getCounts'])->name('counts_banlog');
        
        Route::get('statistics/data', [HisobotController::class, 'index'])->name('sales_data_index');
        Route::get('statistics/sales_chart_data', [HisobotController::class, 'getSalesStats'])->name('saleschart_data');
        
        Route::get('staff', [SellerStaffController::class, 'index']);
        Route::post('staff', [SellerStaffController::class, 'store']);
        Route::get('staff/{id}', [SellerStaffController::class, 'show']);
        Route::post('staff/{id}', [SellerStaffController::class, 'update']);
        Route::get('staff/{id}/delete', [SellerStaffController::class, 'destroy']);
        Route::get('staff/{id}/getpassword', [SellerStaffController::class, 'getPasswordSms']);
        
        Route::get('locations', [SellerLocationController::class, 'index']);
        Route::post('locations', [SellerLocationController::class, 'store']);
        Route::post('locations/{id}', [SellerLocationController::class, 'update']);
        Route::get('locations/{id}/delete', [SellerLocationController::class, 'destroy']);
        Route::get('locations/{id}/main', [SellerLocationController::class, 'makeMainLocation']);
});
});

// Kuryerlar ilovasi uchun yo'llar
Route::prefix('courier')->group(function () {
    Route::post('auth', [CourierAuthController::class, 'auth']);
    Route::post('contact-request', [CourierAuthController::class, 'contactRequest']);
    Route::get('devices', [CourierController::class, 'getDevices']);
    Route::get('update/password', [CourierController::class, 'updatePassword']);
    Route::post('update/fcm', [CourierController::class, 'updateFcm']);
    Route::get('notifications', [CourierController::class, 'notifications']);
    Route::get('notifications/count', [CourierController::class, 'notificationsCount']);
    Route::post('notifications/read', [CourierController::class, 'markAsRead']);
    Route::post('notifications/read-all', [CourierController::class, 'markAllAsRead']);
    
    Route::get('transactions/latest', [CourierTransactionController::class, 'getTransactions']);
    Route::get('transactions/withdrawal', [CourierTransactionController::class, 'requestWithdrawal']);
    Route::get('transactions/count', [CourierTransactionController::class, 'getTotal']);
    Route::get('transactions/cancel/{transactionId}', [CourierTransactionController::class, 'cancelTransaction']);
    
    Route::get('ban-logs', [CourierBanLogController::class, 'index'])->name('index_banlog');
    Route::get('ban-logs/{id}/read', [CourierBanLogController::class, 'markAsRead'])->name('markAsRead_banlog');
    Route::get('ban-logs/counts', [CourierBanLogController::class, 'getCounts'])->name('counts_banlog');
    
    Route::get('orders/available', [CourierOrderController::class, 'getAvailableOrders']);
    Route::get('orders/confirm/{id}', [CourierOrderController::class, 'confirmOrder']);
    Route::get('orders/view/{id}', [CourierOrderController::class, 'showOrder']);
    Route::get('orders/my', [CourierOrderController::class, 'myOrders']);
    Route::get('orders/toCustomer/{qr}', [CourierOrderController::class, 'toCustomer']);
    
    Route::get('delivery/{col}', [CourierController::class, 'index']);
    Route::get('profile', [CourierController::class, 'my_data']);
    Route::get('scannedCode/{qr_code}', [CourierController::class, 'qr_code']);
    Route::get('takeOrder/{id}', [CourierController::class, 'product_taked']);
    Route::get('deliveredOrder/{qr_code}', [CourierController::class, 'product_delivered']);
});

// Token orqali kiriladigan yo'llar
Route::group(['middleware' => 'token'], function () {
    Route::get('user/premium/plans',     [PremiumController::class, 'plans']);
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


Route::prefix('v1')->group(function () {
    Route::prefix('client')->group(base_path('routes/api_client.php')); 
    Route::prefix('kitobchi')->group(base_path('routes/api_user.php')); 
    Route::prefix('seller')->group(base_path('routes/api_seller.php'));
    Route::prefix('courier')->group(base_path('routes/api_courier.php'));

});
