<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Models\ApiClient;
use App\Models\Books;
use App\Models\Seller;
use App\Models\Stationery;
use App\Support\CatalogOffers;
use App\Support\ProductDeeplink;
use App\Support\StockCodeLookup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Seller-scoped WRITE API (App-ID/Secret kaliti bitta sellerga bog'langan +
 * `stock:write` ability). Marketpleys (Uzum/Wildberries) uslubidagi integratsiya:
 * seller o'z tizimidan zaxirani boshqaradi. Kalit faqat O'Z do'koni mahsulotlariga
 * ta'sir qiladi. Yozuvlar `Idempotency-Key` header'i orqali takrorlanmaydi.
 */
class SellerApiController extends Controller
{
    public function updateStockByCode(Request $request): JsonResponse
    {
        $sellerId = $this->sellerId($request);
        if ($sellerId === null) {
            return response()->json(['status' => 'error', 'message' => "Bu kalit do'konga bog'lanmagan."], 403);
        }

        $data = $request->validate([
            'code' => ['required', 'string', 'max:32'],
            'type' => ['nullable', 'in:book,stationery'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'delta' => ['nullable', 'integer'],
        ]);

        $hasStock = array_key_exists('stock', $data) && $data['stock'] !== null;
        $hasDelta = array_key_exists('delta', $data) && $data['delta'] !== null;
        if (! $hasStock && ! $hasDelta) {
            return response()->json(['status' => 'error', 'message' => '"stock" yoki "delta" yuboring.'], 422);
        }

        // Idempotentlik: bir xil Idempotency-Key bilan takroriy so'rov 2x qo'llanmaydi.
        $idem = trim((string) $request->header('Idempotency-Key', ''));
        $idemCache = $idem !== '' ? 'api-idem:'.$this->clientId($request).':'.sha1($idem) : null;
        if ($idemCache) {
            $cached = Cache::get($idemCache);
            if (is_array($cached)) {
                return response()->json($cached['body'], (int) $cached['status']);
            }
        }

        [$body, $status] = $this->applyStockUpdate($sellerId, $data, $hasStock);

        if ($idemCache && ($body['success'] ?? false)) {
            Cache::put($idemCache, ['body' => $body, 'status' => $status], now()->addHours(24));
        }

        return response()->json($body, $status);
    }

    public function myProducts(Request $request): JsonResponse
    {
        $sellerId = $this->sellerId($request);
        if ($sellerId === null) {
            return response()->json(['status' => 'error', 'message' => "Bu kalit do'konga bog'lanmagan."], 403);
        }

        $seller = Seller::find($sellerId);
        if (! $seller) {
            return response()->json(['status' => 'error', 'message' => "Do'kon topilmadi."], 404);
        }

        $type = $request->query('type', 'book') === 'stationery' ? 'stationery' : 'book';
        $per = min(100, max(1, (int) $request->query('per_page', 50)));

        if ($type === 'book') {
            // Arxivlangan (admin o'chirgan) takliflar sotuvda emas — ro'yxatda ham chiqmaydi.
            $page = $seller->books()->where('is_hidden', false)->whereNull('archived_at')
                ->orderByDesc('updated_at')->paginate($per);
            $data = collect($page->items())->map(fn ($b) => [
                'id' => $b->id,
                'type' => 'book',
                'name' => $b->name,
                // `code` — ISBN (eski shartnoma). Bir xil ISBN bir nechta nashrga
                // tegishli bo'lishi mumkin, shu sabab qoldiq yozishda `artikul`
                // aniqroq kalit.
                'code' => $b->isbn,
                'artikul' => $b->artikul,
                'price' => (int) ($b->price ?? 0),
                'stock' => (int) ($b->count ?? 0),
                'in_stock' => (int) ($b->count ?? 0) > 0,
                // Kitob bahosi — mijozlar sharhlaridan hisoblanadi
                'rating' => (float) ($b->ugc_aggregate_score ?? 0),
                'reviews_count' => (int) ($b->ugc_reviews_count ?? 0),
                'deeplink' => ProductDeeplink::forProduct('book', (int) $b->id, $b->artikul),
            ])->all();
        } else {
            $page = $seller->stationeries()->where('is_hidden', false)->orderByDesc('updated_at')->paginate($per);
            $data = collect($page->items())->map(fn ($s) => [
                'id' => $s->id,
                'type' => 'stationery',
                'name' => $s->name,
                'code' => $s->barcode,
                'price' => (int) ($s->price ?? 0),
                'stock' => (int) ($s->stock ?? 0),
                'in_stock' => (int) ($s->stock ?? 0) > 0,
                'rating' => (float) ($s->ugc_aggregate_score ?? 0),
                'reviews_count' => (int) ($s->ugc_reviews_count ?? 0),
                'deeplink' => ProductDeeplink::forProduct('stationery', (int) $s->id, $s->artikul ?? null),
            ])->all();
        }

        return response()->json([
            'status' => 'success',
            'data' => $data,
            'meta' => [
                'page' => $page->currentPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
            ],
        ]);
    }

    /**
     * GET products/mine/reviews — do'kon O'Z kitoblariga yozilgan sharhlarni
     * o'qiydi.
     *
     * Sharh va baho kitob KARTASIGA tegishli (bitta jismoniy kitob — bitta
     * baho), shuning uchun bir xil kitobni sotayotgan do'konlar bir xil
     * sharhlarni ko'radi. Sharh muallifining ismi berilmaydi — faqat matn,
     * sana va baho.
     */
    public function myReviews(Request $request): JsonResponse
    {
        $sellerId = $this->sellerId($request);
        if ($sellerId === null) {
            return response()->json(['status' => 'error', 'message' => "Bu kalit do'konga bog'lanmagan."], 403);
        }

        $per = min(100, max(1, (int) $request->query('per_page', 20)));
        $code = trim((string) $request->query('code', ''));

        $books = Books::query()
            ->where('seller_id', $sellerId)
            ->where('is_hidden', false)
            ->whereNull('archived_at')
            ->when($code !== '', function ($q) use ($sellerId, $code) {
                $lookup = StockCodeLookup::findSellerBook($sellerId, $code);
                $q->whereKey($lookup['book']?->id ?? 0);
            })
            ->get(['id', 'name', 'isbn', 'artikul', 'edition_id']);

        if ($books->isEmpty()) {
            return response()->json(['status' => 'success', 'data' => [], 'meta' => ['page' => 1, 'per_page' => $per, 'total' => 0]]);
        }

        // Sharhlar kartaga yozilgani uchun do'konning har kitobi bo'yicha
        // "qardosh" takliflar ham hisobga olinadi.
        $byProductId = [];
        $productIds = [];
        foreach ($books as $book) {
            foreach (CatalogOffers::siblingIds($book->edition_id ? (int) $book->edition_id : null, (int) $book->id) as $siblingId) {
                $byProductId[$siblingId] = $book;
                $productIds[] = $siblingId;
            }
        }

        $page = \App\Models\BookClub::query()
            ->whereIn('product_id', array_values(array_unique($productIds)))
            ->where('product_type', 'book')
            ->where('is_deleted', false)
            ->withCount(['likes', 'comments'])
            ->latest('id')
            ->paginate($per);

        $data = collect($page->items())->map(function ($post) use ($byProductId) {
            $book = $byProductId[(int) $post->product_id] ?? null;

            return [
                'id' => (int) $post->id,
                'product_id' => $book?->id,
                'name' => $book?->name,
                'code' => $book?->isbn,
                'artikul' => $book?->artikul,
                'text' => $post->text,
                'score' => $post->ai_post_score !== null ? (float) $post->ai_post_score : null,
                'likes_count' => (int) ($post->likes_count ?? 0),
                'comments_count' => (int) ($post->comments_count ?? 0),
                'created_at' => optional($post->created_at)?->toIso8601String(),
            ];
        })->all();

        return response()->json([
            'status' => 'success',
            'data' => $data,
            'meta' => [
                'page' => $page->currentPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
            ],
        ]);
    }

    /**
     * @return array{0: array<string,mixed>, 1: int}
     */
    private function applyStockUpdate(int $sellerId, array $data, bool $hasStock): array
    {
        $seller = Seller::find($sellerId);
        if (! $seller) {
            return [['status' => 'error', 'success' => false, 'message' => "Do'kon topilmadi."], 404];
        }

        $code = trim($data['code']);
        $type = $data['type'] ?? null;

        // ── Kitob (ISBN yoki 8 xonali artikul) ──
        if ($type === 'book' || $type === null) {
            $lookup = StockCodeLookup::findSellerBook($sellerId, $code);
            $book = $lookup['book'];
            if ($book) {
                $new = $hasStock ? (int) $data['stock'] : max(0, (int) $book->count + (int) $data['delta']);
                app(\App\Services\BranchStockService::class)->setTotalFromLegacy(
                    'book', (int) $book->id, 0, $sellerId, $new, null,
                    ['actor_type' => 'api_client', 'note' => 'API stock update']
                );

                return [$this->ok('book', $book->id, $book->name, $new), 200];
            }
            // Bir xil ISBN ostida bir nechta nashr (qattiq/yumshoq muqova) —
            // tasodifiy tanlab noto'g'ri kitobning qoldig'ini yozmaymiz.
            if ($lookup['reason'] === 'ambiguous') {
                return [[
                    'status' => 'error',
                    'success' => false,
                    'code' => 'ambiguous_code',
                    'message' => "Bu ISBN bilan bir nechta nashr bor. \"code\" sifatida 8 xonali artikul yuboring.",
                    'candidates' => StockCodeLookup::candidates($lookup['matches']),
                ], 409];
            }
            if ($type === 'book') {
                return $lookup['reason'] === 'invalid_isbn'
                    ? [['status' => 'error', 'success' => false, 'message' => "ISBN formati noto'g'ri."], 422]
                    : [['status' => 'error', 'success' => false, 'message' => "Bu kod do'koningizda topilmadi."], 404];
            }
        }

        // ── Kanselyariya (barcode) ──
        if ($type === 'stationery' || $type === null) {
            $normalized = $this->normalizeBarcode($code);
            if ($normalized !== null) {
                $stationery = $seller->stationeries()->where('barcode', $normalized)->first();
                if ($stationery) {
                    $new = $hasStock ? (int) $data['stock'] : max(0, (int) $stationery->stock + (int) $data['delta']);
                    app(\App\Services\BranchStockService::class)->setTotalFromLegacy(
                        'stationery', (int) $stationery->id, 0, $sellerId, $new, null,
                        ['actor_type' => 'api_client', 'note' => 'API stock update']
                    );

                    return [$this->ok('stationery', $stationery->id, $stationery->name, $new), 200];
                }
            }
        }

        return [['status' => 'error', 'success' => false, 'message' => "Bu kod bo'yicha mahsulot yo'q."], 404];
    }

    private function ok(string $type, int $id, ?string $name, int $stock): array
    {
        return [
            'status' => 'success',
            'success' => true,
            'type' => $type,
            'id' => $id,
            'name' => $name,
            'stock' => $stock,
            'in_stock' => $stock > 0,
        ];
    }

    private function sellerId(Request $request): ?int
    {
        $id = $request->attributes->get('api_seller_id');

        return $id ? (int) $id : null;
    }

    private function clientId(Request $request): int
    {
        $client = $request->attributes->get('api_client');

        return $client instanceof ApiClient ? (int) $client->id : 0;
    }

    private function normalizeBarcode(?string $raw): ?string
    {
        $clean = preg_replace('/[^0-9]/', '', (string) $raw) ?? '';
        if ($clean === '') {
            return null;
        }

        return strlen($clean) >= 8 && strlen($clean) <= 14 ? $clean : null;
    }
}
