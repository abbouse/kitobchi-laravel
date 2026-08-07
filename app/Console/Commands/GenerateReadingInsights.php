<?php

namespace App\Console\Commands;

use App\Models\BookReadingInsight;
use App\Models\Books;
use App\Models\Stationery;
use App\Services\ReadingIntelligence\ReadingInsightGenerator;
use Illuminate\Console\Command;

/**
 * "Reading Intelligence" kontentini (qiyinlik, kayfiyat, kimlar uchun,
 * sharh xulosasi) FON JARAYONIDA, item sahifasi so'rovlaridan TASHQARIDA
 * generatsiya qiladi.
 *
 * MUHIM: bu buyruq mavjud emasligining o'zi item sahifasini SEKINLASHTIRMAYDI
 * — `ReadingInsightGenerator::get()` faqat mavjud keshni o'qiydi, hech qachon
 * AI kutib turmaydi. Bu buyruq ishlamasa, foydalanuvchilar shunchaki
 * "yangi mahsulot" (kontentsiz) kartasini ko'raverishadi — xato emas,
 * faqat kambag'alroq tajriba.
 *
 * Ustuvorlik: eng ko'p sotilgan / eng ko'p ko'rilgan mahsulotlardan
 * boshlanadi — kam qiziqilgan mahsulotlar keyinroq navbatga tushadi.
 */
class GenerateReadingInsights extends Command
{
    protected $signature = 'reading-intelligence:generate-insights
        {--type=all : book|stationery|all}
        {--limit=15 : Bir yurishda har turdan nechta mahsulot}
        {--force : Allaqachon tahlil qilingan mahsulotlarni ham QAYTA generatsiya qilish (masalan, AI prompt/sxema o\'zgarganda — eng ko\'p sotilganidan boshlab)}';

    protected $description = 'Reading Intelligence kartochkasi uchun mahsulot tahlilini (qiyinlik/kayfiyat/auditoriya) fon jarayonida generatsiya qiladi';

    public function handle(ReadingInsightGenerator $generator): int
    {
        $type = strtolower(trim((string) $this->option('type')));
        $limit = max(1, (int) $this->option('limit'));
        $force = (bool) $this->option('force');
        // MUHIM: OpenAI'ning RPM (daqiqasiga so'rov) limitiga urilib
        // qolmaslik uchun har bir mahsulotdan keyin pauza — ketma-ket
        // zarba bilan o'nlab so'rov yubormaslik kerak (config orqali
        // sozlanadi, kod o'zgartirish shart emas).
        $delayMs = max(0, (int) config('reading_intelligence.generation_delay_ms', 2000));

        $processed = 0;
        $failed = 0;
        $first = true;

        foreach ($this->candidateTypes($type) as $productType) {
            $ids = $this->pendingProductIds($productType, $limit, $force);

            foreach ($ids as $id) {
                if (! $first && $delayMs > 0) {
                    usleep($delayMs * 1000);
                }
                $first = false;

                $result = $generator->generateAndStore($productType, $id);
                $processed++;

                if ($result === null) {
                    $failed++;
                }
            }
        }

        $this->info("Reading Intelligence: {$processed} mahsulot qayta ishlandi, {$failed} muvaffaqiyatsiz.");

        return $failed > 0 && $failed === $processed ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @return array<int, string>
     */
    private function candidateTypes(string $type): array
    {
        return match ($type) {
            'book', 'books' => ['book'],
            'stationery', 'stationeries' => ['stationery'],
            default => ['book', 'stationery'],
        };
    }

    /**
     * Hali `book_reading_insights` yozuvi yo'q mahsulotlar — eng ko'p
     * sotilganlardan boshlab, chegaralangan miqdorda.
     *
     * `$force = true` bo'lsa — "hali yo'q" filtri olib tashlanadi, ya'ni
     * ALLAQACHON tahlil qilingan mahsulotlar ham qayta navbatga tushadi.
     * Bu holatda `generateAndStore()` ichidagi content_hash solishtiruvi
     * hal qiladi: agar mahsulot (yoki AI prompt/sxema — `schema:v2` kabi)
     * o'zgarmagan bo'lsa, hech narsa qayta yozilmaydi (AI ham chaqirilmaydi,
     * xarajat ketmaydi); faqat HAQIQATAN eskirgan yozuvlar yangilanadi.
     * Shuning uchun `--force` ni istalgan vaqt xavfsiz qayta-qayta
     * ishga tushirish mumkin — ortiqcha ish qilmaydi.
     *
     * @return array<int, int>
     */
    private function pendingProductIds(string $type, int $limit, bool $force = false): array
    {
        $query = $type === 'book' ? Books::query() : Stationery::query();

        if (! $force) {
            $existingIds = BookReadingInsight::query()
                ->where('product_type', $type)
                ->pluck('product_id');

            $query->whereNotIn('id', $existingIds);
        }

        return $query
            ->where('is_hidden', false)
            ->orderByDesc('totalSales')
            ->limit($limit)
            ->pluck('id')
            ->all();
    }
}
