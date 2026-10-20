<?php

namespace App\Services\KitobchiId;

use App\Models\KitobchiId\IdAuthCode;
use App\Models\KitobchiId\IdClient;
use App\Models\KitobchiId\IdRefreshToken;
use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Kitobchi ID tokenlari: authorization code, access token (JWT, RFC 9068),
 * ID token (OIDC) va aylanuvchi (rotating) refresh tokenlar.
 *
 * Xavfsizlik qoidalari:
 *  - code va refresh token ochiq holda saqlanmaydi — faqat SHA-256 hash;
 *  - code bir martalik; qayta ishlatilsa shu koddan chiqqan barcha tokenlar bekor qilinadi;
 *  - PKCE S256 majburiy;
 *  - refresh token har ishlatilganda almashtiriladi; eski token qayta kelsa (o'g'irlik
 *    belgisi) butun "oila" bekor qilinadi.
 */
class TokenService
{
    public const REFRESH_PREFIX = 'kid_rt_';

    public function __construct(private KeyStore $keys)
    {
    }

    public static function issuer(): string
    {
        return rtrim((string) config('kitobchi_id.issuer'), '/');
    }

    public static function randomToken(int $bytes = 32): string
    {
        return KeyStore::b64url(random_bytes($bytes));
    }

    public static function hash(string $token): string
    {
        return hash('sha256', $token);
    }

    /* ------------------------------------------------------------------ */
    /*  Subject                                                            */
    /* ------------------------------------------------------------------ */

    /**
     * first_party ilovalar — Kitobchi user id.
     * Uchinchi tomon — pairwise: bir xil foydalanuvchi turli sektorlarda turli `sub`
     * oladi, shuning uchun ilovalar o'zaro ma'lumot birlashtira olmaydi.
     */
    public function subjectFor(User $user, IdClient $client): string
    {
        if ($client->first_party) {
            return (string) $user->id;
        }

        $salt = (string) config('kitobchi_id.pairwise_salt');

        return KeyStore::b64url(hash_hmac('sha256', $client->sector().'|'.$user->id, $salt, true));
    }

    /* ------------------------------------------------------------------ */
    /*  Authorization code                                                 */
    /* ------------------------------------------------------------------ */

    public function createAuthCode(IdClient $client, User $user, array $req, int $authTime): string
    {
        $code = self::randomToken(32);

        IdAuthCode::create([
            'code_hash' => self::hash($code),
            'client_pk' => $client->id,
            'user_id' => $user->id,
            'redirect_uri' => $req['redirect_uri'],
            'scope' => $req['scope'],
            'code_challenge' => $req['code_challenge'],
            'nonce' => $req['nonce'] ?? null,
            'auth_time' => $authTime,
            'expires_at' => now()->addSeconds((int) config('kitobchi_id.code_ttl', 300)),
        ]);

        return $code;
    }

    /** @return array token javobi */
    public function exchangeCode(IdClient $client, string $code, string $redirectUri, string $verifier): array
    {
        if (! preg_match('/^[A-Za-z0-9\-._~]{43,128}$/', $verifier)) {
            throw new OAuthException('invalid_grant', 'code_verifier noto\'g\'ri formatda');
        }

        // Xatolar tranzaksiyadan QAYTARILADI (throw emas) — shunda kodni "ishlatilgan"
        // deb belgilash va bekor qilishlar rollback bo'lmaydi: har qanday muvaffaqiyatsiz
        // urinish kodni yoqib yuboradi.
        $result = DB::transaction(function () use ($client, $code, $redirectUri, $verifier) {
            /** @var IdAuthCode|null $row */
            $row = IdAuthCode::where('code_hash', self::hash($code))->lockForUpdate()->first();

            if (! $row || (int) $row->client_pk !== (int) $client->id) {
                return new OAuthException('invalid_grant', 'Kod yaroqsiz');
            }

            if ($row->used_at !== null) {
                // Qayta ishlatish — kod o'g'irlangan bo'lishi mumkin
                $this->revokeByAuthCode($row);
                Log::warning('kitobchi-id: auth code reuse', ['client' => $client->client_id, 'user' => $row->user_id]);

                return new OAuthException('invalid_grant', 'Kod allaqachon ishlatilgan');
            }

            $row->used_at = now();
            $row->save();

            if ($row->expires_at->isPast()) {
                return new OAuthException('invalid_grant', 'Kod muddati tugagan');
            }
            if (! hash_equals($row->redirect_uri, $redirectUri)) {
                return new OAuthException('invalid_grant', 'redirect_uri mos emas');
            }
            $computed = KeyStore::b64url(hash('sha256', $verifier, true));
            if (! hash_equals($row->code_challenge, $computed)) {
                return new OAuthException('invalid_grant', 'PKCE tekshiruvidan o\'tmadi');
            }

            try {
                $user = $this->activeUser((int) $row->user_id);
            } catch (OAuthException $e) {
                return $e;
            }

            return $this->issueTokens($client, $user, $row->scope, (int) $row->auth_time, $row->nonce, null, $row);
        });

        if ($result instanceof OAuthException) {
            throw $result;
        }

        return $result;
    }

    /* ------------------------------------------------------------------ */
    /*  Refresh token                                                      */
    /* ------------------------------------------------------------------ */

    public function refresh(IdClient $client, string $token, ?string $requestedScope): array
    {
        if (! str_starts_with($token, self::REFRESH_PREFIX)) {
            throw new OAuthException('invalid_grant', 'Refresh token yaroqsiz');
        }

        $result = DB::transaction(function () use ($client, $token, $requestedScope) {
            /** @var IdRefreshToken|null $row */
            $row = IdRefreshToken::where('token_hash', self::hash($token))->lockForUpdate()->first();

            if (! $row || (int) $row->client_pk !== (int) $client->id) {
                return new OAuthException('invalid_grant', 'Refresh token yaroqsiz');
            }

            if ($row->used_at !== null || $row->revoked_at !== null) {
                // Eski (aylantirilgan) token qayta keldi — oila bekor qilinadi.
                $this->revokeFamily($row->family_id);
                Log::warning('kitobchi-id: refresh token reuse, family revoked', [
                    'client' => $client->client_id, 'user' => $row->user_id, 'family' => $row->family_id,
                ]);

                return new OAuthException('invalid_grant', 'Refresh token bekor qilingan');
            }

            if ($row->expires_at->isPast() || $row->family_expires_at->isPast()) {
                return new OAuthException('invalid_grant', 'Refresh token muddati tugagan');
            }

            $granted = self::scopes($row->scope);
            $scope = $row->scope;
            if ($requestedScope !== null && $requestedScope !== '') {
                $asked = self::scopes($requestedScope);
                if (array_diff($asked, $granted)) {
                    return new OAuthException('invalid_scope', 'So\'ralgan scope dastlabki ruxsatdan keng');
                }
                $scope = implode(' ', $asked);
            }

            $row->used_at = now();
            $row->save();

            try {
                $user = $this->activeUser((int) $row->user_id);
            } catch (OAuthException $e) {
                $this->revokeFamily($row->family_id);

                return $e;
            }

            // Toraytirilgan scope'da offline_access bo'lmasa ham oila davom etadi
            // (asl ruxsatda bor edi).
            return $this->issueTokens($client, $user, $scope, (int) $row->auth_time, null, $row);
        });

        if ($result instanceof OAuthException) {
            throw $result; // tranzaksiya commit bo'lgandan keyin (revoke saqlanadi)
        }

        return $result;
    }

    public function revokeFamily(string $familyId): void
    {
        IdRefreshToken::where('family_id', $familyId)->whereNull('revoked_at')->update(['revoked_at' => now()]);
    }

    /** Tokenni bekor qilish (RFC 7009). Boshqa clientga tegishli token jim e'tiborsiz qoldiriladi. */
    public function revoke(IdClient $client, string $token, ?string $hint): void
    {
        $tryRefresh = function () use ($client, $token): bool {
            if (! str_starts_with($token, self::REFRESH_PREFIX)) {
                return false;
            }
            $row = IdRefreshToken::where('token_hash', self::hash($token))->first();
            if ($row && (int) $row->client_pk === (int) $client->id) {
                $this->revokeFamily($row->family_id);
            }

            return true;
        };
        $tryAccess = function () use ($client, $token): bool {
            try {
                $claims = $this->verifyAccessToken($token);
            } catch (Throwable) {
                return false;
            }
            if (($claims['client_id'] ?? null) === $client->client_id) {
                $this->denyJti((string) $claims['jti'], (int) $claims['exp']);
            }

            return true;
        };

        if ($hint === 'access_token') {
            $tryAccess() || $tryRefresh();
        } else {
            $tryRefresh() || $tryAccess();
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Token chiqarish                                                    */
    /* ------------------------------------------------------------------ */

    private function issueTokens(
        IdClient $client,
        User $user,
        string $scope,
        int $authTime,
        ?string $nonce,
        ?IdRefreshToken $parent,
        ?IdAuthCode $code = null,
    ): array {
        $scopes = self::scopes($scope);
        $now = time();
        $sub = $this->subjectFor($user, $client);
        $key = $this->keys->active();

        $accessTtl = (int) config('kitobchi_id.access_token_ttl', 600);
        $jti = self::randomToken(16);
        $access = JWT::encode([
            'iss' => self::issuer(),
            'sub' => $sub,
            'aud' => self::issuer(),
            'client_id' => $client->client_id,
            'scope' => $scope,
            'auth_time' => $authTime,
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + $accessTtl,
            'jti' => $jti,
            // Shifrlangan ichki user id (pairwise sub uchun userinfo'da kerak)
            'ku' => Crypt::encryptString((string) $user->id),
        ], $key['pem'], 'RS256', $key['kid'], ['typ' => 'at+jwt']);

        if ($code) {
            // Kod qayta ishlatilsa shu access tokenni ham bekor qilish uchun
            Cache::put('kid:code_jti:'.$code->id, $jti, $accessTtl);
        }

        $response = [
            'access_token' => $access,
            'token_type' => 'Bearer',
            'expires_in' => $accessTtl,
            'scope' => $scope,
        ];

        if (in_array('openid', $scopes, true)) {
            $claims = [
                'iss' => self::issuer(),
                'sub' => $sub,
                'aud' => $client->client_id,
                'azp' => $client->client_id,
                'iat' => $now,
                'exp' => $now + (int) config('kitobchi_id.id_token_ttl', 3600),
                'auth_time' => $authTime,
                'at_hash' => KeyStore::b64url(substr(hash('sha256', $access, true), 0, 16)),
                'amr' => ['otp'],
            ];
            if ($nonce !== null && $nonce !== '') {
                $claims['nonce'] = $nonce;
            }
            $claims += $this->userClaims($user, $scopes);
            $response['id_token'] = JWT::encode($claims, $key['pem'], 'RS256', $key['kid'], ['typ' => 'JWT']);
        }

        if ($parent !== null || in_array('offline_access', $scopes, true)) {
            $response['refresh_token'] = $this->createRefreshToken($client, $user, $parent ? $parent->scope : $scope, $authTime, $parent, $code);
        }

        return $response;
    }

    private function createRefreshToken(IdClient $client, User $user, string $scope, int $authTime, ?IdRefreshToken $parent, ?IdAuthCode $code): string
    {
        $token = self::REFRESH_PREFIX.self::randomToken(32);
        $familyExpires = $parent?->family_expires_at
            ?? now()->addSeconds((int) config('kitobchi_id.refresh_token_absolute_ttl'));
        $expires = now()->addSeconds((int) config('kitobchi_id.refresh_token_ttl'));
        if ($expires->greaterThan($familyExpires)) {
            $expires = $familyExpires->copy();
        }
        $request = request();

        IdRefreshToken::create([
            'token_hash' => self::hash($token),
            'family_id' => $parent?->family_id ?? bin2hex(random_bytes(16)),
            'client_pk' => $client->id,
            'user_id' => $user->id,
            'auth_code_id' => $parent?->auth_code_id ?? $code?->id,
            'scope' => $scope,
            'auth_time' => $authTime,
            'expires_at' => $expires,
            'family_expires_at' => $familyExpires,
            'ip' => $request?->ip(),
            'user_agent' => mb_substr((string) $request?->userAgent(), 0, 255),
        ]);

        return $token;
    }

    private function revokeByAuthCode(IdAuthCode $code): void
    {
        IdRefreshToken::where('auth_code_id', $code->id)->whereNull('revoked_at')->update(['revoked_at' => now()]);
        $jti = Cache::get('kid:code_jti:'.$code->id);
        if ($jti) {
            $this->denyJti($jti, time() + (int) config('kitobchi_id.access_token_ttl', 600));
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Tekshirish                                                         */
    /* ------------------------------------------------------------------ */

    /** Access tokenni tekshiradi va claim'larni qaytaradi. */
    public function verifyAccessToken(string $jwt): array
    {
        $header = $this->header($jwt);
        if (($header['typ'] ?? '') !== 'at+jwt') {
            // ID tokenni access token o'rnida ishlatishning oldini oladi
            throw new OAuthException('invalid_token', 'Token turi noto\'g\'ri', 401);
        }
        $claims = $this->decode($jwt);

        if (($claims['iss'] ?? null) !== self::issuer() || ($claims['aud'] ?? null) !== self::issuer()) {
            throw new OAuthException('invalid_token', 'Token boshqa issuer uchun', 401);
        }
        if (! isset($claims['jti'], $claims['sub'], $claims['client_id']) || $this->isJtiDenied((string) $claims['jti'])) {
            throw new OAuthException('invalid_token', 'Token bekor qilingan', 401);
        }

        return $claims;
    }

    /** id_token_hint uchun: imzo va issuer tekshiriladi, muddati o'tgan bo'lsa ham qabul qilinadi. */
    public function verifyIdTokenHint(string $jwt): ?array
    {
        try {
            $leeway = JWT::$leeway;
            JWT::$leeway = 365 * 24 * 3600;
            try {
                $claims = $this->decode($jwt);
            } finally {
                JWT::$leeway = $leeway;
            }
        } catch (Throwable) {
            return null;
        }

        return ($claims['iss'] ?? null) === self::issuer() ? $claims : null;
    }

    private function decode(string $jwt): array
    {
        $keys = [];
        foreach ($this->keys->verificationKeys() as $kid => $pem) {
            $keys[$kid] = new Key($pem, 'RS256');
        }
        if (! $keys) {
            throw new OAuthException('server_error', 'Kalit yo\'q', 500);
        }
        $prevLeeway = JWT::$leeway;
        JWT::$leeway = max($prevLeeway, 30);
        try {
            return (array) JWT::decode($jwt, $keys);
        } catch (OAuthException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new OAuthException('invalid_token', 'Token yaroqsiz', 401);
        } finally {
            JWT::$leeway = $prevLeeway;
        }
    }

    private function header(string $jwt): array
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            throw new OAuthException('invalid_token', 'Token yaroqsiz', 401);
        }
        $json = base64_decode(strtr($parts[0], '-_', '+/'), true);

        return is_string($json) ? (array) json_decode($json, true) : [];
    }

    public function denyJti(string $jti, int $exp): void
    {
        $ttl = max(60, $exp - time() + 60);
        Cache::put('kid:deny:'.$jti, 1, $ttl);
    }

    private function isJtiDenied(string $jti): bool
    {
        return (bool) Cache::get('kid:deny:'.$jti);
    }

    /* ------------------------------------------------------------------ */
    /*  Foydalanuvchi ma'lumotlari                                         */
    /* ------------------------------------------------------------------ */

    public function activeUser(int $id): User
    {
        /** @var User|null $user */
        $user = User::where('id', $id)->where('isDeleted', 'no')->first();
        if (! $user || $user->isBlocked()) {
            throw new OAuthException('invalid_grant', 'Akkaunt faol emas');
        }

        return $user;
    }

    /** Scope'ga ko'ra OIDC standart claim'lari. */
    public function userClaims(User $user, array $scopes): array
    {
        $c = [];
        if (in_array('profile', $scopes, true)) {
            $c['name'] = trim(($user->name ?? '').' '.($user->lastname ?? '')) ?: null;
            $c['given_name'] = $user->name ?: null;
            $c['family_name'] = $user->lastname ?: null;
            $c['preferred_username'] = $user->username ?: null;
            $c['picture'] = $this->avatarUrl($user->avatar);
            $c['locale'] = $user->locale ?: 'uz';
            $c['updated_at'] = $user->updated_at?->getTimestamp();
        }
        if (in_array('phone', $scopes, true) && $user->phone_number) {
            $c['phone_number'] = '+'.ltrim((string) $user->phone_number, '+');
            $c['phone_number_verified'] = $user->phone_verified_at !== null;
        }
        if (in_array('email', $scopes, true) && $user->email) {
            $c['email'] = $user->email;
            $c['email_verified'] = ! empty($user->email_verified_at);
        }

        return array_filter($c, fn ($v) => $v !== null);
    }

    private function avatarUrl(?string $avatar): ?string
    {
        if (! $avatar) {
            return null;
        }
        if (preg_match('#^https://#i', $avatar)) {
            return $avatar;
        }

        try {
            return Storage::disk('public')->url($avatar);
        } catch (Throwable) {
            return null;
        }
    }

    /** "openid  profile openid" → ['openid','profile'] */
    public static function scopes(?string $scope): array
    {
        return array_values(array_unique(array_filter(explode(' ', trim((string) $scope)), 'strlen')));
    }
}
