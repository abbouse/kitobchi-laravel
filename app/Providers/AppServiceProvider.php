<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Relations\Relation;
use App\Models\Sold;
use App\Observers\SoldObserver;

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
}
}
