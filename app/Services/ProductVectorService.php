<?php

namespace App\Services;

use App\Models\BookEdition;
use App\Models\BookEditionVector;
use App\Models\Books;
use App\Models\Stationery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class ProductVectorService
{
    private const VECTOR_DIMENSIONS = 1536;

    /**
     * Kanselyariya vektorini qayta yasashga sabab bo'ladigan maydonlar.
     *
     * Narx, qoldiq va sotuv soni bu yerda YO'Q: ular vektorga kirmaydi (saralash
     * va filtr SQL'da). Ilgari embed matniga aniq sotuv soni yozilardi va har
     * sotuv OpenAI'ga yangi so'rov yuborardi. Holat maydonlari qoladi —
     * mahsulot yashirilsa vektori tozalanadi.
     */
    private const STATIONERY_FIELDS = [
        'name',
        'description',
        'category_id',
        'seller_id',
        'material',
        'status',
        'is_hidden',
        'is_approved',
    ];

    public function __construct(
        private readonly OpenAIService $openAI,
    ) {
    }

    public function shouldQueueSync(Books|Stationery $product): bool
    {
        // Kitob vektori kitob KARTASIGA tegishli va karta o'zgarganda yangilanadi
        // (BookEdition::booted). Do'kon taklifidagi narx, qoldiq yoki sotuv
        // vektorga ta'sir qilmaydi — bu yerda hech narsa navbatga qo'yilmaydi.
        if ($product instanceof Books) {
            return false;
        }

        if ($product->wasRecentlyCreated) {
            return true;
        }

        return $product->wasChanged(self::STATIONERY_FIELDS);
    }

    public function syncByTypeAndId(string $type, int $id): bool
    {
        $normalized = strtolower(trim($type));

        if ($normalized === 'edition') {
            $edition = BookEdition::query()->find($id);

            return $edition ? $this->syncEdition($edition) : false;
        }

        // Deploy paytida navbatda qolgan eski ('book', <taklif id>) ishlar:
        // taklifning kartasi qayta hisoblanadi.
        if (in_array($normalized, ['book', 'books'], true)) {
            $editionId = (int) Books::query()->whereKey($id)->value('edition_id');
            $edition = $editionId > 0 ? BookEdition::query()->find($editionId) : null;

            return $edition ? $this->syncEdition($edition) : false;
        }

        $product = $this->loadProduct($type, $id);

        if (! $product) {
            return false;
        }

        return $this->syncProduct($product);
    }

    /**
     * Kitob kartasining vektorini yangilaydi. Matn o'zgarmagan bo'lsa (hash
     * bir xil) OpenAI'ga borilmaydi.
     *
     * Kartaning sotuvdagi taklifi bor-yo'qligi bu yerda TEKSHIRILMAYDI: vektor
     * kitob matnining xususiyati. Qaysi kitob qidiruvda chiqishini indeks va
     * natijalarni yuklash bosqichi hal qiladi (`offers_count` va taklifning
     * holati) — do'kon kitobni vaqtincha yashirib, qayta chiqarsa, vektor uchun
     * qayta pul to'lanmaydi.
     */
    public function syncEdition(BookEdition $edition): bool
    {
        if ($edition->trashed() || in_array($edition->status, [BookEdition::STATUS_MERGED, BookEdition::STATUS_REJECTED], true)) {
            return $this->clearEditionVector((int) $edition->id);
        }

        $text = $this->buildEditionEmbedText($edition);

        if (trim($text) === '') {
            Log::warning('Edition vector skipped because embed text is empty', ['edition_id' => $edition->id]);

            return false;
        }

        $hash = md5($text);
        $existing = BookEditionVector::query()->find($edition->id);

        if ($existing && $existing->text_hash === $hash) {
            return true;
        }

        $vector = $this->openAI->getVector($text);

        if (! $this->isValidVector($vector)) {
            Log::warning('Edition vector generation returned invalid payload', ['edition_id' => $edition->id]);

            return false;
        }

        BookEditionVector::query()->updateOrCreate(
            ['edition_id' => $edition->id],
            ['vector' => $vector, 'text_hash' => $hash]
        );

        $this->invalidateSearchIndex('book');

        return true;
    }

    private function clearEditionVector(int $editionId): bool
    {
        $deleted = BookEditionVector::query()->whereKey($editionId)->delete();

        if ($deleted > 0) {
            $this->invalidateSearchIndex('book');
        }

        return $deleted > 0;
    }

    /**
     * Kitob MAZMUNI: nom, muallif, kategoriya, teglar, til, yil, muqova,
     * nashriyot, tavsif. Do'kon, narx va sotuv soni yo'q — ular har do'konda
     * boshqa va vaqt o'tishi bilan o'zgaradi; vektorga kirsa, bir kitob 20 ta
     * har xil vektor olardi va har sotuvda qayta yasalardi.
     */
    private function buildEditionEmbedText(BookEdition $edition): string
    {
        $edition->loadMissing(['authorProfile', 'category', 'publisher']);

        $tagIds = array_values(array_filter(array_map('intval', (array) ($edition->tag_ids ?? []))));
        $tags = $tagIds === []
            ? []
            : \App\Models\BookTag::query()->whereIn('id', $tagIds)->pluck('tag_name_uz')->filter()->values()->all();

        return $this->openAI->buildProductEmbedText([
            'name' => $edition->title,
            'author' => $edition->authorProfile?->name ?: $edition->author,
            'category' => $edition->category?->name_uz ?? $edition->category?->title,
            'tags' => $tags,
            'lang' => $edition->lang,
            'year' => $edition->year,
            'coverType' => $edition->coverType,
            'publisher' => $edition->publisher?->name,
            'description' => $edition->description,
        ]);
    }

    public function syncProduct(Books|Stationery $product): bool
    {
        if ($product instanceof Books) {
            $edition = $product->edition_id ? BookEdition::query()->find($product->edition_id) : null;

            return $edition ? $this->syncEdition($edition) : false;
        }

        $product->loadMissing(['category', 'seller', 'tags']);

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
        if ($this->normalizeType($type) === 'book') {
            return $this->rebuildEditions($force, $limit);
        }

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

    /**
     * Kitob kartalari vektorini to'ldiradi / yangilaydi.
     *
     * Navbat: sotuvdagi taklifi bor kartalar (`offers_count > 0`), vektori
     * yo'q yoki hash'i bo'sh (matn o'zgargan / eski formatdan ko'chirilgan).
     * Ko'p sotiladiganlar birinchi — qidiruvda eng ko'p ko'rinadiganlari avval
     * to'g'rilanadi.
     *
     * Birlashtirilgan, rad etilgan va o'chirilgan kartalarning vektori
     * tozalanadi — ular qidiruvga tushmasligi kerak.
     *
     * @return array{cleared:int, synced:int}
     */
    private function rebuildEditions(bool $force, int $limit): array
    {
        $cleared = $this->clearDeadEditionVectors();
        $synced = 0;

        $query = BookEdition::query()
            ->whereIn('status', [BookEdition::STATUS_ACTIVE, BookEdition::STATUS_PENDING])
            ->where('offers_count', '>', 0);

        if (! $force) {
            $query->whereNotExists(fn ($sub) => $sub
                ->selectRaw('1')
                ->from('book_edition_vectors')
                ->whereColumn('book_edition_vectors.edition_id', 'book_editions.id')
                ->whereNotNull('book_edition_vectors.text_hash'));
        }

        $ids = $query
            ->orderByDesc('sales_total')
            ->orderBy('id')
            ->when($limit > 0, fn ($q) => $q->limit($limit))
            ->pluck('id');

        foreach ($ids->chunk(100) as $chunk) {
            $editions = BookEdition::query()
                ->with(['authorProfile', 'category', 'publisher'])
                ->whereIn('id', $chunk->all())
                ->get();

            foreach ($editions as $edition) {
                if ($this->syncEdition($edition)) {
                    $synced++;
                }
            }
        }

        if ($synced > 0 || $cleared > 0) {
            $this->invalidateSearchIndex('book');
        }

        return ['cleared' => $cleared, 'synced' => $synced];
    }

    private function clearDeadEditionVectors(): int
    {
        return BookEditionVector::query()
            ->whereIn('edition_id', fn ($sub) => $sub
                ->select('id')
                ->from('book_editions')
                ->where(fn ($w) => $w
                    ->whereNotNull('deleted_at')
                    ->orWhereIn('status', [BookEdition::STATUS_MERGED, BookEdition::STATUS_REJECTED])))
            ->delete();
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

    /** Kanselyariya mazmuni. Kitoblar uchun — `buildEditionEmbedText`. */
    private function buildEmbedText(Stationery $product): string
    {
        return $this->openAI->buildProductEmbedText([
            'name' => $product->name,
            'category' => $product->category?->name_uz ?? $product->category?->name,
            'tags' => $product->tags->pluck('name_uz')->filter()->values()->all(),
            'material' => $product->material,
            'description' => $product->description,
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

    private function clearVector(Stationery $product): bool
    {
        if ($product->getRawOriginal('vectorData') === null) {
            return false;
        }

        $product->updateQuietly([
            'vectorData'       => null,
            'vector_text_hash' => null,
            'has_vector'       => false,
        ]);

        $this->invalidateSearchIndex('stationery');

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
