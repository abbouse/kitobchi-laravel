<?php

return [
    // AI to'plam maslahatchisi (boshqaruv > To'plamlar).
    'enabled' => (bool) env('COLLECTION_ADVISOR_ENABLED', true),
    'model' => env('COLLECTION_ADVISOR_MODEL', 'gpt-4o-mini'),

    // Talab analizi oynasi (kun) — sotuv/savat/ko'rish shu oraliqda hisoblanadi.
    'demand_days' => (int) env('COLLECTION_ADVISOR_DEMAND_DAYS', 30),

    // AI'ga yuboriladigan nomzod kitoblar soni (talab bo'yicha eng yuqori).
    'candidate_limit' => (int) env('COLLECTION_ADVISOR_CANDIDATES', 40),

    // Talab ballari uchun signal og'irliklari (sotuv eng kuchli signal).
    'weights' => [
        'sales' => (float) env('COLLECTION_ADVISOR_W_SALES', 5.0),
        'carts' => (float) env('COLLECTION_ADVISOR_W_CARTS', 3.0),
        'views' => (float) env('COLLECTION_ADVISOR_W_VIEWS', 1.0),
    ],

    // To'plamdagi kitoblar soni chegarasi (AI shu oraliqda tanlaydi).
    'min_books' => (int) env('COLLECTION_ADVISOR_MIN_BOOKS', 4),
    'max_books' => (int) env('COLLECTION_ADVISOR_MAX_BOOKS', 8),

    // Marketing chegirmasi uchun tavsiya oralig'i (%).
    'target_discount_min' => (float) env('COLLECTION_ADVISOR_DISCOUNT_MIN', 8),
    'target_discount_max' => (float) env('COLLECTION_ADVISOR_DISCOUNT_MAX', 25),

    // MARGIN POLI: chegirmadan keyin platforma sof margini komissiyaning
    // kamida shuncha % qismidan past tushmasligi kerak. Chegirma shu polga
    // qarab avtomatik cheklanadi (deterministik, AI hal qilmaydi).
    'margin_floor_percent' => (float) env('COLLECTION_ADVISOR_MARGIN_FLOOR', 25),
];
