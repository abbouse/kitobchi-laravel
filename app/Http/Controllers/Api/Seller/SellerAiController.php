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
            'history' => ['nullable', 'array', 'max:12'],
            'history.*.role' => ['required_with:history', 'string', 'in:user,assistant'],
            'history.*.content' => ['required_with:history', 'string', 'max:4000'],
        ]);

        $seller = Auth::guard('seller')->user();
        abort_unless($seller, 401);
        $storeSellerId = (int) ($seller->parent_id ?: $seller->id);
        $storeSeller = Seller::query()->find($storeSellerId);

        $context = $this->buildSellerContext($seller, $storeSeller, $storeSellerId);

        $appLocale = strtolower((string) ($data['app_locale'] ?? ''));
        $preferredLanguage = match (true) {
            str_starts_with($appLocale, 'ru') => 'Russian',
            str_starts_with($appLocale, 'en') => 'English',
            str_starts_with($appLocale, 'ja') => 'Japanese',
            default => 'Uzbek',
        };

        $system = implode("\n", [
            "Sen Kitobchi marketplace'ning sellerlar bilan ishlaydigan ichki AI xodimisan.",
            "Sellerga oddiy chatbot kabi emas, Kitobchi jamoasidagi bilimli operator/maslahatchi kabi gapir: aniq, xotirjam, amaliy va do'kon holatidan kelib chiqib.",
            "Kitobchi doirasi: seller do'koni, buyurtmalar, kuryerga topshirish, QR/kod, filiallar, xodimlar, mahsulotlar, kitob/kanselyariya/sovg'a, artikul, stock, narx, balans, pul yechish, komissiya, premium, reklama, reyting, support ticket, hujjat/excel/csv/word/pdf tahlili.",
            "Rus tili to'liq qo'llab-quvvatlanadi. Seller ruscha yozsa ruscha javob ber; hech qachon 'ruscha javob berolmayman' yoki 'Kitobchiga murojaat qiling' deb umumiy rad javob bermagin.",
            "Javob tili: avval seller xabarining tilini tanla. O'zbekcha bo'lsa o'zbekcha, ruscha bo'lsa ruscha, inglizcha bo'lsa inglizcha. Aralash bo'lsa ko'proq ishlatilgan til. Til noaniq bo'lsa {$preferredLanguage}.",
            "Platformadan tashqari mavzu bo'lsa qisqa chegarani tushuntir va suhbatni Kitobchi bo'yicha foydali savolga qaytar. Rad javob ham seller yozgan tilda bo'lsin.",
            "Agar aniq ma'lumot kontekstda bo'lmasa, taxminni fakt sifatida aytma. Qanday tekshirish yoki qaysi sahifaga kirish kerakligini ayt.",
            "Kichik actionlar qoidasi: stock, narx, chegirma narx, sotuv holati va mahsulotni yashirish/ko'rsatish faqat preview va seller tasdig'idan keyin yangilanadi. Seller mahsulot nomi, ID yoki artikul bilan 'stockni 50 qil', 'narxini 120000 qil', 'sotuvdan ol' desa preview tayyorlanadi; mahsulot noaniq bo'lsa hujjat so'rama, mahsulot nomi/artikul/ID ni so'ra. Buyurtma statusi, balans, pul yechish, mahsulot o'chirish kabi xavfli amallarni chatdan bevosita bajarma; kerakli bosqichlarni va xavfsiz yo'lni tushuntir.",
            "Hujjat matni berilsa, uni seller do'koni konteksti bilan solishtir: qaysi mahsulotlar topildi, qaysilari topilmadi, stock/narx/artikul bo'yicha nima qilish kerakligini amaliy ayt.",
            "Maslahatlarda do'kondagi real signalga tayan: kam qolgan tovar, tekshiruvdan o'tmagan mahsulot, yangi buyurtma, qabul qilingan buyurtma, filial, xodim ruxsati, premium/verified, balans, top mahsulot.",
            "Mahsulot nomlari, artikul, ID, telefon, ISBN, barcode, summa va raqamlarni tarjima qilma.",
            "Ichki JSON kontekstni aynan ko'chirib berma; undan foydalanib, sellerga oddiy tilda xulosa va tavsiya yoz.",
            "Javob strukturasini seller savoliga mos qil: oddiy savolga 2-5 jumla, tahlil so'ralganda qisqa bo'limlar va aniq next-step.",
            "Seller konteksti JSON: " . json_encode($context, JSON_UNESCAPED_UNICODE),
        ]);

        $documentText = trim((string) ($data['document_text'] ?? ''));
        $userContent = trim($data['message']);
        if ($documentText !== '') {
            $userContent .= "\n\nSeller yuborgan hujjatdan matn:\n" . mb_substr($documentText, 0, 20000);
        }

        $stockTextAction = $this->productTextActionPreview(
            $seller,
            $storeSellerId,
            $data['message'],
            $preferredLanguage,
        );
        if ($stockTextAction !== null) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'answer' => $stockTextAction['answer'],
                    'context' => $context,
                    'action' => $stockTextAction['action'] ?? null,
                ],
            ]);
        }

        $messages = [['role' => 'system', 'content' => $system]];
        foreach (($data['history'] ?? []) as $message) {
            $content = trim((string) ($message['content'] ?? ''));
            if ($content === '') {
                continue;
            }

            $messages[] = [
                'role' => $message['role'] === 'assistant' ? 'assistant' : 'user',
                'content' => mb_substr($content, 0, 4000),
            ];
        }
        $messages[] = ['role' => 'user', 'content' => $userContent];

        try {
            $answer = app(OpenAIService::class)->askSimpleWithMessages($messages, 1100, 0.22);
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

    private function productTextActionPreview(Seller $seller, int $storeSellerId, string $message, string $language): ?array
    {
        $mutation = $this->extractProductMutation($message);
        if ($mutation === null) {
            return null;
        }

        if (! array_key_exists('new_value', $mutation)) {
            return [
                'answer' => $this->aiText($language, $mutation['missing_key']),
            ];
        }

        $match = $this->matchProductFromText($storeSellerId, $message);
        if (($match['status'] ?? null) === 'ambiguous') {
            $options = collect($match['options'] ?? [])
                ->take(5)
                ->map(fn ($item) => "#{$item['id']} {$item['name']} ({$item['type']}, artikul: " . ($item['artikul'] ?: '-') . ")")
                ->implode("\n");

            return [
                'answer' => $this->aiText($language, 'product_action_ambiguous', ['options' => $options]),
            ];
        }

        if (($match['status'] ?? null) !== 'matched') {
            return [
                'answer' => $this->aiText($language, 'product_action_missing'),
            ];
        }

        $product = $match['product'];
        $oldValue = $product[$mutation['key']] ?? null;
        $newValue = $mutation['new_value'];

        if ($mutation['key'] === 'discount_price' && (int) $newValue > 0 && (int) $newValue >= (int) ($product['price'] ?? 0)) {
            return [
                'answer' => $this->aiText($language, 'discount_price_invalid'),
            ];
        }

        $payload = [
            'source' => 'seller_chat_text',
            'message' => mb_substr($message, 0, 500),
            'product_type' => $product['type'],
            'product_id' => $product['id'],
            'product_name' => $product['name'],
            'identifier' => $product['artikul'] ?: $product['id'],
            'field' => $mutation['key'],
            'label' => $mutation['label'],
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'match_reason' => $match['reason'] ?? 'text',
            'confidence' => $match['confidence'] ?? 0.9,
        ];

        $summary = "{$product['name']}: {$mutation['label']} " . $this->formatActionValue($oldValue) . " → " . $this->formatActionValue($newValue);
        $action = SellerAiAction::create([
            'token' => (string) Str::uuid(),
            'seller_id' => $storeSellerId,
            'requested_by_seller_id' => $seller->id,
            'action_type' => 'product_field_update',
            'status' => 'preview',
            'summary' => $summary,
            'payload' => $payload,
        ]);

        return [
            'answer' => $this->aiText($language, 'product_action_preview_ready', [
                'name' => $product['name'],
                'field' => $mutation['label'],
                'old' => $this->formatActionValue($oldValue),
                'new' => $this->formatActionValue($newValue),
                'artikul' => $product['artikul'] ?: '-',
            ]),
            'action' => $this->actionPayload($action),
        ];
    }

    private function extractProductMutation(string $message): ?array
    {
        $text = mb_strtolower($message);

        if ($this->looksLikeStockUpdateRequest($message)) {
            $stock = $this->extractRequestedStock($message);
            return $stock === null
                ? ['missing_key' => 'stock_number_missing']
                : ['key' => 'stock', 'label' => 'stock', 'new_value' => $stock];
        }

        if (preg_match('/\b(discount|chegirma|скидочн|скидка)\b/u', $text)
            && preg_match('/\b(price|narx|narxi|цена|стоимост)\b/u', $text)) {
            $price = $this->extractMoneyValue($message);
            return $price === null
                ? ['missing_key' => 'price_number_missing']
                : ['key' => 'discount_price', 'label' => 'chegirma narx', 'new_value' => $price];
        }

        if (preg_match('/\b(price|narx|narxi|цена|стоимост)\b/u', $text)) {
            $price = $this->extractMoneyValue($message);
            return $price === null
                ? ['missing_key' => 'price_number_missing']
                : ['key' => 'price', 'label' => 'narx', 'new_value' => $price];
        }

        if (preg_match('/sotuvdan\s+ol|sotuvdan\s+yech|faolsiz|aktivmas|inactive|deactivate|выключ|сними\s+с\s+продаж|убери\s+с\s+продаж/u', $text)) {
            return ['key' => 'status', 'label' => 'sotuv holati', 'new_value' => false];
        }

        if (preg_match('/sotuvga\s+qo[‘\']?y|faollashtir|aktivlashtir|active|activate|включ|верни\s+в\s+продаж|поставь\s+на\s+продаж/u', $text)) {
            return ['key' => 'status', 'label' => 'sotuv holati', 'new_value' => true];
        }

        if (preg_match('/yashir|hide|скрой|скрыть/u', $text)) {
            return ['key' => 'is_hidden', 'label' => "ko'rinish holati", 'new_value' => true];
        }

        if (preg_match('/ko[‘\']?rsat|show|покажи|отобраз/u', $text)) {
            return ['key' => 'is_hidden', 'label' => "ko'rinish holati", 'new_value' => false];
        }

        return null;
    }

    private function extractMoneyValue(string $message): ?int
    {
        $normalized = str_replace(["\xc2\xa0", ' '], ' ', $message);
        $patterns = [
            '/(?:price|narx|narxi|цена|стоимость|скидочн\w*\s+цена|chegirma\w*\s+narx)\D{0,40}(\d[\d\s.,]{0,18})/iu',
            '/(\d[\d\s.,]{0,18})\s*(?:so[‘\']?m|sum|uzs|сум)\b/iu',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $normalized, $match)) {
                $digits = preg_replace('/\D/u', '', $match[1]);
                if ($digits !== '') {
                    return (int) $digits;
                }
            }
        }

        if (! preg_match_all('/\d[\d\s.,]{0,18}/u', $normalized, $matches) || empty($matches[0])) {
            return null;
        }

        $values = [];
        foreach ($matches[0] as $raw) {
            $digits = preg_replace('/\D/u', '', $raw);
            if ($digits !== '') {
                $values[] = (int) $digits;
            }
        }

        $values = array_values(array_filter($values, fn ($value) => $value >= 0));
        return $values === [] ? null : (int) end($values);
    }

    private function formatActionValue(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'active' : 'inactive';
        }

        if (is_numeric($value)) {
            return number_format((int) $value, 0, '.', ' ');
        }

        return (string) ($value ?? '-');
    }

    private function stockTextActionPreview(Seller $seller, int $storeSellerId, string $message, string $language): ?array
    {
        if (! $this->looksLikeStockUpdateRequest($message)) {
            return null;
        }

        $newStock = $this->extractRequestedStock($message);
        if ($newStock === null) {
            return [
                'answer' => $this->aiText($language, 'stock_number_missing'),
            ];
        }

        $match = $this->matchProductFromText($storeSellerId, $message);
        if (($match['status'] ?? null) === 'ambiguous') {
            $options = collect($match['options'] ?? [])
                ->take(5)
                ->map(fn ($item) => "#{$item['id']} {$item['name']} ({$item['type']}, artikul: " . ($item['artikul'] ?: '-') . ")")
                ->implode("\n");

            return [
                'answer' => $this->aiText($language, 'stock_product_ambiguous', ['options' => $options]),
            ];
        }

        if (($match['status'] ?? null) !== 'matched') {
            return [
                'answer' => $this->aiText($language, 'stock_product_missing'),
            ];
        }

        $product = $match['product'];
        $oldStock = (int) $product['stock'];
        $preview = [[
            'row' => null,
            'source' => [
                'reason' => 'seller_chat_text',
                'message' => mb_substr($message, 0, 500),
            ],
            'status' => 'matched',
            'match_reason' => $match['reason'] ?? 'text',
            'product_type' => $product['type'],
            'product_id' => $product['id'],
            'product_name' => $product['name'],
            'identifier' => $product['artikul'] ?: $product['id'],
            'old_stock' => $oldStock,
            'new_stock' => $newStock,
            'confidence' => $match['confidence'] ?? 0.9,
        ]];

        $action = SellerAiAction::create([
            'token' => (string) Str::uuid(),
            'seller_id' => $storeSellerId,
            'requested_by_seller_id' => $seller->id,
            'action_type' => 'stock_bulk_update',
            'status' => 'preview',
            'summary' => $this->stockSummary($preview),
            'payload' => [
                'parsed' => [
                    'source' => 'seller_chat_text',
                    'row_count' => 1,
                    'truncated' => false,
                ],
                'items' => $preview,
            ],
        ]);

        return [
            'answer' => $this->aiText($language, 'stock_preview_ready', [
                'name' => $product['name'],
                'old' => (string) $oldStock,
                'new' => (string) $newStock,
                'artikul' => $product['artikul'] ?: '-',
            ]),
            'action' => $this->actionPayload($action),
        ];
    }

    private function looksLikeStockUpdateRequest(string $message): bool
    {
        $text = mb_strtolower($message);
        $hasStockWord = preg_match('/\b(stock|count|qty|quantity|qoldiq|soni|sonini|miqdor|ostatok)\b|остат|количеств|наличи/u', $text) === 1;
        $hasActionWord = preg_match('/qil|qilib|o[‘\']?zgartir|yangila|qo[‘\']?y|belgila|set|change|update|сдел|постав|установ|измен|обнов/u', $text) === 1;

        return $hasStockWord && $hasActionWord;
    }

    private function extractRequestedStock(string $message): ?int
    {
        $text = str_replace(["\xc2\xa0", ' '], ' ', $message);

        $patterns = [
            '/(?:stock|qoldiq|soni|sonini|miqdor|count|qty|quantity|ostatok|остаток|количество|наличие)\D{0,40}(\d{1,7})/iu',
            '/(\d{1,7})\s*(?:ta|dona|шт|pcs)?\s*(?:qil|qilib|qilsang|qiling|ga|set|установ|постав|сдел)/iu',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $match)) {
                return max(0, (int) $match[1]);
            }
        }

        if (preg_match_all('/\d{1,7}/u', $text, $matches) && ! empty($matches[0])) {
            $numbers = $matches[0];
            return max(0, (int) end($numbers));
        }

        return null;
    }

    private function matchProductFromText(int $sellerId, string $message): array
    {
        $typeHint = $this->extractProductTypeHint($message);

        if (preg_match('/(?:artikul|артикул|sku)\s*[:#-]?\s*([A-Za-z0-9]+)/iu', $message, $match)) {
            $product = $this->findProductByIdentifier($sellerId, $match[1], $typeHint);
            if ($product) {
                return ['status' => 'matched', 'product' => $product, 'reason' => 'artikul', 'confidence' => 0.98];
            }
        }

        if (preg_match('/(?:^|\s)#(\d{1,10})\b/u', $message, $match)
            || preg_match('/\b(?:id|айди)\s*[:#-]?\s*(\d{1,10})\b/iu', $message, $match)) {
            $product = $this->findProductById($sellerId, (int) $match[1], $typeHint);
            if ($product) {
                return ['status' => 'matched', 'product' => $product, 'reason' => 'id', 'confidence' => 0.95];
            }
        }

        $name = $this->extractProductNameFromStockMessage($message);
        if ($name === null) {
            return ['status' => 'missing'];
        }

        $matches = $this->findProductsByName($sellerId, $name, $typeHint);
        if (count($matches) === 1) {
            return ['status' => 'matched', 'product' => $matches[0], 'reason' => 'name', 'confidence' => 0.82];
        }

        if (count($matches) > 1) {
            return ['status' => 'ambiguous', 'options' => $matches];
        }

        return ['status' => 'missing'];
    }

    private function extractProductTypeHint(string $message): ?string
    {
        $text = mb_strtolower($message);
        if (preg_match('/\b(book|kitob|книга|книгу|китоб)\b/u', $text)) {
            return 'book';
        }
        if (preg_match('/\b(stationery|kanselyariya|канцеляр|канстовар)\b/u', $text)) {
            return 'stationery';
        }

        return null;
    }

    private function extractProductNameFromStockMessage(string $message): ?string
    {
        foreach (['/"([^"]{2,120})"/u', "/'([^']{2,120})'/u", '/«([^»]{2,120})»/u'] as $pattern) {
            if (preg_match($pattern, $message, $match)) {
                return trim($match[1]);
            }
        }

        $text = mb_strtolower($message);
        $text = preg_replace('/(?:stock|qoldiq|soni|sonini|miqdor|count|qty|quantity|ostatok|остаток|количество|наличие|price|narx|narxi|цена|стоимост|discount|chegirma|скидочн|скидка)\D{0,40}\d{1,12}/iu', ' ', $text);
        $text = preg_replace('/\d{1,7}\s*(?:ta|dona|шт|pcs)?\s*(?:qil|qilib|qilsang|qiling|ga|set|установ|постав|сдел)?/iu', ' ', $text);
        $text = preg_replace('/\b(?:shu|ushbu|mana|bu|maxsulotni|mahsulotni|tovarni|productni|kitobni|книгу|товар|товара|этот|эту|данный|пожалуйста|iltimos|please|stock|qoldiq|soni|sonini|price|narx|narxi|chegirma|discount|скидка|цена|sotuvdan|sotuvga|faollashtir|faolsiz|yashir|ko[‘\']?rsat|qil|qilib|ber|set|change|update|сделай|поставь|установи|измени|включи|выключи|скрой|покажи)\b/iu', ' ', $text);
        $text = preg_replace('/[^\p{L}\p{N}\s\.\-]/u', ' ', $text);
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? '');

        return mb_strlen($text) >= 2 ? $text : null;
    }

    private function findProductByIdentifier(int $sellerId, string $identifier, ?string $typeHint = null): ?array
    {
        $identifier = preg_replace('/\s+/', '', trim($identifier));
        if ($identifier === '') {
            return null;
        }

        if ($typeHint !== 'stationery') {
            $book = Books::query()
                ->where('seller_id', $sellerId)
                ->where(fn ($query) => $query->where('artikul', $identifier)->orWhere('isbn', $identifier))
                ->first(['id', 'name', 'artikul', 'price', 'discountPrice', 'status', 'is_hidden']);
            if ($book) {
                return $this->productArray($book, 'book');
            }
        }

        if ($typeHint !== 'book') {
            $stationery = Stationery::query()
                ->where('seller_id', $sellerId)
                ->where(fn ($query) => $query->where('artikul', $identifier)->orWhere('barcode', $identifier))
                ->first(['id', 'name', 'artikul', 'price', 'discount_price', 'status', 'is_hidden']);
            if ($stationery) {
                return $this->productArray($stationery, 'stationery');
            }
        }

        return null;
    }

    private function findProductById(int $sellerId, int $id, ?string $typeHint = null): ?array
    {
        if ($id <= 0) {
            return null;
        }

        if ($typeHint !== 'stationery') {
            $book = Books::query()->where('seller_id', $sellerId)->find($id, ['id', 'name', 'artikul', 'price', 'discountPrice', 'status', 'is_hidden']);
            if ($book) {
                return $this->productArray($book, 'book');
            }
        }

        if ($typeHint !== 'book') {
            $stationery = Stationery::query()->where('seller_id', $sellerId)->find($id, ['id', 'name', 'artikul', 'price', 'discount_price', 'status', 'is_hidden']);
            if ($stationery) {
                return $this->productArray($stationery, 'stationery');
            }
        }

        return null;
    }

    private function findProductsByName(int $sellerId, string $name, ?string $typeHint = null): array
    {
        $name = trim($name);
        if ($name === '') {
            return [];
        }

        $results = [];
        if ($typeHint !== 'stationery') {
            $books = Books::query()
                ->where('seller_id', $sellerId)
                ->where(fn ($query) => $query
                    ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($name)])
                    ->orWhere('name', 'like', '%' . $name . '%'))
                ->limit(6)
                ->get(['id', 'name', 'artikul', 'price', 'discountPrice', 'status', 'is_hidden']);

            foreach ($books as $book) {
                $results[] = $this->productArray($book, 'book');
            }
        }

        if ($typeHint !== 'book') {
            $stationeries = Stationery::query()
                ->where('seller_id', $sellerId)
                ->where(fn ($query) => $query
                    ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($name)])
                    ->orWhere('name', 'like', '%' . $name . '%'))
                ->limit(6)
                ->get(['id', 'name', 'artikul', 'price', 'discount_price', 'status', 'is_hidden']);

            foreach ($stationeries as $stationery) {
                $results[] = $this->productArray($stationery, 'stationery');
            }
        }

        return array_slice($results, 0, 6);
    }

    private function productArray(Books|Stationery $product, string $type): array
    {
        return [
            'type' => $type,
            'id' => $product->id,
            'name' => $product->name,
            'artikul' => $product->artikul,
            'stock' => (int) ($type === 'book' ? $product->count : $product->stock),
            'price' => (int) $product->price,
            'discount_price' => (int) ($type === 'book' ? ($product->discountPrice ?? 0) : ($product->discount_price ?? 0)),
            'status' => (bool) $product->status,
            'is_hidden' => (bool) $product->is_hidden,
        ];
    }

    private function aiText(string $language, string $key, array $replace = []): string
    {
        $locale = match ($language) {
            'Russian' => 'ru',
            'English' => 'en',
            default => 'uz',
        };

        $texts = [
            'uz' => [
                'stock_number_missing' => "Stockni o'zgartirishim uchun yangi qoldiq sonini yozing. Masalan: artikul 100123 stockni 50 qil.",
                'stock_product_missing' => "Qaysi mahsulot ekanini aniq topa olmadim. Mahsulot nomi, ID yoki artikulini yozing, masalan: artikul 100123 stockni 50 qil.",
                'stock_product_ambiguous' => "Bir nechta o'xshash mahsulot topildi. Qaysi biri kerakligini ID yoki artikul bilan yozing:\n:options",
                'stock_preview_ready' => ":name uchun stock o'zgarishi tayyor: :old → :new. Artikul: :artikul. Tasdiqlasangiz, shu qiymat bazada yangilanadi.",
                'price_number_missing' => "Narxni o'zgartirishim uchun yangi summani ham yozing. Masalan: artikul 100123 narxini 120000 qil.",
                'product_action_missing' => "Qaysi mahsulot ekanini aniq topa olmadim. Mahsulot nomi, ID yoki artikulini yozing.",
                'product_action_ambiguous' => "Bir nechta o'xshash mahsulot topildi. Qaysi biri kerakligini ID yoki artikul bilan yozing:\n:options",
                'discount_price_invalid' => "Chegirma narx asosiy narxdan kichik bo'lishi kerak. To'g'ri chegirma narxini yozing.",
                'product_action_preview_ready' => ":name uchun :field o'zgarishi tayyor: :old → :new. Artikul: :artikul. Tasdiqlasangiz, bazada yangilanadi.",
            ],
            'ru' => [
                'stock_number_missing' => "Чтобы изменить остаток, напишите новое количество. Например: артикул 100123 сделай stock 50.",
                'stock_product_missing' => "Я не смог точно определить товар. Напишите название, ID или артикул товара, например: артикул 100123 сделай stock 50.",
                'stock_product_ambiguous' => "Нашёл несколько похожих товаров. Уточните нужный товар по ID или артикулу:\n:options",
                'stock_preview_ready' => "Изменение остатка для :name готово: :old → :new. Артикул: :artikul. Подтвердите, и я обновлю значение в базе.",
                'price_number_missing' => "Чтобы изменить цену, напишите новую сумму. Например: артикул 100123 поставь цену 120000.",
                'product_action_missing' => "Я не смог точно определить товар. Напишите название, ID или артикул товара.",
                'product_action_ambiguous' => "Нашёл несколько похожих товаров. Уточните нужный товар по ID или артикулу:\n:options",
                'discount_price_invalid' => "Цена со скидкой должна быть меньше основной цены. Напишите корректную цену со скидкой.",
                'product_action_preview_ready' => "Изменение поля :field для :name готово: :old → :new. Артикул: :artikul. Подтвердите, и я обновлю значение в базе.",
            ],
            'en' => [
                'stock_number_missing' => "To change stock, send the new quantity. Example: artikul 100123 set stock to 50.",
                'stock_product_missing' => "I could not identify the exact product. Send the product name, ID, or artikul, for example: artikul 100123 set stock to 50.",
                'stock_product_ambiguous' => "I found several similar products. Please specify the exact one by ID or artikul:\n:options",
                'stock_preview_ready' => "Stock change is ready for :name: :old → :new. Artikul: :artikul. Confirm it and I will update the database.",
                'price_number_missing' => "To change price, send the new amount too. Example: artikul 100123 set price to 120000.",
                'product_action_missing' => "I could not identify the exact product. Send the product name, ID, or artikul.",
                'product_action_ambiguous' => "I found several similar products. Please specify the exact one by ID or artikul:\n:options",
                'discount_price_invalid' => "Discount price must be lower than the main price. Send a valid discount price.",
                'product_action_preview_ready' => "Change is ready for :name, field :field: :old → :new. Artikul: :artikul. Confirm it and I will update the database.",
            ],
        ];

        $text = $texts[$locale][$key] ?? $texts['uz'][$key] ?? $key;
        foreach ($replace as $name => $value) {
            $text = str_replace(':' . $name, (string) $value, $text);
        }

        return $text;
    }

    private function buildSellerContext(Seller $actor, ?Seller $storeSeller, int $storeSellerId): array
    {
        $bookBase = Books::query()->where('seller_id', $storeSellerId);
        $stationeryBase = Stationery::query()->where('seller_id', $storeSellerId);
        $ordersBase = SellerOrder::query()->where('seller_id', $storeSellerId);

        $lowBooks = (clone $bookBase)
            ->whereStockAvailable('>', 0)
            ->whereStockAvailable('<=', 5)
            ->orderByStock('asc')
            ->limit(5)
            ->get(['id', 'name', 'artikul', 'price'])
            ->map(fn (Books $book) => [
                'type' => 'book',
                'id' => $book->id,
                'name' => $book->name,
                'artikul' => $book->artikul,
                'stock' => (int) $book->count,
                'price' => (int) $book->price,
            ])
            ->values();

        $lowStationery = (clone $stationeryBase)
            ->whereStockAvailable('>', 0)
            ->whereStockAvailable('<=', 5)
            ->orderByStock('asc')
            ->limit(5)
            ->get(['id', 'name', 'artikul', 'price'])
            ->map(fn (Stationery $item) => [
                'type' => 'stationery',
                'id' => $item->id,
                'name' => $item->name,
                'artikul' => $item->artikul,
                'stock' => (int) $item->stock,
                'price' => (int) $item->price,
            ])
            ->values();

        $topBooks = (clone $bookBase)
            ->orderByDesc('totalSales')
            ->limit(5)
            ->get(['id', 'name', 'artikul', 'totalSales', 'totalRevenue'])
            ->map(fn (Books $book) => [
                'type' => 'book',
                'id' => $book->id,
                'name' => $book->name,
                'artikul' => $book->artikul,
                'sold' => (int) $book->totalSales,
                'revenue' => (int) $book->totalRevenue,
                'stock' => (int) $book->count,
            ])
            ->values();

        $topStationery = (clone $stationeryBase)
            ->orderByDesc('totalSales')
            ->limit(5)
            ->get(['id', 'name', 'artikul', 'totalSales', 'totalRevenue'])
            ->map(fn (Stationery $item) => [
                'type' => 'stationery',
                'id' => $item->id,
                'name' => $item->name,
                'artikul' => $item->artikul,
                'sold' => (int) $item->totalSales,
                'revenue' => (int) $item->totalRevenue,
                'stock' => (int) $item->stock,
            ])
            ->values();

        $recentOrders = (clone $ordersBase)
            ->latest()
            ->limit(6)
            ->get(['id', 'order_id', 'amount', 'delivery_type', 'status', 'status_code', 'created_at'])
            ->map(fn (SellerOrder $order) => [
                'seller_order_id' => $order->id,
                'main_order_id' => $order->order_id,
                'amount' => (int) $order->amount,
                'status' => $order->status_code,
                'legacy_status' => $order->status,
                'delivery_type' => $order->delivery_type,
                'created_at' => optional($order->created_at)->format('d.m.Y H:i'),
            ])
            ->values();

        $locations = $storeSeller
            ? $storeSeller->locations()
                ->withCount('staff')
                ->limit(8)
                ->get(['id', 'fullAddress', 'description', 'is_main'])
                ->map(fn ($location) => [
                    'id' => $location->id,
                    'address' => $location->fullAddress,
                    'description' => $location->description,
                    'is_main' => (bool) $location->is_main,
                    'staff_count' => (int) $location->staff_count,
                ])
                ->values()
            : collect();

        $latestActions = SellerAiAction::query()
            ->where('seller_id', $storeSellerId)
            ->latest()
            ->limit(5)
            ->get(['action_type', 'status', 'summary', 'created_at', 'applied_at'])
            ->map(fn (SellerAiAction $action) => [
                'action_type' => $action->action_type,
                'status' => $action->status,
                'summary' => $action->summary,
                'created_at' => optional($action->created_at)->format('d.m.Y H:i'),
                'applied_at' => optional($action->applied_at)->format('d.m.Y H:i'),
            ])
            ->values();

        $statusCounts = [
            'payment_pending' => (clone $ordersBase)->whereIn('status', [0, '0', 'payment_pending'])->count(),
            'new' => (clone $ordersBase)->whereIn('status', [1, '1', 'new', 'pending'])->count(),
            'accepted' => (clone $ordersBase)->whereIn('status', [2, '2', 'accepted', 'packing', 'packed'])->count(),
            'handed_to_courier' => (clone $ordersBase)->whereIn('status', [3, '3', 'handed_to_courier'])->count(),
            'cancelled' => (clone $ordersBase)->whereIn('status', [4, '4', 'cancelled'])->count(),
        ];

        return [
            'shop' => [
                'id' => $storeSellerId,
                'name' => $storeSeller?->shop_name,
                'status' => $storeSeller?->status,
                'is_hidden' => (bool) ($storeSeller?->is_hidden ?? false),
                'is_verified' => (bool) ($storeSeller?->isVerified ?? false),
                'is_premium' => (bool) ($storeSeller?->isPremiumShop ?? false),
                'premium_expires_at' => optional($storeSeller?->isPremiumExpiresAt)->format('d.m.Y H:i'),
                'activity_types' => $storeSeller?->activity_types ?? [],
                'rating' => $storeSeller?->rating,
                'rating_reviews_count' => (int) ($storeSeller?->rating_reviews_count ?? 0),
                'reputation_score' => $storeSeller?->reputation_score,
                'commission_percent' => $storeSeller?->commission_percent,
                'balance' => (int) ($storeSeller?->balance ?? 0),
                'balance_human' => number_format((int) ($storeSeller?->balance ?? 0), 0, '.', ' ') . " so'm",
                'contract_status' => $storeSeller?->contract_computed_status,
                'contract_days_remaining' => $storeSeller?->contract_days_remaining,
            ],
            'actor' => [
                'id' => $actor->id,
                'is_owner' => ! $actor->parent_id,
                'role' => $actor->role,
                'staff_status' => $actor->staff_status,
                'assigned_location_id' => $actor->seller_location_id,
                'can_withdraw_balance' => (bool) $actor->can_withdraw_balance,
            ],
            'catalog' => [
                'books_count' => (clone $bookBase)->count(),
                'active_books_count' => (clone $bookBase)->where('status', true)->where('is_hidden', false)->count(),
                'stationery_count' => (clone $stationeryBase)->count(),
                'active_stationery_count' => (clone $stationeryBase)->where('status', true)->where('is_hidden', false)->count(),
                'low_stock' => $lowBooks->merge($lowStationery)->take(10)->values(),
                'out_of_stock' => [
                    'books' => (clone $bookBase)->whereStockAvailable('<=', 0)->count(),
                    'stationery' => (clone $stationeryBase)->whereStockAvailable('<=', 0)->count(),
                ],
                'not_approved' => [
                    'books' => (clone $bookBase)->where('is_approved', 0)->count(),
                    'stationery' => (clone $stationeryBase)->where('is_approved', 0)->count(),
                ],
                'top_products' => $topBooks->merge($topStationery)
                    ->sortByDesc('sold')
                    ->take(8)
                    ->values(),
            ],
            'orders' => [
                'total' => (clone $ordersBase)->count(),
                'today' => (clone $ordersBase)->whereDate('created_at', today())->count(),
                'last_7_days' => (clone $ordersBase)->where('created_at', '>=', now()->subDays(7))->count(),
                'status_counts' => $statusCounts,
                'recent' => $recentOrders,
            ],
            'locations' => $locations,
            'ai_actions' => [
                'available_safe_actions' => [
                    'parse_document',
                    'stock_bulk_update_preview',
                    'stock_bulk_update_apply_after_seller_confirmation',
                    'product_stock_price_status_visibility_preview',
                    'product_field_update_apply_after_seller_confirmation',
                    'rollback_applied_stock_update',
                    'rollback_applied_product_field_update',
                ],
                'not_allowed_directly_in_chat' => [
                    'withdraw_money',
                    'change_order_status',
                    'delete_product',
                    'change_price_without_preview',
                    'change_balance',
                ],
                'latest' => $latestActions,
            ],
        ];
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

        if (! in_array($action->action_type, ['stock_bulk_update', 'product_field_update'], true)) {
            return response()->json(['status' => 'error', 'message' => 'Noma’lum action turi.'], 422);
        }

        $result = DB::transaction(function () use ($action) {
            if ($action->action_type === 'product_field_update') {
                return $this->applyProductFieldUpdate($action);
            }

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
                $this->writeStockViaService($type, $product, (int) $newStock, $action, 'AI: stock yangilandi');

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
            'message' => count($result) . ' ta o‘zgarish bajarildi.',
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
            if ($action->action_type === 'product_field_update') {
                return $this->rollbackProductFieldUpdate($action);
            }

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
                $this->writeStockViaService($type, $product, (int) $oldStock, $action, 'AI: stock rollback');

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

    private function applyProductFieldUpdate(SellerAiAction $action): array
    {
        $payload = $action->payload ?? [];
        $type = $payload['product_type'] ?? null;
        $id = (int) ($payload['product_id'] ?? 0);
        $field = (string) ($payload['field'] ?? '');
        $column = $this->productFieldColumn($type, $field);

        if (! $column || $id <= 0) {
            $action->update([
                'status' => 'cancelled',
                'result' => ['error' => 'invalid_product_field_update_payload'],
            ]);
            return [];
        }

        $product = $type === 'book'
            ? Books::query()->where('seller_id', $action->seller_id)->lockForUpdate()->find($id)
            : Stationery::query()->where('seller_id', $action->seller_id)->lockForUpdate()->find($id);

        if (! $product) {
            $action->update([
                'status' => 'cancelled',
                'result' => ['error' => 'product_not_found'],
            ]);
            return [];
        }

        $oldValue = $product->{$column};
        $newValue = $this->normalizeProductFieldValue($field, $payload['new_value'] ?? null);

        if (in_array($column, ['count', 'stock'], true)) {
            $this->writeStockViaService($type, $product, (int) $newValue, $action, 'AI: stock yangilandi');
        } else {
            $product->{$column} = $newValue;
            $product->save();
        }

        $applied = [[
            'product_type' => $type,
            'product_id' => $product->id,
            'name' => $product->name,
            'field' => $field,
            'column' => $column,
            'old_value' => $oldValue,
            'new_value' => $newValue,
        ]];

        $action->update([
            'status' => 'applied',
            'result' => ['applied' => $applied],
            'applied_at' => now(),
        ]);

        return $applied;
    }

    private function rollbackProductFieldUpdate(SellerAiAction $action): array
    {
        $rows = [];
        foreach (($action->result['applied'] ?? []) as $item) {
            $type = $item['product_type'] ?? null;
            $id = (int) ($item['product_id'] ?? 0);
            $column = (string) ($item['column'] ?? '');
            if (! in_array($column, ['count', 'stock', 'price', 'discountPrice', 'discount_price', 'status', 'is_hidden'], true)) {
                continue;
            }

            $product = $type === 'book'
                ? Books::query()->where('seller_id', $action->seller_id)->lockForUpdate()->find($id)
                : Stationery::query()->where('seller_id', $action->seller_id)->lockForUpdate()->find($id);

            if (! $product) {
                continue;
            }

            $currentValue = $product->{$column};

            if (in_array($column, ['count', 'stock'], true)) {
                $this->writeStockViaService($type, $product, (int) ($item['old_value'] ?? 0), $action, 'AI: stock rollback');
            } else {
                $product->{$column} = $item['old_value'] ?? null;
                $product->save();
            }

            $rows[] = [
                'product_type' => $type,
                'product_id' => $product->id,
                'name' => $product->name,
                'field' => $item['field'] ?? $column,
                'before_rollback_value' => $currentValue,
                'restored_value' => $item['old_value'] ?? null,
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
    }

    private function productFieldColumn(?string $type, string $field): ?string
    {
        return match ($field) {
            'stock' => $type === 'book' ? 'count' : ($type === 'stationery' ? 'stock' : null),
            'price' => 'price',
            'discount_price' => $type === 'book' ? 'discountPrice' : ($type === 'stationery' ? 'discount_price' : null),
            'status' => 'status',
            'is_hidden' => 'is_hidden',
            default => null,
        };
    }

    private function normalizeProductFieldValue(string $field, mixed $value): int|bool
    {
        return match ($field) {
            'status', 'is_hidden' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            default => max(0, (int) $value),
        };
    }


    /**
     * FILIAL STOCK: AI action orqali stock o'zgarishi service'ga yo'naltiriladi
     * (legacy ustun setterlari endi yozmaydi).
     */
    private function writeStockViaService(?string $type, $product, int $value, SellerAiAction $action, string $note): void
    {
        $stockType = $type === 'book' ? 'book' : 'stationery';
        app(\App\Services\BranchStockService::class)->setTotalFromLegacy(
            $stockType, (int) $product->id, 0, (int) $action->seller_id, max(0, $value), null,
            ['actor_type' => 'seller', 'actor_id' => $action->seller_id, 'ref_type' => 'seller_ai_action', 'ref_id' => $action->id, 'note' => $note]
        );
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

        Books::query()->where('seller_id', $sellerId)->inStock()->get(['id', 'name', 'artikul', 'isbn'])->each(function ($book) use (&$preview, $seen) {
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

        Stationery::query()->where('seller_id', $sellerId)->inStock()->get(['id', 'name', 'artikul', 'barcode'])->each(function ($item) use (&$preview, $seen) {
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
                ->first(['id', 'name', 'artikul', 'isbn']);
            if ($book) {
                return ['type' => 'book', 'id' => $book->id, 'name' => $book->name, 'stock' => (int) $book->count, 'reason' => $key, 'identifier' => $value, 'confidence' => 0.98];
            }

            $stationery = Stationery::query()
                ->where('seller_id', $sellerId)
                ->where(fn ($q) => $q->where('artikul', $value)->orWhere('barcode', $value))
                ->first(['id', 'name', 'artikul', 'barcode']);
            if ($stationery) {
                return ['type' => 'stationery', 'id' => $stationery->id, 'name' => $stationery->name, 'stock' => (int) $stationery->stock, 'reason' => $key, 'identifier' => $value, 'confidence' => 0.98];
            }
        }

        $name = mb_strtolower(trim((string) ($record['name'] ?? $row[0] ?? '')));
        if ($name === '') return null;

        $book = Books::query()->where('seller_id', $sellerId)->whereRaw('LOWER(TRIM(name)) = ?', [$name])->first(['id', 'name']);
        if ($book) {
            return ['type' => 'book', 'id' => $book->id, 'name' => $book->name, 'stock' => (int) $book->count, 'reason' => 'name', 'confidence' => 0.72];
        }

        $stationery = Stationery::query()->where('seller_id', $sellerId)->whereRaw('LOWER(TRIM(name)) = ?', [$name])->first(['id', 'name']);
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
