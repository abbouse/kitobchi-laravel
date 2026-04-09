<?php
namespace App\Exports;

use App\Models\Seller;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class SellersExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(private array $filters = []) {}

    public function query()
    {
        $q = Seller::query();
        if (!empty($this->filters['tab']) && $this->filters['tab'] !== 'all') {
            $q->where('status', $this->filters['tab']);
        }
        if (!empty($this->filters['region'])) {
            $q->where('region', $this->filters['region']);
        }
        return $q->orderBy('id');
    }

    public function headings(): array
    {
        return ['ID','Do\'kon nomi','Ism','Familiya','Telefon','Viloyat','Holat','Balans','Faoliyat','Qo\'shilgan'];
    }

    public function map($s): array
    {
        return [
            $s->id, $s->shop_name, $s->firstname, $s->lastname,
            $s->phone_number, $s->region, $s->status,
            $s->balance ?? 0,
            implode(', ', $s->activity_types ?? []),
            $s->created_at?->format('d.m.Y'),
        ];
    }
}

// ─────────────────────────────────────────────────────

namespace App\Exports;

use App\Models\Couriers;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class CouriersExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(private array $filters = []) {}

    public function query()
    {
        $q = Couriers::query();
        if (!empty($this->filters['region'])) $q->where('region', $this->filters['region']);
        if (!empty($this->filters['tab'])) {
            if ($this->filters['tab'] === 'active')   $q->where('status', 1);
            if ($this->filters['tab'] === 'inactive') $q->where('status', 0);
        }
        return $q->orderBy('id');
    }

    public function headings(): array
    {
        return ['ID','Ism','Familiya','Telefon','Viloyat','Balans','Holat','Qo\'shilgan'];
    }

    public function map($c): array
    {
        return [
            $c->id, $c->first_name, $c->last_name, $c->phone_number,
            $c->region, $c->balance ?? 0,
            $c->status ? 'Aktiv' : 'Nofaol',
            $c->created_at?->format('d.m.Y'),
        ];
    }
}