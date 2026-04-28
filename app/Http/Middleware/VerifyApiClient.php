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
            $clientAbilities = $client->abilities ?? [];
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

        $response = $next($request);

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
}
