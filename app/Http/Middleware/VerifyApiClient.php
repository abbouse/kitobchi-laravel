<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\ApiClient;
use Closure;
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
    public function handle(Request $request, Closure $next, string ...$abilities): Response
    {
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
            ->where('app_secret', $appSecret)
            ->where('is_active', true)
            ->first();

        if (!$client) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Invalid or inactive API credentials',
            ], 403);
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

        return $next($request);
    }
}