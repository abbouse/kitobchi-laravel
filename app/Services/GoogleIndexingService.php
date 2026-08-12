<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleIndexingService
{
    /**
     * Google Indexing API orqali sahifa yangilanganligi haqida Googlebot'ga ping yuborish.
     * (URL_UPDATED / URL_DELETED)
     */
    public function notifyUrl(string $url, string $type = 'URL_UPDATED'): bool
    {
        try {
            $keyPath = storage_path('app/google-indexing-credentials.json');

            if (! file_exists($keyPath)) {
                Log::debug("Google Indexing API credentials file not found at {$keyPath} — skipping ping for {$url}");
                return false;
            }

            $creds = json_decode(file_get_contents($keyPath), true);
            if (empty($creds['client_email']) || empty($creds['private_key'])) {
                return false;
            }

            $token = $this->getAccessToken($creds);
            if (! $token) {
                return false;
            }

            $response = Http::withToken($token)
                ->post('https://indexing.googleapis.com/v1/urlNotifications:publish', [
                    'url' => $url,
                    'type' => $type,
                ]);

            if ($response->successful()) {
                Log::info("Google Indexing API notification sent successfully for {$url}");
                return true;
            }

            Log::warning("Google Indexing API returned status {$response->status()}", [
                'url' => $url, 'body' => $response->body(),
            ]);

            return false;
        } catch (\Throwable $e) {
            Log::error("Google Indexing API error for {$url}: {$e->getMessage()}");
            return false;
        }
    }

    private function getAccessToken(array $creds): ?string
    {
        $header = base64_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $now = time();
        $claim = base64_encode(json_encode([
            'iss' => $creds['client_email'],
            'scope' => 'https://www.googleapis.com/auth/indexing',
            'aud' => 'https://oauth2.googleapis.com/token',
            'exp' => $now + 3600,
            'iat' => $now,
        ]));

        $signatureInput = "{$header}.{$claim}";
        openssl_sign($signatureInput, $signature, $creds['private_key'], 'SHA256');
        $jwt = "{$signatureInput}." . base64_encode($signature);

        $res = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]);

        return $res->json('access_token');
    }
}
