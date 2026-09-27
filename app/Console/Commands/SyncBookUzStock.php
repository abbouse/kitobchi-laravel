<?php

namespace App\Console\Commands;

use App\Services\CatalogParsers\BookUzStockSyncService;
use Illuminate\Console\Command;

class SyncBookUzStock extends Command
{
    protected $signature = 'catalog:sync-bookuz-stock
                            {--limit=0 : Maksimal tekshiriladigan kitoblar soni (0 = barchasi)}
                            {--import-new : Book.uz dagi Qatortol qoldig\'i mavjud yangi kitoblarni ham Seller 55 ga qo\'shish}
                            {--dry-run : Bazaga yozmasdan faqat hisoblash rejimida ishlash}';

    protected $description = 'Seller 55 (Book.uz) mahsulotlarini Book.uz saytining "Toshkent - Qatortol - bosh do\'kon" filiali qoldiqlari bilan sinxronlash';

    public function handle(BookUzStockSyncService $service): int
    {
        $limit = (int) $this->option('limit');
        $importNew = (bool) $this->option('import-new');
        $dryRun = (bool) $this->option('dry-run');

        $this->info("=================================================================");
        $this->info("  BOOK.UZ (SELLER #55) — QATORTOL FILIALI QOLDIG'INI SINXRONLASH");
        $this->info("=================================================================");
        $this->line("Filial: <comment>Toshkent - Qatortol - bosh do'kon</comment>");
        $this->line("Seller ID: <comment>55</comment>");
        $this->line("Rejim: ".($dryRun ? '<fg=yellow>DRY-RUN (faqat tekshiruv, bazaga yozilmaydi)</>' : '<fg=green>Haqiqiy sinxronlash (qoldiqlar yangilanadi)</>'));
        if ($importNew) {
            $this->line("Yangi kitoblar: <comment>Qatortolda bor yangi kitoblar Seller 55 ga qo'shiladi</comment>");
        }
        $this->newLine();

        $report = $service->syncSeller55Stock(
            $limit > 0 ? $limit : null,
            $importNew,
            $dryRun,
            function (string $message) {
                $this->line("<fg=gray>[".now()->format('H:i:s')."]</> {$message}");
            }
        );

        $this->newLine();
        $this->info("=================================================================");
        $this->info("                     SINXRONLASH HISOBOTI                       ");
        $this->info("=================================================================");
        $this->table(
            ['Ko\'rsatkich', 'Qiymat'],
            [
                ['Tekshirilgan Seller 55 kitoblari', $report['scanned']],
                ['Book.uz da topilganlar', $report['matched']],
                ['Book.uz da topilmaganlar (qoldiq 0 qilindi)', $report['not_found']],
                ['Qatortol filialida bor kitoblar', $report['in_stock']],
                ['Qatortolda yo\'q (0 dona) bo\'lganlar', $report['zeroed']],
                ['Qoldig\'i o\'zgargan kitoblar soni', $report['stock_changed']],
                ['Narxi yangilangan kitoblar soni', $report['price_changed']],
                ['Jami biriktirilgan Qatortol qoldig\'i', $report['total_stock_count'].' dona'],
                ['Yangi qo\'shilgan kitoblar', $report['new_imported']],
                ['Sarflangan vaqt', $report['duration_seconds'].' soniya'],
            ]
        );

        if (! empty($report['updated_samples'])) {
            $this->newLine();
            $this->info("Yangilangan ayrim namunalar (birinchi 15 ta):");
            $rows = [];
            foreach (array_slice($report['updated_samples'], 0, 15) as $sample) {
                $priceInfo = isset($sample['new_price']) ? number_format($sample['new_price'], 0, '', ' ') : '-';
                $rows[] = [
                    $sample['id'],
                    \Illuminate\Support\Str::limit($sample['name'], 30),
                    $sample['isbn'] ?? '-',
                    $sample['old_stock'],
                    $sample['new_stock'],
                    $priceInfo,
                    $sample['match'],
                ];
            }
            $this->table(['ID', 'Nomi', 'ISBN', 'Eski qoldiq', 'Yangi (Qatortol)', 'Hozirgi narx', 'Usul'], $rows);
        }

        $this->newLine();
        $this->info("✅ Jarayon muvaffaqiyatli yakunlandi!");

        return Command::SUCCESS;
    }
}
