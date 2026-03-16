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
            'payme'      => \App\Http\Middleware\PaymeMiddleware::class,
        ]);

        // auth:user guruhidagi har bir so'rovda last_seen_at ni yangilaydi
        $middleware->appendToGroup('api', \App\Http\Middleware\UpdateLastSeen::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->withSchedule(function ($schedule) {

        // ── 1. To'lanmagan buyurtmalarga eslatma (har 10 daqiqa) ─────────────
        $schedule->command('orders:remind-unpaid')
            ->everyTenMinutes()
            ->timezone('Asia/Tashkent')
            ->appendOutputTo(storage_path('logs/remind_unpaid.log'));

        // ── 2. To'lanmagan buyurtmalarni bekor qilish ────────────────────────
        //  everyFiveMinutes — 1 soatdan o'tgan buyurtmalar max 5 daqiqa kutadi
        $schedule->command('orders:cancel-unpaid')
            ->everyFiveMinutes()
            ->timezone('Asia/Tashkent')
            ->appendOutputTo(storage_path('logs/cancel_unpaid.log'));

        // ── 3. Savatcha eslatmalari (kuniga 3 marta) ─────────────────────────
        $schedule->command('cart:remind --time=morning')
            ->dailyAt('08:00')
            ->timezone('Asia/Tashkent')
            ->appendOutputTo(storage_path('logs/cart_remind.log'));

        $schedule->command('cart:remind --time=afternoon')
            ->dailyAt('13:00')
            ->timezone('Asia/Tashkent')
            ->appendOutputTo(storage_path('logs/cart_remind.log'));

        $schedule->command('cart:remind --time=evening')
            ->dailyAt('19:00')
            ->timezone('Asia/Tashkent')
            ->appendOutputTo(storage_path('logs/cart_remind.log'));

        // ── 4. Haftalik kitob o'qish eslatmasi (sesh, pay, shan) ─────────────
        // Seshamba 10:00
        $schedule->command('users:book-remind')
            ->weeklyOn(2, '10:00')
            ->timezone('Asia/Tashkent')
            ->appendOutputTo(storage_path('logs/book_remind.log'));
        // Payshanba 17:00
        $schedule->command('users:book-remind')
            ->weeklyOn(4, '17:00')
            ->timezone('Asia/Tashkent')
            ->appendOutputTo(storage_path('logs/book_remind.log'));
        // Shanba 12:00
        $schedule->command('users:book-remind')
            ->weeklyOn(6, '12:00')
            ->timezone('Asia/Tashkent')
            ->appendOutputTo(storage_path('logs/book_remind.log'));

        // ── 5. Vektorlarni qayta qurish (yakshanba, 02:00) ───────────────────
        $schedule->command('vectors:rebuild --force')
            ->weeklyOn(0, '02:00')
            ->timezone('Asia/Tashkent')
            ->appendOutputTo(storage_path('logs/vector_rebuild.log'));

        // ── 5. Queue batch tozalash ───────────────────────────────────────────
        $schedule->command('queue:prune-batches --hours=24')
            ->daily()
            ->timezone('Asia/Tashkent');

        // ── 6. Backup ─────────────────────────────────────────────────────────
        $schedule->command('backup:clean')
            ->dailyAt('01:10')
            ->timezone('Asia/Tashkent');

        $schedule->command('backup:run')
            ->dailyAt('02:10')
            ->timezone('Asia/Tashkent');

    })->create();