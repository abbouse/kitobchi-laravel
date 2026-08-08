<?php

namespace App\Jobs;

use App\Services\ReadingIntelligence\ReadingInsightGenerator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Bitta mahsulot uchun Reading Intelligence tahlilini DARHOL (navbat/queue
 * orqali, fon jarayonida) generatsiya qiladi.
 *
 * MUHIM — nega kerak: `reading-intelligence:generate-insights` (har
 * daqiqalik sweep) mahsulotlarni `totalSales` bo'yicha navbatlaydi —
 * demak YANGI qo'shilgan (hali sotuvi yo'q) kitob har doim navbat OXIRIDA
 * qoladi va agar backlog bo'lsa, birinchi xaridorlar item sahifasini
 * ochganda tahlil hali tayyor bo'lmasligi mumkin. Bu job sweep'ni
 * ALMASHTIRMAYDI (u hamon umumiy backlog/qayta ishlash uchun ishlaydi) —
 * balki ANIQ shu bitta mahsulotni navbatsiz, darhol tayyorlaydi
 * (`ReadingInsightObserver` orqali kitob yaratilganda/tahrirlanganda
 * dispatch qilinadi).
 *
 * Navbat (queue) orqali ishlagani uchun HTTP so'rovini bloklamaydi —
 * kitob yaratish/tahrirlash so'rovi OpenAI javobini kutib turmaydi.
 */
class GenerateReadingInsightJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 60;

    public function __construct(
        public readonly string $productType,
        public readonly int $productId,
    ) {
    }

    public function handle(ReadingInsightGenerator $generator): void
    {
        $generator->generateAndStore($this->productType, $this->productId);
    }
}
