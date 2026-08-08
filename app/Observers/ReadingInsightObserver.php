<?php

namespace App\Observers;

use App\Jobs\GenerateReadingInsightJob;
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
 * eski keshni o'chiramiz/yangisini navbatga qo'yamiz, xuddi shunday
 * `ProductModerationObserver` naqshiga mos ravishda. Haqiqiy AI chaqiruvi
 * HAMON fon jarayonida (queue job yoki sweep buyrug'i ichida) amalga
 * oshadi — hech qachon HTTP so'rovini bloklamaydi.
 *
 * MUHIM (2026-08-08): dispatch'larga ATAYLAB `->afterResponse()` qo'shilgan
 * — `.env`da `QUEUE_CONNECTION=sync` bo'lsa (hozir shunday edi), oddiy
 * `dispatch()` HTTP javobini kutib, so'rov ICHIDA bloklab ishlaydi (sync
 * drayver navbatni emulyatsiya qilmaydi, darhol bajaradi). `afterResponse()`
 * esa javob mijozga YUBORILGANDAN keyin ishga tushadi — navbat sozlamasidan
 * qat'i nazar, admin panelidan kitob qo'shish/tahrirlash so'rovini
 * hech qachon bloklamaydi.
 *
 * FAQAT KITOB uchun darhol (proaktiv) navbatga qo'yamiz — chunki
 * `reading-intelligence:generate-insights` sweep'i mahsulotlarni
 * `totalSales` bo'yicha navbatlaydi, ya'ni YANGI (hali sotuvi yo'q)
 * kitob har doim navbat OXIRIDA qoladi. Kanselyariya bu kartada umuman
 * ishtirok etmagani uchun (`ReadingIntelligenceService`ga qarang) unga
 * darhol-dispatch shart emas — faqat eski keshni tozalash yetarli, uni
 * hamon umumiy sweep tez orada qamrab oladi.
 */
class ReadingInsightObserver
{
    private const BOOK_FIELDS = ['name', 'author', 'category_id', 'description', 'pages'];
    private const STATIONERY_FIELDS = ['name', 'category_id', 'description'];

    /**
     * Yangi kitob qo'shilganda sweep'ni kutmasdan DARHOL navbatga
     * qo'yamiz — shu orqali birinchi xaridorlar item sahifasini
     * ochganda tahlil allaqachon tayyor bo'lish ehtimoli oshadi.
     *
     * 5 soniyalik kechikish bilan: ba'zi admin oqimlarida kitob avval
     * yaratilib, so'ng bir necha soniya ichida qo'shimcha maydonlar
     * (masalan, muqova, tavsif) alohida so'rov(lar)da to'ldirilishi
     * mumkin — kechikishsiz darhol generatsiya qilsak, hali to'liq
     * bo'lmagan ma'lumot asosida ishlab, keyin `updated()` orqali
     * darhol qayta eskirib, ikkinchi (ortiqcha) AI chaqiruviga olib
     * kelishi mumkin edi.
     */
    public function created(Books|Stationery $product): void
    {
        if (! $product instanceof Books) {
            return;
        }

        GenerateReadingInsightJob::dispatch('book', $product->id)
            ->delay(now()->addSeconds(5))
            ->afterResponse();
    }

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

        // Kitob uchun — o'chirilgandan keyin ham darhol qayta navbatga
        // qo'yamiz, aks holda tahrirlangan kitob xuddi yangisi kabi
        // totalSales-navbatida orqada qolib, sweep'ni kutib turardi.
        if ($type === 'book') {
            GenerateReadingInsightJob::dispatch('book', $product->id)
                ->delay(now()->addSeconds(5))
                ->afterResponse();
        }
    }
}
