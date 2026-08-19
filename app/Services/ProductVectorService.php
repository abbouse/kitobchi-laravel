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

        // ── Hash tekshiruvi — matn o'zgarmagan bo'lsa OpenAI ga bormaymiz ──
        // Har bir savdo totalSales ni o'zgartiradi, lekin embed matni odatda
        // bir xil qoladi (savdo darajasi label bo'yicha). Bu tekshiruv
        // keraksiz embedding so'rovlarini (va xarajatni) keskin kamaytiradi.
        $hash = md5($text);

        $existingVector = $product->getRawOriginal('vectorData');
        $existingHash   = $product->getRawOriginal('vector_text_hash');

        if ($existingHash === $hash && $existingVector !== null) {
            return true; // Hech narsa o'zgarmagan
        }

        $vector = $this->openAI->getVector($text);

        if (! $this->isValidVector($vector)) {
            Log::warning('Product vector generation returned invalid payload', $this->logContext($product));
            return false;
        }

        $product->updateQuietly([
            'vectorData'       => $vector,
            'vector_text_hash' => $hash,
            'has_vector'       => true,
        ]);

        $this->invalidateSearchIndex($product instanceof Books ? 'book' : 'stationery');

        return true;
    }

    /**
     * Semantik qidiruv indeksining cache'ini tozalaydi.
     */
    public function invalidateSearchIndex(string $type): void
    {
        try {
            VectorSearchService::invalidateIndex($this->normalizeType($type));
        } catch (\Throwable $e) {
            Log::warning('Vector index invalidation failed', ['type' => $type, 'message' => $e->getMessage()]);
        }
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

        if ($synced > 0 || $cleared > 0) {
            $this->invalidateSearchIndex($type);
        }

        return [
            'cleared' => $cleared,
            'synced' => $synced,
        ];
    }

    private function clearInactiveVectors(string $type, int $limit = 0): int
    {
        $query = $this->queryForType($type)
            ->whereNotNull('vectorData')
            ->where(function (Builder $inactiveQuery) {
                // Eslatma: stock sharti olib tashlangan — tugagan mahsulot
                // vectori saqlanadi (chatbotda ko'rinishi va stock-alert uchun)
                $inactiveQuery
                    ->where('status', false)
                    ->orWhere('is_hidden', 1)
                    ->orWhere('is_approved', '!=', 1)
                    ->orWhereDoesntHave('seller', fn (Builder $sellerQuery) => $sellerQuery
                        ->where('status', 'approved')
                        ->where('is_hidden', 0));
            })
            ->orderBy('id');

        if ($limit > 0) {
            $ids = $query->limit($limit)->pluck('id');

            if ($ids->isEmpty()) {
                return 0;
            }

            $cleared = $this->queryForType($type)
                ->whereIn('id', $ids)
                ->update(['vectorData' => null, 'vector_text_hash' => null, 'has_vector' => false]);
        } else {
            $cleared = $query->update(['vectorData' => null, 'vector_text_hash' => null, 'has_vector' => false]);
        }

        if ($cleared > 0) {
            $this->invalidateSearchIndex($type);
        }

        return $cleared;
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

        // Stock tekshirilmaydi — tugagan mahsulot ham indeksda qoladi
        // (chatbot ko'rsatadi, mijoz "kelganda xabar ber" bosadi)
        return true;
    }

    private function buildEmbedText(Books|Stationery $product): string
    {
        if ($product instanceof Books) {
            return $this->openAI->buildProductEmbedText([
                'name' => $product->name,
                'author' => $product->authorProfile?->name ?: $product->author,
                'category' => $product->category?->name_uz ?? $product->category?->title,
                'tags' => $product->tags->pluck('tag_name_uz')->filter()->values()->all(),
                'artikul' => $product->artikul,
                'lang' => $product->lang,
                'year' => $product->year,
                'coverType' => $product->coverType,
                'price' => $product->discountPrice ?: $product->price,
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
            'artikul' => $product->artikul,
            'material' => $product->material,
            'price' => $product->discount_price ?: $product->price,
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
            'vectorData'       => null,
            'vector_text_hash' => null,
            'has_vector'       => false,
        ]);

        $this->invalidateSearchIndex($product instanceof Books ? 'book' : 'stationery');

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
