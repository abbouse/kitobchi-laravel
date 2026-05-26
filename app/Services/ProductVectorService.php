<?php

namespace App\Services;

use App\Models\Books;
use App\Models\Stationery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class ProductVectorService
{
    private const VECTOR_DIMENSIONS = 1536;

    private const BOOK_FIELDS = [
        'name',
        'author',
        'author_id',
        'description',
        'category_id',
        'seller_id',
        'lang',
        'year',
        'coverType',
        'status',
        'is_hidden',
        'is_approved',
        'count',
        'totalSales',
        'totalSalesWeek',
    ];

    private const STATIONERY_FIELDS = [
        'name',
        'description',
        'category_id',
        'seller_id',
        'material',
        'status',
        'is_hidden',
        'is_approved',
        'stock',
        'totalSales',
        'totalSalesWeek',
    ];

    public function __construct(
        private readonly OpenAIService $openAI,
    ) {
    }

    public function shouldQueueSync(Books|Stationery $product): bool
    {
        if ($product->wasRecentlyCreated) {
            return true;
        }

        $fields = $product instanceof Books ? self::BOOK_FIELDS : self::STATIONERY_FIELDS;

        return $product->wasChanged($fields);
    }

    public function syncByTypeAndId(string $type, int $id): bool
    {
        $product = $this->loadProduct($type, $id);

        if (! $product) {
            return false;
        }

        return $this->syncProduct($product);
    }

    public function syncProduct(Books|Stationery $product): bool
    {
        $product->loadMissing(['category', 'seller', 'tags']);

        if ($product instanceof Books) {
            $product->loadMissing('authorProfile');
        }

        if (! $this->isEligible($product)) {
            return $this->clearVector($product);
        }

        $text = $this->buildEmbedText($product);

        if (trim($text) === '') {
            Log::warning('Product vector skipped because embed text is empty', $this->logContext($product));
            return $this->clearVector($product);
        }

        $vector = $this->openAI->getVector($text);

        if (! $this->isValidVector($vector)) {
            Log::warning('Product vector generation returned invalid payload', $this->logContext($product));
            return false;
        }

        $product->updateQuietly([
            'vectorData' => $vector,
        ]);

        return true;
    }

    public function rebuildType(string $type, bool $force = false, int $limit = 0): array
    {
        $cleared = $this->clearInactiveVectors($type, $limit);
        $synced = 0;

        $query = $this->queryForType($type)
            ->with($this->relationsForType($type))
            ->activeForVector()
            ->orderBy('id');

        if (! $force) {
            $query->vectorNeedsSync();
        }

        $processed = 0;
        $query->chunkById(100, function (Collection $products) use (&$processed, &$synced, $limit) {
            foreach ($products as $product) {
                if ($limit > 0 && $processed >= $limit) {
                    return false;
                }

                $processed++;

                if ($this->syncProduct($product)) {
                    $synced++;
                }
            }

            return $limit <= 0 || $processed < $limit;
        });

        return [
            'cleared' => $cleared,
            'synced' => $synced,
        ];
    }

    private function clearInactiveVectors(string $type, int $limit = 0): int
    {
        $query = $this->queryForType($type)
            ->whereNotNull('vectorData')
            ->where(function (Builder $inactiveQuery) use ($type) {
                $inactiveQuery
                    ->where('status', false)
                    ->orWhere('is_hidden', 1)
                    ->orWhere('is_approved', '!=', 1)
                    ->orWhereDoesntHave('seller', fn (Builder $sellerQuery) => $sellerQuery
                        ->where('status', 'approved')
                        ->where('is_hidden', 0));

                if ($type === 'book') {
                    $inactiveQuery->orWhere('count', '<=', 0);
                } else {
                    $inactiveQuery->orWhere('stock', '<=', 0);
                }
            })
            ->orderBy('id');

        if ($limit > 0) {
            $ids = $query->limit($limit)->pluck('id');

            if ($ids->isEmpty()) {
                return 0;
            }

            return $this->queryForType($type)
                ->whereIn('id', $ids)
                ->update(['vectorData' => null]);
        }

        return $query->update(['vectorData' => null]);
    }

    private function loadProduct(string $type, int $id): Books|Stationery|null
    {
        return $this->queryForType($type)
            ->with($this->relationsForType($type))
            ->find($id);
    }

    private function queryForType(string $type): Builder
    {
        return match ($this->normalizeType($type)) {
            'book' => Books::query(),
            'stationery' => Stationery::query(),
        };
    }

    private function relationsForType(string $type): array
    {
        return match ($this->normalizeType($type)) {
            'book' => ['category', 'seller', 'tags', 'authorProfile'],
            'stationery' => ['category', 'seller', 'tags'],
        };
    }

    private function normalizeType(string $type): string
    {
        $normalized = strtolower(trim($type));

        return match ($normalized) {
            'book', 'books' => 'book',
            'stationery', 'stationeries' => 'stationery',
            default => throw new \InvalidArgumentException("Unsupported vector product type: {$type}"),
        };
    }

    private function isEligible(Books|Stationery $product): bool
    {
        $seller = $product->seller;

        if (! $seller || ($seller->status ?? null) !== 'approved' || (int) ($seller->is_hidden ?? 0) === 1) {
            return false;
        }

        if (! (bool) ($product->status ?? false)) {
            return false;
        }

        if ((int) ($product->is_hidden ?? 0) === 1 || (int) ($product->is_approved ?? 0) !== 1) {
            return false;
        }

        $stock = $product instanceof Books
            ? (int) ($product->count ?? 0)
            : (int) ($product->stock ?? 0);

        return $stock > 0;
    }

    private function buildEmbedText(Books|Stationery $product): string
    {
        if ($product instanceof Books) {
            return $this->openAI->buildProductEmbedText([
                'name' => $product->name,
                'author' => $product->authorProfile?->name ?: $product->author,
                'category' => $product->category?->name_uz ?? $product->category?->title,
                'tags' => $product->tags->pluck('tag_name_uz')->filter()->values()->all(),
                'shop_name' => $product->seller?->shop_name,
                'description' => $product->description,
                'totalSales' => $product->totalSales,
                'totalSalesWeek' => $product->totalSalesWeek,
            ]);
        }

        return $this->openAI->buildProductEmbedText([
            'name' => $product->name,
            'category' => $product->category?->name_uz ?? $product->category?->name,
            'tags' => $product->tags->pluck('name_uz')->filter()->values()->all(),
            'shop_name' => $product->seller?->shop_name,
            'description' => $product->description,
            'totalSales' => $product->totalSales,
            'totalSalesWeek' => $product->totalSalesWeek,
        ]);
    }

    private function isValidVector(mixed $vector): bool
    {
        if (! is_array($vector) || count($vector) !== self::VECTOR_DIMENSIONS) {
            return false;
        }

        foreach ($vector as $value) {
            if (! is_numeric($value)) {
                return false;
            }
        }

        return true;
    }

    private function clearVector(Books|Stationery $product): bool
    {
        if ($product->getRawOriginal('vectorData') === null) {
            return false;
        }

        $product->updateQuietly([
            'vectorData' => null,
        ]);

        return true;
    }

    private function logContext(Books|Stationery $product): array
    {
        return [
            'product_id' => $product->id,
            'product_type' => $product instanceof Books ? 'book' : 'stationery',
        ];
    }
}
