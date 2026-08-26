<?php

namespace App\Console\Commands;

use App\Models\BookCategories;
use App\Models\Books;
use App\Models\Collection;
use App\Models\CollectionItem;
use App\Support\ProductVisibilityScope;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Dasturiy SEO uchun: har bir faol kitob kategoriyasi bo'yicha eng ko'p
 * sotilgan mahsulotlardan QORALAMA (is_active=false) kolleksiya yaratadi.
 * Bular hech qachon avtomatik e'lon qilinmaydi — chunki thin/duplicate
 * kontent muammosini yana boshidan qaytarmaslik uchun har bir kolleksiya
 * odam tomonidan (yoki AI yordamida, lekin tekshirilib) haqiqiy `intro`
 * matni bilan boyitilishi va shundan keyingina `collections:publish`
 * orqali yoqilishi kerak.
 */
class GenerateCollectionDrafts extends Command
{
    protected $signature = 'collections:generate-drafts {--per-collection=16} {--min-books=6}';
    protected $description = 'Har bir faol kategoriya uchun qoralama (is_active=false) kolleksiya yaratadi — nashr qilishdan oldin intro matni yozib, collections:publish bilan yoqish kerak';

    public function handle(): int
    {
        $perCollection = max(4, (int) $this->option('per-collection'));
        $minBooks = max(1, (int) $this->option('min-books'));

        $categories = BookCategories::where('is_active', true)->get();
        $created = 0;
        $skipped = 0;

        foreach ($categories as $category) {
            // TUZATILDI: konsolda (artisan) app locale odatda 'en' bo'ladi,
            // shuning uchun BookCategories::name accessor (locale'ga qarab
            // tanlaydi) inglizcha nomni qaytarib yuborardi (masalan
            // "psychology-and-personal-development") — natijada slug/sarlavha
            // ham inglizcha chiqardi. Endi name_uz aniq ustuvor.
            $categoryName = $category->name_uz ?: $category->name;
            if (! $categoryName) {
                continue;
            }

            $slug = Str::slug($categoryName . '-eng-yaxshi-kitoblar');

            if (Collection::where('slug', $slug)->exists()) {
                $skipped++;
                continue;
            }

            $books = ProductVisibilityScope::applyBooks(
                Books::query()->where('category_id', $category->id)
            )->orderByDesc('totalSales')->take($perCollection)->get(['id', 'totalSales']);

            if ($books->count() < $minBooks) {
                continue;
            }

            $collection = Collection::create([
                'slug' => $slug,
                'title' => "Eng yaxshi {$categoryName} kitoblari",
                'intro' => null, // QASDAN bo'sh — nashrdan oldin qo'lda/AI yordamida yoziladi
                'meta_description' => "{$categoryName} bo'yicha eng ko'p sotilgan va sara kitoblar to'plami — Kitobchi marketpleysida.",
                'type' => 'book',
                'is_active' => false,
                'sort_order' => 100,
            ]);

            foreach ($books->values() as $i => $book) {
                CollectionItem::create([
                    'collection_id' => $collection->id,
                    'product_type' => 'book',
                    'product_id' => $book->id,
                    'position' => $i,
                ]);
            }

            $created++;
            $this->info("QORALAMA yaratildi: {$slug} ({$books->count()} kitob) — intro yozib collections:publish bilan yoqing.");
        }

        $this->info("Tugadi. Yaratildi: {$created}, o'tkazib yuborildi (mavjud/kam kitob): {$skipped}.");

        return self::SUCCESS;
    }
}
