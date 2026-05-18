<?php

namespace App\Services;

use App\Models\ProjectSetting;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Native SDK (iOS/Android) bilan ishlash uchun yengillashtirilgan service.
 *
 * Client (mobil app) TelegramLogin SDK dan olingan `id_token` ni backend ga yuboradi.
 * Backend faqat ikki ishni qiladi:
 *   1) /api/auth/telegram/config → enabled + client_id (app shu bilan SDK'ni configure qiladi)
 *   2) id_token ni JWKS orqali signature + iss/aud/exp claim'lari bo'yicha tekshiradi.
 *
 * /token endpoint, client_secret, PKCE — bular manual OIDC flow uchun edi, hozir kerak emas.
 */
class TelegramOidcService
{
    private const ISSUER = 'https://oauth.telegram.org';
    private const DEFAULT_REDIRECT_URI_IOS = 'https://app3206985527-login.tg.dev';
    private const DEFAULT_REDIRECT_URI_ANDROID = 'https://app2854400165-login.tg.dev/tglogin';
    private const DEBUG_REDIRECT_URI_IOS = 'kitobchi://tglogin';
    private const DEBUG_REDIRECT_URI_ANDROID = 'kitobchi://telegram-login';
    private const CONFIG_CACHE_KEY = 'telegram_oidc_configuration';
    private const JWKS_CACHE_KEY = 'telegram_oidc_jwks';

    public function publicConfig(?ProjectSetting $settings = null): array
    {
        $settings ??= ProjectSetting::first();

        return [
            'enabled'   => (bool) ($settings?->telegram_login_enabled ?? false),
            'client_id' => $this->clientId($settings),
            'scopes'    => $this->scopes($settings),
            'issuer'    => self::ISSUER,
            'redirect_uri_ios' => $this->redirectUriIos($settings),
            'redirect_uri_android' => $this->redirectUriAndroid($settings),
        ];
    }

    public function clientId(?ProjectSetting $settings = null): ?string
    {
        $settings ??= ProjectSetting::first();

        return $settings?->telegram_client_id ?: env('TELEGRAM_LOGIN_CLIENT_ID');
    }

    public function scopes(?ProjectSetting $settings = null): string
    {
        $settings ??= ProjectSetting::first();

        return trim((string) ($settings?->telegram_scopes ?: 'openid profile phone'));
    }

    public function redirectUriIos(?ProjectSetting $settings = null): string
    {
        $settings ??= ProjectSetting::first();

        return $this->sanitizeRedirectUri(
            $settings?->telegram_redirect_uri_ios
            ?: env('TELEGRAM_LOGIN_REDIRECT_URI_IOS')
            ?: self::DEFAULT_REDIRECT_URI_IOS,
            self::DEFAULT_REDIRECT_URI_IOS,
            false,
        );
    }

    public function redirectUriAndroid(?ProjectSetting $settings = null): string
    {
        $settings ??= ProjectSetting::first();

        return $this->sanitizeRedirectUri(
            $settings?->telegram_redirect_uri_android
            ?: env('TELEGRAM_LOGIN_REDIRECT_URI_ANDROID')
            ?: self::DEFAULT_REDIRECT_URI_ANDROID,
            self::DEFAULT_REDIRECT_URI_ANDROID,
            true,
        );
    }

    /**
     * Native SDK dan kelgan id_token ni tekshiradi.
     * Muvaffaqiyatli holatda decoded claims massivini qaytaradi.
     */
    public function validateIdToken(string $idToken, ?string $expectedClientId = null): array
    {
        $expectedClientId ??= $this->clientId();

        if ($idToken === '') {
            throw new RuntimeException('id_token bo‘sh.');
        }

        if (!$expectedClientId) {
            throw new RuntimeException('Telegram client_id sozlanmagan.');
        }

        $keys = JWK::parseKeySet($this->jwks());
        $decoded = (array) JWT::decode($idToken, $keys);

        if (($decoded['iss'] ?? null) !== self::ISSUER) {
            throw new RuntimeException('Telegram issuer noto‘g‘ri.');
        }

        $audience = $decoded['aud'] ?? null;
        $validAudience = is_array($audience)
            ? in_array((string) $expectedClientId, array_map('strval', $audience), true)
            : (string) $audience === (string) $expectedClientId;

        if (!$validAudience) {
            throw new RuntimeException('Telegram audience noto‘g‘ri.');
        }

        if (isset($decoded['exp']) && (int) $decoded['exp'] < now()->timestamp) {
            throw new RuntimeException('Telegram token muddati tugagan.');
        }

        return $decoded;
    }

    private function discovery(): array
    {
        return Cache::remember(self::CONFIG_CACHE_KEY, 3600, function () {
            return Http::timeout(10)
                ->get(self::ISSUER . '/.well-known/openid-configuration')
                ->throw()
                ->json();
        });
    }

    private function jwks(): array
    {
        return Cache::remember(self::JWKS_CACHE_KEY, 3600, function () {
            $oidc = $this->discovery();
            $jwksUri = Arr::get($oidc, 'jwks_uri', self::ISSUER . '/.well-known/jwks.json');

            return Http::timeout(10)
                ->get($jwksUri)
                ->throw()
                ->json();
        });
    }

    private function sanitizeRedirectUri(?string $candidate, string $fallback, bool $requireAndroidPath): string
    {
        $value = trim((string) $candidate);
        if ($value === '') {
            return $fallback;
        }

        $parts = parse_url($value);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));
        $path = (string) ($parts['path'] ?? '');

        $isSupportedCustomScheme = $scheme === 'kitobchi'
            && (
                (!$requireAndroidPath && $host === 'tglogin')
                || ($requireAndroidPath && $host === 'telegram-login')
            );

        $isTelegramUniversalLink = $scheme === 'https'
            && str_ends_with($host, '.tg.dev');

        if (!$isTelegramUniversalLink && !$isSupportedCustomScheme) {
            return $fallback;
        }

        if ($requireAndroidPath && $isTelegramUniversalLink && $path === '') {
            return $fallback;
        }

        return $value;
    }
}
