<?php

namespace App\Http\Controllers\KitobchiId;

use App\Http\Controllers\Controller;
use App\Models\KitobchiId\IdClient;
use App\Services\KitobchiId\KeyStore;
use App\Services\KitobchiId\OAuthException;
use App\Services\KitobchiId\TokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

/**
 * Token, UserInfo, Revocation, Discovery va JWKS endpointlari (stateless, CSRF'siz).
 */
class TokenController extends Controller
{
    public function __construct(private TokenService $tokens, private KeyStore $keys)
    {
    }

    /** POST /token */
    public function token(Request $request): JsonResponse
    {
        try {
            $this->assertForm($request);
            $client = $this->authenticateClient($request);

            $grant = (string) $request->input('grant_type');
            $result = match ($grant) {
                'authorization_code' => $this->tokens->exchangeCode(
                    $client,
                    $this->str($request, 'code'),
                    $this->str($request, 'redirect_uri'),
                    $this->str($request, 'code_verifier'),
                ),
                'refresh_token' => $this->tokens->refresh(
                    $client,
                    $this->str($request, 'refresh_token'),
                    is_string($request->input('scope')) ? $request->input('scope') : null,
                ),
                default => throw new OAuthException('unsupported_grant_type', 'Faqat authorization_code va refresh_token'),
            };

            return $this->json($result);
        } catch (OAuthException $e) {
            return $this->oauthError($e, $request);
        }
    }

    /** POST /revoke (RFC 7009) */
    public function revoke(Request $request): JsonResponse
    {
        try {
            $this->assertForm($request);
            $client = $this->authenticateClient($request);
            $token = $this->str($request, 'token');
            $this->tokens->revoke($client, $token, is_string($request->input('token_type_hint')) ? $request->input('token_type_hint') : null);
        } catch (OAuthException $e) {
            if ($e->error === 'invalid_client' || $e->error === 'invalid_request') {
                return $this->oauthError($e, $request);
            }
        }

        // Token topilmasa ham 200 — mavjudligi oshkor qilinmaydi
        return $this->json([]);
    }

    /** GET|POST /userinfo */
    public function userinfo(Request $request): JsonResponse
    {
        $bearer = $request->bearerToken();
        if (! $bearer) {
            return $this->json(['error' => 'invalid_token'], 401, ['WWW-Authenticate' => 'Bearer realm="kitobchi-id"']);
        }

        try {
            $claims = $this->tokens->verifyAccessToken($bearer);
            $scopes = TokenService::scopes($claims['scope'] ?? '');
            if (! in_array('openid', $scopes, true)) {
                throw new OAuthException('insufficient_scope', 'openid scope kerak', 403);
            }
            $client = IdClient::where('client_id', $claims['client_id'])->first();
            if (! $client || ! $client->isActive()) {
                throw new OAuthException('invalid_token', 'Ilova faol emas', 401);
            }
            $user = $this->tokens->activeUser((int) $this->resolveUserId($claims, $client));
        } catch (OAuthException $e) {
            $status = in_array($e->status, [401, 403], true) ? $e->status : 401;
            $error = $e->error === 'insufficient_scope' ? 'insufficient_scope' : 'invalid_token';

            return $this->json(['error' => $error], $status, [
                'WWW-Authenticate' => 'Bearer realm="kitobchi-id", error="'.$error.'"',
            ]);
        }

        return $this->json(['sub' => $claims['sub']] + $this->tokens->userClaims($user, $scopes));
    }

    /** GET /.well-known/openid-configuration */
    public function discovery(): JsonResponse
    {
        $iss = TokenService::issuer();

        return response()->json([
            'issuer' => $iss,
            'authorization_endpoint' => $iss.'/authorize',
            'token_endpoint' => $iss.'/token',
            'userinfo_endpoint' => $iss.'/userinfo',
            'jwks_uri' => $iss.'/jwks',
            'revocation_endpoint' => $iss.'/revoke',
            'end_session_endpoint' => $iss.'/logout',
            'scopes_supported' => array_keys((array) config('kitobchi_id.scopes')),
            'response_types_supported' => ['code'],
            'response_modes_supported' => ['query'],
            'grant_types_supported' => ['authorization_code', 'refresh_token'],
            'subject_types_supported' => ['public', 'pairwise'],
            'id_token_signing_alg_values_supported' => ['RS256'],
            'token_endpoint_auth_methods_supported' => ['client_secret_basic', 'client_secret_post', 'none'],
            'revocation_endpoint_auth_methods_supported' => ['client_secret_basic', 'client_secret_post', 'none'],
            'code_challenge_methods_supported' => ['S256'],
            'claims_supported' => [
                'sub', 'iss', 'aud', 'exp', 'iat', 'auth_time', 'nonce', 'at_hash', 'azp', 'amr',
                'name', 'given_name', 'family_name', 'preferred_username', 'picture', 'locale', 'updated_at',
                'phone_number', 'phone_number_verified', 'email', 'email_verified',
            ],
            'prompt_values_supported' => ['none', 'login', 'consent', 'select_account'],
            'authorization_response_iss_parameter_supported' => true,
            'request_parameter_supported' => false,
            'request_uri_parameter_supported' => false,
            'claims_parameter_supported' => false,
            'ui_locales_supported' => ['uz'],
            'service_documentation' => url('/developers/api/kitobchi-id'),
        ], 200, ['Cache-Control' => 'public, max-age=3600']);
    }

    /** GET /jwks */
    public function jwks(): JsonResponse
    {
        return response()->json($this->keys->jwks(), 200, ['Cache-Control' => 'public, max-age=900']);
    }

    /* ------------------------------------------------------------------ */

    /**
     * Client autentifikatsiyasi: client_secret_basic | client_secret_post | none (public + PKCE).
     * Bir vaqtda ikki usul ishlatish taqiqlangan.
     */
    private function authenticateClient(Request $request): IdClient
    {
        $basicId = $basicSecret = null;
        $auth = (string) $request->header('Authorization', '');
        if (stripos($auth, 'Basic ') === 0) {
            $decoded = base64_decode(substr($auth, 6), true);
            if ($decoded === false || ! str_contains($decoded, ':')) {
                throw new OAuthException('invalid_client', 'Basic sarlavha noto\'g\'ri', 401);
            }
            [$basicId, $basicSecret] = explode(':', $decoded, 2);
            $basicId = urldecode($basicId);
            $basicSecret = urldecode($basicSecret);
        }

        $postId = $request->input('client_id');
        $postSecret = $request->input('client_secret');
        if ($basicId !== null && $postSecret !== null) {
            throw new OAuthException('invalid_request', 'Faqat bitta client autentifikatsiya usulidan foydalaning');
        }
        if ($postId !== null && ! is_string($postId) || $postSecret !== null && ! is_string($postSecret)) {
            throw new OAuthException('invalid_request', 'client_id/client_secret noto\'g\'ri');
        }
        if ($basicId !== null && $postId !== null && $postId !== $basicId) {
            throw new OAuthException('invalid_client', 'client_id mos emas', 401);
        }

        $clientId = $basicId ?? $postId;
        $secret = $basicId !== null ? $basicSecret : $postSecret;
        if (! $clientId) {
            throw new OAuthException('invalid_client', 'client_id kerak', 401);
        }

        $client = IdClient::where('client_id', $clientId)->first();
        if (! $client || ! $client->isActive()) {
            throw new OAuthException('invalid_client', 'Client topilmadi', 401);
        }

        if ($client->isPublic()) {
            if ($secret !== null && $secret !== '') {
                throw new OAuthException('invalid_client', 'Public client secret yubormasligi kerak', 401);
            }

            return $client;
        }

        if (! $client->verifySecret($secret)) {
            throw new OAuthException('invalid_client', 'Client autentifikatsiyadan o\'tmadi', 401);
        }

        return $client;
    }

    private function resolveUserId(array $claims, IdClient $client): int
    {
        // Pairwise `sub` dan user id'ni tiklab bo'lmaydi, shuning uchun access tokenda
        // server kaliti (APP_KEY) bilan shifrlangan `ku` claim'i bor — ilova uni o'qiy olmaydi.
        try {
            $uid = (int) Crypt::decryptString((string) ($claims['ku'] ?? ''));
        } catch (\Throwable) {
            throw new OAuthException('invalid_token', 'Token yaroqsiz', 401);
        }
        if ($client->first_party && (string) $uid !== (string) $claims['sub']) {
            throw new OAuthException('invalid_token', 'Token yaroqsiz', 401);
        }

        return $uid;
    }

    private function assertForm(Request $request): void
    {
        if (! $request->isMethod('post')) {
            throw new OAuthException('invalid_request', 'POST kerak');
        }
        $type = strtolower((string) $request->header('Content-Type'));
        if (! str_starts_with($type, 'application/x-www-form-urlencoded')) {
            throw new OAuthException('invalid_request', 'Content-Type application/x-www-form-urlencoded bo\'lishi kerak');
        }
    }

    private function str(Request $request, string $key): string
    {
        $v = $request->input($key);
        if (! is_string($v) || $v === '' || strlen($v) > 4096) {
            throw new OAuthException('invalid_request', "$key majburiy");
        }

        return $v;
    }

    private function oauthError(OAuthException $e, Request $request): JsonResponse
    {
        $headers = [];
        if ($e->error === 'invalid_client' && stripos((string) $request->header('Authorization'), 'Basic ') === 0) {
            $headers['WWW-Authenticate'] = 'Basic realm="kitobchi-id"';
        }
        $status = $e->error === 'invalid_client' ? 401 : ($e->status >= 500 ? 500 : 400);

        return $this->json($e->toArray(), $status, $headers);
    }

    private function json(array $data, int $status = 200, array $headers = []): JsonResponse
    {
        return response()->json($data === [] ? new \stdClass() : $data, $status, $headers + [
            'Cache-Control' => 'no-store',
            'Pragma' => 'no-cache',
        ]);
    }
}
