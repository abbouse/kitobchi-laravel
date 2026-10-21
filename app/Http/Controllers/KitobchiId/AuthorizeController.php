<?php

namespace App\Http\Controllers\KitobchiId;

use App\Http\Controllers\Controller;
use App\Models\KitobchiId\IdClient;
use App\Models\KitobchiId\IdConsent;
use App\Models\User;
use App\Services\KitobchiId\OtpService;
use App\Services\KitobchiId\PhoneAccounts;
use App\Services\KitobchiId\TokenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * OIDC Authorization endpoint + kirish (SMS kod) + rozilik oynasi.
 *
 * Oqim: /authorize → (kirish) → (rozilik) → redirect_uri?code=…&state=…&iss=…
 * So'rov parametrlari sessiyada `rid` kaliti ostida saqlanadi; brauzer faqat
 * tasodifiy `rid` ni ko'radi.
 */
class AuthorizeController extends Controller
{
    private const MAX_PENDING = 5;

    public function __construct(private TokenService $tokens, private OtpService $otp)
    {
    }

    /* ================================================================== */
    /*  GET /authorize                                                     */
    /* ================================================================== */

    public function handleAuthorize(Request $request): Response
    {
        $q = $request->query();

        // 1) Client va redirect_uri — xato bo'lsa HECH QACHON redirect qilinmaydi
        //    (ochiq redirect zaifligining oldini olish).
        $clientId = is_string($q['client_id'] ?? null) ? $q['client_id'] : '';
        $client = $clientId !== '' ? IdClient::where('client_id', $clientId)->first() : null;
        if (! $client || ! $client->isActive()) {
            return $this->errorPage('Ilova topilmadi yoki o\'chirilgan.', 'invalid_client');
        }
        $redirectUri = is_string($q['redirect_uri'] ?? null) ? $q['redirect_uri'] : '';
        if ($redirectUri === '' || ! $client->hasRedirectUri($redirectUri)) {
            return $this->errorPage('Qaytish manzili (redirect_uri) ushbu ilova uchun ro\'yxatdan o\'tmagan.', 'invalid_redirect_uri');
        }

        $state = is_string($q['state'] ?? null) ? $q['state'] : '';
        $fail = fn (string $error, string $desc) => $this->redirectError($redirectUri, $error, $desc, $state);

        foreach (['client_id', 'redirect_uri', 'response_type', 'scope', 'state', 'code_challenge', 'code_challenge_method', 'nonce', 'prompt', 'max_age', 'login_hint', 'ui_locales'] as $p) {
            if (isset($q[$p]) && ! is_string($q[$p])) {
                return $fail('invalid_request', "$p parametri takrorlangan");
            }
        }
        if (isset($q['request']) || isset($q['request_uri'])) {
            return $fail(isset($q['request']) ? 'request_not_supported' : 'request_uri_not_supported', 'Request object qo\'llab-quvvatlanmaydi');
        }
        if (($q['response_type'] ?? '') !== 'code') {
            return $fail('unsupported_response_type', 'Faqat response_type=code');
        }
        if ($state === '' || strlen($state) > 512) {
            return $fail('invalid_request', 'state majburiy (1–512 belgi)');
        }
        if (($q['code_challenge_method'] ?? '') !== 'S256' || ! preg_match('/^[A-Za-z0-9_-]{43}$/', (string) ($q['code_challenge'] ?? ''))) {
            return $fail('invalid_request', 'PKCE majburiy: code_challenge (S256, 43 belgi) va code_challenge_method=S256');
        }

        $scopes = TokenService::scopes($q['scope'] ?? '');
        $known = array_keys((array) config('kitobchi_id.scopes'));
        if (! in_array('openid', $scopes, true)) {
            return $fail('invalid_scope', 'openid scope majburiy');
        }
        if ($unknown = array_diff($scopes, $known)) {
            return $fail('invalid_scope', 'Noma\'lum scope: '.implode(' ', $unknown));
        }
        if ($forbidden = array_diff($scopes, (array) $client->allowed_scopes)) {
            return $fail('invalid_scope', 'Ilovaga ruxsat berilmagan scope: '.implode(' ', $forbidden));
        }

        $nonce = (string) ($q['nonce'] ?? '');
        if ($nonce === '' || strlen($nonce) > 255) {
            return $fail('invalid_request', 'nonce majburiy (1–255 belgi)');
        }

        $prompt = TokenService::scopes($q['prompt'] ?? '');
        if (array_diff($prompt, ['none', 'login', 'consent', 'select_account']) || (in_array('none', $prompt, true) && count($prompt) > 1)) {
            return $fail('invalid_request', 'prompt qiymati noto\'g\'ri');
        }
        $maxAge = isset($q['max_age']) && ctype_digit((string) $q['max_age']) ? (int) $q['max_age'] : null;

        $req = [
            'client_id' => $client->client_id,
            'redirect_uri' => $redirectUri,
            'scope' => implode(' ', $scopes),
            'state' => $state,
            'nonce' => $nonce,
            'code_challenge' => $q['code_challenge'],
            'prompt' => $prompt,
            'max_age' => $maxAge,
            'login_hint' => OtpService::normalizePhone((string) ($q['login_hint'] ?? '')),
            'created_at' => time(),
        ];

        $rid = $this->storeRequest($request, $req);

        // prompt=login / select_account — mavjud sessiyani qayta tasdiqlash talab qilinadi
        if (array_intersect($prompt, ['login', 'select_account'])) {
            $this->forgetLogin($request);
        }

        return $this->decide($request, $rid);
    }

    /* ================================================================== */
    /*  Qaror: kirish → rozilik → kod                                      */
    /* ================================================================== */

    public function resume(Request $request): Response
    {
        return $this->decide($request, (string) $request->query('rid'));
    }

    private function decide(Request $request, string $rid): Response
    {
        [$req, $client] = $this->loadRequest($request, $rid);
        if (! $req) {
            return $this->errorPage('Kirish so\'rovi eskirgan. Ilovaga qaytib, qaytadan urinib ko\'ring.', 'expired_request');
        }

        $none = in_array('none', $req['prompt'], true);
        $user = $this->sessionUser($request, $req['max_age']);

        if (! $user) {
            if ($none) {
                return $this->finishWithError($request, $rid, $req, 'login_required', 'Foydalanuvchi tizimga kirmagan');
            }

            return $this->loginPage($request, $rid, $client, $req);
        }

        $scopes = TokenService::scopes($req['scope']);
        $needsConsent = ! $client->first_party && (
            in_array('consent', $req['prompt'], true) || ! $this->hasConsent($user, $client, $scopes)
        );

        if ($needsConsent) {
            if ($none) {
                return $this->finishWithError($request, $rid, $req, 'consent_required', 'Foydalanuvchi roziligi kerak');
            }

            return $this->consentPage($request, $rid, $client, $req, $user);
        }

        return $this->issueCode($request, $rid, $client, $req, $user);
    }

    private function issueCode(Request $request, string $rid, IdClient $client, array $req, User $user): Response
    {
        $code = $this->tokens->createAuthCode($client, $user, $req, (int) $request->session()->get('kid.auth_time'));
        $this->dropRequest($request, $rid);

        return $this->redirectTo($req['redirect_uri'], [
            'code' => $code,
            'state' => $req['state'],
            'iss' => TokenService::issuer(), // RFC 9207 — mix-up hujumidan himoya
        ]);
    }

    /* ================================================================== */
    /*  Kirish: SMS kod                                                    */
    /* ================================================================== */

    public function loginSend(Request $request): Response
    {
        $rid = (string) $request->input('rid');
        [$req, $client] = $this->loadRequest($request, $rid);
        if (! $req) {
            return $this->errorPage('Kirish so\'rovi eskirgan. Ilovaga qaytib, qaytadan urinib ko\'ring.', 'expired_request');
        }

        $viaSms = $request->input('via') === 'sms';
        // SMS zaxirasi faqat sessiyadagi (kod yuborilgan) raqamga
        $phone = $viaSms
            ? ((string) $request->session()->get('kid.otp.'.$rid) ?: null)
            : OtpService::normalizePhone((string) $request->input('phone'));
        if (! $phone) {
            return $this->loginPage($request, $rid, $client, $req, error: 'Telefon raqamni +998 XX XXX XX XX ko\'rinishida kiriting.');
        }

        $res = $this->otp->send($phone, $client->name, $viaSms);
        if (! $res['ok'] && $res['error'] !== 'wait') {
            $msg = $res['error'] === 'daily_limit'
                ? 'Bugun bu raqamga juda ko\'p kod yuborildi. Ertaga urinib ko\'ring.'
                : 'Kodni yuborib bo\'lmadi. Birozdan so\'ng qayta urinib ko\'ring.';

            return $this->loginPage($request, $rid, $client, $req, error: $msg, phone: $phone, step: $viaSms ? 'code' : null);
        }

        $request->session()->put('kid.otp.'.$rid, $phone);

        return redirect()->to(route('kitobchi-id.resume', ['rid' => $rid]).'&step=code', 303);
    }

    public function loginVerify(Request $request): Response
    {
        $rid = (string) $request->input('rid');
        [$req, $client] = $this->loadRequest($request, $rid);
        $phone = (string) $request->session()->get('kid.otp.'.$rid);
        if (! $req || $phone === '') {
            return $this->errorPage('Kirish so\'rovi eskirgan. Ilovaga qaytib, qaytadan urinib ko\'ring.', 'expired_request');
        }

        $result = $this->otp->verify($phone, (string) $request->input('code'));
        if ($result !== 'ok') {
            $msg = match ($result) {
                'locked' => 'Juda ko\'p noto\'g\'ri urinish. Yangi kod so\'rang.',
                'expired' => 'Kod muddati tugagan. Yangi kod so\'rang.',
                default => 'Kod noto\'g\'ri. Qayta tekshirib kiriting.',
            };
            if ($result !== 'invalid') {
                $request->session()->forget('kid.otp.'.$rid);
            }

            return $this->loginPage($request, $rid, $client, $req, error: $msg, phone: $phone, step: $result === 'invalid' ? 'code' : 'phone');
        }

        $user = app(PhoneAccounts::class)->resolve($phone, $client);

        if ($user->isBlocked()) {
            return $this->errorPage('Akkauntingiz vaqtincha cheklangan. Qo\'llab-quvvatlash xizmatiga murojaat qiling.', 'account_blocked');
        }

        // Sessiya fiksatsiyasining oldini olish
        $pending = $request->session()->get('kid.req', []);
        $request->session()->regenerate();
        $request->session()->put('kid.req', $pending);
        $request->session()->forget('kid.otp.'.$rid);
        $request->session()->put('kid.uid', $user->id);
        $request->session()->put('kid.auth_time', time());

        return redirect()->route('kitobchi-id.resume', ['rid' => $rid], 303);
    }

    /** Boshqa akkaunt bilan kirish */
    public function switchAccount(Request $request): Response
    {
        $rid = (string) $request->input('rid');
        $this->forgetLogin($request);

        return redirect()->route('kitobchi-id.resume', ['rid' => $rid], 303);
    }

    /* ================================================================== */
    /*  Rozilik                                                            */
    /* ================================================================== */

    public function consent(Request $request): Response
    {
        $rid = (string) $request->input('rid');
        [$req, $client] = $this->loadRequest($request, $rid);
        if (! $req) {
            return $this->errorPage('Kirish so\'rovi eskirgan. Ilovaga qaytib, qaytadan urinib ko\'ring.', 'expired_request');
        }
        $user = $this->sessionUser($request, $req['max_age']);
        if (! $user) {
            return redirect()->route('kitobchi-id.resume', ['rid' => $rid], 303);
        }

        if ($request->input('decision') !== 'allow') {
            return $this->finishWithError($request, $rid, $req, 'access_denied', 'Foydalanuvchi rad etdi');
        }

        $scopes = TokenService::scopes($req['scope']);
        $consent = IdConsent::firstOrNew(['user_id' => $user->id, 'client_pk' => $client->id]);
        $consent->scopes = array_values(array_unique(array_merge((array) $consent->scopes, $scopes)));
        $consent->save();

        return $this->issueCode($request, $rid, $client, $req, $user);
    }

    /* ================================================================== */
    /*  Chiqish (RP-initiated logout)                                      */
    /* ================================================================== */

    public function endSession(Request $request): Response
    {
        $hint = (string) $request->query('id_token_hint', '');
        $postLogout = (string) $request->query('post_logout_redirect_uri', '');
        $state = (string) $request->query('state', '');
        $claims = $hint !== '' ? $this->tokens->verifyIdTokenHint($hint) : null;

        $this->forgetLogin($request);

        if ($postLogout !== '') {
            $clientId = $claims['aud'] ?? (string) $request->query('client_id', '');
            $client = is_string($clientId) && $clientId !== '' ? IdClient::where('client_id', $clientId)->first() : null;
            // Faqat ro'yxatdan o'tgan manzilga va faqat imzosi to'g'ri id_token_hint bilan
            if ($client && $claims && $client->hasPostLogoutRedirectUri($postLogout)) {
                return $this->redirectTo($postLogout, $state !== '' ? ['state' => $state] : []);
            }
        }

        return $this->page('kitobchi-id.logged-out', []);
    }

    /* ================================================================== */
    /*  Yordamchilar                                                       */
    /* ================================================================== */

    private function sessionUser(Request $request, ?int $maxAge): ?User
    {
        $uid = (int) $request->session()->get('kid.uid');
        $authTime = (int) $request->session()->get('kid.auth_time');
        if (! $uid || ! $authTime) {
            return null;
        }
        $age = time() - $authTime;
        if ($age > (int) config('kitobchi_id.session_max_age') || ($maxAge !== null && $age > $maxAge)) {
            return null;
        }
        $user = User::where('id', $uid)->where('isDeleted', 'no')->first();
        if (! $user || $user->isBlocked()) {
            $this->forgetLogin($request);

            return null;
        }

        return $user;
    }

    private function forgetLogin(Request $request): void
    {
        $request->session()->forget(['kid.uid', 'kid.auth_time']);
    }

    private function hasConsent(User $user, IdClient $client, array $scopes): bool
    {
        $consent = IdConsent::where('user_id', $user->id)->where('client_pk', $client->id)->first();

        return $consent && ! array_diff($scopes, (array) $consent->scopes);
    }

    private function storeRequest(Request $request, array $req): string
    {
        $all = (array) $request->session()->get('kid.req', []);
        // Eng eski so'rovlarni tashlab yuborish
        uasort($all, fn ($a, $b) => ($a['created_at'] ?? 0) <=> ($b['created_at'] ?? 0));
        while (count($all) >= self::MAX_PENDING) {
            array_shift($all);
        }
        $rid = TokenService::randomToken(16);
        $all[$rid] = $req;
        $request->session()->put('kid.req', $all);

        return $rid;
    }

    /** @return array{0: ?array, 1: ?IdClient} */
    private function loadRequest(Request $request, string $rid): array
    {
        if (! preg_match('/^[A-Za-z0-9_-]{22}$/', $rid)) {
            return [null, null];
        }
        $req = $request->session()->get('kid.req.'.$rid);
        if (! is_array($req) || time() - (int) $req['created_at'] > 1800) {
            return [null, null];
        }
        $client = IdClient::where('client_id', $req['client_id'])->first();
        if (! $client || ! $client->isActive() || ! $client->hasRedirectUri($req['redirect_uri'])) {
            return [null, null];
        }

        return [$req, $client];
    }

    private function dropRequest(Request $request, string $rid): void
    {
        $all = (array) $request->session()->get('kid.req', []);
        unset($all[$rid]);
        $request->session()->put('kid.req', $all);
        $request->session()->forget('kid.otp.'.$rid);
    }

    private function finishWithError(Request $request, string $rid, array $req, string $error, string $desc): Response
    {
        $this->dropRequest($request, $rid);

        return $this->redirectError($req['redirect_uri'], $error, $desc, $req['state']);
    }

    private function redirectError(string $uri, string $error, string $desc, string $state): Response
    {
        $params = ['error' => $error, 'error_description' => $desc];
        if ($state !== '') {
            $params['state'] = $state;
        }
        $params['iss'] = TokenService::issuer();

        return $this->redirectTo($uri, $params);
    }

    private function redirectTo(string $uri, array $params): Response
    {
        $sep = str_contains($uri, '?') ? '&' : '?';
        $target = $params ? $uri.$sep.http_build_query($params, '', '&', PHP_QUERY_RFC3986) : $uri;

        return $this->secure(redirect()->away($target, 303));
    }

    private function loginPage(Request $request, string $rid, IdClient $client, array $req, ?string $error = null, ?string $phone = null, ?string $step = null): Response
    {
        $otpPhone = (string) $request->session()->get('kid.otp.'.$rid);
        $step ??= ($request->query('step') === 'code' && $otpPhone !== '') ? 'code' : 'phone';

        return $this->page('kitobchi-id.login', [
            'rid' => $rid,
            'client' => $client,
            'step' => $step,
            'phone' => $phone ?? ($otpPhone ?: $req['login_hint']),
            'error' => $error,
            'resend' => (int) config('kitobchi_id.otp_resend_seconds', 60),
            // Kod qayerga ketgani: 'app' (Kitobchi ilovasidagi chat) yoki 'sms'
            'delivery' => $otpPhone !== '' ? $this->otp->status($otpPhone) : null,
        ], $error ? 422 : 200);
    }

    private function consentPage(Request $request, string $rid, IdClient $client, array $req, User $user): Response
    {
        $labels = (array) config('kitobchi_id.scopes');
        $scopes = array_values(array_filter(TokenService::scopes($req['scope']), fn ($s) => $s !== 'openid'));

        return $this->page('kitobchi-id.consent', [
            'rid' => $rid,
            'client' => $client,
            'user' => $user,
            'maskedPhone' => self::maskPhone((string) $user->phone_number),
            'scopes' => array_map(fn ($s) => ['key' => $s, 'label' => $labels[$s] ?? $s], $scopes),
            'redirectHost' => parse_url($req['redirect_uri'], PHP_URL_HOST) ?: $req['redirect_uri'],
        ]);
    }

    private function errorPage(string $message, string $code): Response
    {
        return $this->page('kitobchi-id.error', ['message' => $message, 'code' => $code], 400);
    }

    private function page(string $view, array $data, int $status = 200): Response
    {
        return $this->secure(response()->view($view, $data, $status));
    }

    /** Kirish sahifalari iframe ichida ochilmaydi, keshlanmaydi, referer yuborilmaydi. */
    private function secure(Response $response): Response
    {
        $response->headers->set('Cache-Control', 'no-store');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Referrer-Policy', 'no-referrer');
        $response->headers->set('Content-Security-Policy', "default-src 'none'; img-src 'self' https: data:; style-src 'unsafe-inline'; script-src 'unsafe-inline'; frame-ancestors 'none'; base-uri 'none'");

        return $response;
    }

    public static function maskPhone(string $phone): string
    {
        $d = preg_replace('/\D+/', '', $phone);
        if (strlen($d) !== 12) {
            return $phone;
        }

        return '+'.substr($d, 0, 3).' '.substr($d, 3, 2).' *** ** '.substr($d, 10, 2);
    }
}
