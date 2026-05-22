<?php
namespace App\Exports;
 
use App\Models\Books;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
 
class BooksExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(private array $filters = []) {}
 
    public function query()
    {
        $q = Books::with(['category','seller', 'authorProfile']);
        if (!empty($this->filters['search'])) {
            $s = $this->filters['search'];
            $q->where(fn($x) => $x
                ->where('name','like',"%$s%")
                ->orWhereHas('authorProfile', fn ($authorQuery) => $authorQuery->where('name', 'like', "%$s%")));
        }
        if (!empty($this->filters['tab']) && $this->filters['tab'] !== 'all') {
            $map = ['pending'=>0,'approved'=>1,'rejected'=>2];
            $q->where('is_approved', $map[$this->filters['tab']] ?? 0);
        }
        return $q->orderBy('id');
    }
 
    public function headings(): array
    {
        return ['ID','Nomi','Muallif','Kategoriya','Sotuvchi','Narx','Chegirma narxi','Zaxira','Moderatsiya','Ko\'rinish','Qo\'shilgan'];
    }
 
    public function map($book): array
    {
        $approved = ['0'=>'Kutilmoqda','1'=>'Tasdiqlangan','2'=>'Rad etilgan'];
        return [
            $book->id,
            $book->name,
            $book->author,
            $book->category?->name_uz,
            $book->seller?->shop_name,
            $book->price,
            $book->discountPrice,
            $book->count,
            $approved[$book->is_approved] ?? '—',
            $book->status ? 'Ko\'rinadi' : 'Yashirin',
            $book->created_at?->format('d.m.Y'),
        ];
    }
}
