<?php

namespace Tests\Feature;

use App\Models\ApiClient;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ExternalAndShopApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('api_clients')) {
            Schema::create('api_clients', function ($table) {
                $table->id();
                $table->string('name')->nullable();
                $table->unsignedBigInteger('seller_id')->nullable();
                $table->string('app_id')->unique();
                $table->string('app_secret');
                $table->text('abilities')->nullable();
                $table->text('allowed_ips')->nullable();
                $table->boolean('is_active')->default(true);
                $table->integer('rate_limit_per_second')->nullable();
                $table->integer('rate_limit_per_minute')->nullable();
                $table->timestamps();
            });
        }
    }

    public function test_developer_api_documentation_pages_load_successfully(): void
    {
        $response = $this->get('/developers/api');
        $response->assertStatus(200);
        $response->assertSee('Kitobchi');

        $openapi = $this->getJson('/developers/api/openapi.json');
        $openapi->assertStatus(200);
        $openapi->assertJsonStructure([
            'openapi',
            'info' => ['title', 'version'],
            'paths',
        ]);

        $postman = $this->getJson('/developers/api/postman.json');
        $postman->assertStatus(200);
        $postman->assertJsonStructure([
            'info' => ['name', 'schema'],
            'item',
        ]);
    }

    public function test_external_client_api_requires_credentials(): void
    {
        // 1. Missing X-App-ID / X-App-Secret -> 401
        $missing = $this->getJson('/api/v1/client/products/book/1');
        $missing->assertStatus(401);
        $missing->assertJson([
            'status' => 'error',
            'message' => 'Kalit yuborilmagan.',
        ]);

        // 2. Invalid credentials -> 403
        $invalid = $this->withHeaders([
            'X-App-ID' => 'app_invalid_key_123',
            'X-App-Secret' => 'sec_invalid_secret_456',
        ])->getJson('/api/v1/client/products/book/1');

        $invalid->assertStatus(403);
        $invalid->assertJson([
            'status' => 'error',
            'message' => "Kalit noto'g'ri yoki o'chirilgan.",
        ]);
    }

    public function test_api_client_with_read_ability_is_authorized(): void
    {
        $client = ApiClient::create([
            'name' => 'Partner Client',
            'app_id' => 'app_valid_test_key',
            'app_secret' => 'sec_valid_test_secret_123456789012345678901234',
            'abilities' => ['read'],
            'is_active' => true,
        ]);

        $response = $this->withHeaders([
            'X-App-ID' => $client->app_id,
            'X-App-Secret' => $client->app_secret,
        ])->getJson('/api/v1/client/deeplink?type=book&id=1');

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'data' => [
                'type' => 'book',
                'id' => 1,
            ],
        ]);
    }

    public function test_api_client_missing_write_ability_is_forbidden(): void
    {
        $readOnlyClient = ApiClient::create([
            'name' => 'Read-only Partner',
            'app_id' => 'app_readonly_key',
            'app_secret' => 'sec_readonly_secret_1234567890123456789012',
            'abilities' => ['read'],
            'is_active' => true,
        ]);

        $response = $this->withHeaders([
            'X-App-ID' => $readOnlyClient->app_id,
            'X-App-Secret' => $readOnlyClient->app_secret,
        ])->postJson('/api/v1/client/products/stock/by-code', [
            'code' => 'TEST-123',
            'count' => 10,
        ]);

        $response->assertStatus(403);
        $response->assertJson([
            'status' => 'error',
            'message' => 'Ruxsat yetarli emas: stock:write',
        ]);
    }

    public function test_app_version_check_returns_valid_versions(): void
    {
        \Illuminate\Support\Facades\Cache::put('app:versions:v1', [[
            'market_version_ios' => '1.0.0',
            'market_version_android' => '1.0.0',
            'contacts' => [],
        ]], 600);

        $response = $this->getJson('/api/appversion/check');
        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'ok' => true,
        ]);
    }

    public function test_unauthenticated_seller_routes_are_protected(): void
    {
        $this->getJson('/api/v1/seller/session/config')->assertStatus(401);
        $this->getJson('/api/v1/seller/products/categories')->assertStatus(401);
        $this->getJson('/api/v1/seller/orders/last-orders')->assertStatus(401);
        $this->getJson('/api/v1/seller/locations')->assertStatus(401);
        $this->getJson('/api/v1/seller/couriers')->assertStatus(401);
        $this->getJson('/api/v1/seller/transactions/count')->assertStatus(401);
    }
}
