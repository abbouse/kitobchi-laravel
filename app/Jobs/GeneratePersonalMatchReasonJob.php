<?php

namespace App\Jobs;

use App\Services\ReadingIntelligence\ReadingIntelligenceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * "Bu menga mosmi?" kartochkasidagi shaxsiy "nega mos" jumlasini (Holat A —
 * kuchli did-moslik) AI orqali generatsiya qilib, `user_book_match_reasons`
 * jadvaliga yozadi.
 *
 * MUHIM — nega alohida Job: bu chaqiruv avval `ReadingIntelligenceService::
 * personalReason()` ICHIDA, HTTP so'rovi davomida JONLI bajarilardi —
 * `OpenAIService::askJsonWithMessages()` 14s timeout'li, parse xato bo'lsa
 * yana 1 marta qayta urinadi (eng yomon holatda ~28s!) — bu Item sahifasida
 * karta "juda sekin chiqadi" muammosining ASOSIY (eng katta ta'sirli)
 * sababi edi. Endi bu chaqiruv shu Job orqali, FAQAT javob yuborilgandan
 * KEYIN (`->afterResponse()`), fonda bajariladi — foydalanuvchi javobni
 * hech qachon kutmaydi, KEYINGI safar esa tayyor (keshlangan) jumla darhol
 * ko'rsatiladi.
 */
class GeneratePersonalMatchReasonJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 45;

    public function __construct(
        public readonly int $userId,
        public readonly string $productType,
        public readonly int $productId,
        public readonly string $locale,
    ) {
    }

    public function handle(ReadingIntelligenceService $service): void
    {
        $service->generateAndCachePersonalReason(
            $this->userId,
            $this->productType,
            $this->productId,
            $this->locale,
        );
    }
}
