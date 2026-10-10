<?php

namespace App\Services;

use App\Enums\OrderStatusCode;
use App\Models\Sold;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AiBuyerWishService
{
    public function __construct(
        protected OpenAIService $openAI
    ) {}

    /**
     * Berilgan buyurtma uchun avtomatik samimiy o'zbekcha tilak yaratadi va saqlaydi.
     * Shartlar:
     * - buyurtmada buyerWish hali bo'lmasligi kerak
     * - buyurtma statusi faqat YANGI (pending) yoki QADOQLANMOQDA (packing) bo'lishi kerak
     * - kuryerga berilgan (in_delivery) yoki bekor bo'lganlarga generatsiya qilinmaydi
     */
    public function generateForOrder(Sold $order): ?string
    {
        // 1. Agar xaridor o'zi tilak yozgan bo'lsa yoki allaqachon generatsiya qilingan bo'lsa
        if (! empty(trim((string) $order->buyerWish))) {
            return $order->buyerWish;
        }

        // 2. Status tekshiruvi: faqat pending yoki packing
        $statusCode = OrderStatusCode::fromLegacy($order->status);
        if (! in_array($statusCode, [OrderStatusCode::PENDING, OrderStatusCode::PACKING], true)) {
            Log::info("AiBuyerWishService: buyurtma #{$order->id} statusi mos emas ({$order->status}), o'tkazib yuborildi.");
            return null;
        }

        // 3. OpenAI Prompt — o'zidan keyingi notanish kitobxonga samimiy tilak
        // MUHIM: Xaridor o'zidan keyin kim, qaysi kitobni olishini bilmaydi (ism, jins, kitob nomi noma'lum).
        // Tilak har qanday kitobxonga mos keladigan, mutlaqo neytral, samimiy va insoniy bo'lishi shart!
        $prompt = "Sen 'Kitobchi' internet do'konida kitob sotib olgan oddiy o'zbek kitobxonisan. "
            . "Senda o'zingdan keyin buyurtma beradigan MUTLAQO NOTANISH navbatdagi kitobxonga (uning ismi, jinsi va qanday kitob olgani senga noma'lum) chekda chop etiladigan samimiy, do'stona, qisqa tilak yozish imkoni bor.\n\n"
            . "TALABLAR:\n"
            . "- U qanday kitob olganini BILMAYSAN, shuning uchun aniq kitob nomi yoki janriga bog'lama ('zo'r asar tanlabsiz' yoki 'buni o'qiganman' DEMA). Har qanday kitobga/mutolaaga tushadigan umumiy samimiy tilak bo'lsin.\n"
            . "- Uning jinsi yoki yoshini BILMAYSAN, 'aka', 'uka', 'opa', 'singil' deb atama. Neytral, hurmatli va do'stona bo'lsin.\n"
            . "- Mutlaqo kitobiy, rasmiy yoki robotona jumlalar YOZMA. Xuddi Telegramda do'stona yozayotgandek tabiiy, og'zaki xalqona tilda yoz.\n"
            . "- Kerakli joyda ataylab xalqona so'zlashuv xatolari yoki qisqartmalari bo'lsin (masalan: 'mazza qb oqing', 'vaxtiz unumli otsin', 'choy bn zor ketadi', 'foydali bsin', 'yaxwi mutolaa', 'kayfiyatiz kutarilsin').\n"
            . "- EMOJI (smaylik) UMUMAN ISHLATMA! Chek printeri emojilarni chiqara olmaydi.\n"
            . "- Uzunligi: 1 yoki 2 qisqa jumla (60-120 belgi orasida bo'lsin).\n"
            . "- Qo'shtirnoqsiz, faqat tilak matnining o'zini qaytar.";

        try {
            $rawWish = $this->openAI->askSimple($prompt, maxTokens: 80, temperature: 0.95);
            $cleanWish = $this->cleanWishText($rawWish);

            if ($cleanWish === '') {
                $cleanWish = $this->getFallbackWish();
            }

            // Ma'lumotlar bazasiga saqlaymiz
            $order->update(['buyerWish' => $cleanWish]);
            Log::info("AiBuyerWishService: buyurtma #{$order->id} uchun tilak muvaffaqiyatli saqlandi: '{$cleanWish}'");

            return $cleanWish;
        } catch (\Throwable $e) {
            Log::error("AiBuyerWishService: buyurtma #{$order->id} uchun OpenAI xatosi: {$e->getMessage()}");
            $fallback = $this->getFallbackWish();
            $order->update(['buyerWish' => $fallback]);
            return $fallback;
        }
    }

    /**
     * 30 daqiqa oldin yaratilgan, lekin hali tilagi yo'q bo'lgan barcha mos buyurtmalarni qayta ishlash.
     */
    public function processEligibleOrders(int $limit = 40): int
    {
        $eligibleOrders = Sold::query()
            ->where('created_at', '<=', now()->subMinutes(30))
            ->where('created_at', '>=', now()->subHours(48))
            ->where(function ($query) {
                $query->whereNull('buyerWish')
                    ->orWhere('buyerWish', '')
                    ->orWhereIn('buyerWish', ['—', '-', '.', 'none']);
            })
            ->whereIn('status', [
                OrderStatusCode::PENDING->legacy(),
                OrderStatusCode::PACKING->legacy(),
            ])
            ->latest('id')
            ->limit($limit)
            ->get();

        $processed = 0;
        foreach ($eligibleOrders as $order) {
            $wish = $this->generateForOrder($order);
            if ($wish !== null) {
                $processed++;
            }
        }

        return $processed;
    }

    /**
     * OpenAI javobini tozalash (emojilar, ortiqcha tirnoqlar, probellar).
     */
    protected function cleanWishText(string $text): string
    {
        // Qo'shtirnoq va qavslarni olib tashlash
        $text = trim($text, " \t\n\r\0\x0B\"'«»“”");

        // Emojilarni olib tashlash (xprinter qora kvadrat chiqarmasligi uchun)
        $text = preg_replace('/[\x{1F600}-\x{1F64F}\x{1F300}-\x{1F5FF}\x{1F680}-\x{1F6FF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}]/u', '', $text) ?? $text;

        // Ortiqcha probellarni tozalash
        $text = preg_replace('/\s+/', ' ', trim($text)) ?? $text;

        return Str::limit($text, 160);
    }

    /**
     * OpenAI ishlamay qolsa — tabiiy insoniy samimiy fallbacklar.
     */
    protected function getFallbackWish(): string
    {
        $fallbacks = [
            'mazza qb o\'qing, vaqtingiz maroqli va unumli o\'tsin!',
            'yaxshi mutolaa tilayman, choy bilan birga juda ajoyib ketadi!',
            'har bir sahifasi manfaatli bo\'lsin, yaxshi kayfiyat tilayman!',
            'vaqtingiz mazmunli o\'tsin, yangi fikrlar va ilhom olib kelsin!',
            'mutolaa maroqli bo\'lsin, o\'qishdan charchamang!',
            'yaxwi mutolaa, har doim yaxshi kitoblar hamrohingiz bo\'lsin!',
        ];

        return $fallbacks[array_rand($fallbacks)];
    }
}
