<?php

namespace App\Console\Commands;

use App\Models\BookClub;
use App\Models\Books;
use App\Models\Sold;
use App\Models\Stationery;
use App\Support\ProductPayloadFormatter;
use Illuminate\Console\Command;

class BackfillBookClubProductSnapshots extends Command
{
    protected $signature = 'bookclub:backfill-product-snapshots {--force : Existing snapshotlarni ham qayta yozish}';

    protected $description = 'Book Club product-linked postlari uchun product snapshotlarni to\'ldiradi';

    public function handle(): int
    {
        $force = (bool) $this->option('force');

        $query = BookClub::query()
            ->whereNotNull('product_id')
            ->whereIn('product_type', ['book', 'stationery', 'order']);

        if (!$force) {
            $query->whereNull('product_snapshot');
        }

        $total = (clone $query)->count();

        if ($total === 0) {
            $this->info('Snapshot to\'ldirish kerak bo\'lgan post topilmadi.');
            return self::SUCCESS;
        }

        $updated = 0;
        $missing = 0;

        $query->orderBy('id')->chunkById(200, function ($posts) use (&$updated, &$missing) {
            foreach ($posts as $post) {
                $snapshot = $this->buildSnapshot($post->product_type, (int) $post->product_id, (int) $post->user_id);

                if ($snapshot === null) {
                    $missing++;
                    continue;
                }

                $post->forceFill([
                    'product_snapshot' => $snapshot,
                ])->saveQuietly();

                $updated++;
            }
        });

        $this->info("Snapshot yangilandi: {$updated}");
        if ($missing > 0) {
            $this->warn("Product topilmagani uchun o'tkazib yuborildi: {$missing}");
        }

        return self::SUCCESS;
    }

    private function buildSnapshot(string $productType, int $productId, int $userId): ?array
    {
        if ($productId <= 0) {
            return null;
        }

        if ($productType === 'order') {
            $order = Sold::where('id', $productId)
                ->where('user_id', $userId)
                ->first(['id', 'amount', 'items', 'status']);

            if (!$order) {
                return null;
            }

            return [
                'id' => $order->id,
                'product_type' => 'order',
                'name' => null,
                'order_id' => $order->id,
                'amount' => $order->amount,
                'items' => $order->items ?? [],
                'status' => $order->status,
            ];
        }

        $product = $productType === 'stationery'
            ? Stationery::with(['category', 'tags', 'seller'])->find($productId)
            : Books::with(['category', 'tags', 'seller'])->find($productId);

        if (!$product) {
            return null;
        }

        return ProductPayloadFormatter::format($product, [
            'user' => null,
            'type' => $productType === 'stationery' ? 'stationery' : 'book',
            'category_format' => 'title',
        ]);
    }
}
