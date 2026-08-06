<?php

return [
    // S1 — did moslik (foydalanuvchi taste vektori vs mahsulot vektori)
    // cosine similarity chegaralari. Real trafik kelgach A/B test bilan
    // sozlanishi kerak — shuning uchun kod ichida emas, shu yerda.
    'taste_strong_threshold' => (float) env('READING_INTEL_TASTE_STRONG', 0.60),
    'taste_soft_threshold'   => (float) env('READING_INTEL_TASTE_SOFT', 0.30),
    'min_purchases_for_taste' => (int) env('READING_INTEL_MIN_PURCHASES', 3),

    // S2 — kollaborativ signal (shu kategoriyani sevganlarning necha
    // foizi bu mahsulotni ham sotib olgan/yoqtirgan)
    'collaborative_min_positive_rate' => (float) env('READING_INTEL_COLLAB_RATE', 0.40),
    'collaborative_min_sample_size'   => (int) env('READING_INTEL_COLLAB_SAMPLE', 30),
    'collaborative_cache_ttl'         => (int) env('READING_INTEL_COLLAB_TTL', 3600),

    // S3 — universal sifat (rank/ugc) uchun minimal ma'lumot bo'lishi kerak
    'min_reviews_for_quality' => (int) env('READING_INTEL_MIN_REVIEWS', 3),
    'rank_top_n' => (int) env('READING_INTEL_RANK_TOP_N', 10),
    'rank_cache_ttl' => (int) env('READING_INTEL_RANK_TTL', 1800),

    // `reading-intelligence:generate-insights` fon buyrug'i har bir
    // mahsulotdan keyin shuncha millisekund kutadi — OpenAI'ning RPM
    // (daqiqasiga so'rov) limitiga urilib qolmaslik uchun. Hisobingiz
    // yuqori tier'da bo'lsa (yoki xatolar davom etsa) .env orqali
    // sozlang — kodni o'zgartirish shart emas.
    'generation_delay_ms' => (int) env('READING_INTEL_GENERATION_DELAY_MS', 2000),
];
