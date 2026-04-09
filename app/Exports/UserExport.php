<?php
// ══════════════════════════════════════════════════════
// app/Exports/UsersExport.php
// ══════════════════════════════════════════════════════
namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UsersExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    public function __construct(private array $filters = []) {}

    public function query()
    {
        $query = User::query();

        if (!empty($this->filters['search'])) {
            $s = $this->filters['search'];
            $query->where(fn($q) => $q->where('name','like',"%$s%")
                ->orWhere('lastname','like',"%$s%")
                ->orWhere('phone_number','like',"%$s%"));
        }

        if (isset($this->filters['is_premium'])) {
            $query->where('is_premium', $this->filters['is_premium']);
        }

        return $query->orderBy('id');
    }

    public function headings(): array
    {
        return ['ID', 'Ism', 'Familiya', 'Telefon', 'Email', 'Premium', 'Tasdiqlangan', 'Balans (UZS)', 'Cashback', 'Qo\'shilgan'];
    }

    public function map($user): array
    {
        return [
            $user->id,
            $user->name,
            $user->lastname,
            $user->phone_number,
            $user->email,
            $user->is_premium ? 'Ha' : 'Yo\'q',
            $user->isVerified ? 'Ha' : 'Yo\'q',
            $user->real_balance ?? 0,
            $user->cashback ?? 0,
            $user->created_at?->format('d.m.Y H:i'),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '4F7CFF']]],
        ];
    }
}