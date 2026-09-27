<?php

namespace App\Console\Commands;

use App\Services\CatalogParsers\BookUzStockSyncService;
use Illuminate\Console\Command;

class SyncBookUzStock extends Command
{
    protected $signature = 'catalog:sync-bookuz-stock
                            {--limit=0 : Maksimal tekshiriladigan kitoblar soni (0 = barchasi)}
                            {--skip-new : Yangi kitoblarni import qilishni o\'tkazib yuborish (faqat mavjud kitoblarni yangilash)}
                            {--dry-run : Bazaga yozmasdan faqat hisoblash rejimida ishlash}';

    protected $description = 'Seller 55 (Book.uz) mahsulotlarini Book.uz saytidagi Qatortol (111) va Chorsu (777) filiallari qoldiqlari bilan sinxronlash, yo\'q kitoblarni qo\'shib global kartaga ulash';

    public function handle(BookUzStockSyncService $service): int
    {
        $limit = (int) $this->option('limit');
        $skipNew = (bool) $this->option('skip-new');
        $importNew = ! $skipNew;
        $dryRun = (bool) $this->option('dry-run');

        $this->info("=================================================================");
        $this->info("  BOOK.UZ (SELLER #55) — QATORTOL (111) & CHORSU (777) QOLDIQLARI");
        $this->info("=================================================================");
        $this->line("Filiallar: <comment>111 - Qatortol (bosh do'kon) & 777 - Chorsu filial</comment>");
        $this->line("Seller ID: <comment>55</comment>");
        $this->line("Rejim: ".($dryRun ? '<fg=yellow>DRY-RUN (faqat tekshiruv, bazaga yozilmaydi)</>' : '<fg=green>Haqiqiy sinxronlash (qoldiq va narxlar yangilanadi)</>'));
        $this->line("Yangi kitoblar: ".($importNew ? '<fg=cyan>Ha (Book.uz da bor kitoblar Seller 55 ga qo\'shiladi va global kartaga ulanadi)</>' : '<fg=yellow>O\'tkazib yuboriladi (--skip-new)</>'));
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
                ['Filiallarda bor kitoblar', $report['in_stock']],
                ['Filiallarda yo\'q (0 dona) bo\'lganlar', $report['zeroed']],
                ['Qoldig\'i o\'zgargan kitoblar soni', $report['stock_changed']],
                ['Narxi yangilangan kitoblar soni', $report['price_changed']],
                ['Yangi qo\'shilgan va global kitobga ulanganlar', $report['new_imported']],
                ['Global katalogga ulangan kitoblar', $report['global_linked'] ?? 0],
                ['Qatortol (111) jami qoldig\'i', ($report['qatortol_stock_count'] ?? 0).' dona'],
                ['Chorsu (777) jami qoldig\'i', ($report['chorsu_stock_count'] ?? 0).' dona'],
                ['Umumiy biriktirilgan jami qoldiq', $report['total_stock_count'].' dona'],
                ['Sarflangan vaqt', $report['duration_seconds'].' soniya'],
            ]
        );

        if (! empty($report['updated_samples'])) {
            $this->newLine();
            $this->info("Yangilangan ayrim namunalar (birinchi 15 ta):");
            $rows = [];
            foreach (array_slice($report['updated_samples'], 0, 15) as $sample) {
                $priceInfo = isset($sample['new_price']) && $sample['new_price'] > 0
                    ? number_format($sample['new_price'], 0, '', ' ').' so\'m'
                    : '-';
                $rows[] = [
                    $sample['id'],
                    \Illuminate\Support\Str::limit($sample['name'], 28),
                    $sample['isbn'] ?? '-',
                    $sample['old_stock'],
                    $sample['qatortol_stock'] ?? '-',
                    $sample['chorsu_stock'] ?? '-',
                    $sample['new_stock'],
                    $priceInfo,
                    ! empty($sample['global_linked']) ? '✅ Ulangan' : '❌',
                    $sample['match'],
                ];
            }
            $this->table(['ID', 'Nomi', 'ISBN', 'Eski', 'Qatortol (111)', 'Chorsu (777)', 'Jami', 'Hozirgi narx', 'Global', 'Usul'], $rows);
        }

        $this->newLine();
        $this->info("✅ Jarayon muvaffaqiyatli yakunlandi!");

        return Command::SUCCESS;
    }
}
