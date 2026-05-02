<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withBroadcasting(
        '/broadcasting/auth',
        ['middleware' => ['api', 'auth:user,seller,courier']],
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            \App\Http\Middleware\SetLandingLocale::class,
        ]);

        $middleware->alias([
            'api.client' => \App\Http\Middleware\VerifyApiClient::class,
            'payme' => \App\Http\Middleware\PaymeMiddleware::class,
            'auth.panel' => \App\Http\Middleware\AuthenticatePanel::class,
            'panel.permission' => \App\Http\Middleware\PanelPermission::class,
        ]);

        $middleware->appendToGroup('api', \App\Http\Middleware\UpdateLastSeen::class);

        // API javoblarini lokalizatsiya: `lang=` query, `X-App-Locale` yoki
        // `Accept-Language` headerini hisobga oladi. Default — uz.
        $middleware->prependToGroup('api', \App\Http\Middleware\SetApiLocale::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->withSchedule(function ($schedule) {

        $tz = 'Asia/Tashkent';

        $schedule->command('ai:daily-reset')
            ->dailyAt('00:05')->timezone($tz)->withoutOverlapping()->runInBackground();

        $schedule->command('orders:remind-unpaid')
            ->everyTenMinutes()->timezone($tz);

        $schedule->command('orders:cancel-unpaid')
            ->everyFiveMinutes()->timezone($tz);

        $schedule->command('cart:remind --time=morning')
            ->dailyAt('08:00')->timezone($tz);

        $schedule->command('cart:remind --time=afternoon')
            ->dailyAt('13:00')->timezone($tz);

        $schedule->command('cart:remind --time=evening')
            ->dailyAt('19:00')->timezone($tz);

        $schedule->command('users:book-remind')
            ->weeklyOn(2, '10:00')->timezone($tz);

        $schedule->command('users:book-remind')
            ->weeklyOn(4, '17:00')->timezone($tz);

        $schedule->command('users:book-remind')
            ->weeklyOn(6, '12:00')->timezone($tz);

        $schedule->command('backup:clean')
            ->dailyAt('01:15')->timezone($tz);

        $schedule->command('backup:run')
            ->dailyAt('02:10')->timezone($tz);

        $schedule->command('vectors:rebuild --force')
            ->weeklyOn(0, '02:30')->timezone($tz);

        $schedule->command('queue:prune-batches --hours=24')
            ->dailyAt('03:00')->timezone($tz);

        // ── Gift sertifikatlar: muddati o'tganlarni bekor qilish ─────
        $schedule->command('gifts:expire')
            ->dailyAt('02:00')->timezone($tz)->withoutOverlapping();

        // ── Mystery Box: navbat tekshiruvi ────────────────────────────
        $schedule->command('mystery-box:check-deliveries')
            ->dailyAt('08:30')->timezone($tz)->withoutOverlapping();

        // ── Kangaroo: kitob/kanstovar moderatsiya + book club UGC ─────
        $schedule->command('kangaroo:sync-content-moderation')
            ->everyThirtyMinutes()
            ->timezone($tz)
            ->withoutOverlapping(25);

        $schedule->command('seller-premium:sync-renewals')
            ->hourly()
            ->timezone($tz)
            ->withoutOverlapping();

        // ── Phase 3: Kuryer bonus tizimi ──────────────────────────────
        // Har minutda surge bonusini oshirib boradi va SLA-5min ogohlantirish
        // push xabarlarini yuboradi. withoutOverlapping(2) — agar oldingi run
        // 1 minutdan oshib ketsa ham yangi run boshlanmaydi (2 min lock).
        $schedule->command('courier:refresh-bonus')
            ->everyMinute()
            ->timezone($tz)
            ->withoutOverlapping(2)
            ->runInBackground();

    })->create();
