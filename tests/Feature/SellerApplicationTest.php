<?php

namespace Tests\Feature;

use App\Models\Seller;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Catalog\CatalogFixtures;
use Tests\TestCase;

/**
 * Hamkorlik arizasi: do'kon yoki muallif, eng zarur savollar.
 */
class SellerApplicationTest extends TestCase
{
    use CatalogFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        $this->requireMysql();
        parent::setUp();
    }

    public function test_author_application_stores_answers(): void
    {
        $this->postJson('/api/v1/seller/contact', [
            'business_role' => 'author',
            'contact_name' => 'Aziza  Karimova',
            'phone_number' => '+998 (90) 111-22-33',
            'shop_name' => 'Aziza K.',
            'region' => 'Toshkent',
            'books_status' => 'upcoming',
            'titles_count' => '2_5',
            'publishing' => 'publisher',
            'publisher_name' => 'Yangi asr avlodi',
            'genres' => ['fiction', 'poetry', 'hack'],
            'rights_confirmed' => true,
            'terms_accepted' => true,
        ])->assertCreated()->assertJsonPath('business_role', 'author');

        $seller = Seller::where('phone_number', '+998901112233')->firstOrFail();
        $this->assertSame('Aziza', $seller->firstname);
        $this->assertSame('Karimova', $seller->lastname);
        $this->assertSame('upcoming', $seller->application_data['books_status']);
        $this->assertSame(['fiction', 'poetry'], $seller->application_data['genres']);
        $this->assertSame('Yangi asr avlodi', $seller->application_data['publisher_name']);
    }

    public function test_author_must_confirm_rights_and_answer(): void
    {
        $base = [
            'business_role' => 'author', 'contact_name' => 'Aziza Karimova',
            'phone_number' => '+998901112244', 'shop_name' => 'Aziza', 'region' => 'Toshkent',
            'books_status' => 'published', 'titles_count' => '1', 'publishing' => 'self',
            'terms_accepted' => true,
        ];
        $this->postJson('/api/v1/seller/contact', $base)->assertStatus(422);
        $this->postJson('/api/v1/seller/contact', ['rights_confirmed' => true] + array_diff_key($base, ['books_status' => 1]))
            ->assertStatus(422);
        $this->assertFalse(Seller::where('phone_number', '+998901112244')->exists());
    }

    public function test_shop_application_and_legacy_app_still_work(): void
    {
        $this->postJson('/api/v1/seller/contact', [
            'business_role' => 'seller', 'contact_name' => 'Bobur Aliyev',
            'phone_number' => '+998901112255', 'shop_name' => 'Kitob uyi', 'region' => 'Samarqand',
            'activity_types' => ['Kitob', 'Kanstovar'], 'legal_type' => 'entrepreneur',
            'assortment' => '100_1000', 'has_store' => true, 'terms_accepted' => true,
        ])->assertCreated();
        $shop = Seller::where('phone_number', '+998901112255')->firstOrFail();
        $this->assertSame('entrepreneur', $shop->legal_type);
        $this->assertTrue($shop->application_data['has_store']);

        // Eski ilova: faqat 4 maydon
        $this->postJson('/api/v1/seller/contact', [
            'phone_number' => '+998901112266', 'shop_name' => 'Eski', 'region' => 'Buxoro',
            'activity_types' => ['Kitob'],
        ])->assertCreated();
        $this->assertNull(Seller::where('phone_number', '+998901112266')->value('application_data'));
    }
}
