<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** Kitobxonlar klubi profilidagi "Javon": ko'rinish huquqi va yashirish. */
class ProfileShelfTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $phone): User
    {
        return User::query()->forceCreate(['name' => 'A', 'lastname' => 'B', 'phone_number' => $phone, 'password' => bcrypt('x')]);
    }

    public function test_shelf_visibility(): void
    {
        $owner = $this->user('998901110001');
        $other = $this->user('998901110002');

        // Bo'sh javon — ochiq
        $this->getJson("/api/v1/kitobchi/products/shelf/{$owner->id}")
            ->assertOk()->assertJsonPath('hidden', false)->assertJsonPath('data', [])->assertJsonPath('meta.total_books', 0);

        // Egasi yashiradi
        Sanctum::actingAs($owner, ['*'], 'user');
        $this->postJson('/api/v1/kitobchi/book_club/shelf/visibility', ['hidden' => true])
            ->assertOk()->assertJsonPath('hidden_from_others', true);
        $this->getJson("/api/v1/kitobchi/products/shelf/{$owner->id}")
            ->assertOk()->assertJsonPath('is_owner', true)->assertJsonPath('hidden', false);

        // Boshqa foydalanuvchiga yopiq
        Sanctum::actingAs($other, ['*'], 'user');
        $this->getJson("/api/v1/kitobchi/products/shelf/{$owner->id}")
            ->assertOk()->assertJsonPath('hidden', true)->assertJsonPath('data', []);
        $this->getJson("/api/v1/kitobchi/products/shelf/{$owner->id}/category/1")
            ->assertOk()->assertJsonPath('hidden', true);

        $this->getJson('/api/v1/kitobchi/products/shelf/999999')->assertNotFound();
    }
}
