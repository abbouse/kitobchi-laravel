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
            'auth.panel' => \App\Http\Middleware\AuthenticatePanel::class,
            'auth.hubdesk' => \App\Http\Middleware\AuthenticateHubDesk::class,
            'panel.permission' => \App\Http\Middleware\PanelPermission::class,
        ]);

        $middleware->appendToGroup('api', \App\Http\Middleware\UpdateLastSeen::class);
        $middleware->appendToGroup('web', \App\Http\Middleware\SecurityHeaders::class);
        $middleware->appendToGroup('api', \App\Http\Middleware\SecurityHeaders::class);

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

        $schedule->command('cashback:release-pending')
            ->everyTenMinutes()->timezone($tz)->withoutOverlapping();

        foreach ([1, 3, 5] as $cartReminderDay) {
            $schedule->command('cart:remind --time=morning')
                ->weeklyOn($cartReminderDay, '08:00')->timezone($tz);

            $schedule->command('cart:remind --time=afternoon')
                ->weeklyOn($cartReminderDay, '13:00')->timezone($tz);

            $schedule->command('cart:remind --time=evening')
                ->weeklyOn($cartReminderDay, '19:00')->timezone($tz);
        }

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

        $schedule->command('vectors:rebuild --type=all --limit=120')
            ->everyTenMinutes()
            ->timezone($tz)
            ->withoutOverlapping(9)
            ->runInBackground();

        $schedule->command('queue:prune-batches --hours=24')
            ->dailyAt('03:00')->timezone($tz);

        // ── Gift sertifikatlar: muddati o'tganlarni bekor qilish ─────
        $schedule->command('gifts:expire')
            ->dailyAt('02:00')->timezone($tz)->withoutOverlapping();

        // ── Mystery Box: navbat va monthly shipment queue tekshiruvi ──
        $schedule->command('mystery-box:check-deliveries')
            ->everyTenMinutes()->timezone($tz)->withoutOverlapping();

        // ── Pending gift/mystery payments: 30 daqiqadan keyin bekor qilish ──
        $schedule->command('shop:cleanup-pending-special-payments --minutes=30')
            ->everyTenMinutes()
            ->timezone($tz)
            ->withoutOverlapping();

        // ── Book Club AI baholash — kuniga 2 marta ───────────────────
        $schedule->command('openai:score-book-club-content')
            ->twiceDaily(9, 21)
            ->timezone($tz)
            ->withoutOverlapping();

        // ── Book Club komment moderatsiyasi — haftalik ───────────────
        $schedule->command('openai:moderate-book-club-comments --all=1')
            ->weeklyOn(0, '03:30')
            ->timezone($tz)
            ->withoutOverlapping();

        $schedule->command('parser:sync-book-uz')
            ->weeklyOn(0, '05:10')
            ->timezone($tz)
            ->withoutOverlapping()
            ->runInBackground();

        // ── Seller reputatsiyasi — har tong qayta hisoblanadi ─────────
        $schedule->command('sellers:recalculate-reputation')
            ->dailyAt('04:50')
            ->timezone($tz)
            ->withoutOverlapping();

        // ── User reputatsiyasi va COD ishonchi — har tong qayta hisob ──────
        $schedule->command('users:recalculate-reputation')
            ->dailyAt('05:05')
            ->timezone($tz)
            ->withoutOverlapping();

        $schedule->command('products:send-review-prompts')
            ->twiceDaily(11, 18)
            ->timezone($tz)
            ->withoutOverlapping();

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
