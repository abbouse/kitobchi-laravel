<?php

namespace Tests\Feature;

use App\Console\Commands\KitobchiIdClient;
use App\Models\KitobchiId\IdClient;
use App\Models\KitobchiId\IdRefreshToken;
use App\Models\User;
use App\Services\KitobchiId\KeyStore;
use App\Services\SmsService;
use Firebase\JWT\JWT;
use Firebase\JWT\JWK;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Kitobchi ID (OIDC) — to'liq oqim va xavfsizlik tekshiruvlari.
 * MySQL talab qilmaydi: kerakli jadvallar sqlite xotirasida yaratiladi.
 */
class KitobchiIdTest extends TestCase
{
    private const REDIRECT = 'https://partner.example.uz/callback';

    private string $keysDir;

    private array $cookies = [];

    private ?string $lastSms = null;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name')->nullable();
            $t->string('lastname')->nullable();
            $t->string('username')->nullable();
            $t->string('avatar')->nullable();
            $t->string('locale')->nullable();
            $t->string('email')->nullable();
            $t->timestamp('email_verified_at')->nullable();
            $t->string('phone_number')->nullable();
            $t->timestamp('phone_verified_at')->nullable();
            $t->string('status')->default('active');
            $t->timestamp('blocked_until')->nullable();
            $t->string('isDeleted')->default('no');
            $t->string('password')->nullable();
            $t->rememberToken();
            $t->timestamps();
        });
        (include base_path('database/migrations/2026_10_21_100000_create_kitobchi_id_tables.php'))->up();

        $this->keysDir = sys_get_temp_dir().'/kid-keys-'.bin2hex(random_bytes(4));
        config([
            'kitobchi_id.keys_path' => $this->keysDir,
            'kitobchi_id.issuer' => 'https://kitobchi.com/api/id',
            'cache.default' => 'array',
            'session.driver' => 'array',
        ]);
        $this->app->forgetInstance(KeyStore::class);
        (new KeyStore($this->keysDir))->generate();

        $this->mock(SmsService::class, function ($m) {
            $m->shouldReceive('send')->andReturnUsing(function ($phone, $text) {
                $this->lastSms = $text;

                return ['ok' => true];
            });
        });
    }

    /* ------------------------------------------------------------------ */

    private function makeClient(array $over = []): array
    {
        $secret = 'kid_cs_test_'.bin2hex(random_bytes(8));
        $client = IdClient::create(array_merge([
            'client_id' => 'kid_'.bin2hex(random_bytes(6)),
            'client_secret_hash' => password_hash($secret, PASSWORD_DEFAULT),
            'name' => 'Hamkor Do\'kon',
            'type' => 'confidential',
            'first_party' => false,
            'redirect_uris' => [self::REDIRECT],
            'post_logout_redirect_uris' => ['https://partner.example.uz/bye'],
            'allowed_scopes' => ['openid', 'profile', 'phone', 'offline_access'],
            'status' => 'active',
        ], $over));

        return [$client, $secret];
    }

    private function pkce(): array
    {
        $verifier = rtrim(strtr(base64_encode(random_bytes(48)), '+/', '-_'), '=');

        return [$verifier, rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=')];
    }

    private function authorizeUrl(IdClient $client, string $challenge, array $over = []): string
    {
        return '/api/id/authorize?'.http_build_query(array_merge([
            'client_id' => $client->client_id,
            'redirect_uri' => self::REDIRECT,
            'response_type' => 'code',
            'scope' => 'openid profile phone offline_access',
            'state' => 'st-123',
            'nonce' => 'n-456',
            'code_challenge' => $challenge,
            'code_challenge_method' => 'S256',
        ], $over));
    }

    /** Brauzer kabi cookie (sessiya) saqlab so'rov yuborish */
    private function browser(string $method, string $uri, array $data = []): TestResponse
    {
        $res = $this->withUnencryptedCookies($this->cookies)->call($method, $uri, $data);
        foreach ($res->headers->getCookies() as $c) {
            $this->cookies[$c->getName()] = $c->getValue();
        }

        return $res;
    }

    private function ridFrom(TestResponse $res): string
    {
        preg_match('/name="rid" value="([^"]+)"/', $res->getContent(), $m);
        $this->assertNotEmpty($m[1] ?? null, 'rid topilmadi');

        return $m[1];
    }

    private function loginAndGetCode(IdClient $client, string $challenge, string $phone = '998901112233', array $over = []): array
    {
        $page = $this->browser('GET', $this->authorizeUrl($client, $challenge, $over));
        $page->assertOk()->assertSee('Kitobchi ID orqali kirish', false);
        $rid = $this->ridFrom($page);

        $this->browser('POST', '/api/id/login/send', ['rid' => $rid, 'phone' => '+'.$phone])->assertStatus(303);
        $this->assertStringContainsString("\"Hamkor Do'kon\"", (string) $this->lastSms, 'SMS da ilova nomi bo\'lishi kerak');
        preg_match('/kodi: (\d{6})/', (string) $this->lastSms, $m);

        $verify = $this->browser('POST', '/api/id/login/verify', ['rid' => $rid, 'code' => $m[1]]);
        $verify->assertStatus(303);
        $resume = $this->browser('GET', $verify->headers->get('Location'));

        if ($resume->status() === 200) { // rozilik oynasi
            $resume->assertSee('Ruxsat berish', false);
            $resume = $this->browser('POST', '/api/id/consent', ['rid' => $rid, 'decision' => 'allow']);
        }
        $resume->assertStatus(303);
        $loc = $resume->headers->get('Location');
        $this->assertStringStartsWith(self::REDIRECT.'?', $loc);
        parse_str(parse_url($loc, PHP_URL_QUERY), $q);

        return $q;
    }

    private function token(array $data, ?array $basic = null): TestResponse
    {
        $headers = ['CONTENT_TYPE' => 'application/x-www-form-urlencoded', 'HTTP_ACCEPT' => 'application/json'];
        if ($basic) {
            $headers['HTTP_AUTHORIZATION'] = 'Basic '.base64_encode(urlencode($basic[0]).':'.urlencode($basic[1]));
        }

        return $this->call('POST', '/api/id/token', $data, [], [], $headers);
    }

    private function decode(string $jwt): array
    {
        $jwks = $this->getJson('/api/id/jwks')->assertOk()->json();

        return (array) JWT::decode($jwt, JWK::parseKeySet($jwks));
    }

    /* ------------------------------------------------------------------ */

    public function test_discovery_and_jwks(): void
    {
        $d = $this->getJson('/api/id/.well-known/openid-configuration')->assertOk()->json();
        $this->assertSame('https://kitobchi.com/api/id', $d['issuer']);
        $this->assertSame(['S256'], $d['code_challenge_methods_supported']);
        $this->assertSame(['code'], $d['response_types_supported']);

        $jwks = $this->getJson('/api/id/jwks')->assertOk()->json('keys');
        $this->assertCount(1, $jwks);
        $this->assertArrayNotHasKey('d', $jwks[0], 'JWKS da maxfiy qism bo\'lmasligi kerak');
        $this->assertArrayNotHasKey('p', $jwks[0]);
    }

    public function test_full_code_flow_with_pkce_pairwise_and_userinfo(): void
    {
        [$client, $secret] = $this->makeClient();
        [$verifier, $challenge] = $this->pkce();

        $q = $this->loginAndGetCode($client, $challenge);
        $this->assertSame('st-123', $q['state']);
        $this->assertSame('https://kitobchi.com/api/id', $q['iss']);

        $res = $this->token(['grant_type' => 'authorization_code', 'code' => $q['code'], 'redirect_uri' => self::REDIRECT, 'code_verifier' => $verifier], [$client->client_id, $secret]);
        $res->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $t = $res->json();
        $this->assertSame('Bearer', $t['token_type']);
        $this->assertStringStartsWith('kid_rt_', $t['refresh_token']);

        $id = $this->decode($t['id_token']);
        $this->assertSame($client->client_id, $id['aud']);
        $this->assertSame('n-456', $id['nonce']);
        $this->assertSame('+998901112233', $id['phone_number']);
        $user = User::where('phone_number', '998901112233')->firstOrFail();
        $this->assertNotSame((string) $user->id, $id['sub'], 'Uchinchi tomon uchun sub pairwise bo\'lishi kerak');
        $this->assertSame(43, strlen($id['sub']));

        $info = $this->withToken($t['access_token'])->getJson('/api/id/userinfo')->assertOk()->json();
        $this->assertSame($id['sub'], $info['sub']);
        $this->assertSame('+998901112233', $info['phone_number']);
        $this->assertTrue($info['phone_number_verified']);

        // ID token access token o'rnida ishlamaydi
        $this->withToken($t['id_token'])->getJson('/api/id/userinfo')->assertStatus(401);

        // Ikkinchi marta: sessiya + rozilik bor → to'g'ridan-to'g'ri kod (prompt=none ham ishlaydi)
        [$v2, $c2] = $this->pkce();
        $r = $this->browser('GET', $this->authorizeUrl($client, $c2, ['prompt' => 'none']));
        $r->assertStatus(303);
        $this->assertStringContainsString('code=', $r->headers->get('Location'));
    }

    public function test_code_is_single_use_and_reuse_revokes_tokens(): void
    {
        [$client, $secret] = $this->makeClient();
        [$verifier, $challenge] = $this->pkce();
        $q = $this->loginAndGetCode($client, $challenge);
        $body = ['grant_type' => 'authorization_code', 'code' => $q['code'], 'redirect_uri' => self::REDIRECT, 'code_verifier' => $verifier];

        $t = $this->token($body, [$client->client_id, $secret])->assertOk()->json();
        $this->token($body, [$client->client_id, $secret])->assertStatus(400)->assertJson(['error' => 'invalid_grant']);

        // Birinchi almashinuvdan chiqqan tokenlar ham bekor qilindi
        $this->withToken($t['access_token'])->getJson('/api/id/userinfo')->assertStatus(401);
        $this->token(['grant_type' => 'refresh_token', 'refresh_token' => $t['refresh_token']], [$client->client_id, $secret])
            ->assertStatus(400)->assertJson(['error' => 'invalid_grant']);
    }

    public function test_wrong_pkce_verifier_burns_code(): void
    {
        [$client, $secret] = $this->makeClient();
        [$verifier, $challenge] = $this->pkce();
        $q = $this->loginAndGetCode($client, $challenge);
        [$wrong] = $this->pkce();

        $this->token(['grant_type' => 'authorization_code', 'code' => $q['code'], 'redirect_uri' => self::REDIRECT, 'code_verifier' => $wrong], [$client->client_id, $secret])
            ->assertStatus(400)->assertJson(['error' => 'invalid_grant']);
        // To'g'ri verifier bilan ham endi ishlamaydi
        $this->token(['grant_type' => 'authorization_code', 'code' => $q['code'], 'redirect_uri' => self::REDIRECT, 'code_verifier' => $verifier], [$client->client_id, $secret])
            ->assertStatus(400);
    }

    public function test_refresh_rotation_and_reuse_detection(): void
    {
        [$client, $secret] = $this->makeClient();
        [$verifier, $challenge] = $this->pkce();
        $q = $this->loginAndGetCode($client, $challenge);
        $t1 = $this->token(['grant_type' => 'authorization_code', 'code' => $q['code'], 'redirect_uri' => self::REDIRECT, 'code_verifier' => $verifier], [$client->client_id, $secret])->json();

        $t2 = $this->token(['grant_type' => 'refresh_token', 'refresh_token' => $t1['refresh_token']], [$client->client_id, $secret])->assertOk()->json();
        $this->assertNotSame($t1['refresh_token'], $t2['refresh_token']);

        // Scope'ni toraytirish mumkin, kengaytirish mumkin emas
        $this->token(['grant_type' => 'refresh_token', 'refresh_token' => $t2['refresh_token'], 'scope' => 'openid email'], [$client->client_id, $secret])
            ->assertStatus(400)->assertJson(['error' => 'invalid_scope']);

        // Eski token qayta keldi → butun oila bekor
        $this->token(['grant_type' => 'refresh_token', 'refresh_token' => $t1['refresh_token']], [$client->client_id, $secret])->assertStatus(400);
        $this->token(['grant_type' => 'refresh_token', 'refresh_token' => $t2['refresh_token']], [$client->client_id, $secret])->assertStatus(400);
        $this->assertSame(0, IdRefreshToken::whereNull('revoked_at')->count());
    }

    public function test_client_authentication_rules(): void
    {
        [$client, $secret] = $this->makeClient();
        [$pub] = $this->makeClient(['type' => 'public', 'client_secret_hash' => null]);
        [$other, $otherSecret] = $this->makeClient();
        [$verifier, $challenge] = $this->pkce();
        $q = $this->loginAndGetCode($client, $challenge);
        $body = ['grant_type' => 'authorization_code', 'code' => $q['code'], 'redirect_uri' => self::REDIRECT, 'code_verifier' => $verifier];

        $this->token($body, [$client->client_id, 'wrong'])->assertStatus(401)->assertHeader('WWW-Authenticate');
        $this->token($body + ['client_id' => $client->client_id])->assertStatus(401);           // confidential secret'siz
        $this->token($body + ['client_id' => $pub->client_id, 'client_secret' => 'x'])->assertStatus(401); // public + secret
        $this->token($body, [$other->client_id, $otherSecret])->assertStatus(400);                // boshqa client kodi
        // JSON body qabul qilinmaydi
        $this->postJson('/api/id/token', $body)->assertStatus(400)->assertJson(['error' => 'invalid_request']);
    }

    public function test_public_client_flow_with_pkce_only(): void
    {
        [$pub] = $this->makeClient(['type' => 'public', 'client_secret_hash' => null, 'first_party' => true]);
        [$verifier, $challenge] = $this->pkce();
        $q = $this->loginAndGetCode($pub, $challenge); // first_party — rozilik oynasi yo'q
        $t = $this->token(['grant_type' => 'authorization_code', 'code' => $q['code'], 'redirect_uri' => self::REDIRECT, 'code_verifier' => $verifier, 'client_id' => $pub->client_id])
            ->assertOk()->json();
        $user = User::where('phone_number', '998901112233')->firstOrFail();
        $this->assertSame((string) $user->id, $this->decode($t['id_token'])['sub'], 'first_party uchun sub = user id');
    }

    public function test_authorize_validation_never_redirects_to_unregistered_uri(): void
    {
        [$client] = $this->makeClient();
        [, $challenge] = $this->pkce();

        $this->get($this->authorizeUrl($client, $challenge, ['redirect_uri' => 'https://evil.example/cb']))->assertStatus(400)->assertSee('redirect_uri');
        $this->get($this->authorizeUrl($client, $challenge, ['redirect_uri' => self::REDIRECT.'/x']))->assertStatus(400);
        $this->get($this->authorizeUrl($client, $challenge, ['client_id' => 'nope']))->assertStatus(400);

        $cases = [
            [['code_challenge' => null], 'invalid_request'],
            [['code_challenge_method' => 'plain'], 'invalid_request'],
            [['response_type' => 'token'], 'unsupported_response_type'],
            [['state' => null], 'invalid_request'],
            [['nonce' => null], 'invalid_request'],
            [['scope' => 'profile'], 'invalid_scope'],
            [['scope' => 'openid email'], 'invalid_scope'],    // ilovaga ruxsat berilmagan
            [['scope' => 'openid admin'], 'invalid_scope'],
            [['prompt' => 'none'], 'login_required'],
            [['prompt' => 'none login'], 'invalid_request'],
            [['request' => 'eyJ'], 'request_not_supported'],
        ];
        foreach ($cases as [$over, $error]) {
            $r = $this->get($this->authorizeUrl($client, $challenge, $over));
            $r->assertStatus(303);
            $loc = $r->headers->get('Location');
            $this->assertStringStartsWith(self::REDIRECT.'?', $loc, json_encode($over));
            $this->assertStringContainsString('error='.$error, $loc, json_encode($over));
        }
        $r = $this->get($this->authorizeUrl($client, $challenge, ['prompt' => 'none']));
        $this->assertStringContainsString('state=st-123', $r->headers->get('Location'));
    }

    public function test_consent_deny_and_frame_protection(): void
    {
        [$client] = $this->makeClient();
        [, $challenge] = $this->pkce();
        $page = $this->browser('GET', $this->authorizeUrl($client, $challenge));
        $this->assertStringContainsString("frame-ancestors 'none'", $page->headers->get('Content-Security-Policy'));
        $this->assertSame('no-store, private', $page->headers->get('Cache-Control'));
        $rid = $this->ridFrom($page);
        $this->browser('POST', '/api/id/login/send', ['rid' => $rid, 'phone' => '901112233'])->assertStatus(303);
        preg_match('/kodi: (\d{6})/', (string) $this->lastSms, $m);
        $resume = $this->browser('GET', $this->browser('POST', '/api/id/login/verify', ['rid' => $rid, 'code' => $m[1]])->headers->get('Location'));
        $resume->assertOk()->assertSee('Rad etish', false);
        $deny = $this->browser('POST', '/api/id/consent', ['rid' => $rid, 'decision' => 'deny']);
        $this->assertStringContainsString('error=access_denied', $deny->headers->get('Location'));
    }

    public function test_otp_attempt_limit_and_no_master_code(): void
    {
        [$client] = $this->makeClient();
        [, $challenge] = $this->pkce();
        $rid = $this->ridFrom($this->browser('GET', $this->authorizeUrl($client, $challenge)));
        $this->browser('POST', '/api/id/login/send', ['rid' => $rid, 'phone' => '998901234567']);
        preg_match('/kodi: (\d{6})/', (string) $this->lastSms, $m);
        $wrong = $m[1] === '222222' ? '333333' : '222222';

        for ($i = 0; $i < 4; $i++) {
            $this->browser('POST', '/api/id/login/verify', ['rid' => $rid, 'code' => $wrong])->assertStatus(422)->assertSee('Kod noto');
        }
        $this->browser('POST', '/api/id/login/verify', ['rid' => $rid, 'code' => $wrong])->assertStatus(422)->assertSee("ko'p noto");
        // 5 xatodan keyin to'g'ri kod ham ishlamaydi
        $this->browser('POST', '/api/id/login/verify', ['rid' => $rid, 'code' => $m[1]])->assertStatus(400);
        $this->assertSame(0, User::where('phone_number', '998901234567')->count(), 'Kod tasdiqlanmaguncha user yaratilmaydi');
    }

    public function test_revoke_and_logout(): void
    {
        [$client, $secret] = $this->makeClient();
        [$verifier, $challenge] = $this->pkce();
        $q = $this->loginAndGetCode($client, $challenge);
        $t = $this->token(['grant_type' => 'authorization_code', 'code' => $q['code'], 'redirect_uri' => self::REDIRECT, 'code_verifier' => $verifier], [$client->client_id, $secret])->json();

        $revoke = fn (array $d) => $this->call('POST', '/api/id/revoke', $d, [], [], [
            'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
            'HTTP_AUTHORIZATION' => 'Basic '.base64_encode($client->client_id.':'.$secret),
        ]);
        $revoke(['token' => $t['access_token'], 'token_type_hint' => 'access_token'])->assertOk();
        $this->withToken($t['access_token'])->getJson('/api/id/userinfo')->assertStatus(401);
        $revoke(['token' => $t['refresh_token']])->assertOk();
        $revoke(['token' => 'unknown'])->assertOk();
        $this->token(['grant_type' => 'refresh_token', 'refresh_token' => $t['refresh_token']], [$client->client_id, $secret])->assertStatus(400);

        // Logout: faqat ro'yxatdagi manzilga va imzosi to'g'ri id_token_hint bilan
        $bad = $this->browser('GET', '/api/id/logout?'.http_build_query(['post_logout_redirect_uri' => 'https://evil.example/', 'id_token_hint' => $t['id_token']]));
        $bad->assertOk()->assertSee('chiqdingiz', false);
        $ok = $this->browser('GET', '/api/id/logout?'.http_build_query(['post_logout_redirect_uri' => 'https://partner.example.uz/bye', 'id_token_hint' => $t['id_token'], 'state' => 'z']));
        $ok->assertStatus(303)->assertHeader('Location', 'https://partner.example.uz/bye?state=z');
        // Sessiya yopildi → prompt=none endi login_required
        [, $c2] = $this->pkce();
        $this->assertStringContainsString('login_required', $this->browser('GET', $this->authorizeUrl($client, $c2, ['prompt' => 'none']))->headers->get('Location'));
    }

    public function test_blocked_user_cannot_refresh(): void
    {
        [$client, $secret] = $this->makeClient();
        [$verifier, $challenge] = $this->pkce();
        $q = $this->loginAndGetCode($client, $challenge);
        $t = $this->token(['grant_type' => 'authorization_code', 'code' => $q['code'], 'redirect_uri' => self::REDIRECT, 'code_verifier' => $verifier], [$client->client_id, $secret])->json();
        User::where('phone_number', '998901112233')->update(['status' => 'blocked']);
        $this->token(['grant_type' => 'refresh_token', 'refresh_token' => $t['refresh_token']], [$client->client_id, $secret])->assertStatus(400);
        $this->withToken($t['access_token'])->getJson('/api/id/userinfo')->assertStatus(401);
    }

    public function test_redirect_uri_registration_rules(): void
    {
        $ok = ['https://app.uz/cb', 'http://127.0.0.1:8080/cb', 'http://localhost/cb'];
        foreach ($ok as $u) {
            $this->assertNull(KitobchiIdClient::validateRedirect($u, false), $u);
        }
        $this->assertNull(KitobchiIdClient::validateRedirect('uz.partner.app:/oauth', true));
        foreach (['http://app.uz/cb', 'https://app.uz/cb#x', 'https://*.app.uz/cb', 'javascript:alert(1)', 'myapp:/cb'] as $u) {
            $this->assertNotNull(KitobchiIdClient::validateRedirect($u, true), $u);
        }
        $this->assertNotNull(KitobchiIdClient::validateRedirect('uz.partner.app:/oauth', false), 'custom scheme faqat public');
    }
}
