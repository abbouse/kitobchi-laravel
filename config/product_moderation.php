<?php

return [
    'enabled' => env('PRODUCT_AI_MODERATION_ENABLED', true),
    'model' => env('PRODUCT_AI_MODERATION_MODEL', 'gpt-4o-mini'),
    'batch_size' => max(1, (int) env('PRODUCT_AI_MODERATION_BATCH_SIZE', 4)),
    'run_limit' => max(1, (int) env('PRODUCT_AI_MODERATION_RUN_LIMIT', 80)),
    'max_images_per_product' => max(1, (int) env('PRODUCT_AI_MODERATION_MAX_IMAGES', 3)),
    'image_detail' => env('PRODUCT_AI_MODERATION_IMAGE_DETAIL', 'high'),

    // Bitta OpenAI so'roviga qo'shiladigan jami rasmlar soni bo'yicha
    // yuqori chegara. Ko'p mahsulot rasmi bitta so'rovda aralashsa, model
    // rasmni noto'g'ri mahsulotga bog'lab qo'yishi ("image mismatch" xato
    // pozitivi) ehtimoli oshadi — shu sabab past tutiladi.
    'max_images_per_request' => max(1, (int) env('PRODUCT_AI_MODERATION_MAX_IMAGES_PER_REQUEST', 4)),

    'approval_confidence' => min(1, max(0.5, (float) env('PRODUCT_AI_MODERATION_APPROVAL_CONFIDENCE', 0.78))),

    // Reject qarori FAQAT rasmga (image_mismatch va h.k.) asoslangan bo'lsa,
    // shu confidence darajasidan pastida avtomatik human_review ga tushadi —
    // rasm-mahsulot mosligi ko'p-itemli batchda modelning eng ko'p
    // adashadigan joyi, shuning uchun yuqori bar qo'yilgan.
    'image_reject_confidence' => min(1, max(0.5, (float) env('PRODUCT_AI_MODERATION_IMAGE_REJECT_CONFIDENCE', 0.93))),
    'processing_timeout_minutes' => max(10, (int) env('PRODUCT_AI_MODERATION_PROCESSING_TIMEOUT', 45)),
    'max_retry_minutes' => max(60, (int) env('PRODUCT_AI_MODERATION_MAX_RETRY_MINUTES', 1440)),
];
