<?php

namespace App\Services;

use App\Models\{Sold, Books, User, Seller};
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\{Fill, Alignment, Border};

class DashboardExportService
{
    /**
     * Moliyaviy hisobotni Excel formatida eksport qilish
     */
    public function exportFinancialReport($dateFrom = null, $dateTo = null)
    {
        $dateFrom = $dateFrom ?? now()->startOfMonth();
        $dateTo = $dateTo ?? now()->endOfMonth();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header styling
        $this->styleHeader($sheet);

        // Title
        $sheet->setCellValue('A1', 'KITOBCHI.UZ - MOLIYAVIY HISOBOT');
        $sheet->mergeCells('A1:F1');
        $sheet->getStyle('A1')->getFont()->setSize(16)->setBold(true);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Date range
        $sheet->setCellValue('A2', "Davr: {$dateFrom->format('d.m.Y')} - {$dateTo->format('d.m.Y')}");
        $sheet->mergeCells('A2:F2');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Data
        $row = 4;
        
        // Summary section
        $summary = $this->getFinancialSummary($dateFrom, $dateTo);
        
        $sheet->setCellValue('A' . $row, 'Umumiy Ko\'rsatkichlar');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(12);
        $row++;

        foreach ($summary as $label => $value) {
            $sheet->setCellValue('A' . $row, $label);
            $sheet->setCellValue('B' . $row, $value);
            $row++;
        }

        $row += 2;

        // Top sellers
        $sheet->setCellValue('A' . $row, 'TOP 10 SOTUVCHILAR');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(12);
        $row++;

        $sheet->setCellValue('A' . $row, '#');
        $sheet->setCellValue('B' . $row, 'Do\'kon');
        $sheet->setCellValue('C' . $row, 'Sotuvlar');
        $sheet->setCellValue('D' . $row, 'Daromad');
        $sheet->getStyle('A' . $row . ':D' . $row)->getFont()->setBold(true);
        $row++;

        $topSellers = $this->getTopSellers($dateFrom, $dateTo, 10);
        $index = 1;
        foreach ($topSellers as $seller) {
            $sheet->setCellValue('A' . $row, $index++);
            $sheet->setCellValue('B' . $row, $seller['shop_name']);
            $sheet->setCellValue('C' . $row, $seller['total_sales']);
            $sheet->setCellValue('D' . $row, number_format($seller['total_revenue']) . ' so\'m');
            $row++;
        }

        // Auto-size columns
        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Save file
        $filename = 'moliyaviy_hisobot_' . now()->format('Y_m_d_His') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        
        $path = storage_path('app/public/exports/' . $filename);
        $writer->save($path);

        return $filename;
    }

    /**
     * Sotuvlar hisobotini eksport qilish
     */
    public function exportSalesReport($dateFrom, $dateTo)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Headers
        $sheet->setCellValue('A1', 'SOTUVLAR HISOBOTI');
        $sheet->mergeCells('A1:H1');
        $this->styleHeader($sheet);

        $row = 3;
        $headers = ['#', 'Sana', 'Buyurtma ID', 'Mijoz', 'Summa', 'Holat', 'To\'lov', 'Yetkazish'];
        
        foreach (range('A', 'H') as $index => $col) {
            $sheet->setCellValue($col . $row, $headers[$index]);
            $sheet->getStyle($col . $row)->getFont()->setBold(true);
        }
        $row++;

        // Data
        $sales = Sold::whereBetween('created_at', [$dateFrom, $dateTo])
            ->with('user')
            ->get();

        $index = 1;
        foreach ($sales as $sale) {
            $sheet->setCellValue('A' . $row, $index++);
            $sheet->setCellValue('B' . $row, $sale->created_at->format('d.m.Y H:i'));
            $sheet->setCellValue('C' . $row, '#' . $sale->id);
            $sheet->setCellValue('D' . $row, $sale->user->name ?? 'N/A');
            $sheet->setCellValue('E' . $row, number_format($sale->amount) . ' so\'m');
            $sheet->setCellValue('F' . $row, $this->getStatusName($sale->status));
            $sheet->setCellValue('G' . $row, $this->getPaymentStatusName($sale->paymentStatus));
            $sheet->setCellValue('H' . $row, $sale->deliveryType ?? 'N/A');
            $row++;
        }

        // Auto-size
        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'sotuvlar_hisobot_' . now()->format('Y_m_d_His') . '.xlsx';
        $path = storage_path('app/public/exports/' . $filename);
        
        $writer = new Xlsx($spreadsheet);
        $writer->save($path);

        return $filename;
    }

    /**
     * Inventar hisoboti
     */
    public function exportInventoryReport()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'INVENTAR HISOBOTI');
        $sheet->mergeCells('A1:G1');
        $this->styleHeader($sheet);

        $row = 3;
        $headers = ['#', 'Kitob', 'Muallif', 'Kategoriya', 'Stok', 'Narx', 'Do\'kon'];
        
        foreach (range('A', 'G') as $index => $col) {
            $sheet->setCellValue($col . $row, $headers[$index]);
            $sheet->getStyle($col . $row)->getFont()->setBold(true);
        }
        $row++;

        $books = Books::with(['category', 'seller'])
            ->orderBy('count')
            ->get();

        $index = 1;
        foreach ($books as $book) {
            $sheet->setCellValue('A' . $row, $index++);
            $sheet->setCellValue('B' . $row, $book->name);
            $sheet->setCellValue('C' . $row, $book->author);
            $sheet->setCellValue('D' . $row, $book->category->name_uz ?? 'N/A');
            $sheet->setCellValue('E' . $row, $book->count);
            $sheet->setCellValue('F' . $row, number_format($book->price) . ' so\'m');
            $sheet->setCellValue('G' . $row, $book->seller->shop_name ?? 'N/A');

            // Low stock warning (qizil rang)
            if ($book->count <= 5) {
                $sheet->getStyle('E' . $row)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('FF6B6B');
            }

            $row++;
        }

        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'inventar_hisobot_' . now()->format('Y_m_d_His') . '.xlsx';
        $path = storage_path('app/public/exports/' . $filename);
        
        $writer = new Xlsx($spreadsheet);
        $writer->save($path);

        return $filename;
    }

    // ==================== HELPER METHODS ====================

    private function styleHeader($sheet)
    {
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('4A90E2');
        $sheet->getStyle('A1')->getFont()->getColor()->setRGB('FFFFFF');
    }

    private function getFinancialSummary($dateFrom, $dateTo)
    {
        $totalRevenue = Sold::whereBetween('created_at', [$dateFrom, $dateTo])
            ->where('paymentStatus', 2)
            ->sum('amount');

        $totalOrders = Sold::whereBetween('created_at', [$dateFrom, $dateTo])->count();
        
        $avgOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;

        return [
            'Umumiy Daromad' => number_format($totalRevenue) . ' so\'m',
            'Buyurtmalar Soni' => $totalOrders,
            'O\'rtacha Savdo' => number_format($avgOrderValue) . ' so\'m',
            'To\'langan' => Sold::whereBetween('created_at', [$dateFrom, $dateTo])
                ->where('paymentStatus', 2)->count(),
            'Kutilmoqda' => Sold::whereBetween('created_at', [$dateFrom, $dateTo])
                ->where('paymentStatus', 1)->count(),
        ];
    }

    private function getTopSellers($dateFrom, $dateTo, $limit = 10)
    {
        return Seller::select('sellers.*')
            ->withSum(['books' => function($query) use ($dateFrom, $dateTo) {
                // Bu yerda Sold modelidagi items ni parse qilish kerak
            }], 'totalRevenue')
            ->orderByDesc('books_sum_total_revenue')
            ->limit($limit)
            ->get()
            ->map(function($seller) {
                return [
                    'shop_name' => $seller->shop_name,
                    'total_sales' => $seller->books->sum('totalSales'),
                    'total_revenue' => $seller->books->sum('totalRevenue'),
                ];
            })
            ->toArray();
    }

    private function getStatusName($status)
    {
        return match($status) {
            'A' => 'Kutilmoqda',
            'P' => 'Qadoqlanmoqda',
            'B' => 'Yo\'lda',
            'C' => 'Yakunlandi',
            'F' => 'Bekor qilindi',
            default => 'Noma\'lum',
        };
    }

    private function getPaymentStatusName($status)
    {
        return match($status) {
            2, '2' => 'To\'langan',
            1, '1' => 'Kutilmoqda',
            0, '0' => 'Naqd',
            3, '3' => 'Bekor qilingan',
            default => 'Noma\'lum',
        };
    }
}
