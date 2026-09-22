<?php

namespace App\Providers;

use App\Models\BookClub;
use App\Models\BookClubComment;
use App\Models\BookClubCommentLike;
use App\Models\BookClubLikes;
use App\Models\Books;
use App\Models\CourierOrder;
use App\Models\FavouriteProducts;
use App\Models\MyCart;
use App\Models\Sold;
use App\Models\Stationery;
use App\Models\StationeryVariant;
use App\Observers\BookStockObserver;
use App\Observers\CourierOrderObserver;
use App\Observers\ProductModerationObserver;
use App\Observers\ProductObserver;
use App\Observers\ReadingInsightObserver;
use App\Observers\ReadingIntelTasteCacheObserver;
use App\Observers\SoldObserver;
use App\Observers\StationeryStockObserver;
use App\Observers\StationeryVariantStockObserver;
use App\Observers\UserProgressObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // BranchStockService — singleton: request davomida totalCache saqlanadi,
        // shuning uchun warmTotals() bilan iliqlangan qiymatlar model accessorlariga
        // ko'rinadi (stock N+1 oldi olinadi).
        $this->app->singleton(\App\Services\BranchStockService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureRateLimiters();

        Relation::morphMap([
            'book' => \App\Models\Books::class,
            'stationery' => \App\Models\Stationery::class,
        ]);

        Books::observe(ProductModerationObserver::class);
        Books::observe(ProductObserver::class);
        Books::observe(BookStockObserver::class);
        Books::observe(ReadingInsightObserver::class);
        Books::observe(\App\Observers\BooksObserver::class);
        // GLOBAL KATALOG: kartaga ulash + buy box (ro'yxatlarda kitob bitta marta)
        Books::observe(\App\Observers\CatalogOfferObserver::class);
        \App\Models\BranchStock::saved(fn ($row) => app(\App\Observers\CatalogOfferObserver::class)->stockSaved($row));
        \App\Models\BranchStock::deleted(fn ($row) => app(\App\Observers\CatalogOfferObserver::class)->stockDeleted($row));
        \App\Models\Seller::updated(fn ($seller) => app(\App\Observers\CatalogOfferObserver::class)->sellerUpdated($seller));
        Stationery::observe(ProductModerationObserver::class);
        Stationery::observe(ProductObserver::class);
        Stationery::observe(StationeryStockObserver::class);
        Stationery::observe(ReadingInsightObserver::class);
        Stationery::observe(\App\Observers\StationeryObserver::class);
        StationeryVariant::observe(StationeryVariantStockObserver::class);
        Sold::observe(SoldObserver::class);

        // Yangi `pending` CourierOrder paydo bo'lganda barcha kuryerlarga FCM yuboramiz
        CourierOrder::observe(CourierOrderObserver::class);
        BookClub::observe(UserProgressObserver::class);
        BookClubComment::observe(UserProgressObserver::class);
        BookClubLikes::observe(UserProgressObserver::class);
        BookClubCommentLike::observe(UserProgressObserver::class);
        FavouriteProducts::observe(UserProgressObserver::class);

        // Reading Intelligence — savat/sevimlilar o'zgarganda did-vektor
        // keshini darhol tozalaydi (1 soat kutish o'rniga).
        FavouriteProducts::observe(ReadingIntelTasteCacheObserver::class);
        MyCart::observe(ReadingIntelTasteCacheObserver::class);

        // Veb marketpleys layout'i (header'dagi Sevimlilar badge) har bir
        // sahifada shu sonni ko'rsatadi — har bir controller'da qo'lda
        // hisoblab yubormaslik uchun View Composer orqali avtomatik uzatiladi.
        \Illuminate\Support\Facades\View::composer('layouts.marketplace', function ($view) {
            $user = \Illuminate\Support\Facades\Auth::user();
            // MUHIM: xom son emas — faqat hozir ko'rinadigan (bloklangan
            // do'konga tegishli bo'lmagan) sevimlilar sanaladi, aks holda
            // header badge foydalanuvchi ko'ra olmaydigan mahsulotlarni
            // ham qo'shib yuboradi (bir joyda: ProductVisibilityScope).
            $view->with('kcFavCount', $user
                ? \App\Support\ProductVisibilityScope::visibleFavouriteCount($user->id)
                : 0);
        });
    }

    private function configureRateLimiters(): void
    {
        RateLimiter::for('auth-user', function (Request $request) {
            $phone = preg_replace('/\D+/', '', (string) $request->input('phone_number'));

            return Limit::perMinute(8)->by(($phone ?: 'guest').'|'.$request->ip());
        });

        RateLimiter::for('auth-telegram', function (Request $request) {
            $deviceId = (string) $request->input('device_id', 'unknown-device');

            return Limit::perMinute(12)->by($deviceId.'|'.$request->ip());
        });

        RateLimiter::for('send-sms', function (Request $request) {
            $phone = preg_replace('/\D+/', '', (string) $request->input('phone_number'));

            return Limit::perMinute(5)->by(($phone ?: 'guest').'|'.$request->ip());
        });

        RateLimiter::for('auth-seller', function (Request $request) {
            $phone = preg_replace('/\D+/', '', (string) $request->input('phone_number'));

            return Limit::perMinute(6)->by(($phone ?: 'guest').'|'.$request->ip());
        });

        RateLimiter::for('auth-courier', function (Request $request) {
            $phone = preg_replace('/\D+/', '', (string) $request->input('phone_number'));

            return Limit::perMinute(6)->by(($phone ?: 'guest').'|'.$request->ip());
        });

        RateLimiter::for('auth-panel', function (Request $request) {
            $email = mb_strtolower(trim((string) $request->input('email', 'guest')));

            return Limit::perMinute(5)->by($email.'|'.$request->ip());
        });

        RateLimiter::for('password-recovery', function (Request $request) {
            $phone = preg_replace('/\D+/', '', (string) $request->input('phone_number'));

            return Limit::perMinute(3)->by(($phone ?: 'guest').'|'.$request->ip());
        });

        RateLimiter::for('registration-light', function (Request $request) {
            $phone = preg_replace('/\D+/', '', (string) $request->input('phone_number'));

            return Limit::perMinute(3)->by(($phone ?: 'guest').'|'.$request->ip());
        });
    }
}
