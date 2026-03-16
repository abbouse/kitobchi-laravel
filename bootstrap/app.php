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

        // ── 0. AI limit reset (00:05 — MySQL tayyor bo'lguncha 5 daqiqa) ─────
        // ❌ 00:00 edi — server/MySQL restart vaqtiga to'g'ri kelib xato berardi
        $schedule->command('ai:daily-reset')
            ->dailyAt('00:05')
            ->timezone('Asia/Tashkent')
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/ai_reset.log'));

        // ── 1. To'lanmagan buyurtmalarga eslatma (har 10 daqiqa) ─────────────
        $schedule->command('orders:remind-unpaid')
            ->everyTenMinutes()
            ->timezone('Asia/Tashkent')
            ->appendOutputTo(storage_path('logs/remind_unpaid.log'));

        // ── 2. To'lanmagan buyurtmalarni bekor qilish ────────────────────────
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
        $schedule->command('users:book-remind')
            ->weeklyOn(2, '10:00')
            ->timezone('Asia/Tashkent')
            ->appendOutputTo(storage_path('logs/book_remind.log'));

        $schedule->command('users:book-remind')
            ->weeklyOn(4, '17:00')
            ->timezone('Asia/Tashkent')
            ->appendOutputTo(storage_path('logs/book_remind.log'));

        $schedule->command('users:book-remind')
            ->weeklyOn(6, '12:00')
            ->timezone('Asia/Tashkent')
            ->appendOutputTo(storage_path('logs/book_remind.log'));

        // ── 5. Backup ─────────────────────────────────────────────────────────
        // ❌ backup:clean 01:10 edi — MySQL restart zonasiga yaqin
        $schedule->command('backup:clean')
            ->dailyAt('01:15')
            ->timezone('Asia/Tashkent');

        // backup:run o'zgarmadi — 02:10 xavfsiz vaqt
        $schedule->command('backup:run')
            ->dailyAt('02:10')
            ->timezone('Asia/Tashkent');

        // ── 6. Vektorlarni qayta qurish ───────────────────────────────────────
        // ❌ 02:00 edi — backup:run bilan overlap qilardi (ikkalasi og'ir operatsiya)
        // ✅ 02:30 — backup:run tugagandan keyin boshlanadi
        $schedule->command('vectors:rebuild --force')
            ->weeklyOn(0, '02:30')
            ->timezone('Asia/Tashkent')
            ->appendOutputTo(storage_path('logs/vector_rebuild.log'));

        // ── 7. Queue batch tozalash ───────────────────────────────────────────
        // ❌ daily() edi — vaqt belgilanmagan, Laravel uni 00:00 da ishlatadi
        // ✅ 03:00 — barcha og'ir operatsiyalar tugagandan keyin
        $schedule->command('queue:prune-batches --hours=24')
            ->dailyAt('03:00')
            ->timezone('Asia/Tashkent');

    })->create();