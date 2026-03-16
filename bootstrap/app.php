<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        // commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withBroadcasting(
        '/broadcasting/auth',
        ['middleware' => ['api', 'auth:user']],
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
        'api.client' => \App\Http\Middleware\VerifyApiClient::class,
        'payme' => \App\Http\Middleware\PaymeMiddleware::class,
        ]);

        // auth:user guruhidagi har bir so'rovda last_seen_at ni yangilaydi
        $middleware->appendToGroup('api', \App\Http\Middleware\UpdateLastSeen::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->withSchedule(function ($schedule) {
    // 01:00 dagi restartdan qochish uchun 01:05 da ishga tushiramiz
    $schedule->command('orders:cancel-unpaid')
        ->hourlyAt(5) 
        ->appendOutputTo(storage_path('logs/unpaid_orders_cancel.log'));

    $schedule->command('queue:prune-batches --hours=24')->daily();

    // Backup komandalarini ham restart vaqtidan (01:00 va 02:00) 10 daqiqa keyinga suramiz
    $schedule->command('backup:clean')->daily()->at('01:10');
    $schedule->command('backup:run')->daily()->at('02:10');
})->create();
