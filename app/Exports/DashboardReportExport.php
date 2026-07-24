<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Dashboard hisobotini bitta varaqda seksiyalarga bo'lib eksport qiladi.
 * Ma'lumot controllerda tayyor 2D massiv sifatida uzatiladi (aniqligi uchun
 * ekrandagi payload bilan bir xil manba).
 */
class DashboardReportExport implements FromArray, ShouldAutoSize, WithTitle
{
    /**
     * @param  array<int, array<int, mixed>>  $rows
     */
    public function __construct(private array $rows, private string $sheetTitle = 'Dashboard')
    {
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function title(): string
    {
        return $this->sheetTitle;
    }
}
