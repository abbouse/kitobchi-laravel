<?php

namespace Tests\Feature;

use App\Http\Controllers\PushController;
use App\Models\KitobchiId\IdClient;
use App\Models\KitobchiId\IdCodeDelivery;
use App\Models\User;
use App\Services\KitobchiId\KeyStore;
use App\Services\SmsService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Kitobchi ID: kodni Kitobchi ilovasidagi rasmiy chatga yetkazish, SMS zaxirasi
 * va ekotizim ilovalari uchun brauzersiz (native) kirish.
 */
class KitobchiIdAppDeliveryTest extends TestCase
{
    private const REDIRECT = 'https://partner.example.uz/callback';

    private const PHONE = '998901112233';

    private array $cookies = [];

    private array $sms = [];

    private array $pushes = [];

    private bool $pushOk = true;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

        Schema::create('users', function (Blueprint $t) {
            $t->id();
            foreach (['name', 'lastname', 'username', 'avatar', 'locale', 'email', 'phone_number', 'password', 'position', 'staff_role', 'role_emoji', 'role_title', 'role_place'] as $c) {
                $t->string($c)->nullable();
            }
            $t->boolean('isVerified')->default(false);
            $t->boolean('isSupport')->default(false);
            $t->timestamp('email_verified_at')->nullable();
            $t->timestamp('phone_verified_at')->nullable();
            $t->timestamp('last_seen_at')->nullable();
            $t->string('status')->default('active');
            $t->timestamp('blocked_until')->nullable();
            $t->string('isDeleted')->default('no');
            $t->rememberToken();
            $t->timestamps();
        });
        Schema::create('sellers', function (Blueprint $t) {
            $t->id();
            $t->string('shop_name')->nullable();
            $t->string('photo')->nullable();
            $t->unsignedBigInteger('user_id')->nullable();
            $t->unsignedBigInteger('parent_id')->nullable();
            $t->timestamps();
        });
        Schema::create('conversations', function (Blueprint $t) {
            $t->id();
            $t->string('type');
            foreach (['title', 'description', 'public_username', 'invite_token', 'avatar', 'hidden_by'] as $c) {
                $t->string($c)->nullable();
            }
            $t->boolean('is_public')->default(false);
            foreach (['created_by_id', 'user_id', 'receiver_id', 'shop_id', 'courier_id', 'order_id'] as $c) {
                $t->unsignedBigInteger($c)->nullable();
            }
            $t->timestamp('last_message_at')->nullable();
            $t->timestamp('messages_hidden_at')->nullable();
            $t->timestamps();
        });
        Schema::create('conversation_participants', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('conversation_id');
            $t->unsignedBigInteger('user_id');
            $t->string('role')->nullable();
            $t->timestamp('joined_at')->nullable();
            $t->timestamp('muted_until')->nullable();
            $t->timestamps();
        });
        Schema::create('messages', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('conversation_id');
            $t->unsignedBigInteger('sender_id')->nullable();
            $t->string('sender_type')->nullable();
            $t->unsignedBigInteger('reply_to_id')->nullable();
            $t->text('message')->nullable();
            $t->boolean('is_read')->default(false);
            $t->boolean('is_deleted')->default(false);
            $t->boolean('is_edited')->default(false);
            $t->string('hidden_by')->nullable();
            $t->timestamps();
        });
        Schema::create('personal_access_tokens', function (Blueprint $t) {
            $t->id();
            $t->morphs('tokenable');
            $t->string('name');
            $t->string('token', 64)->unique();
            $t->text('abilities')->nullable();
            $t->timestamp('last_used_at')->nullable();
            $t->timestamp('expires_at')->nullable();
            $t->timestamps();
        });
        Schema::create('connected_devices', function (Blueprint $t) {
            $t->id();
            foreach (['token', 'device_id', 'device_name', 'platform', 'project_id', 'user_type', 'fcm_token'] as $c) {
                $t->string($c)->nullable();
            }
            $t->unsignedBigInteger('user_id')->nullable();
            $t->timestamps();
        });
        (include base_path('database/migrations/2026_10_21_100000_create_kitobchi_id_tables.php'))->up();
        (include base_path('database/migrations/2026_10_22_100000_create_id_code_deliveries_table.php'))->up();

        DB::table('sellers')->insert(['id' => 1, 'shop_name' => 'Kitobchi', 'user_id' => 777]);

        $keys = sys_get_temp_dir().'/kid-keys-'.bin2hex(random_bytes(4));
        config([
            'kitobchi_id.keys_path' => $keys,
            'kitobchi_id.issuer' => 'https://kitobchi.com/api/id',
            'cache.default' => 'array',
            'session.driver' => 'array',
            'broadcasting.default' => 'null',
        ]);
        (new KeyStore($keys))->generate();

        $this->mock(SmsService::class, function ($m) {
            $m->shouldReceive('send')->andReturnUsing(function ($phone, $text) {
                $this->sms[] = $text;

                return ['ok' => true];
            });
        });
        $this->mock(PushController::class, function ($m) {
            $m->shouldReceive('sendPush')->andReturnUsing(function ($req) {
                $this->pushes[] = $req->all();

                return response()->json(['success' => $this->pushOk]);
            });
        });
    }

    /* ------------------------------------------------------------------ */

    private function makeUser(bool $withApp = true, ?string $lastUsed = null): User
    {
        $user = User::forceCreate(['name' => 'Aziz', 'lastname' => 'Karimov', 'phone_number' => self::PHONE, 'phone_verified_at' => now()]);
        if ($withApp) {
            $hash = hash('sha256', 'plain-'.$user->id);
            DB::table('personal_access_tokens')->insert([
                'tokenable_type' => $user->getMorphClass(), 'tokenable_id' => $user->id, 'name' => 'app',
                'token' => $hash, 'last_used_at' => $lastUsed ?? now(), 'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('connected_devices')->insert([
                'token' => $hash, 'user_type' => 'user', 'user_id' => $user->id, 'fcm_token' => 'fcm-'.$user->id,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        return $user;
    }

    private function client(array $over = []): IdClient
    {
        return IdClient::create(array_merge([
            'client_id' => 'kid_'.bin2hex(random_bytes(6)),
            'client_secret_hash' => null,
            'name' => 'Hamkor',
            'type' => 'public',
            'first_party' => true,
            'redirect_uris' => [self::REDIRECT],
            'allowed_scopes' => ['openid', 'profile', 'phone', 'offline_access'],
            'status' => 'active',
        ], $over));
    }

    private function pkce(): array
    {
        $v = rtrim(strtr(base64_encode(random_bytes(48)), '+/', '-_'), '=');

        return [$v, rtrim(strtr(base64_encode(hash('sha256', $v, true)), '+/', '-_'), '=')];
    }

    private function browser(string $method, string $uri, array $data = []): TestResponse
    {
        $res = $this->withUnencryptedCookies($this->cookies)->call($method, $uri, $data);
        foreach ($res->headers->getCookies() as $c) {
            $this->cookies[$c->getName()] = $c->getValue();
        }

        return $res;
    }

    private function pushedCode(): string
    {
        $last = end($this->pushes);
        preg_match('/kodi: (\d{6})/', $last['body'], $m);

        return $m[1];
    }

    private function startWeb(IdClient $client): string
    {
        [, $challenge] = $this->pkce();
        $page = $this->browser('GET', '/api/id/authorize?'.http_build_query([
            'client_id' => $client->client_id, 'redirect_uri' => self::REDIRECT, 'response_type' => 'code',
            'scope' => 'openid profile', 'state' => 's', 'nonce' => 'n', 'code_challenge' => $challenge, 'code_challenge_method' => 'S256',
        ]));
        preg_match('/name="rid" value="([^"]+)"/', $page->getContent(), $m);

        return $m[1];
    }

    /* ------------------------------------------------------------------ */

    public function test_code_goes_to_official_chat_not_sms_and_is_hidden_from_staff(): void
    {
        $user = $this->makeUser();
        $rid = $this->startWeb($this->client());
        $this->browser('POST', '/api/id/login/send', ['rid' => $rid, 'phone' => '+'.self::PHONE])->assertStatus(303);

        $this->assertSame([], $this->sms, 'Ilova faol — SMS yuborilmasligi kerak');
        $this->assertCount(1, $this->pushes);
        $this->assertSame(['fcm-'.$user->id], $this->pushes[0]['tokens']);
        $code = $this->pushedCode();

        // messages jadvalida va chatda kod YO'Q
        $conv = DB::table('conversations')->where('user_id', $user->id)->where('shop_id', 1)->first();
        $this->assertNotNull($conv);
        $msg = DB::table('messages')->where('conversation_id', $conv->id)->first();
        $this->assertStringNotContainsString($code, $msg->message);
        $this->assertStringContainsString('••••••', $msg->message);
        $this->assertStringNotContainsString($code, (string) DB::table('id_code_deliveries')->value('code_enc'));

        // Sahifa: kod ilovaga yuborildi, SMS tugmasi bor
        $this->browser('GET', '/api/id/authorize/resume?rid='.$rid.'&step=code')
            ->assertOk()->assertSee('Kitobchi ilovangizdagi', false)->assertSee('SMS orqali olish');

        // Chat egasi ilovada kodni ko'radi
        Sanctum::actingAs($user, ['*'], 'user');
        $res = $this->getJson("/api/v1/kitobchi/conversations/{$conv->id}/messages");
        $res->assertOk();
        $this->assertSame($code, $res->json('data.0.kitobchi_id.code'));
        $this->assertSame('active', $res->json('data.0.kitobchi_id.status'));

        // Do'kon egasi (seller #1 bog'langan user) kodni ko'rmaydi
        $this->assertSame([], \App\Services\KitobchiId\AppCodeChannel::payloadsFor([$msg->id], 777));

        // Kod kiritilgach chatda "ishlatildi" bo'ladi, shifrlangan nusxa o'chadi
        $this->browser('POST', '/api/id/login/verify', ['rid' => $rid, 'code' => $code])->assertStatus(303);
        $d = IdCodeDelivery::first();
        $this->assertNotNull($d->used_at);
        $this->assertNull($d->code_enc);
    }

    public function test_sms_fallback_sends_same_code_after_30_seconds(): void
    {
        $this->makeUser();
        $rid = $this->startWeb($this->client());
        $this->browser('POST', '/api/id/login/send', ['rid' => $rid, 'phone' => self::PHONE]);
        $code = $this->pushedCode();

        $this->browser('POST', '/api/id/login/send', ['rid' => $rid, 'via' => 'sms'])->assertStatus(303);
        $this->assertSame([], $this->sms, '30 soniyagacha SMS yo\'q');

        $this->travel(31)->seconds();
        $this->browser('POST', '/api/id/login/send', ['rid' => $rid, 'via' => 'sms'])->assertStatus(303);
        $this->assertCount(1, $this->sms);
        $this->assertStringContainsString($code, $this->sms[0], 'SMS da o\'sha kod');
        $this->browser('POST', '/api/id/login/verify', ['rid' => $rid, 'code' => $code])->assertStatus(303);
    }

    public function test_sms_when_no_active_app_or_push_fails_or_new_user(): void
    {
        // Ilova 40 kun ishlatilmagan
        $this->makeUser(true, now()->subDays(40)->toDateTimeString());
        $rid = $this->startWeb($this->client());
        $this->browser('POST', '/api/id/login/send', ['rid' => $rid, 'phone' => self::PHONE]);
        $this->assertCount(1, $this->sms);
        $this->assertSame([], $this->pushes);
    }

    public function test_push_failure_falls_back_to_sms(): void
    {
        $this->makeUser();
        $this->pushOk = false;
        $rid = $this->startWeb($this->client());
        $this->browser('POST', '/api/id/login/send', ['rid' => $rid, 'phone' => self::PHONE]);
        $this->assertCount(1, $this->pushes);
        $this->assertCount(1, $this->sms);
        $this->browser('GET', '/api/id/authorize/resume?rid='.$rid.'&step=code')->assertSee('SMS orqali 6 xonali', false);
    }

    public function test_unknown_phone_gets_sms(): void
    {
        $rid = $this->startWeb($this->client());
        $this->browser('POST', '/api/id/login/send', ['rid' => $rid, 'phone' => '998977776655']);
        $this->assertCount(1, $this->sms);
        $this->assertSame(0, DB::table('conversations')->count());
    }

    public function test_native_login_without_browser(): void
    {
        $user = $this->makeUser();
        $client = $this->client();
        [$verifier, $challenge] = $this->pkce();

        $start = $this->postJson('/api/id/native/start', [
            'client_id' => $client->client_id, 'phone' => '+998 90 111 22 33', 'scope' => 'openid profile phone offline_access',
            'redirect_uri' => self::REDIRECT, 'code_challenge' => $challenge, 'code_challenge_method' => 'S256', 'nonce' => 'nn',
        ])->assertOk()->assertJson(['channel' => 'app', 'sms_available_in' => 30]);
        $session = $start->json('auth_session');

        $this->postJson('/api/id/native/verify', ['client_id' => $client->client_id, 'auth_session' => $session, 'code' => '000000'])
            ->assertStatus(400)->assertJson(['error' => 'invalid_code']);

        $this->travel(31)->seconds();
        $this->postJson('/api/id/native/resend', ['client_id' => $client->client_id, 'auth_session' => $session])
            ->assertOk()->assertJson(['channel' => 'sms']);
        $this->assertStringContainsString($this->pushedCode(), $this->sms[0]);

        $verify = $this->postJson('/api/id/native/verify', ['client_id' => $client->client_id, 'auth_session' => $session, 'code' => $this->pushedCode()])
            ->assertOk();
        $tokens = $this->call('POST', '/api/id/token', [
            'grant_type' => 'authorization_code', 'code' => $verify->json('authorization_code'),
            'redirect_uri' => self::REDIRECT, 'code_verifier' => $verifier, 'client_id' => $client->client_id,
        ], [], [], ['CONTENT_TYPE' => 'application/x-www-form-urlencoded'])->assertOk()->json();
        $this->assertArrayHasKey('refresh_token', $tokens);
        $this->assertArrayHasKey('id_token', $tokens);

        // Sessiya bir martalik
        $this->postJson('/api/id/native/verify', ['client_id' => $client->client_id, 'auth_session' => $session, 'code' => $this->pushedCode()])
            ->assertStatus(400)->assertJson(['error' => 'invalid_session']);
    }

    public function test_native_login_rejected_for_third_party_and_bad_input(): void
    {
        [, $challenge] = $this->pkce();
        $third = $this->client(['first_party' => false]);
        $body = ['phone' => self::PHONE, 'scope' => 'openid', 'redirect_uri' => self::REDIRECT,
            'code_challenge' => $challenge, 'code_challenge_method' => 'S256', 'nonce' => 'n'];

        $this->postJson('/api/id/native/start', $body + ['client_id' => $third->client_id])
            ->assertStatus(403)->assertJson(['error' => 'unauthorized_client']);

        $first = $this->client();
        $this->postJson('/api/id/native/start', ['code_challenge_method' => 'plain'] + $body + ['client_id' => $first->client_id])
            ->assertStatus(400);
        $this->postJson('/api/id/native/start', ['redirect_uri' => 'https://evil.example/'] + $body + ['client_id' => $first->client_id])
            ->assertStatus(400);
        $this->postJson('/api/id/native/verify', ['client_id' => $first->client_id, 'auth_session' => 'nope', 'code' => '123456'])
            ->assertStatus(400)->assertJson(['error' => 'invalid_session']);
        $this->assertSame([], $this->sms);
    }
}
