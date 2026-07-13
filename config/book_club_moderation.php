<?php

return [
    // Yangi kontent muallifning o'ziga ko'rinadi, boshqalarga esa AI qaroridan
    // keyin ochiladi. Bu spam tarqalishini moderatsiya oralig'ida ham to'xtatadi.
    'hold_pending' => (bool) env('BOOK_CLUB_MODERATION_HOLD_PENDING', true),
    'post_limit' => (int) env('BOOK_CLUB_MODERATION_POST_LIMIT', 150),
    'comment_limit' => (int) env('BOOK_CLUB_MODERATION_COMMENT_LIMIT', 300),

    // Reklama faqat obro'li, ommaviy platformalarga olib borganda ko'rib
    // chiqiladi. Allowlist reklamaning o'zini avtomatik tasdiqlamaydi: firib,
    // haqorat yoki spam bo'lsa AI baribir yashiradi.
    'trusted_domains' => [
        'kitobchi.com',
        'google.com', 'youtube.com', 'youtu.be',
        'instagram.com', 'facebook.com', 'threads.net',
        'tiktok.com', 'x.com', 'twitter.com',
        'linkedin.com', 'pinterest.com', 'goodreads.com',
        'telegram.org', 't.me', 'whatsapp.com', 'wa.me',
        'apple.com', 'microsoft.com', 'amazon.com',
        'uzum.uz', 'olx.uz', 'asaxiy.uz', 'book.uz', 'hilolnashr.uz',
        'texnomart.uz', 'mediapark.uz', 'idea.uz',
        'click.uz', 'payme.uz', 'paylov.uz',
        'my.gov.uz', 'gov.uz', 'lex.uz',
        'kun.uz', 'daryo.uz', 'gazeta.uz', 'spot.uz',
        'hh.uz', 'iticket.uz', 'afisha.uz', 'yandex.uz',
    ],

    'trusted_platform_names' => [
        'kitobchi', 'google', 'youtube', 'instagram', 'facebook', 'threads',
        'tiktok', 'twitter', 'linkedin', 'pinterest', 'goodreads', 'telegram',
        'whatsapp', 'amazon', 'apple', 'microsoft', 'uzum', 'olx', 'asaxiy',
        'book.uz', 'hilol nashr', 'texnomart', 'mediapark', 'click', 'payme',
        'paylov', 'my.gov.uz', 'kun.uz', 'daryo', 'gazeta.uz', 'spot.uz',
        'hh.uz', 'iticket', 'afisha', 'yandex',
    ],

    'shortener_domains' => [
        'bit.ly', 'tinyurl.com', 't.co', 'goo.gl', 'cutt.ly', 'is.gd',
        'rb.gy', 'clck.ru', 'shorturl.at', 'rebrand.ly',
    ],
];
