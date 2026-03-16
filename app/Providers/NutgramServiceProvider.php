<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use SergiX44\Nutgram\Nutgram;

class NutgramServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Artisan command uchun (webhook set/delete)
        $this->app->singleton(Nutgram::class, function () {
            return new Nutgram(config('nutgram.token'));
            // routes bu yerda YUKLANMAYDI — faqat webhook controller da yuklanadi
        });
    }
}