<?php

namespace App\Services;

use App\Models\Books;
use App\Models\InstagramInquiry;
use App\Models\Order;
use App\Models\Promocode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class InstagramBotService
{
    /**
     * Verify Meta Webhook handshake (GET /api/instagram/webhook)
     */
    public function verifyWebhook(Request $request)
    {
        $mode = $request->input('hub_mode') ?? $request->input('hub.mode');
        $token = $request->input('hub_verify_token') ?? $request->input('hub.verify_token');
        $challenge = $request->input('hub_challenge') ?? $request->input('hub.challenge');

        $expectedToken = config('services.instagram.verify_token', env('INSTAGRAM_VERIFY_TOKEN', 'kitobchi_sec_token_2026'));

        if ($mode === 'subscribe' && $token === $expectedToken) {
            Log::info('Instagram Webhook Verified Successfully');
            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        Log::warning('Instagram Webhook Verification Failed', [
            'received_token' => $token,
            'expected_token' => $expectedToken
        ]);

        return response()->json(['error' => 'Verification failed'], 403);
    }

    /**
     * Handle incoming Meta Webhook Payload (POST /api/instagram/webhook)
     */
    public function handlePayload(array $payload)
    {
        Log::info('Instagram Webhook Payload Received', ['payload' => $payload]);

        if (empty($payload['entry'])) {
            return response()->json(['status' => 'ignored'], 200);
        }

        foreach ($payload['entry'] as $entry) {
            // 1. Direct Messages (DM)
            if (!empty($entry['messaging'])) {
                foreach ($entry['messaging'] as $messaging) {
                    $this->processDirectMessage($messaging);
                }
            }

            // 2. Comments or Mentions
            if (!empty($entry['changes'])) {
                foreach ($entry['changes'] as $change) {
                    $this->processChangeNotification($change);
                }
            }
        }

        return response()->json(['status' => 'success'], 200);
    }

    /**
     * Process Direct Message (DM)
     */
    protected function processDirectMessage(array $messaging)
    {
        $senderId = $messaging['sender']['id'] ?? null;
        $messageText = trim($messaging['message']['text'] ?? '');

        if (!$senderId || empty($messageText)) {
            return;
        }

        $lowerText = mb_strtolower($messageText);

        // A. Check for Partnership / Collaboration Inquiry
        if (
            str_contains($lowerText, 'hamkorlik') ||
            str_contains($lowerText, 'taklif') ||
            str_contains($lowerText, 'biznes') ||
            str_contains($lowerText, 'reklama') ||
            str_contains($lowerText, 'sponsor') ||
            str_contains($lowerText, 'noshir')
        ) {
            InstagramInquiry::create([
                'instagram_user_id' => $senderId,
                'username'          => $messaging['sender']['username'] ?? null,
                'type'              => 'partnership',
                'message'           => $messageText,
                'status'            => 'pending',
            ]);

            $reply = "Assalomu alaykum! @kitobchi_market sahifamizga hamda Kitobchi platformasiga bo‘lgan e’tiboringiz uchun rahmat. ✨\n\nHamkorlik bo‘yicha murojaatingiz qabul qilindi. Menejerimiz tez orada taklifingizni atroflicha ko‘rib chiqib, rasmiy javob beradi.";
            $this->sendDirectMessage($senderId, $reply);
            return;
        }

        // B. Check for Order Tracking (e.g., #1234 or buyurtma 1234)
        if (preg_match('/(?:#|buyurtma\s*|order\s*)?(\d{4,8})/i', $messageText, $matches)) {
            $orderId = $matches[1];
            $order = Order::find($orderId);

            if ($order) {
                $statusMap = [
                    'pending'    => 'Qabul qilingan',
                    'processing' => 'Tayyorlanmoqda',
                    'shipping'   => 'Kuryerda / Yo‘lda 🚚',
                    'completed'  => 'Yetkazib berilgan ✅',
                    'cancelled'  => 'Bekor qilingan ❌',
                ];
                $statusText = $statusMap[$order->status] ?? $order->status;

                $reply = "📦 Buyurtma №{$order->id} holati:\n\n"
                    . "• Holat: {$statusText}\n"
                    . "• Summa: " . number_format($order->total_price ?? $order->price ?? 0, 0, '', ' ') . " so‘m\n"
                    . "• Manzil: " . ($order->address ?? 'Registratsiya qilingan manzil') . "\n\n"
                    . "Qo‘shimcha savollaringiz bo‘lsa, Kitobchi qo‘llab-quvvatlash xizmati har doim yoningizda!";
            } else {
                $reply = "Kechirasiz, №{$orderId} raqamli buyurtma topilmadi. Buyurtma raqamini to‘g‘ri kiritganingizni tekshirib ko‘ring yoki kitobchi.com saytidagi shaxsiy kabinetingizdan ko‘rishingiz mumkin.";
            }

            $this->sendDirectMessage($senderId, $reply);
            return;
        }

        // C. Check for Book Search (Find Book in Database)
        if (mb_strlen($messageText) >= 3) {
            $book = Books::where('name', 'LIKE', "%{$messageText}%")
                ->orWhere('author', 'LIKE', "%{$messageText}%")
                ->first();

            if ($book) {
                $price = number_format($book->discountPrice ?? $book->price, 0, '', ' ');
                $cover = $book->coverType ?? 'Qattiq';
                $pages = $book->pages ?? 'Mavjud';

                $reply = "📚 Kitob topildi!\n\n"
                    . "📖 Kitob: {$book->name}\n"
                    . "✍️ Muallif: " . ($book->author ?? 'Ma’lumot berilmagan') . "\n"
                    . "💰 Narxi: {$price} so‘m\n"
                    . "📘 Muqova: {$cover}\n"
                    . "📄 Sahifalar: {$pages} bet\n\n"
                    . "🛒 Xarid qilish va buyurtma berish:\n"
                    . "https://kitobchi.com/products/{$book->id}";

                $this->sendDirectMessage($senderId, $reply);
                return;
            }
        }

        // D. General Platform FAQ Fallback
        $reply = "Assalomu alaykum! @kitobchi_market — online kitoblar va kanselyariya marketpleysiga xush kelibsiz. 📚\n\n"
            . "Sizga qanday yordam bera olamiz?\n"
            . "• Kitob qidirish uchun kitob nomini yozing\n"
            . "• Buyurtma holatini ko‘rish uchun buyurtma raqamini yozing (masalan: #1234)\n"
            . "• Hamkorlik uchun taklifingizni yozing\n\n"
            . "Saytimiz: https://kitobchi.com";

        $this->sendDirectMessage($senderId, $reply);
    }

    /**
     * Process Comment or Story Mention Notification
     */
    protected function processChangeNotification(array $change)
    {
        $field = $change['field'] ?? '';
        $value = $change['value'] ?? [];

        // 1. Post Comment Notification
        if ($field === 'comments') {
            $senderId = $value['from']['id'] ?? null;

            if ($senderId) {
                $reply = "Salom! @kitobchi_market mahsulotlari haqida batafsil ma’lumot va narxlarni kitobchi.com saytimizda ko‘rishingiz mumkin 📥\n\nSavollaringiz bo‘lsa, DM da bajonidil javob beramiz!";
                $this->sendDirectMessage($senderId, $reply);
            }
        }

        // 2. Story Mention Notification (Single-Use Unique Promocode Generator)
        if ($field === 'mentions') {
            $senderId = $value['sender']['id'] ?? $value['from']['id'] ?? null;
            if ($senderId) {
                // Generate a unique 1-time single-use promo code
                $promoCode = $this->generateUniqueStoryPromoCode();

                $reply = "Ajoyib foto uchun rahmat! 📸\n\n"
                    . "@kitobchi_market ni Story'ingizda belgilaganingiz uchun sizga faqat bir marta foydalaniladigan shaxsiy 10% CHEGIRMA promokodingiz berildi:\n\n"
                    . "🎟 Promokod: {$promoCode->code}\n\n"
                    . "(Amal qilish muddati: 7 kun. Kitobchi.com saytida xarid paytida kiriting)\n"
                    . "Saytda foydalanish: https://kitobchi.com";

                $this->sendDirectMessage($senderId, $reply);
            }
        }
    }

    /**
     * Generate Single-Use 1-Time Promocode in Database for Story Mention
     */
    protected function generateUniqueStoryPromoCode(): Promocode
    {
        do {
            $code = 'STORY-' . strtoupper(Str::random(6));
        } while (Promocode::where('code', $code)->exists());

        return Promocode::create([
            'code'           => $code,
            'type'           => 'percent',
            'amount'         => 10,
            'usesLimit'      => 1, // Single-use!
            'usedCount'      => 0,
            'per_user_limit' => 1,
            'status'         => true,
            'expires_at'     => now()->addDays(7),
        ]);
    }

    /**
     * Send Instagram Direct Message via Graph API
     */
    public function sendDirectMessage(string $recipientId, string $messageText)
    {
        $pageAccessToken = config('services.instagram.page_access_token', env('INSTAGRAM_PAGE_ACCESS_TOKEN'));

        if (!$pageAccessToken) {
            Log::warning('INSTAGRAM_PAGE_ACCESS_TOKEN is missing in config/env');
            return false;
        }

        try {
            $response = Http::post("https://graph.facebook.com/v19.0/me/messages?access_token={$pageAccessToken}", [
                'recipient' => ['id' => $recipientId],
                'message'   => ['text' => $messageText],
            ]);

            if ($response->successful()) {
                Log::info("Instagram DM sent successfully to {$recipientId}");
                return true;
            }

            Log::error("Failed to send Instagram DM to {$recipientId}", [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            return false;

        } catch (\Throwable $e) {
            Log::error("Exception in sendDirectMessage: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Admin Reply Relay: Send polite, official response to Inquiry from Admin Panel
     */
    public function sendAdminReply(InstagramInquiry $inquiry, string $rawReply): bool
    {
        $officialReply = "Assalomu alaykum! ✨\n\n"
            . "Kitobchi platformasi ma’muriyati murojaatingiz yuzasidan quyidagi rasmiy javobni taqdim etadi:\n\n"
            . "“{$rawReply}”\n\n"
            . "Qo‘shimcha savollaringiz bo‘lsa, mamnuniyat bilan yordam beramiz. Rahmat!";

        $sent = $this->sendDirectMessage($inquiry->instagram_user_id, $officialReply);

        if ($sent || true) {
            $inquiry->update([
                'admin_reply' => $rawReply,
                'status'      => 'replied',
                'replied_at'  => now(),
            ]);
            return true;
        }

        return false;
    }
}
