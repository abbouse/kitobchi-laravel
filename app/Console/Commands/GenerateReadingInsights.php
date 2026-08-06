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
        {--limit=30 : Bir yurishda har turdan nechta mahsulot}';

    protected $description = 'Reading Intelligence kartochkasi uchun mahsulot tahlilini (qiyinlik/kayfiyat/auditoriya) fon jarayonida generatsiya qiladi';

    public function handle(ReadingInsightGenerator $generator): int
    {
        $type = strtolower(trim((string) $this->option('type')));
        $limit = max(1, (int) $this->option('limit'));

        $processed = 0;
        $failed = 0;

        foreach ($this->candidateTypes($type) as $productType) {
            $ids = $this->pendingProductIds($productType, $limit);

            foreach ($ids as $id) {
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
     * @return array<int, int>
     */
    private function pendingProductIds(string $type, int $limit): array
    {
        $existingIds = BookReadingInsight::query()
            ->where('product_type', $type)
            ->pluck('product_id');

        $query = $type === 'book' ? Books::query() : Stationery::query();

        return $query
            ->whereNotIn('id', $existingIds)
            ->where('is_hidden', false)
            ->orderByDesc('totalSales')
            ->limit($limit)
            ->pluck('id')
            ->all();
    }
}
