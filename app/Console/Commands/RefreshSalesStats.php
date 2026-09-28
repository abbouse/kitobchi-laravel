<?php

namespace App\Console\Commands;

use App\Models\Books;
use App\Models\Stationery;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * SOTUV STATISTIKASI — HAQIQIY HAFTALIK OYNA VA KITOB DARAJASIDAGI SON.
 *
 * 1) `totalSalesWeek` "haftalik" deb nomlangan, lekin hech qachon nolga
 *    qaytmasdi: har sotuvda oshar, bekor qilinganda kamayar edi. Natijada
 *    "haftalik trend" aslida "butun davr bo'yicha eng ko'p sotilganlar" edi.
 *    Bu buyruq uni buyurtmalardan oxirgi 7 kun bo'yicha qayta hisoblaydi
 *    (kitob va kanselyariya). `OrderService` sotuvda sonni darhol oshirishda
 *    davom etadi — oraliqda ham ro'yxat yangi turadi, bu buyruq esa haqiqiy
 *    qiymatga tenglashtiradi.
 *
 * 2) Kitob kartasiga umumiy son yoziladi: `sales_week`, `sales_total` —
 *    kitobning BARCHA do'konlardagi sotuvi. Bozor ro'yxatlari shu bo'yicha
 *    saralaydi (`Books::orderByBookSales`).
 *
 * Sotuv deb hisoblanadi: bekor qilinmagan buyurtma qatori, bekor
 * qilinmagan do'kon buyurtmasi, bekor qilinmagan/qaytarilmagan asosiy
 * buyurtma. Bu `OrderService` hisoblagichining ma'nosi bilan bir xil
 * (buyurtma yaratilganda sanaladi, bekor bo'lsa ayriladi).
 */
class RefreshSalesStats extends Command
{
    protected $signature = 'products:sales-stats {--days=7 : Haftalik oyna necha kun}';

    protected $description = "Haftalik sotuvni buyurtmalardan qayta hisoblaydi va kitob kartasiga umumiy sotuvni yozadi";

    public function handle(): int
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->warn('Faqat MySQL uchun.');

            return self::SUCCESS;
        }

        $days = max(1, (int) $this->option('days'));
        $since = now()->subDays($days)->toDateTimeString();

        $books = $this->refreshWeekly('books', 'book', $since);
        $stationery = $this->refreshWeekly('stationeries', 'stationery', $since);
        $editions = $this->refreshEditionTotals();

        $this->reindex(Books::class, $books['ids']);
        $this->reindex(Stationery::class, $stationery['ids']);

        $this->info("Kitob: {$books['count']} ta, kanselyariya: {$stationery['count']} ta haftalik son yangilandi.");
        $this->info("Kitob kartalari: {$editions} ta umumiy son yangilandi.");

        return self::SUCCESS;
    }

    /**
     * @return array{count:int, ids:array<int,int>}
     */
    private function refreshWeekly(string $table, string $type, string $since): array
    {
        $window = $this->windowSql();
        $bindings = [$type, $since];

        // Qaysi qatorlar o'zgaradi — qidiruv indeksini faqat shularga yangilash uchun
        $ids = collect(DB::select("
            SELECT p.id
            FROM {$table} p
            LEFT JOIN ({$window}) w ON w.product_id = p.id
            WHERE p.totalSalesWeek <> COALESCE(w.qty, 0)
        ", $bindings))->pluck('id')->map(fn ($id) => (int) $id)->all();

        if ($ids === []) {
            return ['count' => 0, 'ids' => []];
        }

        $affected = DB::update("
            UPDATE {$table} p
            LEFT JOIN ({$window}) w ON w.product_id = p.id
            SET p.totalSalesWeek = COALESCE(w.qty, 0)
            WHERE p.totalSalesWeek <> COALESCE(w.qty, 0)
        ", $bindings);

        return ['count' => $affected, 'ids' => $ids];
    }

    /** Oxirgi N kundagi haqiqiy sotuv — mahsulot bo'yicha. */
    private function windowSql(): string
    {
        return "
            SELECT soi.product_id, SUM(soi.quantity) AS qty
            FROM seller_order_items soi
            JOIN seller_orders so ON so.id = soi.order_id
            JOIN solds s ON s.id = so.order_id
            WHERE soi.type = ?
              AND soi.cancelled_at IS NULL
              AND so.cancelled_at IS NULL
              AND (s.status IS NULL OR s.status <> 'F')
              AND (s.status_code IS NULL OR s.status_code NOT IN ('cancelled', 'returned'))
              AND s.created_at >= ?
            GROUP BY soi.product_id
        ";
    }

    /**
     * Karta = uning barcha takliflari yig'indisi (yashirilgan/arxivlangan
     * takliflar ham — o'sha orqali bo'lgan sotuv baribir shu kitobniki).
     */
    private function refreshEditionTotals(): int
    {
        return DB::update("
            UPDATE book_editions e
            LEFT JOIN (
                SELECT edition_id,
                       SUM(COALESCE(totalSalesWeek, 0)) AS week_qty,
                       SUM(COALESCE(totalSales, 0)) AS total_qty
                FROM books
                WHERE edition_id IS NOT NULL
                GROUP BY edition_id
            ) s ON s.edition_id = e.id
            SET e.sales_week = COALESCE(s.week_qty, 0),
                e.sales_total = COALESCE(s.total_qty, 0)
            WHERE e.sales_week <> COALESCE(s.week_qty, 0)
               OR e.sales_total <> COALESCE(s.total_qty, 0)
        ");
    }

    /**
     * Meilisearch hujjatida `totalSalesWeek` bor (saralash uchun). To'g'ridan
     * SQL bilan yangilanganda model hodisalari ishlamaydi — indeks o'zi
     * yangilanmaydi.
     *
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $model
     * @param  array<int,int>  $ids
     */
    private function reindex(string $model, array $ids): void
    {
        if ($ids === [] || config('scout.driver') === null || config('scout.driver') === 'null') {
            return;
        }

        foreach (array_chunk($ids, 500) as $chunk) {
            try {
                $model::query()->whereIn('id', $chunk)->searchable();
            } catch (\Throwable $e) {
                Log::warning('Sales stats reindex failed', ['error' => $e->getMessage()]);

                return;
            }
        }
    }
}
