<?php

namespace App\Observers;

use App\Models\BookReadingInsight;
use App\Models\Books;
use App\Models\Stationery;
use Illuminate\Support\Facades\Cache;

/**
 * Mahsulot mazmuni (nomi, tavsifi, muallifi, kategoriyasi) sezilarli
 * o'zgarganda, eski Reading Intelligence tahlilini (qiyinlik/kayfiyat/
 * auditoriya) o'chiradi — shu orqali `reading-intelligence:generate-insights`
 * fon buyrug'i uni "yangi" deb hisoblab qayta generatsiya qiladi.
 *
 * MUHIM: bu yerda AI hech qachon to'g'ridan-to'g'ri chaqirilmaydi — faqat
 * eski keshni o'chiramiz, xuddi shunday `ProductModerationObserver`
 * naqshiga mos ravishda. Haqiqiy qayta generatsiya HAMON fon jarayonida,
 * pauza bilan (rate-limit xavfsiz) amalga oshadi.
 */
class ReadingInsightObserver
{
    private const BOOK_FIELDS = ['name', 'author', 'category_id', 'description', 'pages'];
    private const STATIONERY_FIELDS = ['name', 'category_id', 'description'];

    public function updated(Books|Stationery $product): void
    {
        $fields = $product instanceof Books ? self::BOOK_FIELDS : self::STATIONERY_FIELDS;

        if (! $product->wasChanged($fields)) {
            return;
        }

        $type = $product instanceof Books ? 'book' : 'stationery';

        BookReadingInsight::query()
            ->where('product_type', $type)
            ->where('product_id', $product->id)
            ->delete();

        // `ReadingInsightGenerator::get()` dagi 10 daqiqalik keshni ham
        // darhol tozalaymiz — aks holda o'chirilgan qatordan oldingi
        // (endi eskirgan) natija yana bir muddat ko'rsatilib turadi.
        Cache::forget("reading-intel:insight:{$type}:{$product->id}");
    }
}
