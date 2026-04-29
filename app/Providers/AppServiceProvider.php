<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Relations\Relation;
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
}
