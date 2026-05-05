<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use App\Models\Sold;
use App\Models\CourierOrder;
use App\Models\BookClub;
use App\Models\BookClubComment;
use App\Models\BookClubLikes;
use App\Models\BookClubCommentLike;
use App\Models\FavouriteProducts;
use App\Observers\SoldObserver;
use App\Observers\CourierOrderObserver;
use App\Observers\UserProgressObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
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
        Sold::observe(SoldObserver::class);

        // Yangi `pending` CourierOrder paydo bo'lganda barcha kuryerlarga FCM yuboramiz
        CourierOrder::observe(CourierOrderObserver::class);
        BookClub::observe(UserProgressObserver::class);
        BookClubComment::observe(UserProgressObserver::class);
        BookClubLikes::observe(UserProgressObserver::class);
        BookClubCommentLike::observe(UserProgressObserver::class);
        FavouriteProducts::observe(UserProgressObserver::class);
    }

    private function configureRateLimiters(): void
    {
        RateLimiter::for('auth-user', function (Request $request) {
            $phone = preg_replace('/\D+/', '', (string) $request->input('phone_number'));

            return Limit::perMinute(8)->by(($phone ?: 'guest') . '|' . $request->ip());
        });

        RateLimiter::for('auth-telegram', function (Request $request) {
            $deviceId = (string) $request->input('device_id', 'unknown-device');

            return Limit::perMinute(12)->by($deviceId . '|' . $request->ip());
        });

        RateLimiter::for('send-sms', function (Request $request) {
            $phone = preg_replace('/\D+/', '', (string) $request->input('phone_number'));

            return Limit::perMinute(5)->by(($phone ?: 'guest') . '|' . $request->ip());
        });

        RateLimiter::for('auth-seller', function (Request $request) {
            $phone = preg_replace('/\D+/', '', (string) $request->input('phone_number'));

            return Limit::perMinute(6)->by(($phone ?: 'guest') . '|' . $request->ip());
        });

        RateLimiter::for('auth-courier', function (Request $request) {
            $phone = preg_replace('/\D+/', '', (string) $request->input('phone_number'));

            return Limit::perMinute(6)->by(($phone ?: 'guest') . '|' . $request->ip());
        });

        RateLimiter::for('auth-panel', function (Request $request) {
            $email = mb_strtolower(trim((string) $request->input('email', 'guest')));

            return Limit::perMinute(5)->by($email . '|' . $request->ip());
        });

        RateLimiter::for('password-recovery', function (Request $request) {
            $phone = preg_replace('/\D+/', '', (string) $request->input('phone_number'));

            return Limit::perMinute(3)->by(($phone ?: 'guest') . '|' . $request->ip());
        });

        RateLimiter::for('registration-light', function (Request $request) {
            $phone = preg_replace('/\D+/', '', (string) $request->input('phone_number'));

            return Limit::perMinute(3)->by(($phone ?: 'guest') . '|' . $request->ip());
        });
    }
}
