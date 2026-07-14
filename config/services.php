<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],
    'paylov' => [
        'base_url' => rtrim(env('PAYLOV_BASE_URL', 'https://paylov.uz'), '/'),
        'access_token' => env('PAYLOV_ACCESS_TOKEN'),
        'consumer_key' => env('PAYLOV_CONSUMER_KEY'),
        'consumer_secret' => env('PAYLOV_CONSUMER_SECRET'),
        'username' => env('PAYLOV_USERNAME'),
        'password' => env('PAYLOV_PASSWORD'),
        'merchant_id' => env('PAYLOV_MERCHANT_ID'),
        'hold_minutes' => (int) env('PAYLOV_ORDER_HOLD_MINUTES', 10080),
        'hold_time_key' => env('PAYLOV_HOLD_TIME_KEY', 'time'),

        // ── OFD (Fiskalizatsiya) ──────────────────────────────────────
        // https://developer.paylov.uz/uz/subscribe/ofd/register
        'ofd' => [
            'enabled' => (bool) env('PAYLOV_OFD_ENABLED', false),
            // Oddiy karta xaridida receiptType/advanceContractId yuborilmaydi:
            // ular Paylov hujjatidagi majburiy Card Payment fieldlari ro'yxatida yo'q.
            // Tovar topshirilgach olinadigan nasiya to'lovi: Kredit = 2.
            // Avans = 1 faqat tovar topshirilishidan oldin real pul yechilsa ishlatiladi.
            'split_credit_receipt_type' => (int) env('PAYLOV_OFD_SPLIT_CREDIT_RECEIPT_TYPE', 2),
            // Faqat eski split tranzaksiyalar uchun fallback. Yangi shartnomada
            // alohida, o'zgarmas fiscal_contract_id avtomatik yaratiladi.
            'split_advance_contract_id' => env(
                'PAYLOV_OFD_SPLIT_ADVANCE_CONTRACT_ID',
                env('PAYLOV_OFD_ADVANCE_CONTRACT_ID', ''),
            ),
            // Item narxlari OFD ga tiyin ko'rinishida yuboriladi.
            // To'lov API'lari qaysi birlikda ishlatilgan bo'lsa, shunga
            // moslang: to'lovlar so'mda yuborilsa 100, tiyinda bo'lsa 1.
            'amount_multiplier' => (int) env('PAYLOV_OFD_AMOUNT_MULTIPLIER', 100),
            // QQS foizi (soliq rejimiga qarab; soddalashtirilganda 0)
            'vat_percent' => (int) env('PAYLOV_OFD_VAT_PERCENT', 0),
            // Mahsulot turi bo'yicha default IKPU (mahsulotda o'zi bo'lmasa)
            'book_ikpu' => env('PAYLOV_OFD_BOOK_IKPU', ''),
            'book_package_code' => env('PAYLOV_OFD_BOOK_PACKAGE_CODE', ''),
            'stationery_ikpu' => env('PAYLOV_OFD_STATIONERY_IKPU', ''),
            'stationery_package_code' => env('PAYLOV_OFD_STATIONERY_PACKAGE_CODE', ''),
            // Yetkazish/qadoqlash xizmatlari uchun IKPU
            'service_ikpu' => env('PAYLOV_OFD_SERVICE_IKPU', ''),
            'service_package_code' => env('PAYLOV_OFD_SERVICE_PACKAGE_CODE', ''),
            // Marketpleys STIRi (ixtiyoriy, item darajasida yuboriladi)
            'tin' => env('PAYLOV_OFD_TIN', ''),
        ],
    ],

    // Saytdagi "Biz bilan aloqa" formasi murojaatlari boradigan inbox
    'contact_inbox' => env('CONTACT_INBOX_EMAIL', 'toordaliev@gmail.com'),

    // ── Split (nasiya) shartnomalari uchun kreditor rekvizitlari ──────
    'split_lender' => [
        'name' => env('SPLIT_LENDER_NAME', "YaTT TURDALIYEV ABBOS ERKIN O'G'LI"),
        'name_ru' => env('SPLIT_LENDER_NAME_RU', 'ЯТТ TURDALIYEV ABBOS ERKIN O‘G‘LI'),
        'inn' => env('SPLIT_LENDER_INN', '51503025990027'),
        'account' => env('SPLIT_LENDER_ACCOUNT', '2021 8000 4071 9264 9001'),
        'mfo' => env('SPLIT_LENDER_MFO', '00491'),
        'bank' => env('SPLIT_LENDER_BANK', '"Trastbank" XAB Bosh ofisi'),
        'bank_ru' => env('SPLIT_LENDER_BANK_RU', 'Головной офис ЧАБ «Трастбанк»'),
        'email' => env('SPLIT_LENDER_EMAIL', 'info@kitobchi.com'),
        'phone' => env('SPLIT_LENDER_PHONE', ''),
        'address' => env('SPLIT_LENDER_ADDRESS', ''),
        'address_ru' => env('SPLIT_LENDER_ADDRESS_RU', ''),
        // Undirish xatida beriladigan to'lov muddati (kalendar kun)
        'demand_days' => (int) env('SPLIT_DEMAND_DAYS', 10),
    ],

    'openai' => [
        'key' => env('OPENAI_API_KEY'),
    ],
    'eskiz' => [
        'email' => env('ESKIZ_EMAIL', 'toordaliev@gmail.com'),
        'password' => env('ESKIZ_PASSWORD', 'aF6WH2CcaKes30zgLPCZ1MM7CPfPlgCX07HoM8rE'),
        'from' => env('ESKIZ_FROM', '4546'),
        'auth_url' => env('ESKIZ_AUTH_URL', 'https://notify.eskiz.uz/api/auth/login'),
        'sms_url' => env('ESKIZ_SMS_URL', 'https://notify.eskiz.uz/api/message/sms/send'),
        'callback_url' => env('ESKIZ_CALLBACK_URL'),
        'connect_timeout' => (int) env('ESKIZ_CONNECT_TIMEOUT', 5),
        'timeout' => (int) env('ESKIZ_TIMEOUT', 10),
    ],

];
