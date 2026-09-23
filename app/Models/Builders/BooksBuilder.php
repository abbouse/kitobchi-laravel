<?php

namespace App\Models\Builders;

use App\Models\Books;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

/**
 * Ommaviy (mass) yozuvlar himoyasi: `Books::query()->where(...)->update([...])`
 * model hodisalarini chetlab o'tadi, shuning uchun qulf shu yerda ham turadi.
 *
 * Kitobning O'ZINIKI bo'lgan maydonlari (nom, muallif, rasm…) faqat KATALOGGA
 * ULANMAGAN takliflarga yoziladi; ulanganlarida esa so'rovning qolgan
 * ustunlari (narx, moderatsiya, ko'rinish…) odatdagidek qo'llanadi — ya'ni
 * himoya butun yangilanishni bekor qilmaydi, faqat kitob maydonlarini kesadi.
 */
class BooksBuilder extends Builder
{
    public function update(array $values)
    {
        if (Books::catalogWritesAllowed()) {
            return parent::update($values);
        }

        $blocked = array_values(array_intersect(array_keys($values), Books::CATALOG_MANAGED));
        if ($blocked === []) {
            return parent::update($values);
        }

        Log::info('Katalogdagi kitoblar ommaviy yangilanishdan himoyalandi', ['fields' => $blocked]);

        $table = $this->getModel()->getTable();
        $values = $this->addUpdatedAtColumn($values);

        // 1. Ulanmagan takliflar — so'rov o'zgarishsiz
        $affected = (clone $this)->toBase()->whereNull($table . '.edition_id')->update($values);

        // 2. Ulanganlar — kitob maydonlarisiz (faqat taklif ustunlari)
        $allowed = Arr::except($values, Books::CATALOG_MANAGED);
        if (Arr::except($allowed, [$this->getModel()->getUpdatedAtColumn()]) !== []) {
            $affected += (clone $this)->toBase()->whereNotNull($table . '.edition_id')->update($allowed);
        }

        return $affected;
    }

    /**
     * `upsert()` Eloquent'da to'g'ridan-to'g'ri baza so'roviga tushadi (update()
     * dan o'tmaydi) — kitob maydonlari konflikt ro'y berganda yangilanmasin.
     */
    public function upsert(array $values, $uniqueBy, $update = null)
    {
        if (! Books::catalogWritesAllowed() && is_array($update)) {
            $blocked = array_values(array_intersect($update, Books::CATALOG_MANAGED));
            if ($blocked !== []) {
                Log::warning('Books::upsert kitob maydonlarini yangilamoqchi edi — kesildi', ['fields' => $blocked]);
                $update = array_values(array_diff($update, Books::CATALOG_MANAGED));
            }
        }

        return parent::upsert($values, $uniqueBy, $update);
    }
}
