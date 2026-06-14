<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Models\Books;
use App\Models\Seller;
use App\Models\SellerAiAction;
use App\Models\SellerOrder;
use App\Models\Stationery;
use App\Services\OpenAIService;
use App\Services\SellerAiDocumentParser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SellerAiController extends Controller
{
    public function __construct(
        private readonly SellerAiDocumentParser $documentParser,
    ) {
        $this->middleware('auth:seller');
    }

    public function chat(Request $request): JsonResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'min:1', 'max:5000'],
            'document_text' => ['nullable', 'string', 'max:60000'],
            'app_locale' => ['nullable', 'string', 'max:12'],
        ]);

        $seller = Auth::guard('seller')->user();
        abort_unless($seller, 401);
        $storeSellerId = (int) ($seller->parent_id ?: $seller->id);
        $storeSeller = Seller::query()->find($storeSellerId);

        $context = [
            'shop_name' => $storeSeller?->shop_name,
            'seller_id' => $storeSellerId,
            'books_count' => Books::query()->where('seller_id', $storeSellerId)->count(),
            'stationery_count' => Stationery::query()->where('seller_id', $storeSellerId)->count(),
            'pending_orders' => SellerOrder::query()->where('seller_id', $storeSellerId)->where('status', 'pending')->count(),
            'accepted_orders' => SellerOrder::query()->where('seller_id', $storeSellerId)->whereIn('status', ['accepted', 'packing', 'packed'])->count(),
            'balance' => $storeSeller?->balance,
            'activity_types' => $storeSeller?->activity_types ?? [],
        ];

        $appLocale = strtolower((string) ($data['app_locale'] ?? ''));
        $preferredLanguage = str_starts_with($appLocale, 'ru')
            ? 'Russian'
            : (str_starts_with($appLocale, 'uz') ? 'Uzbek' : 'the seller message language');

        $system = implode("\n", [
            "Sen Kitobchi marketplace seller yordamchisisan.",
            "Faqat Kitobchi platformasi, seller do'koni, buyurtma, mahsulot, stock, balans, kuryer, support va hujjat tahlili haqida javob ber.",
            "Boshqa mavzu so'ralsa, muloyimlik bilan Kitobchi doirasida yordam bera olishingni ayt.",
            "Hozir write amal bajarish huquqing yo'q: stock, narx, mahsulot yoki buyurtmani o'zgartirishni so'ralsa, faqat reja va tasdiqlash uchun preview kerakligini tushuntir.",
            "Javob tili: seller xabari qaysi tilda yozilgan bo'lsa, o'sha tilda javob ber. Agar xabar tili aniq bo'lmasa, {$preferredLanguage} tilida javob ber.",
            "Agar seller o'zbekcha yozsa o'zbekcha, ruscha yozsa ruscha, inglizcha yozsa inglizcha javob ber. Aralash tilda yozsa seller ko'proq ishlatgan tilni tanla.",
            "Mahsulot nomlari, artikul, ID, telefon va raqamlarni tarjima qilma.",
            "Javoblar sodda, aniq va qisqa bo'lsin.",
            "Seller konteksti: " . json_encode($context, JSON_UNESCAPED_UNICODE),
        ]);

        $documentText = trim((string) ($data['document_text'] ?? ''));
        $userContent = trim($data['message']);
        if ($documentText !== '') {
            $userContent .= "\n\nSeller yuborgan hujjatdan matn:\n" . mb_substr($documentText, 0, 20000);
        }

        try {
            $answer = app(OpenAIService::class)->askSimpleWithMessages([
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $userContent],
            ], 900, 0.25);
        } catch (\Throwable $exception) {
            return response()->json([
                'status' => 'error',
                'message' => 'OpenAI API sozlanmagan yoki hozir javob bermayapti.',
            ], 503);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'answer' => $answer,
                'context' => $context,
            ],
        ]);
    }

    public function parseDocument(Request $request): JsonResponse
    {
        $data = $request->validate([
            'file' => ['required', 'file', 'mimes:csv,xls,xlsx,doc,docx,pdf', 'max:20480'],
        ]);

        try {
            return response()->json([
                'status' => 'success',
                'message' => 'Fayl o‘qildi.',
                'data' => $this->documentParser->parse($data['file']),
            ]);
        } catch (\Throwable $exception) {
            return response()->json([
                'status' => 'error',
                'message' => $exception->getMessage() ?: 'Faylni o‘qib bo‘lmadi.',
            ], 422);
        }
    }

    public function previewStockUpdate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'file' => ['required', 'file', 'mimes:csv,xls,xlsx,doc,docx,pdf', 'max:20480'],
            'zero_missing' => ['nullable', 'boolean'],
        ]);

        $seller = Auth::guard('seller')->user();
        abort_unless($seller, 401);
        $storeSellerId = (int) ($seller->parent_id ?: $seller->id);

        $parsed = $this->documentParser->parse($data['file']);
        $preview = $this->buildStockPreview($storeSellerId, $parsed['rows'] ?? []);
        if ($request->boolean('zero_missing')) {
            $preview = $this->appendMissingProductsAsZero($storeSellerId, $preview);
        }

        $action = SellerAiAction::create([
            'token' => (string) Str::uuid(),
            'seller_id' => $storeSellerId,
            'requested_by_seller_id' => $seller->id,
            'action_type' => 'stock_bulk_update',
            'status' => 'preview',
            'source_file_name' => $parsed['file_name'] ?? null,
            'summary' => $this->stockSummary($preview),
            'payload' => [
                'parsed' => [
                    'file_name' => $parsed['file_name'] ?? null,
                    'extension' => $parsed['extension'] ?? null,
                    'row_count' => $parsed['row_count'] ?? 0,
                    'truncated' => $parsed['truncated'] ?? false,
                ],
                'items' => $preview,
            ],
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'AI stock preview tayyor.',
            'data' => $this->actionPayload($action),
        ]);
    }

    public function applyAction(Request $request, string $token): JsonResponse
    {
        $request->validate([
            'confirm' => ['accepted'],
        ]);

        $action = $this->sellerAction($token);
        if ($action->status !== 'preview') {
            return response()->json(['status' => 'error', 'message' => 'Bu action allaqachon bajarilgan yoki yopilgan.'], 422);
        }

        if ($action->action_type !== 'stock_bulk_update') {
            return response()->json(['status' => 'error', 'message' => 'Noma’lum action turi.'], 422);
        }

        $result = DB::transaction(function () use ($action) {
            $applied = [];
            foreach (($action->payload['items'] ?? []) as $item) {
                if (($item['status'] ?? '') !== 'matched') {
                    continue;
                }

                $type = $item['product_type'] ?? null;
                $id = (int) ($item['product_id'] ?? 0);
                $newStock = max(0, (int) ($item['new_stock'] ?? 0));

                $product = $type === 'book'
                    ? Books::query()->where('seller_id', $action->seller_id)->lockForUpdate()->find($id)
                    : Stationery::query()->where('seller_id', $action->seller_id)->lockForUpdate()->find($id);

                if (! $product) {
                    continue;
                }

                $stockColumn = $type === 'book' ? 'count' : 'stock';
                $oldStock = (int) ($product->{$stockColumn} ?? 0);
                $product->{$stockColumn} = $newStock;
                $product->save();

                $applied[] = [
                    'product_type' => $type,
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'old_stock' => $oldStock,
                    'new_stock' => $newStock,
                ];
            }

            $action->update([
                'status' => 'applied',
                'result' => ['applied' => $applied],
                'applied_at' => now(),
            ]);

            return $applied;
        });

        return response()->json([
            'status' => 'success',
            'message' => count($result) . ' ta mahsulot stock qiymati yangilandi.',
            'data' => $this->actionPayload($action->fresh()),
        ]);
    }

    public function rollbackAction(string $token): JsonResponse
    {
        $action = $this->sellerAction($token);
        if ($action->status !== 'applied') {
            return response()->json(['status' => 'error', 'message' => 'Faqat bajarilgan action rollback qilinadi.'], 422);
        }

        $rolledBack = DB::transaction(function () use ($action) {
            $rows = [];
            foreach (($action->result['applied'] ?? []) as $item) {
                $type = $item['product_type'] ?? null;
                $id = (int) ($item['product_id'] ?? 0);
                $oldStock = max(0, (int) ($item['old_stock'] ?? 0));

                $product = $type === 'book'
                    ? Books::query()->where('seller_id', $action->seller_id)->lockForUpdate()->find($id)
                    : Stationery::query()->where('seller_id', $action->seller_id)->lockForUpdate()->find($id);

                if (! $product) {
                    continue;
                }

                $stockColumn = $type === 'book' ? 'count' : 'stock';
                $currentStock = (int) ($product->{$stockColumn} ?? 0);
                $product->{$stockColumn} = $oldStock;
                $product->save();

                $rows[] = [
                    'product_type' => $type,
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'before_rollback_stock' => $currentStock,
                    'restored_stock' => $oldStock,
                ];
            }

            $result = $action->result ?? [];
            $result['rolled_back'] = $rows;
            $action->update([
                'status' => 'rolled_back',
                'result' => $result,
                'rolled_back_at' => now(),
            ]);

            return $rows;
        });

        return response()->json([
            'status' => 'success',
            'message' => count($rolledBack) . ' ta mahsulot eski stock holatiga qaytarildi.',
            'data' => $this->actionPayload($action->fresh()),
        ]);
    }

    public function actionsHistory(): JsonResponse
    {
        $seller = Auth::guard('seller')->user();
        abort_unless($seller, 401);
        $storeSellerId = (int) ($seller->parent_id ?: $seller->id);

        $actions = SellerAiAction::query()
            ->where('seller_id', $storeSellerId)
            ->latest()
            ->limit(30)
            ->get()
            ->map(fn (SellerAiAction $action) => $this->actionPayload($action));

        return response()->json(['status' => 'success', 'data' => $actions]);
    }

    private function sellerAction(string $token): SellerAiAction
    {
        $seller = Auth::guard('seller')->user();
        abort_unless($seller, 401);
        $storeSellerId = (int) ($seller->parent_id ?: $seller->id);

        return SellerAiAction::query()
            ->where('seller_id', $storeSellerId)
            ->where('token', $token)
            ->firstOrFail();
    }

    private function buildStockPreview(int $sellerId, array $rows): array
    {
        $header = [];
        $items = [];

        foreach ($rows as $index => $row) {
            $clean = array_values(array_filter(array_map(fn ($v) => trim((string) $v), $row), fn ($v) => $v !== ''));
            if ($clean === []) {
                continue;
            }

            if ($index === 0 && $this->looksLikeHeader($clean)) {
                $header = array_map(fn ($v) => $this->normalizeKey($v), $clean);
                continue;
            }

            $record = $this->rowRecord($clean, $header);
            $desiredStock = $this->desiredStock($record, $clean);
            $matched = $this->matchSellerProduct($sellerId, $record, $clean);

            $items[] = [
                'row' => $index + 1,
                'source' => $record,
                'status' => $matched ? 'matched' : 'unmatched',
                'match_reason' => $matched['reason'] ?? null,
                'product_type' => $matched['type'] ?? null,
                'product_id' => $matched['id'] ?? null,
                'product_name' => $matched['name'] ?? ($record['name'] ?? $clean[0] ?? null),
                'identifier' => $matched['identifier'] ?? ($record['artikul'] ?? $record['isbn'] ?? $record['barcode'] ?? null),
                'old_stock' => $matched['stock'] ?? null,
                'new_stock' => $desiredStock,
                'confidence' => $matched ? ($matched['confidence'] ?? 0.75) : 0,
            ];
        }

        return $items;
    }

    private function appendMissingProductsAsZero(int $sellerId, array $preview): array
    {
        $seen = collect($preview)
            ->filter(fn ($item) => ($item['status'] ?? '') === 'matched')
            ->map(fn ($item) => ($item['product_type'] ?? '') . ':' . ($item['product_id'] ?? ''))
            ->all();

        Books::query()->where('seller_id', $sellerId)->where('count', '>', 0)->get(['id', 'name', 'artikul', 'isbn', 'count'])->each(function ($book) use (&$preview, $seen) {
            if (in_array('book:' . $book->id, $seen, true)) return;
            $preview[] = [
                'row' => null,
                'source' => ['reason' => 'file_missing_zero'],
                'status' => 'matched',
                'match_reason' => 'faylda yo‘q, zero_missing yoqilgan',
                'product_type' => 'book',
                'product_id' => $book->id,
                'product_name' => $book->name,
                'identifier' => $book->artikul ?: $book->isbn,
                'old_stock' => (int) $book->count,
                'new_stock' => 0,
                'confidence' => 1,
            ];
        });

        Stationery::query()->where('seller_id', $sellerId)->where('stock', '>', 0)->get(['id', 'name', 'artikul', 'barcode', 'stock'])->each(function ($item) use (&$preview, $seen) {
            if (in_array('stationery:' . $item->id, $seen, true)) return;
            $preview[] = [
                'row' => null,
                'source' => ['reason' => 'file_missing_zero'],
                'status' => 'matched',
                'match_reason' => 'faylda yo‘q, zero_missing yoqilgan',
                'product_type' => 'stationery',
                'product_id' => $item->id,
                'product_name' => $item->name,
                'identifier' => $item->artikul ?: $item->barcode,
                'old_stock' => (int) $item->stock,
                'new_stock' => 0,
                'confidence' => 1,
            ];
        });

        return $preview;
    }

    private function matchSellerProduct(int $sellerId, array $record, array $row): ?array
    {
        foreach (['artikul', 'isbn', 'barcode'] as $key) {
            $value = preg_replace('/\s+/', '', (string) ($record[$key] ?? ''));
            if ($value === '') continue;

            $book = Books::query()
                ->where('seller_id', $sellerId)
                ->where(fn ($q) => $q->where('artikul', $value)->orWhere('isbn', $value))
                ->first(['id', 'name', 'artikul', 'isbn', 'count']);
            if ($book) {
                return ['type' => 'book', 'id' => $book->id, 'name' => $book->name, 'stock' => (int) $book->count, 'reason' => $key, 'identifier' => $value, 'confidence' => 0.98];
            }

            $stationery = Stationery::query()
                ->where('seller_id', $sellerId)
                ->where(fn ($q) => $q->where('artikul', $value)->orWhere('barcode', $value))
                ->first(['id', 'name', 'artikul', 'barcode', 'stock']);
            if ($stationery) {
                return ['type' => 'stationery', 'id' => $stationery->id, 'name' => $stationery->name, 'stock' => (int) $stationery->stock, 'reason' => $key, 'identifier' => $value, 'confidence' => 0.98];
            }
        }

        $name = mb_strtolower(trim((string) ($record['name'] ?? $row[0] ?? '')));
        if ($name === '') return null;

        $book = Books::query()->where('seller_id', $sellerId)->whereRaw('LOWER(TRIM(name)) = ?', [$name])->first(['id', 'name', 'count']);
        if ($book) {
            return ['type' => 'book', 'id' => $book->id, 'name' => $book->name, 'stock' => (int) $book->count, 'reason' => 'name', 'confidence' => 0.72];
        }

        $stationery = Stationery::query()->where('seller_id', $sellerId)->whereRaw('LOWER(TRIM(name)) = ?', [$name])->first(['id', 'name', 'stock']);
        if ($stationery) {
            return ['type' => 'stationery', 'id' => $stationery->id, 'name' => $stationery->name, 'stock' => (int) $stationery->stock, 'reason' => 'name', 'confidence' => 0.72];
        }

        return null;
    }

    private function rowRecord(array $row, array $header): array
    {
        $record = [];
        foreach ($row as $index => $value) {
            $key = $header[$index] ?? match ($index) {
                0 => 'name',
                1 => 'stock',
                2 => 'artikul',
                3 => 'barcode',
                default => 'col_' . $index,
            };
            $record[$key] = $value;
        }

        return $record;
    }

    private function desiredStock(array $record, array $row): int
    {
        foreach (['stock', 'count', 'quantity', 'qty', 'qoldiq', 'soni', 'miqdor'] as $key) {
            if (isset($record[$key]) && preg_match('/-?\d+/', (string) $record[$key], $match)) {
                return max(0, (int) $match[0]);
            }
        }

        foreach (array_reverse($row) as $value) {
            if (preg_match('/-?\d+/', (string) $value, $match)) {
                return max(0, (int) $match[0]);
            }
        }

        return 0;
    }

    private function looksLikeHeader(array $row): bool
    {
        $keys = array_map(fn ($value) => $this->normalizeKey($value), $row);
        return count(array_intersect($keys, ['name', 'stock', 'count', 'quantity', 'qty', 'qoldiq', 'artikul', 'isbn', 'barcode'])) > 0;
    }

    private function normalizeKey(string $value): string
    {
        $key = Str::of($value)->lower()->replace([' ', '-', '.', '#'], '_')->toString();
        return match ($key) {
            'nomi', 'mahsulot', 'maxsulot', 'tovar', 'product', 'title' => 'name',
            'qoldiq', 'soni', 'miqdor', 'quantity', 'qty', 'count' => 'stock',
            'shtrix_kod', 'bar_kod', 'barcode' => 'barcode',
            default => $key,
        };
    }

    private function stockSummary(array $items): string
    {
        $matched = collect($items)->where('status', 'matched')->count();
        $unmatched = collect($items)->where('status', 'unmatched')->count();
        return "{$matched} ta mahsulot topildi, {$unmatched} ta qator mos kelmadi.";
    }

    private function actionPayload(SellerAiAction $action): array
    {
        return [
            'token' => $action->token,
            'action_type' => $action->action_type,
            'status' => $action->status,
            'summary' => $action->summary,
            'source_file_name' => $action->source_file_name,
            'payload' => $action->payload,
            'result' => $action->result,
            'created_at' => optional($action->created_at)->format('d.m.Y H:i'),
            'applied_at' => optional($action->applied_at)->format('d.m.Y H:i'),
            'rolled_back_at' => optional($action->rolled_back_at)->format('d.m.Y H:i'),
        ];
    }
}
