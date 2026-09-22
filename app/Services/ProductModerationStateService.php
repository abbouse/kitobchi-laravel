<?php

namespace App\Services;

use App\Models\Books;
use App\Models\Stationery;
use Illuminate\Support\Collection;

class ProductModerationStateService
{
    public function markPending(Books|Stationery $product, string $source = 'product_changed'): void
    {
        $product->updateQuietly([
            'is_approved' => 0,
            'ai_moderation_status' => 'pending',
            'ai_moderation_checked_at' => null,
            'ai_moderation_note' => null,
            'ai_moderation_model' => null,
            'ai_moderation_content_hash' => null,
            'ai_moderation_attempts' => 0,
            'ai_moderation_next_retry_at' => null,
            'ai_moderation_meta' => [
                'source' => $source,
                'queued_at' => now()->toIso8601String(),
            ],
        ]);
        app(\App\Services\Catalog\BuyBoxService::class)->afterModeration($product);
    }

    public function applyPendingAttributes(Books|Stationery $product, string $source): void
    {
        $product->is_approved = 0;
        $product->ai_moderation_status = 'pending';
        $product->ai_moderation_checked_at = null;
        $product->ai_moderation_note = null;
        $product->ai_moderation_model = null;
        $product->ai_moderation_content_hash = null;
        $product->ai_moderation_attempts = 0;
        $product->ai_moderation_next_retry_at = null;
        $product->ai_moderation_meta = [
            'source' => $source,
            'queued_at' => now()->toIso8601String(),
        ];
    }

    public function markBooksPendingByIds(iterable $ids, string $source): void
    {
        $this->markPendingByIds(Books::class, $ids, $source);
    }

    public function markStationeriesPendingByIds(iterable $ids, string $source): void
    {
        $this->markPendingByIds(Stationery::class, $ids, $source);
    }

    private function markPendingByIds(string $modelClass, iterable $ids, string $source): void
    {
        Collection::make($ids)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->chunk(500)
            ->each(function (Collection $chunk) use ($modelClass, $source) {
                $modelClass::query()->whereKey($chunk->all())->update([
                    'is_approved' => 0,
                    'ai_moderation_status' => 'pending',
                    'ai_moderation_checked_at' => null,
                    'ai_moderation_note' => null,
                    'ai_moderation_model' => null,
                    'ai_moderation_content_hash' => null,
                    'ai_moderation_attempts' => 0,
                    'ai_moderation_next_retry_at' => null,
                    'ai_moderation_meta' => json_encode([
                        'source' => $source,
                        'queued_at' => now()->toIso8601String(),
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'updated_at' => now(),
                ]);

                if ($modelClass === Books::class) {
                    $buyBox = app(\App\Services\Catalog\BuyBoxService::class);
                    Books::query()->whereKey($chunk->all())->whereNotNull('edition_id')->distinct()->pluck('edition_id')
                        ->each(fn ($editionId) => $buyBox->touch((int) $editionId));
                }
            });
    }
}
