<?php

/*
|--------------------------------------------------------------------------
| Kitobchi ID — OpenID Connect provayderi
|--------------------------------------------------------------------------
| Issuer o'zgartirilsa (masalan id.kitobchi.com subdomeniga o'tilsa) barcha
| endpointlar discovery hujjati orqali avtomatik yangilanadi.
*/

return [
    // Barqaror bo'lishi shart: so'rov hostidan EMAS, APP_URL dan olinadi.
    // Standart: https://kitobchi.com/api/id
    'issuer' => env('KITOBCHI_ID_ISSUER', rtrim((string) env('APP_URL', 'https://kitobchi.com'), '/').'/api/id'),

    'enabled' => (bool) env('KITOBCHI_ID_ENABLED', true),

    // Soniyalarda
    'code_ttl' => 300,
    'access_token_ttl' => 600,
    'id_token_ttl' => 3600,
    'refresh_token_ttl' => 30 * 24 * 3600,          // sliding — har rotatsiyada yangilanadi
    'refresh_token_absolute_ttl' => 90 * 24 * 3600, // oila (family) uchun qat'iy chegara

    // OTP (telefon orqali kirish)
    'otp_ttl' => 300,
    'otp_resend_seconds' => 60,
    'otp_max_attempts' => 5,
    'otp_daily_limit' => 10,          // bitta raqamga kuniga kod (ilova + SMS)
    // Kod Kitobchi ilovasiga yuborilganda "SMS orqali olish" necha soniyadan keyin ochiladi
    'sms_fallback_seconds' => 30,
    'session_max_age' => 30 * 24 * 3600, // Kitobchi ID sessiyasi (qayta kod so'ralmaydi)

    // Pairwise `sub` uchun maxfiy tuz (o'zgartirilsa uchinchi tomon ilovalaridagi sub'lar o'zgaradi!)
    'pairwise_salt' => env('KITOBCHI_ID_PAIRWISE_SALT', env('APP_KEY')),

    'keys_path' => storage_path('app/kitobchi-id/keys'),

    // Qo'llab-quvvatlanadigan scope'lar va foydalanuvchiga ko'rsatiladigan matn
    'scopes' => [
        'openid' => "Kitobchi ID orqali sizni tanish",
        'profile' => "Ism, familiya, foydalanuvchi nomi va profil rasmi",
        'phone' => 'Tasdiqlangan telefon raqamingiz',
        'email' => 'Elektron pochta manzilingiz',
        'offline_access' => "Siz ilovada bo'lmaganingizda ham kirishni saqlab turish",
    ],
];
