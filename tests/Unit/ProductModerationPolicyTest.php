<?php

namespace Tests\Unit;

use App\Services\ProductModerationPolicy;
use PHPUnit\Framework\TestCase;

class ProductModerationPolicyTest extends TestCase
{
    public function test_valid_book_has_no_deterministic_issues(): void
    {
        $issues = (new ProductModerationPolicy)->deterministicIssues('book', [
            'name' => 'Ikki eshik orasi',
            'author' => 'O‘tkir Hoshimov',
            'description' => 'Inson taqdiri va hayot sinovlari haqidagi roman.',
            'price' => 65000,
            'discount_price' => 55000,
            'category_id' => 1,
            'seller_id' => 1,
            'images' => ['books/example.jpg'],
            'pages' => 320,
        ]);

        self::assertSame([], $issues);
    }

    public function test_invalid_listing_is_blocked_before_ai_approval(): void
    {
        $policy = new ProductModerationPolicy;
        $issues = $policy->deterministicIssues('stationery', [
            'name' => 'Qalam',
            'description' => 'Tel: +998 90 123 45 67, t.me/shubhali',
            'price' => 0,
            'discount_price' => 5000,
            'category_id' => null,
            'seller_id' => 7,
            'images' => [],
        ]);

        self::assertTrue($policy->hasBlockingIssues($issues));
        self::assertContains('invalid_price', array_column($issues, 'code'));
        self::assertContains('missing_image', array_column($issues, 'code'));
        self::assertContains('external_contact_or_link', array_column($issues, 'code'));
        self::assertContains('phone_number_in_listing', array_column($issues, 'code'));
    }

    public function test_isbn_in_description_is_not_mistaken_for_phone_number(): void
    {
        $issues = (new ProductModerationPolicy)->deterministicIssues('book', [
            'name' => 'Sinov kitobi',
            'author' => 'Test Muallif',
            'description' => 'Nashr identifikatori ISBN 978-9943-08-123-1 bilan ro‘yxatga olingan.',
            'price' => 45000,
            'discount_price' => 0,
            'category_id' => 1,
            'seller_id' => 1,
            'images' => ['books/example.jpg'],
            'pages' => 120,
        ]);

        self::assertNotContains('phone_number_in_listing', array_column($issues, 'code'));
    }
}
