<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Models\ApiClient;
use App\Models\Books;
use App\Models\Seller;
use App\Models\Stationery;
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
            return response()->json(['status' => 'error', 'message' => 'This API key is not scoped to a seller.'], 403);
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
            return response()->json(['status' => 'error', 'message' => 'Provide "stock" or "delta".'], 422);
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
            return response()->json(['status' => 'error', 'message' => 'This API key is not scoped to a seller.'], 403);
        }

        $seller = Seller::find($sellerId);
        if (! $seller) {
            return response()->json(['status' => 'error', 'message' => 'Seller not found.'], 404);
        }

        $type = $request->query('type', 'book') === 'stationery' ? 'stationery' : 'book';
        $per = min(100, max(1, (int) $request->query('per_page', 50)));

        if ($type === 'book') {
            $page = $seller->books()->where('is_hidden', false)->orderByDesc('updated_at')->paginate($per);
            $data = collect($page->items())->map(fn ($b) => [
                'id' => $b->id,
                'type' => 'book',
                'name' => $b->name,
                'code' => $b->isbn,
                'price' => (int) ($b->price ?? 0),
                'stock' => (int) ($b->count ?? 0),
                'in_stock' => (int) ($b->count ?? 0) > 0,
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
     * @return array{0: array<string,mixed>, 1: int}
     */
    private function applyStockUpdate(int $sellerId, array $data, bool $hasStock): array
    {
        $seller = Seller::find($sellerId);
        if (! $seller) {
            return [['status' => 'error', 'success' => false, 'message' => 'Seller not found.'], 404];
        }

        $code = trim($data['code']);
        $type = $data['type'] ?? null;

        // ── Kitob (ISBN) ──
        if ($type === 'book' || $type === null) {
            $canonical = Books::normalizeIsbn($code);
            if ($canonical !== null) {
                $book = $seller->books()->whereIsbn($canonical)->first();
                if ($book) {
                    $new = $hasStock ? (int) $data['stock'] : max(0, (int) $book->count + (int) $data['delta']);
                    $book->count = $new;
                    $book->save();

                    return [$this->ok('book', $book->id, $book->name, $new), 200];
                }
                if ($type === 'book') {
                    return [['status' => 'error', 'success' => false, 'message' => 'ISBN not found in your store.'], 404];
                }
            } elseif ($type === 'book') {
                return [['status' => 'error', 'success' => false, 'message' => 'Invalid ISBN format.'], 422];
            }
        }

        // ── Kanselyariya (barcode) ──
        if ($type === 'stationery' || $type === null) {
            $normalized = $this->normalizeBarcode($code);
            if ($normalized !== null) {
                $stationery = $seller->stationeries()->where('barcode', $normalized)->first();
                if ($stationery) {
                    $new = $hasStock ? (int) $data['stock'] : max(0, (int) $stationery->stock + (int) $data['delta']);
                    $stationery->stock = $new;
                    $stationery->save();

                    return [$this->ok('stationery', $stationery->id, $stationery->name, $new), 200];
                }
            }
        }

        return [['status' => 'error', 'success' => false, 'message' => 'No product with this code in your store.'], 404];
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
