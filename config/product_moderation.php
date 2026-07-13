<?php

return [
    'enabled' => env('PRODUCT_AI_MODERATION_ENABLED', true),
    'model' => env('PRODUCT_AI_MODERATION_MODEL', 'gpt-4o-mini'),
    'batch_size' => max(1, (int) env('PRODUCT_AI_MODERATION_BATCH_SIZE', 4)),
    'run_limit' => max(1, (int) env('PRODUCT_AI_MODERATION_RUN_LIMIT', 80)),
    'max_images_per_product' => max(1, (int) env('PRODUCT_AI_MODERATION_MAX_IMAGES', 3)),
    'image_detail' => env('PRODUCT_AI_MODERATION_IMAGE_DETAIL', 'high'),
    'approval_confidence' => min(1, max(0.5, (float) env('PRODUCT_AI_MODERATION_APPROVAL_CONFIDENCE', 0.78))),
    'processing_timeout_minutes' => max(10, (int) env('PRODUCT_AI_MODERATION_PROCESSING_TIMEOUT', 45)),
    'max_retry_minutes' => max(60, (int) env('PRODUCT_AI_MODERATION_MAX_RETRY_MINUTES', 1440)),
];
