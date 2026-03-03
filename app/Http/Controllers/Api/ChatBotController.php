<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use App\Models\{User, Books, BookCategories, BookTag, ChatMessage, ChatMessageItem, FavouriteProducts, MyCart, Seller};
use App\Services\GeminiService;
use App\Events\BotMessageSent;

class ChatBotController extends Controller
{
    protected $geminiService;

    public function __construct(GeminiService $geminiService)
    {
        $this->geminiService = $geminiService;
    }

    private function formatBook($book, $user = null)
    {
        return [
            'id' => $book->id,
            'name' => $book->name,
            'author' => $book->author,
            'category_id' => $book->category_id,
            'category' => $book->category ? $book->category->name_uz : null,
            'images' => $book->images,
            'description' => $book->description,
            'price' => $book->price,
            'discountPrice' => $book->discountPrice,
            'count' => $book->count,
            'sales' => $book->totalSales ?? 0,
            'weekly_sales' => $book->totalSalesWeek ?? 0,
            'lang' => $book->lang ?? 'O\'zbek',
            'langType' => $book->langType ?? '',
            'coverType' => $book->coverType ?? 'Yumshoq',
            'year' => $book->year ?? now()->year,
            'favourite' => $user
                ? FavouriteProducts::where('user_id', $user->id)
                    ->where('product_id', $book->id)
                    ->exists()
                : false,
            'tags' => $book->tags->map(fn($tag) => [
                'uz' => $tag->tag_name_uz,
                'ru' => $tag->tag_name_ru,
                'en' => $tag->tag_name_en,
            ]),
            'seller' => [
                'seller_id' => $book->seller ? $book->seller->id : null,
                'shop_name' => $book->seller ? $book->seller->shop_name : null,
                'photo' => $book->seller ? $book->seller->photo : null,
                'rating' => $book->seller ? ($book->seller->rating ?? 0) : 0,
                'isVerified' => $book->seller ? ($book->seller->isVerified ?? false) : false,
            ],
        ];
    }

    public function ask(Request $request)
    {
        $user = Auth::guard('user')->user();
        if (!$user) return response()->json(['ok' => false, 'error' => 'Login kerek'], 401);

        if ($user->ai_limit < 1) return response()->json(['ok' => false, 'error' => 'Limit tugadi'], 201);
        
        $messageText = trim($request->input('message', ''));
        if (!$messageText) return response()->json(['ok' => false, 'error' => 'Xabar bo\'sh'], 400);

        $user->decrement('ai_limit');

        try {
            ChatMessage::create(['user_id' => $user->id, 'message' => $messageText, 'is_ai' => false]);

            // Intent aniqlash
            $intentPrompt = "Foydalanuvchi xabari: '$messageText'. 
Quyidagilardan FAQAT BITTASINI javob ber:
- HAGGLE: chegirma yoki arzonroq narx so'rasa
- SEARCH: kitob qidirsa, tavsiya so'rasa
- CHAT: salom, qanday hollar, umumiy suhbat
Faqat bir so'z.";
            
            $intent = strtoupper(trim($this->geminiService->askSimple($intentPrompt)));

            if (str_contains($intent, 'HAGGLE')) {
                return $this->handleHaggling($user, $messageText);
            }

            if (str_contains($intent, 'SEARCH')) {
                return $this->handleSearch($user, $messageText);
            }

            return $this->handleGeneralChat($user, $messageText);

        } catch (\Throwable $e) {
            Log::error('ChatBot Error: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'message' => $messageText,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['ok' => false, 'error' => 'Xatolik yuz berdi'], 500);
        }
    }

    private function handleSearch($user, $messageText)
    {
        // 1. Bazadan kategoriya va taglarni olamiz
        $categories = BookCategories::where('is_active', 1)->get();
        $tags = BookTag::all();

        // 2. SMART FILTERING
        $filters = $this->extractFilters($messageText, $categories, $tags);
        
        // 3. Bazaviy query
        $query = Books::whereNotNull('vectorData')
            ->where('is_approved', 1)
            ->where('is_hidden', 0)
            ->whereHas('seller', function($q) {
                $q->where('status', 'approved')
                  ->where('is_hidden', 0);
            });

        // 4. Kategoriya filtri (bazadan olgan)
        if ($filters['category_id']) {
            $query->where('category_id', $filters['category_id']);
        }

        // 5. Tag filtrlari (bazadan olgan)
        if (!empty($filters['tag_ids'])) {
            $query->whereHas('tags', function($q) use ($filters) {
                $q->whereIn('book_tags.id', $filters['tag_ids']);
            });
        }

        // 6. Muallif filtri
        if ($filters['author']) {
            $query->where('author', 'LIKE', "%{$filters['author']}%");
        }

        // 7. Davriy filtrlar
        if ($filters['period'] === 'new') {
            $query->where('created_at', '>=', now()->subMonths(3));
        } elseif ($filters['period'] === 'bestseller') {
            $query->orderByDesc('totalSales');
        } elseif ($filters['period'] === 'week_bestseller') {
            $query->orderByDesc('totalSalesWeek');
        }

        // 8. Narx filtri
        if ($filters['price_range']) {
            $query->whereBetween('price', $filters['price_range']);
        }

        // 9. Til filtri
        if ($filters['lang']) {
            $query->where('lang', $filters['lang']);
        }

        $books = $query->with(['category', 'seller', 'tags'])->get();

        if ($books->isEmpty()) {
            // Fallback - har qanday kitoblarni ko'rsatamiz
            $books = Books::whereNotNull('vectorData')
                ->where('is_approved', 1)
                ->where('is_hidden', 0)
                ->whereHas('seller', function($q) {
                    $q->where('status', 'approved')
                      ->where('is_hidden', 0);
                })
                ->with(['category', 'seller', 'tags'])
                ->inRandomOrder()
                ->limit(20)
                ->get();

            if ($books->isEmpty()) {
                return $this->finalizeAIResponse($user, [
                    'content' => '😔 Hozirda kitoblar mavjud emas. Tez orada yangi kitoblar qo\'shiladi!'
                ], 'text');
            }
        }

        // 10. VECTOR SEARCH bilan reytinglash
        $queryVector = $this->geminiService->getVector($messageText);
        
        $recommendations = $books->map(function ($book) use ($queryVector) {
            // Vector similarity (semantic qidiruv)
            $vectorSimilarity = $this->geminiService->calculateSimilarity($queryVector, $book->vectorData);
            
            // Seller quality score
            $sellerScore = $this->calculateSellerScore($book->seller);
            
            // Popularity score (0-1 oralig'ida)
            $popularityScore = min(($book->totalSales ?? 0) / 200, 1.0);
            
            // Diversity boost - har safar boshqacha bo'lishi uchun
            $diversityBoost = mt_rand(70, 130) / 100; // 0.7 - 1.3
            
            // FINAL SCORE
            // 50% vector + 25% seller + 15% popularity + 10% diversity
            $book->finalScore = (
                ($vectorSimilarity * 0.50) +
                ($sellerScore * 0.25) +
                ($popularityScore * 0.15) +
                ($diversityBoost * 0.10)
            );
            
            return $book;
        })
        ->sortByDesc('finalScore')
        ->values();

        // 11. Dublikat kitoblarni olib tashlash
        $uniqueBooks = $this->removeDuplicateBooks($recommendations);

        // 12. Top 5-8 kitobni olamiz
        $topCount = rand(10, 15);
        $topBooks = $uniqueBooks->take($topCount);

        // 13. AI uchun context tayyorlash
        $bookDetails = $topBooks->map(function($b) {
            $tagNames = $b->tags->pluck('tag_name_uz')->implode(', ');
            return sprintf(
                "ID:%d | Nomi:\"%s\" | Muallif:%s | Kategoriya:%s | Teglar:%s | Narx:%s so'm | Savdo:%d | Do'kon:%s(⭐%.1f)",
                $b->id,
                $b->name,
                $b->author,
                $b->category->name_uz ?? 'Nomalum',
                $tagNames ?: 'Yoq',
                number_format($b->discountPrice > 0 ? $b->discountPrice : $b->price),
                $b->totalSales ?? 0,
                $b->seller->shop_name ?? 'Nomalum',
                $b->seller->rating ?? 0
            );
        })->implode("\n");

        $contextInfo = "So'rov: '$messageText'\n";
        if ($filters['category_name']) $contextInfo .= "Kategoriya: {$filters['category_name']}\n";
        if (!empty($filters['tag_names'])) $contextInfo .= "Teglar: " . implode(', ', $filters['tag_names']) . "\n";

        $prompt = "📚 KITOB TAVSIYA QILISH

$contextInfo

MAVJUD KITOBLAR:
$bookDetails

VAZIFANG:
1. Foydalanuvchiga samimiy, professional va qisqa javob ber
2. Emoji qo'shib yozishga haraklat qil

JAVOB FORMATI (JSON):
{
  \"content\": \"Mijozga sening qisqa so'zing\",
  \"items\": [{\"id\": kitob_id}, ...]
}

MUHIM: items ichida FAQAT yuqoridagi ID lar bo'lsin!";

        $aiRes = $this->geminiService->askJson($prompt);
        
        return $this->finalizeAIResponse($user, $aiRes, 'books', $topBooks);
    }

    /**
     * Kategoriya va taglarni aniqlash (bazadan)
     */
    private function extractFilters($messageText, $categories, $tags)
    {
        $lower = mb_strtolower($messageText);
        
        $filters = [
            'category_id' => null,
            'category_name' => null,
            'tag_ids' => [],
            'tag_names' => [],
            'author' => null,
            'period' => null,
            'price_range' => null,
            'lang' => null,
        ];

        // Kategoriya topish (bazadan)
        foreach ($categories as $cat) {
            $catName = mb_strtolower($cat->name_uz);
            if (str_contains($lower, $catName)) {
                $filters['category_id'] = $cat->id;
                $filters['category_name'] = $cat->name_uz;
                break;
            }
        }

        // Taglarni topish (bazadan)
        foreach ($tags as $tag) {
            $tagName = mb_strtolower($tag->tag_name_uz);
            if (str_contains($lower, $tagName)) {
                $filters['tag_ids'][] = $tag->id;
                $filters['tag_names'][] = $tag->tag_name_uz;
            }
        }

        // Muallif
        if (preg_match('/(.+?)\s+ning\s+kitob/u', $lower, $matches)) {
            $filters['author'] = trim($matches[1]);
        }

        // Davriy filtrlar
        if (preg_match('/yangi|oxirgi|so\'nggi|fresh|toza/', $lower)) {
            $filters['period'] = 'new';
        } elseif (preg_match('/hafta.*eng|haftalik.*top|haftaning/', $lower)) {
            $filters['period'] = 'week_bestseller';
        } elseif (preg_match('/eng.*sotilgan|bestseller|mashhur|ommabop|top/', $lower)) {
            $filters['period'] = 'bestseller';
        }

        // Narx
        if (preg_match('/arzon|50.*ming.*gacha/', $lower)) {
            $filters['price_range'] = [0, 50000];
        } elseif (preg_match('/o\'rtacha|50.*100/', $lower)) {
            $filters['price_range'] = [50000, 100000];
        } elseif (preg_match('/qimmat|100.*ming/', $lower)) {
            $filters['price_range'] = [100000, 999999];
        }

        // Til
        if (preg_match('/o\'zbek\s+til|uzbek/', $lower)) {
            $filters['lang'] = 'O\'zbek';
        } elseif (preg_match('/rus\s+til|russian/', $lower)) {
            $filters['lang'] = 'Rus';
        } elseif (preg_match('/ingliz\s+til|english/', $lower)) {
            $filters['lang'] = 'Ingliz';
        }

        return $filters;
    }

    /**
     * Seller sifatini baholash
     */
    private function calculateSellerScore($seller)
    {
        if (!$seller || $seller->status !== 'approved' || $seller->is_hidden) {
            return 0.1;
        }

        $score = 0;

        // Reyting (35%)
        $score += ($seller->rating ?? 0) / 5 * 0.35;

        // Verified (25%)
        $score += $seller->isVerified ? 0.25 : 0.1;

        // Muvaffaqiyatli buyurtmalar (20%)
        $orders = $seller->successful_orders ?? 0;
        if ($orders > 500) $score += 0.20;
        elseif ($orders > 200) $score += 0.17;
        elseif ($orders > 100) $score += 0.15;
        elseif ($orders > 50) $score += 0.12;
        elseif ($orders > 10) $score += 0.08;

        // Javob tezligi (20%)
        $responseTime = $seller->response_time_hours ?? 24;
        if ($responseTime <= 1) $score += 0.20;
        elseif ($responseTime <= 3) $score += 0.17;
        elseif ($responseTime <= 6) $score += 0.14;
        elseif ($responseTime <= 12) $score += 0.10;
        elseif ($responseTime <= 24) $score += 0.05;

        return min($score, 1.0);
    }

    /**
     * Dublikat kitoblarni olib tashlash
     */
    private function removeDuplicateBooks($books)
    {
        $seen = [];
        $unique = collect();

        foreach ($books as $book) {
            $key = mb_strtolower(preg_replace('/\s+/', '', $book->name . $book->author));
            
            if (!isset($seen[$key])) {
                $seen[$key] = $book;
                $unique->push($book);
            } else {
                $existing = $seen[$key];
                if ($this->calculateSellerScore($book->seller) > $this->calculateSellerScore($existing->seller) + 0.05) {
                    $unique = $unique->reject(fn($b) => $b->id === $existing->id);
                    $unique->push($book);
                    $seen[$key] = $book;
                }
            }
        }

        return $unique;
    }

    private function handleHaggling($user, $messageText)
    {
        $cartTotal = $this->calculateTotal($user->id);
        if ($cartTotal == 0) {
            return $this->finalizeAIResponse($user, [
                'content' => "Savatingiz bo'sh. Avval kitob tanlang! 📚"
            ], 'text');
        }

        $floorPrice = $cartTotal * 0.85;
        $prompt = "Savat: " . number_format($cartTotal) . " so'm
Minimal: " . number_format($floorPrice) . " so'm

Mijoz: '$messageText'

Sen kitob sotuvchisi. O'zbek uslubida savdolash.
Agar rozi bo'lsa va narx {$floorPrice} dan yuqori bo'lsa, qabul qil.
Mijoz bilan avvalgi gaplashgan gaplaringizni (Suhbat tarixi) inobatga ol.

JSON:
{
  \"content\": \"javob\",
  \"status\": \"negotiating|agreed\",
  \"final_price\": narx
}";

        $aiRes = $this->geminiService->askJson($prompt);

        if (($aiRes['status'] ?? '') === 'agreed') {
            $finalPrice = $aiRes['final_price'] ?? $floorPrice;
            $promo = $this->generatePromo($user->id, $cartTotal, $finalPrice);
            $discount = $cartTotal - $finalPrice;
            $aiRes['content'] .= "\n\n🎉 " . number_format($discount) . " so'm tejadingiz!\n";
            $aiRes['content'] .= "🎫 Promokod: **$promo**";
        }

        return $this->finalizeAIResponse($user, $aiRes, 'text');
    }

    private function handleGeneralChat($user, $messageText)
    {
        // Bazadagi kategoriya va teglarni olamiz
        $categories = BookCategories::where('is_active', 1)
            ->select('name_uz')
            ->get()
            ->pluck('name_uz')
            ->implode(', ');

        $prompt = "User: '$messageText'

Sen Kitobchi AI yordamchisisisan.

MAVJUD KATEGORIYALAR: $categories

VAZIFANG:
- Agar kitob so'rasa, qanday kitob kerakligini so'ra
- Oddiy suhbat bo'lsa, qisqa javob ber
- Do'stona va professional bo'l";

        $resText = $this->geminiService->askSimple($prompt);
        return $this->finalizeAIResponse($user, ['content' => $resText], 'text');
    }

    public function addAIToCart(Request $request)
    {
        $user = Auth::guard('user')->user();
    if (!$user) return response()->json(['ok' => false, 'error' => 'Auth error'], 401);
    
    $msgId = $request->input('ai_message_id');
    $items = ChatMessageItem::where('chat_message_id', $msgId)->get();
    
    $addedCount = 0;
    foreach ($items as $item) {
        $book = Books::find($item->product_id);
        if ($book) {
            MyCart::updateOrCreate(
                [
                    'user_id' => $user->id, 
                    'product_id' => $item->product_id,
                    'product_type' => 'book' // Kitob ekanligini belgilaymiz
                ],
                ['count_item' => DB::raw('count_item + 1')]
            );
            $addedCount++;
        }
    }

        if ($addedCount == 0) {
            return response()->json(['ok' => false, 'error' => 'Kitoblar mavjud emas'], 404);
        }

        return response()->json([
            'ok' => true, 
            'message' => "$addedCount ta kitob savatga qo'shildi!",
            'count' => $addedCount
        ]);
    }

    private function finalizeAIResponse($user, $aiData, $type, $rawItems = null)
{
    // 1. Ma'lumotlar bazasiga saqlash
    $aiMsg = ChatMessage::create([
        'user_id' => $user->id,
        'message' => $aiData['content'] ?? ($type == 'text' ? $aiData : 'Xatolik yuz berdi'),
        'is_ai' => true
    ]);

    $formatted = [];
    if (isset($aiData['items']) && is_array($aiData['items'])) {
        foreach ($aiData['items'] as $item) {
            $p = ($rawItems) ? $rawItems->firstWhere('id', $item['id']) : null;
            
            if (!$p) {
                $p = Books::where('id', $item['id'])
                    ->where('is_approved', 1)
                    ->where('is_hidden', 0)
                    ->with(['category', 'seller', 'tags'])
                    ->first();
            }

            if ($p) {
                ChatMessageItem::create([
                    'chat_message_id' => $aiMsg->id,
                    'product_id' => $p->id,
                    'type' => $type
                ]);
                $formatted[] = $this->formatBook($p, $user);
            }
        }
    }
    $payload = [
        'id' => $aiMsg->id,
        'message' => $aiMsg->message, // Asosiy xabar
        'is_ai' => true,
        'data' => [
            'content' => $aiMsg->message,
            'type' => $type,
            'items' => $formatted,
            'action' => $type == 'books' ? 'show_slider' : null,
        ],
        'created_at' => now()->toDateTimeString(),
    ];

    // Socket orqali yuborish
    broadcast(new BotMessageSent($user->id, $payload))->toOthers();

    return response()->json([
        'ok' => true,
        'ai_message_id' => $aiMsg->id,
        'data' => $payload
    ]);
}

    public function history(Request $request)
{
    $user = Auth::guard('user')->user();
    if (!$user) return response()->json(['ok' => false, 'error' => 'Auth error'], 401);

    $messages = ChatMessage::with(['items.product.category', 'items.product.seller', 'items.product.tags'])
        ->where('user_id', $user->id)
        ->orderBy('created_at', 'desc')
        ->paginate(20);

    $formattedMessages = $messages->getCollection()->map(function ($msg) use ($user) {
        $data = null;
        
        if ($msg->is_ai) {
            $validItems = $msg->items->filter(fn($i) => $i->product != null);
            
            $data = [
                'content' => $msg->message,
                'type' => $validItems->isNotEmpty() ? ($validItems->first()->type ?? 'books') : 'text',
                'items' => $validItems->map(fn($item) => $this->formatBook($item->product, $user))->values()
            ];
        }

        return [
            'id' => $msg->id,
            'message' => $msg->message,
            'is_ai' => (bool)$msg->is_ai,
            'data' => $data,
            'created_at' => $msg->created_at->toDateTimeString(),
        ];
    });

    return response()->json([
        'ok' => true,
        'data' => [
            'current_page' => $messages->currentPage(),
            'data' => $formattedMessages,
            'next_page_url' => $messages->nextPageUrl(),
        ]
    ]);
}

    private function calculateTotal($uid) 
{
    // Faqat tegishli userga tegishli savatni yuklaymiz
    $cartItems = MyCart::where('user_id', $uid)->with(['product'])->get();

    if ($cartItems->isEmpty()) {
        return 0;
    }

    $total = 0;
    foreach ($cartItems as $item) {
        $price = $item->product_price; 
        $count = $item->count_item ?? 1;
        
        $total += ($price * $count);
    }

    return $total;
}

    private function generatePromo($uid, $old, $new) {
        $code = "AI-" . strtoupper(Str::random(6));
        DB::table('promocodes')->insert([
            'code' => $code,
            'type' => 'uzs',
            'amount' => max(0, $old - $new),
            'min_order_amount' => $old,
            'status' => 1,
            'usesLimit' => 1,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        return $code;
    }
}