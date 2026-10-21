<?php

namespace App\Http\Controllers\KitobchiId;

use App\Http\Controllers\Controller;
use App\Models\KitobchiId\IdClient;
use App\Services\KitobchiId\OtpService;
use App\Services\KitobchiId\PhoneAccounts;
use App\Services\KitobchiId\TokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Ekotizim (first-party) mobil ilovalari uchun brauzersiz kirish.
 *
 * Foydalanuvchi ilovadan CHIQMAYDI: ilova o'z ekranida raqam va kodni so'raydi,
 * Kitobchi ID esa kodni Kitobchi ilovasidagi chatga (yoki SMS) yuboradi.
 * IETF "OAuth 2.0 for First-Party Applications" (Authorization Challenge) g'oyasiga mos:
 *
 *   POST /native/start  → auth_session (+ kod yuboriladi)
 *   POST /native/resend → o'sha kodni SMS orqali
 *   POST /native/verify → authorization_code
 *   POST /token         → grant_type=authorization_code + code_verifier (PKCE)
 *
 * Faqat `first_party` ilovalar uchun — uchinchi tomon ilovalari foydalanuvchi
 * Kitobchi sahifasini ko'rishi (domen, rozilik) uchun brauzer oqimidan foydalanadi.
 */
class NativeLoginController extends Controller
{
    private const SESSION_TTL = 600;

    public function __construct(
        private OtpService $otp,
        private TokenService $tokens,
        private PhoneAccounts $accounts,
    ) {
    }

    public function start(Request $request): JsonResponse
    {
        $client = $this->client($request);
        if ($client instanceof JsonResponse) {
            return $client;
        }

        $redirectUri = $this->string($request, 'redirect_uri');
        if ($redirectUri === null || ! $client->hasRedirectUri($redirectUri)) {
            return $this->error('invalid_request', 'redirect_uri ro\'yxatdan o\'tmagan');
        }
        if ($request->input('code_challenge_method') !== 'S256'
            || ! preg_match('/^[A-Za-z0-9_-]{43}$/', (string) $this->string($request, 'code_challenge'))) {
            return $this->error('invalid_request', 'PKCE majburiy: code_challenge (S256)');
        }
        $nonce = (string) $this->string($request, 'nonce');
        if ($nonce === '' || strlen($nonce) > 255) {
            return $this->error('invalid_request', 'nonce majburiy');
        }
        $scopes = TokenService::scopes((string) $this->string($request, 'scope'));
        if (! in_array('openid', $scopes, true)) {
            return $this->error('invalid_scope', 'openid scope majburiy');
        }
        if (array_diff($scopes, array_keys((array) config('kitobchi_id.scopes'))) || array_diff($scopes, (array) $client->allowed_scopes)) {
            return $this->error('invalid_scope', 'Ruxsat berilmagan scope');
        }

        $phone = OtpService::normalizePhone((string) $this->string($request, 'phone'));
        if (! $phone) {
            return $this->error('invalid_phone', 'Telefon raqam 998XXXXXXXXX ko\'rinishida bo\'lishi kerak');
        }

        $res = $this->otp->send($phone, $client->name);
        if (! $res['ok'] && $res['error'] !== 'wait') {
            return $this->sendError($res);
        }

        $session = TokenService::randomToken(32);
        Cache::put(self::key($session), [
            'client_pk' => $client->id,
            'phone' => $phone,
            'scope' => implode(' ', $scopes),
            'code_challenge' => (string) $request->input('code_challenge'),
            'nonce' => $nonce,
            'redirect_uri' => $redirectUri,
        ], self::SESSION_TTL);

        return $this->json([
            'auth_session' => $session,
            'masked_phone' => AuthorizeController::maskPhone($phone),
            'expires_in' => (int) config('kitobchi_id.otp_ttl', 300),
        ] + $this->deliveryInfo($phone, $res));
    }

    public function resend(Request $request): JsonResponse
    {
        [$client, $session, $sessionKey] = $this->session($request);
        if ($client instanceof JsonResponse) {
            return $client;
        }

        // Ilovaga yuborilgan bo'lsa — o'sha kod SMS bilan; aks holda yangi SMS
        $status = $this->otp->status($session['phone']);
        $res = $this->otp->send($session['phone'], $client->name, ($status['channel'] ?? null) === 'app');
        if (! $res['ok'] && $res['error'] !== 'wait') {
            return $this->sendError($res);
        }
        Cache::put($sessionKey, $session, self::SESSION_TTL);

        return $this->json($this->deliveryInfo($session['phone'], $res));
    }

    public function verify(Request $request): JsonResponse
    {
        [$client, $session, $sessionKey] = $this->session($request);
        if ($client instanceof JsonResponse) {
            return $client;
        }

        $result = $this->otp->verify($session['phone'], (string) $this->string($request, 'code'));
        if ($result !== 'ok') {
            if ($result !== 'invalid') {
                Cache::forget($sessionKey);
            }

            return match ($result) {
                'locked' => $this->error('too_many_attempts', 'Juda ko\'p noto\'g\'ri urinish. Qaytadan boshlang.', 429),
                'expired' => $this->error('expired_code', 'Kod muddati tugagan. Qaytadan boshlang.'),
                default => $this->error('invalid_code', 'Kod noto\'g\'ri'),
            };
        }
        Cache::forget($sessionKey);

        $user = $this->accounts->resolve($session['phone'], $client);
        if ($user->isBlocked()) {
            return $this->error('account_blocked', 'Akkaunt vaqtincha cheklangan', 403);
        }

        $code = $this->tokens->createAuthCode($client, $user, $session, time());

        return $this->json([
            'authorization_code' => $code,
            'redirect_uri' => $session['redirect_uri'],
            'iss' => TokenService::issuer(),
        ]);
    }

    /* ------------------------------------------------------------------ */

    private function client(Request $request): IdClient|JsonResponse
    {
        $clientId = $this->string($request, 'client_id');
        $client = $clientId ? IdClient::where('client_id', $clientId)->first() : null;
        if (! $client || ! $client->isActive()) {
            return $this->error('invalid_client', 'Ilova topilmadi', 401);
        }
        if (! $client->first_party) {
            return $this->error('unauthorized_client', 'Brauzersiz kirish faqat Kitobchi ekotizimi ilovalari uchun', 403);
        }
        if ($request->filled('client_secret') && ! $client->isPublic() && ! $client->verifySecret((string) $request->input('client_secret'))) {
            return $this->error('invalid_client', 'client_secret noto\'g\'ri', 401);
        }

        return $client;
    }

    /** @return array{0: IdClient|JsonResponse, 1: ?array, 2: ?string} */
    private function session(Request $request): array
    {
        $client = $this->client($request);
        if ($client instanceof JsonResponse) {
            return [$client, null, null];
        }
        $token = (string) $this->string($request, 'auth_session');
        $key = self::key($token);
        $session = $token !== '' ? Cache::get($key) : null;
        if (! is_array($session) || (int) $session['client_pk'] !== (int) $client->id) {
            return [$this->error('invalid_session', 'Sessiya topilmadi yoki eskirgan. Qaytadan boshlang.'), null, null];
        }

        return [$client, $session, $key];
    }

    private function deliveryInfo(string $phone, array $res): array
    {
        $status = $this->otp->status($phone);
        $channel = $res['channel'] ?? ($status['channel'] ?? 'sms');
        $info = ['channel' => $channel];
        if ($channel === 'app') {
            $info['sms_available_in'] = (int) ($status['sms_after'] ?? $res['sms_after'] ?? 30);
        }
        if (isset($res['retry_after'])) {
            $info['retry_after'] = (int) $res['retry_after'];
        }

        return $info;
    }

    private function sendError(array $res): JsonResponse
    {
        return $res['error'] === 'daily_limit'
            ? $this->error('daily_limit', 'Bugun bu raqamga juda ko\'p kod yuborildi', 429)
            : $this->error('delivery_failed', 'Kodni yuborib bo\'lmadi, birozdan so\'ng urinib ko\'ring', 503);
    }

    private function string(Request $request, string $key): ?string
    {
        $v = $request->input($key);

        return is_string($v) && strlen($v) <= 2048 ? $v : null;
    }

    private static function key(string $session): string
    {
        return 'kid_native:'.hash('sha256', $session);
    }

    private function error(string $error, string $description, int $status = 400): JsonResponse
    {
        return $this->json(['error' => $error, 'error_description' => $description], $status);
    }

    private function json(array $data, int $status = 200): JsonResponse
    {
        return response()->json($data, $status, ['Cache-Control' => 'no-store', 'Pragma' => 'no-cache']);
    }
}
