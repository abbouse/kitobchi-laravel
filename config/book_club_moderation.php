<?php

return [
    // Ijtimoiy tarmoqlar uslubi: yangi kontent DARHOL ko'rinadi. AI faqat
    // aniq buzg'unchi kontentni (yuqori ishonch bilan) keyin yashiradi.
    // hold_pending=true bo'lsagina hammasi moderatsiyagacha yashirin turadi
    // (default false — zararsiz postlar bekorga yashirilmaydi).
    'hold_pending' => (bool) env('BOOK_CLUB_MODERATION_HOLD_PENDING', false),
    'post_limit' => (int) env('BOOK_CLUB_MODERATION_POST_LIMIT', 150),
    'comment_limit' => (int) env('BOOK_CLUB_MODERATION_COMMENT_LIMIT', 300),

    // Yangi post uchun push (FCM) ogohlantirish shu 1-5 ballik AI sifat
    // bahosidan (ai_post_score) past bo'lsa YUBORILMAYDI — lekin post o'zi
    // baribir e'lon qilingan va ko'rinishda qoladi (faqat push bosiladi,
    // AI postni hech qachon yashira olmaydi — SendBookClubPushNotification
    // job'iga qarang). Baho hali mavjud bo'lmasa (masalan AI xizmati
    // ulanmagan/xato bergan bo'lsa), fail-open — push yuboriladi.
    'push_min_score' => (float) env('BOOK_CLUB_MODERATION_PUSH_MIN_SCORE', 3.0),

    // AI "hide" qarori shu ishonchdan past bo'lsa — yashirmaymiz (ko'rsatamiz).
    // Og'ir toifalar (scam, jinsiy, nafrat, tahdid, noqonuniy, xavfli link) uchun
    // pastroq bo'sag'a; oddiy toifalar (spam, ma'nosiz) uchun yuqoriroq.
    'hide_confidence' => (float) env('BOOK_CLUB_MODERATION_HIDE_CONFIDENCE', 0.80),
    'severe_confidence' => (float) env('BOOK_CLUB_MODERATION_SEVERE_CONFIDENCE', 0.55),

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
