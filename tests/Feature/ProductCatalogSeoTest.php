<?php

namespace Tests\Feature;

use App\Models\Books;
use Tests\TestCase;

class ProductCatalogSeoTest extends TestCase
{
    public function test_catalog_ajax_search_returns_json(): void
    {
        $response = $this->getJson('/catalog?ajax=1&search=test');
        $response->assertStatus(200);
        $response->assertJsonStructure(['items']);
    }

    public function test_sitemap_returns_valid_xml(): void
    {
        $response = $this->get('/sitemap.xml');
        $response->assertStatus(200);
        $this->assertStringContainsString('text/xml', (string) $response->headers->get('Content-Type'));
        $response->assertSee('<urlset', false);
    }

    public function test_robots_txt_returns_plain_text(): void
    {
        $response = $this->get('/robots.txt');
        $response->assertStatus(200);
        $response->assertSee('User-agent: *');
        $response->assertSee('Sitemap:');
    }

    public function test_share_redirect_uses_landing_layout(): void
    {
        $response = $this->get('/share/product/123');
        $response->assertStatus(200);
        $response->assertSee(__('errors.share_open_app'));
    }

    public function test_real_book_share_redirect_returns_200(): void
    {
        $response = $this->get('/art/10003628');
        $response->assertStatus(200);

        $response2 = $this->get('/share/product/1');
        $response2->assertStatus(200);
    }

    public function test_google_merchant_feed_returns_valid_xml(): void
    {
        $response = $this->get('/google-merchant.xml');
        $response->assertStatus(200);
        $this->assertStringContainsString('text/xml', (string) $response->headers->get('Content-Type'));
        $response->assertSee('<rss', false);
    }
}
