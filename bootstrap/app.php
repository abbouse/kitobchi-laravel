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
            'admin.audit' => \App\Http\Middleware\AdminAuditLogger::class,
            'auth.hubdesk' => \App\Http\Middleware\AuthenticateHubDesk::class,
            'panel.permission' => \App\Http\Middleware\PanelPermission::class,
        ]);

        $middleware->appendToGroup('api', \App\Http\Middleware\UpdateLastSeen::class);
        $middleware->appendToGroup('web', \App\Http\Middleware\SecurityHeaders::class);
        $middleware->appendToGroup('web', \App\Http\Middleware\HandleInertiaRequests::class);
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

        // ── Avvalgi xaridlarga o'xshash / eng ko'p sotilgan mahsulot
        // tavsiyasi (kitob YOKI kanselyariya — foydalanuvchining o'ziga
        // qarab) — HAR 2 HAFTADA 1 MARTA yetarli (tez-tez yuborilsa
        // push charchashi — notification fatigue — xavfi bor). Laravel
        // scheduler'da to'g'ridan-to'g'ri "2 haftada bir" degan metod
        // yo'q, shuning uchun haftaning shu kuni/soatida TEKSHIRILADI,
        // lekin `when()` orqali faqat JUFT ISO hafta raqamlarida
        // (yiliga ~26 marta, ya'ni aynan har 2 haftada 1) haqiqatan
        // ishga tushadi. ─────────────────────────────────────────────
        $schedule->command('users:recommend-books')
            ->weeklyOn(3, '11:00')
            ->timezone($tz)
            ->when(fn () => now($tz)->weekOfYear % 2 === 0);

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

        // ── Book Club AI baholash — HAR KUNI (izohlar keshbek bilan
        // rag'batlantirilgani uchun haftalik kechikish nomaqbul; faqat
        // yangi/o'zgargan kontent baholanadi, xarajat minimal) ─────────
        $schedule->command('openai:score-book-club-content')
            ->dailyAt('03:15')
            ->timezone($tz)
            ->withoutOverlapping()
            ->runInBackground();

        // ── Mahsulot UGC ratinglari — haftasiga 1 marta qayta yig'iladi ──
        $schedule->command('products:refresh-ugc-ratings')
            ->weeklyOn(0, '09:40')
            ->timezone($tz)
            ->withoutOverlapping();

        // Kitob va kanselyariya listinglari: faqat yangi/o'zgargan mahsulotlar
        // barcha metadata va rasmlari bilan AI tekshiruvdan o'tadi.
        $schedule->command('products:moderate-ai --type=all')
            ->everyThirtyMinutes()
            ->timezone($tz)
            ->withoutOverlapping(29)
            ->runInBackground();

        // Reading Intelligence — "bu menga mosmi?" kartochkasi uchun
        // mahsulot tahlilini FON JARAYONIDA generatsiya qiladi. Item
        // sahifasi so'rovlari hech qachon AI kutib turmaydi — bu buyruq
        // ishlamasa ham hech narsa buzilmaydi, faqat kontent kechroq keladi.
        //
        // MUHIM — nega har daqiqada: bitta yurishda (15 kitob + 15
        // kansteller) OpenAI'ga item boshiga `generation_delay_ms` (standart
        // 2000ms) pauza bilan so'rov ketadi — bu OpenAI RPM limitini
        // himoya qiladigan yagona joy. Avval bu buyruq har 30 daqiqada bir
        // marta ishga tushar edi, lekin bitta yurish atigi ~1-2 daqiqa
        // davom etadi — qolgan ~28 daqiqa ishchi shunchaki bo'sh turardi.
        // `withoutOverlapping` bir vaqtda faqat bitta yurishga yo'l qo'yadi,
        // shuning uchun har daqiqada chaqirish item-darajasidagi RPM
        // pauzasini BUZMAYDI — faqat navbatdagi yurish darhol boshlanadi va
        // bo'sh turish vaqti yo'qoladi (backfill ~20-30 barobar tezlashadi).
        // MUHIM: faqat 'book' — kanselyariya (stationery) uchun Reading
        // Intelligence kartasi endi umuman ko'rsatilmaydi (qiyinlik/
        // kayfiyat kabi tushunchalar unga mos emas), shuning uchun uni
        // AI orqali generatsiya qilishning ham hojati yo'q — behuda
        // OpenAI xarajatini oldini oladi.
        $schedule->command('reading-intelligence:generate-insights --type=book --limit=15')
            ->everyMinute()
            ->timezone($tz)
            ->withoutOverlapping(5)
            ->runInBackground();

        // Faqat yangi/o'zgargan kontent batch tekshiriladi; limitlar server va
        // OpenAI yukini nazoratda ushlab turadi.
        $schedule->command('openai:moderate-book-club-comments --post-limit=150 --comment-limit=300')
            ->everyFiveMinutes()
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

        $schedule->command('split:refresh-user-profiles')
            ->dailyAt('05:20')
            ->timezone($tz)
            ->withoutOverlapping();

        // ── Split installmentlar: kunduzi har soatda undiriladi (kechasi bezovta qilmaymiz) ──
        $schedule->command('split:collect-installments')
            ->hourly()
            ->between('09:00', '21:00')
            ->timezone($tz)
            ->withoutOverlapping();

        $schedule->command('split:backfill-fiscal-receipts --limit=100')
            ->everyThirtyMinutes()
            ->timezone($tz)
            ->withoutOverlapping()
            ->runInBackground();

        // ── Split to'lov eslatmasi: 2 kun oldin, ertalab ──
        // ── Bo'sh nasiya limitini eslatuvchi haftalik promo push ──────
        $schedule->command('split:send-limit-promos')
            ->weeklyOn(4, '11:00')
            ->timezone($tz)
            ->withoutOverlapping()
            ->runInBackground();

        $schedule->command('split:send-payment-reminders --days=2')
            ->dailyAt('10:30')
            ->timezone($tz)
            ->withoutOverlapping();

        $schedule->command('products:send-review-prompts')
            ->twiceDaily(11, 18)
            ->timezone($tz)
            ->withoutOverlapping();

        // ── User qiziqish profili: product viewlardan tavsiya signalini yig'ish ──
        $schedule->command('products:refresh-user-interests --limit=400 --days=90 --cleanup-guest-days=45')
            ->hourly()
            ->between('08:00', '23:00')
            ->timezone($tz)
            ->withoutOverlapping()
            ->runInBackground();

        $schedule->command('products:refresh-user-interests --limit=5000 --days=90 --cleanup-guest-days=45')
            ->dailyAt('04:25')
            ->timezone($tz)
            ->withoutOverlapping()
            ->runInBackground();

        $schedule->command('seller-premium:sync-renewals')
            ->hourly()
            ->timezone($tz)
            ->withoutOverlapping();

        $schedule->command('seller-orders:finalize-pending-item-cancellations')
            ->everyMinute()
            ->timezone($tz)
            ->withoutOverlapping(2)
            ->runInBackground();

        $schedule->command('postal:sync-tracking --limit=250')
            ->everyTenMinutes()
            ->timezone($tz)
            ->withoutOverlapping(9)
            ->runInBackground();

    })->create();
