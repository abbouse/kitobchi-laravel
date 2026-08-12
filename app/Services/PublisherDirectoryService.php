<?php

namespace App\Services;

use App\Models\Publisher;
use Illuminate\Support\Str;

/**
 * `AuthorDirectoryService`ga o'xshash — seller nashriyot nomini erkin
 * (ro'yxatdan tanlamasdan) kiritganda, mavjud nashriyotni topadi yoki
 * yo'q bo'lsa yangisini yaratadi. Muallif maydoni ancha vaqtdan beri shu
 * tarzda ishlaydi ("topilmasa yangi muallif yaratiladi"); nashriyot esa
 * avval FAQAT ro'yxatdan tanlashni majburlar edi va mos kelmasa seller
 * hech narsa qila olmasdi — bu servis shu tafovutni bartaraf etadi.
 */
class PublisherDirectoryService
{
    public function resolveOrCreateByName(?string $name): ?Publisher
    {
        $name = $this->cleanName($name);
        if ($name === null) {
            return null;
        }

        $publisher = Publisher::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->first();

        if ($publisher) {
            return $publisher;
        }

        // `name` ustunida unique index bor — parallel so'rovlarda kamdan-kam
        // holatda ikki marta yaratilishga urinish bo'lsa, race'dan keyingi
        // urinish shu yerda ushlanib, mavjudini qaytaradi.
        try {
            return Publisher::query()->create(['name' => $name]);
        } catch (\Illuminate\Database\QueryException) {
            return Publisher::query()
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
                ->first();
        }
    }

    private function cleanName(?string $name): ?string
    {
        $value = trim((string) $name);

        return $value === '' ? null : Str::limit($value, 255, '');
    }
}
