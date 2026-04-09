<?php
namespace App\Exports;

use App\Models\Sold;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class OrdersExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(private array $filters = []) {}

    public function query()
    {
        $q = Sold::with('user');
        if (!empty($this->filters['tab']) && $this->filters['tab'] !== 'all') {
            $q->where('status', $this->filters['tab']);
        }
        if (!empty($this->filters['payment_status'])) {
            $q->where('paymentStatus', $this->filters['payment_status']);
        }
        return $q->orderByDesc('id');
    }

    public function headings(): array
    {
        return ['#ID','Mijoz','Telefon','Summa','Yetkazish narxi','Chegirma','Holat','To\'lov','Yetkazish turi','Sana'];
    }

    public function map($o): array
    {
        $st = ['A'=>'Kutilmoqda','P'=>'Qadoqlanmoqda','B'=>"Yo'lda",'C'=>'Yetkazildi','F'=>'Bekor'];
        $py = ['0'=>'Qabul qilinganida','1'=>'Karta','2'=>"To'langan",'3'=>'Rad etildi'];
        return [
            $o->id,
            $o->user ? $o->user->name.' '.$o->user->lastname : 'Mehmon',
            $o->user?->phone_number,
            $o->amount,
            $o->deliveryPrice,
            $o->discountAmount,
            $st[$o->status] ?? $o->status,
            $py[$o->paymentStatus] ?? '—',
            $o->deliveryType,
            $o->created_at?->format('d.m.Y H:i'),
        ];
    }
}