<?php

namespace Tests\Feature\Catalog;

use App\Models\Books;
use App\Models\Seller;
use App\Services\BranchStockService;
use Illuminate\Support\Facades\DB;

trait CatalogFixtures
{
    protected function requireMysql(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('Katalog testlari MySQL sxemasini talab qiladi (DB_CONNECTION=mysql).');
        }
    }

    protected function makeCategory(): int
    {
        return (int) DB::table('book_categories')->insertGetId([
            'name_uz' => 'Badiiy', 'name_ru' => 'Худ', 'name_en' => 'Fiction', 'name_ja' => 'F',
            'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    protected function makeSeller(array $attrs = []): Seller
    {
        $seller = Seller::query()->forceCreate(array_merge([
            'shop_name' => 'Shop ' . uniqid(),
            'firstname' => 'A', 'lastname' => 'B',
            'phone_number' => '99890' . random_int(1000000, 9999999),
            'password' => bcrypt('x'),
            'status' => 'approved', 'is_hidden' => 0, 'isVerified' => 0,
        ], $attrs));

        DB::table('seller_locations')->insert([
            'seller_id' => $seller->id, 'fullAddress' => 'Toshkent', 'lat' => '41.3', 'lon' => '69.2',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return $seller;
    }

    protected function makeBook(Seller $seller, int $categoryId, array $attrs = [], int $stock = 5): Books
    {
        $book = Books::query()->create(array_merge([
            'seller_id' => $seller->id,
            'name' => "O'tkan kunlar",
            'author' => 'Abdulla Qodiriy',
            'isbn' => '9780306406157',
            'category_id' => $categoryId,
            'description' => 'Roman',
            'images' => ['books/a.jpg'],
            'price' => 50000,
            'discountPrice' => 0,
            'lang' => 'uz', 'langType' => 'latin', 'coverType' => 'hard',
            'year' => 2020, 'pages' => 300,
            'status' => true, 'is_hidden' => false,
        ], $attrs));

        // Moderatsiyadan o'tgan holat (testlar uchun)
        if (! array_key_exists('is_approved', $attrs)) {
            $book->forceFill(['is_approved' => 1])->save();
        }

        if ($stock > 0) {
            app(BranchStockService::class)->setTotalFromLegacy('book', (int) $book->id, 0, (int) $seller->id, $stock);
        }

        return $book->fresh();
    }
}
