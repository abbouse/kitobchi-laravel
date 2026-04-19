<?php

namespace App\Services\Kangaroo;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Kangaroo: kitobchi listing va UGC tahlili (HTTP, qayta urinish).
 */
class KangarooKitobchiModerationClient
{
    public function moderateListings(array $books, array $stationeries): array
    {
        return $this->post('/api/kitobchi/moderate/listings', [
            'books' => $books,
            'stationeries' => $stationeries,
        ]);
    }

    public function moderateUgc(array $posts, array $comments): array
    {
        return $this->post('/api/kitobchi/moderate/ugc', [
            'book_club_posts' => $posts,
            'book_club_comments' => $comments,
        ]);
    }

    private function post(string $path, array $body): array
    {
        $base = rtrim((string) config('services.kangaroo.url'), '/');
        $key = (string) config('services.kangaroo.key');
        if ($base === '' || $key === '') {
            throw new RuntimeException('Kangaroo URL yoki kalit sozlanmagan (services.kangaroo).');
        }

        $url = $base.$path;
        $max = max(1, (int) config('services.kangaroo.http_retries', 3));
        $delayMs = max(50, (int) config('services.kangaroo.http_retry_delay_ms', 250));
        $timeout = max(10, (int) config('services.kangaroo.timeout', 120));

        $lastException = null;

        for ($attempt = 1; $attempt <= $max; $attempt++) {
            try {
                $response = Http::withHeaders([
                    'X-Kangaroo-Key' => $key,
                    'Accept' => 'application/json',
                ])
                    ->timeout($timeout)
                    ->post($url, $body);
            } catch (ConnectionException $e) {
                $lastException = new RuntimeException('Kangaroo ulanmadi: '.$e->getMessage(), 0, $e);
                Log::warning('Kangaroo moderatsiya ulanish xato', [
                    'path' => $path,
                    'attempt' => $attempt,
                    'message' => $e->getMessage(),
                ]);
                if ($attempt === $max) {
                    throw $lastException;
                }
                usleep($delayMs * 1000 * $attempt);

                continue;
            }

            if ($response->successful()) {
                return $response->json() ?? [];
            }

            $status = $response->status();
            Log::warning('Kangaroo moderatsiya HTTP xato', [
                'path' => $path,
                'status' => $status,
                'attempt' => $attempt,
                'body' => mb_substr($response->body(), 0, 500),
            ]);

            if ($attempt === $max || ! $this->isRetryableHttpStatus($status)) {
                throw new RuntimeException('Kangaroo HTTP '.$status);
            }

            usleep($delayMs * 1000 * $attempt);
        }

        throw $lastException ?? new RuntimeException('Kangaroo: javob olinmadi');
    }

    private function isRetryableHttpStatus(int $status): bool
    {
        return in_array($status, [408, 425, 429, 500, 502, 503, 504], true);
    }
}
