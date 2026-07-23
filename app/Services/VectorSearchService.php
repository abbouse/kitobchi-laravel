<?php

namespace App\Services;

use App\Models\Books;
use App\Models\Stationery;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Semantik (vector) qidiruv xizmati.
 *
 * Tezlik strategiyasi:
 *   1. INDEX CACHE — barcha aktiv mahsulot vectorlari normalizatsiya qilinib,
 *      pack('f*') bilan siqilgan binary ko'rinishda file cache da saqlanadi.
 *      JSON decode (har mahsulotga ~30KB matn) o'rniga bir marta tayyorlangan
 *      indeks o'qiladi — butun jadval millisekundlarda skan qilinadi.
 *   2. Vectorlar oldindan normalizatsiya qilingani uchun cosine = dot product.
 *   3. Faqat top-N mahsulot relations bilan to'liq yuklanadi (hydrate).
 *
 * Indeks quyidagi hollarda yangilanadi:
 *   - TTL tugaganda (10 daqiqa)
 *   - ProductVectorService vector yozganda/tozalaganda (invalidateIndex)
 */
class VectorSearchService
{
    private const INDEX_TTL   = 600; // 10 daqiqa
    private const INDEX_STORE = 'file';

    public function __construct(
        private readonly OpenAIService $openAI,
    ) {
    }

    // ─── Indeks cache boshqaruvi ─────────────────────────────────────────────

    private static function indexKey(string $type): string
    {
        return "vector-search-index:{$type}:v1";
    }

    public static function invalidateIndex(string $type): void
    {
        Cache::store(self::INDEX_STORE)->forget(self::indexKey($type));
    }

    /**
     * Tur bo'yicha indeksni qaytaradi: [id => packed_normalized_vector].
     */
    private function getIndex(string $type): array
    {
        return Cache::store(self::INDEX_STORE)->remember(
            self::indexKey($type),
            self::INDEX_TTL,
            fn () => $this->buildIndex($type)
        );
    }

    private function buildIndex(string $type): array
    {
        $index = [];

        $query = $type === 'book'
            ? Books::query()->activeForVector()->vectorReady()->select(['id', 'vectorData'])
            : Stationery::query()->activeForVector()->vectorReady()->select(['id', 'vectorData']);

        $query->orderBy('id')->chunk(500, function ($products) use (&$index) {
            foreach ($products as $product) {
                $vec = $product->vectorData;

                if (! is_array($vec) || empty($vec)) {
                    continue;
                }

                $packed = self::packNormalized($vec);
                if ($packed !== null) {
                    $index[$product->id] = $packed;
                }
            }
        });

        Log::info('Vector search index built', ['type' => $type, 'count' => count($index)]);

        return $index;
    }

    /**
     * Vectorni normalizatsiya qilib binary ga siqadi (1536 float = 6KB).
     */
    private static function packNormalized(array $vec): ?string
    {
        $mag = 0.0;
        foreach ($vec as $v) {
            $mag += $v * $v;
        }
        $mag = sqrt($mag);

        if ($mag <= 0) {
            return null;
        }

        $normalized = [];
        foreach ($vec as $v) {
            $normalized[] = $v / $mag;
        }

        return pack('f*', ...$normalized);
    }

    // ─── Qidiruv ─────────────────────────────────────────────────────────────

    /**
     * Matn so'rovi bo'yicha semantik qidiruv.
     *
     * @param string $query    Qidiruv matni
     * @param string $type     'book' | 'stationery' | 'both'
     * @param int    $limit    Nechta natija qaytarish
     * @param float  $minScore Minimal o'xshashlik (0..1)
     *
     * @return Collection<Books|Stationery> _similarity va _type atributlari bilan
     */
    public function search(string $query, string $type = 'both', int $limit = 12, float $minScore = 0.30, bool $inStockOnly = false): Collection
    {
        $queryVec = $this->openAI->getCachedVector($query);

        if (empty($queryVec)) {
            return collect();
        }

        return $this->searchByVector($queryVec, $type, $limit, $minScore, $inStockOnly);
    }

    /**
     * Tayyor embedding bo'yicha qidiruv (masalan, rasm tahlilidan olingan).
     *
     * @param bool $inStockOnly true — faqat sotuvda bor mahsulotlar (asosiy search);
     *                          false — tugaganlar ham (chatbot, stock-alert uchun)
     */
    public function searchByVector(array $queryVec, string $type = 'both', int $limit = 12, float $minScore = 0.30, bool $inStockOnly = false): Collection
    {
        $packedQuery = self::packNormalized($queryVec);

        if ($packedQuery === null) {
            return collect();
        }

        $queryFloats = unpack('f*', $packedQuery);

        $results = collect();

        if (in_array($type, ['book', 'both'], true)) {
            $results = $results->merge(
                $this->scoreType('book', $queryFloats, $limit * 2, $minScore)
            );
        }

        if (in_array($type, ['stationery', 'both'], true)) {
            $results = $results->merge(
                $this->scoreType('stationery', $queryFloats, $limit * 2, $minScore)
            );
        }

        $top = $results->sortByDesc('_similarity')->take($limit * 2)->values();

        return $this->hydrate($top, $inStockOnly)->take($limit)->values();
    }

    /**
     * Indeks bo'ylab dot product hisoblab, eng yaqinlarini qaytaradi.
     * Indeksdagi hamma vector allaqachon normalizatsiya qilingan.
     *
     * @param array $queryFloats unpack('f*') natijasi — 1-indexed
     *
     * @return Collection<array{id:int, type:string, _similarity:float}>
     */
    private function scoreType(string $type, array $queryFloats, int $take, float $minScore): Collection
    {
        $scored = collect();

        try {
            $index = $this->getIndex($type);
            $dim   = count($queryFloats);

            foreach ($index as $id => $packed) {
                $floats = unpack('f*', $packed);

                if (count($floats) !== $dim) {
                    continue;
                }

                $dot = 0.0;
                for ($i = 1; $i <= $dim; $i++) {
                    $dot += $queryFloats[$i] * $floats[$i];
                }

                if ($dot >= $minScore) {
                    $scored->push([
                        'id'          => $id,
                        'type'        => $type,
                        '_similarity' => $dot,
                    ]);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('VectorSearch scoreType failed', [
                'type'    => $type,
                'message' => $e->getMessage(),
            ]);
        }

        return $scored->sortByDesc('_similarity')->take($take)->values();
    }

    /**
     * Top natijalarni relations bilan to'liq yuklaydi va tartibni saqlaydi.
     * Indeks biroz eskirgan bo'lishi mumkin — shu yerda activeForVector bilan
     * qayta filtrlaymiz (sotuvda yo'q mahsulot mijozga chiqmasin).
     */
    private function hydrate(Collection $scored, bool $inStockOnly = false): Collection
    {
        $bookIds = $scored->where('type', 'book')->pluck('id')->all();
        $statIds = $scored->where('type', 'stationery')->pluck('id')->all();

        $books = empty($bookIds) ? collect() : Books::with(['category', 'seller', 'tags', 'authorProfile'])
            ->activeForVector()
            ->withAvailableTotal()
            ->when($inStockOnly, fn ($q) => $q->inStock())
            ->whereIn('id', $bookIds)->get()->keyBy('id');

        $stats = empty($statIds) ? collect() : Stationery::with(['category', 'seller', 'tags'])
            ->activeForVector()
            ->withAvailableTotal()
            ->when($inStockOnly, fn ($q) => $q->inStock())
            ->whereIn('id', $statIds)->get()->keyBy('id');

        return $scored->map(function (array $row) use ($books, $stats) {
            $product = $row['type'] === 'book'
                ? $books->get($row['id'])
                : $stats->get($row['id']);

            if (! $product) {
                return null;
            }

            $product->_type       = $row['type'];
            $product->_similarity = $row['_similarity'];

            return $product;
        })->filter()->values();
    }
}
