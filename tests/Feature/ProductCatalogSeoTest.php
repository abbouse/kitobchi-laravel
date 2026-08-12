<?php

namespace Tests\Feature;

use App\Models\Books;
use Tests\TestCase;

class ProductCatalogSeoTest extends TestCase
{
    public function test_catalog_page_returns_successful_response(): void
    {
        $response = $this->get('/catalog');
        $response->assertStatus(200);
        $response->assertSee('Kitoblar va Mahsulotlar Katalogi');
    }

    public function test_sitemap_returns_valid_xml(): void
    {
        $response = $this->get('/sitemap.xml');
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/xml; charset=UTF-8');
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

    public function test_google_merchant_feed_returns_valid_xml(): void
    {
        $response = $this->get('/google-merchant.xml');
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/xml; charset=UTF-8');
        $response->assertSee('<rss', false);
    }
}
