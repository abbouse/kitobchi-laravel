<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\ApiClient;
use App\Models\ApiClientRequestLog;
use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tashqi servis API so'rovlarini tekshiradi.
 *
 * Ishlatish:
 *   Route::middleware('api.client')->group(function () {
 *       Route::get('/external/products', ...);
 *   });
 *
 * So'rov sarlavhasi:
 *   X-App-ID:     app_xxxxxxxxxxxxxxxx
 *   X-App-Secret: xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
 */
class VerifyApiClient
{
    private const DEFAULT_LIMIT_PER_SECOND = 8;
    private const DEFAULT_LIMIT_PER_MINUTE = 240;
    private const GET_CACHE_TTL_SECONDS = 120;

    public function handle(Request $request, Closure $next, string ...$abilities): Response
    {
        $startedAt = microtime(true);
        $appId     = $request->header('X-App-ID');
        $appSecret = $request->header('X-App-Secret');

        if (!$appId || !$appSecret) {
            return response()->json([
                'status'  => 'error',
                'message' => 'API credentials missing',
            ], 401);
        }

        /** @var ApiClient|null $client */
        $client = ApiClient::where('app_id', $appId)
            ->where('is_active', true)
            ->first();

        if (!$client || !hash_equals((string) $client->app_secret, (string) $appSecret)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Invalid or inactive API credentials',
            ], 403);
        }

        $rateLimitResponse = $this->ensureRateLimit($client);
        if ($rateLimitResponse) {
            $this->logRequest($request, $client, $rateLimitResponse->getStatusCode(), $startedAt, [
                'rate_limited' => true,
            ]);
            return $rateLimitResponse;
        }

        // Abilities tekshiruvi (ixtiyoriy)
        // Route::middleware('api.client:read,write') deb belgilanadi
        if (!empty($abilities)) {
            $clientAbilities = $this->clientAbilities($client->abilities);
            foreach ($abilities as $ability) {
                if (!in_array($ability, $clientAbilities, true)) {
                    return response()->json([
                        'status'  => 'error',
                        'message' => "Missing ability: {$ability}",
                    ], 403);
                }
            }
        }

        // Keyingi qatlamga client modelini uzatamiz
        $request->attributes->set('api_client', $client);
        $request->attributes->set('api_client_started_at', $startedAt);

        if ($cachedResponse = $this->cachedGetResponse($request, $client)) {
            $this->attachRateLimitHeaders($cachedResponse, $client);
            $this->logRequest($request, $client, $cachedResponse->getStatusCode(), $startedAt, [
                'cache' => $cachedResponse->getStatusCode() === 304 ? 'not_modified' : 'hit',
            ]);

            return $cachedResponse;
        }

        $response = $next($request);
        $this->cacheGetResponse($request, $client, $response);

        $this->attachRateLimitHeaders($response, $client);
        $this->logRequest($request, $client, $response->getStatusCode(), $startedAt);

        return $response;
    }

    private function ensureRateLimit(ApiClient $client): ?Response
    {
        $perSecond = max(1, (int) ($client->rate_limit_per_second ?: self::DEFAULT_LIMIT_PER_SECOND));
        $perMinute = max(1, (int) ($client->rate_limit_per_minute ?: self::DEFAULT_LIMIT_PER_MINUTE));

        $secondKey = "api-client:{$client->id}:sec:" . now()->format('YmdHis');
        $minuteKey = "api-client:{$client->id}:min:" . now()->format('YmdHi');

        $secondCount = Cache::add($secondKey, 0, 2) ? 0 : (int) Cache::get($secondKey, 0);
        $minuteCount = Cache::add($minuteKey, 0, 61) ? 0 : (int) Cache::get($minuteKey, 0);

        if ($secondCount >= $perSecond || $minuteCount >= $perMinute) {
            $retryAfter = $secondCount >= $perSecond ? 1 : 60;

            return response()->json([
                'status' => 'error',
                'message' => 'Rate limit oshib ketdi. Keyinroq urinib ko‘ring.',
                'retry_after' => $retryAfter,
            ], 429, [
                'Retry-After' => (string) $retryAfter,
                'X-RateLimit-Limit-Second' => (string) $perSecond,
                'X-RateLimit-Limit-Minute' => (string) $perMinute,
                'X-RateLimit-Remaining-Second' => (string) max(0, $perSecond - $secondCount),
                'X-RateLimit-Remaining-Minute' => (string) max(0, $perMinute - $minuteCount),
            ]);
        }

        Cache::increment($secondKey);
        Cache::increment($minuteKey);

        return null;
    }

    private function attachRateLimitHeaders(Response $response, ApiClient $client): void
    {
        $perSecond = max(1, (int) ($client->rate_limit_per_second ?: self::DEFAULT_LIMIT_PER_SECOND));
        $perMinute = max(1, (int) ($client->rate_limit_per_minute ?: self::DEFAULT_LIMIT_PER_MINUTE));

        $secondKey = "api-client:{$client->id}:sec:" . now()->format('YmdHis');
        $minuteKey = "api-client:{$client->id}:min:" . now()->format('YmdHi');

        $secondCount = (int) Cache::get($secondKey, 0);
        $minuteCount = (int) Cache::get($minuteKey, 0);

        $response->headers->set('X-RateLimit-Limit-Second', (string) $perSecond);
        $response->headers->set('X-RateLimit-Limit-Minute', (string) $perMinute);
        $response->headers->set('X-RateLimit-Remaining-Second', (string) max(0, $perSecond - $secondCount));
        $response->headers->set('X-RateLimit-Remaining-Minute', (string) max(0, $perMinute - $minuteCount));
    }

    private function logRequest(
        Request $request,
        ApiClient $client,
        int $statusCode,
        float $startedAt,
        array $meta = []
    ): void {
        try {
            ApiClientRequestLog::create([
                'api_client_id' => $client->id,
                'method' => $request->getMethod(),
                'path' => '/' . ltrim($request->path(), '/'),
                'status_code' => $statusCode,
                'duration_ms' => max(0, (int) round((microtime(true) - $startedAt) * 1000)),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'meta' => $meta,
            ]);
        } catch (\Throwable) {
            // audit log xatosi requestni yiqitmasin
        }
    }

    private function cachedGetResponse(Request $request, ApiClient $client): ?Response
    {
        if (! $this->shouldCacheGet($request)) {
            return null;
        }

        $cached = Cache::get($this->getCacheKey($request, $client));
        if (! is_array($cached) || ! isset($cached['content'], $cached['etag'])) {
            return null;
        }

        $requestEtag = trim((string) $request->headers->get('If-None-Match'), '"');
        $cachedEtag = trim((string) $cached['etag'], '"');

        if ($requestEtag !== '' && hash_equals($cachedEtag, $requestEtag)) {
            return response('', 304, [
                'ETag' => '"'.$cachedEtag.'"',
                'X-API-Cache' => 'HIT',
                'Cache-Control' => 'private, max-age='.self::GET_CACHE_TTL_SECONDS,
            ]);
        }

        return response((string) $cached['content'], (int) ($cached['status'] ?? 200), [
            'Content-Type' => (string) ($cached['content_type'] ?? 'application/json'),
            'ETag' => '"'.$cachedEtag.'"',
            'X-API-Cache' => 'HIT',
            'Cache-Control' => 'private, max-age='.self::GET_CACHE_TTL_SECONDS,
        ]);
    }

    private function cacheGetResponse(Request $request, ApiClient $client, Response $response): void
    {
        if (! $this->shouldCacheGet($request) || $response->getStatusCode() !== 200) {
            $response->headers->set('X-API-Cache', 'BYPASS');
            return;
        }

        $content = (string) $response->getContent();
        if ($content === '') {
            $response->headers->set('X-API-Cache', 'BYPASS');
            return;
        }

        $etag = sha1($content);
        Cache::put($this->getCacheKey($request, $client), [
            'status' => $response->getStatusCode(),
            'content' => $content,
            'content_type' => $response->headers->get('Content-Type', 'application/json'),
            'etag' => $etag,
        ], self::GET_CACHE_TTL_SECONDS);

        $response->headers->set('ETag', '"'.$etag.'"');
        $response->headers->set('X-API-Cache', 'MISS');
        $response->headers->set('Cache-Control', 'private, max-age='.self::GET_CACHE_TTL_SECONDS);
    }

    private function shouldCacheGet(Request $request): bool
    {
        if (! $request->isMethod('GET')) {
            return false;
        }

        $cacheControl = strtolower((string) $request->headers->get('Cache-Control'));
        if (str_contains($cacheControl, 'no-cache') || str_contains($cacheControl, 'no-store')) {
            return false;
        }

        return true;
    }

    private function getCacheKey(Request $request, ApiClient $client): string
    {
        return 'api-client-response:'.sha1(implode('|', [
            $client->id,
            $request->method(),
            $request->fullUrl(),
            (string) $request->headers->get('X-App-Locale'),
            (string) $request->headers->get('Accept-Language'),
        ]));
    }

    private function clientAbilities(mixed $abilities): array
    {
        if (is_string($abilities)) {
            $decoded = json_decode($abilities, true);
            $abilities = is_array($decoded) ? $decoded : preg_split('/[\s,]+/', $abilities);
        }

        return collect(is_array($abilities) ? $abilities : [])
            ->map(fn ($ability) => strtolower(trim((string) $ability)))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
