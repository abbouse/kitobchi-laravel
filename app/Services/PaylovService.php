<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class PaylovService
{
    public function __construct(
        private readonly string $baseUrl = '',
        private readonly string $accessToken = '',
        private readonly string $consumerKey = '',
        private readonly string $consumerSecret = '',
        private readonly string $username = '',
        private readonly string $password = '',
        private readonly ?string $merchantId = null,
    ) {
    }

    public static function make(): self
    {
        return new self(
            (string) config('services.paylov.base_url'),
            (string) config('services.paylov.access_token'),
            (string) config('services.paylov.consumer_key'),
            (string) config('services.paylov.consumer_secret'),
            (string) config('services.paylov.username'),
            (string) config('services.paylov.password'),
            config('services.paylov.merchant_id'),
        );
    }

    public function merchantId(): ?string
    {
        return $this->merchantId;
    }

    public function createUserCard(
        string $userId,
        string $cardNumber,
        string $expireDate,
        ?string $phoneNumber = null,
    ): array {
        $payload = [
            'userId' => $userId,
            'cardNumber' => $cardNumber,
            'expireDate' => $expireDate,
        ];

        if ($phoneNumber) {
            $payload['phoneNumber'] = $phoneNumber;
        }

        return $this->post('/merchant/userCard/createUserCard/', $payload);
    }

    public function confirmUserCard(
        string $cardId,
        string $otp,
        ?string $cardName = null,
    ): array {
        $payload = [
            'cardId' => $cardId,
            'otp' => $otp,
        ];

        if ($cardName) {
            $payload['cardName'] = $cardName;
        }

        return $this->post('/merchant/userCard/confirmUserCardCreate/', $payload);
    }

    public function deleteUserCard(string $userCardId): array
    {
        return $this->delete('/merchant/userCard/deleteUserCard/', [
            'userCardId' => $userCardId,
        ]);
    }

    public function createReceipt(string $userId, int $amount, array $account = []): array
    {
        return $this->post('/merchant/receipts/create/', [
            'userId' => $userId,
            'amount' => $amount,
            'account' => (object) $account,
        ]);
    }

    public function payReceipt(string $transactionId, string $cardId, string $userId): array
    {
        return $this->post('/merchant/receipts/pay/', [
            'transactionId' => $transactionId,
            'cardId' => $cardId,
            'userId' => $userId,
        ]);
    }

    public function getTransactions(string $transactionId): array
    {
        return $this->get('/merchant/getTransactions/', [
            'transactionId' => $transactionId,
        ]);
    }

    private function post(string $path, array $payload): array
    {
        return $this->send('post', $path, $payload);
    }

    private function get(string $path, array $query = []): array
    {
        return $this->send('get', $path, [], $query);
    }

    private function delete(string $path, array $query = []): array
    {
        return $this->send('delete', $path, [], $query);
    }

    private function send(string $method, string $path, array $payload = [], array $query = []): array
    {
        if (blank($this->baseUrl)) {
            throw new RuntimeException('PAYLOV_BASE_URL sozlanmagan.');
        }

        $accessToken = $this->resolveAccessToken();
        $url = rtrim($this->baseUrl, '/') . '/' . ltrim($path, '/');
        $maskedAuthorization = 'Bearer ' . $this->maskToken($accessToken);

        $request = Http::withHeaders([
                'Authorization' => 'Bearer ' . trim($accessToken),
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])
            ->acceptJson()
            ->asJson()
            ->timeout(20);

        /** @var Response $response */
        $response = match ($method) {
            'get' => $request->get($url, $query),
            'delete' => $request->withQueryParameters($query)->delete($url),
            default => $request->post($url, $payload),
        };

        $body = $response->json();
        if (!is_array($body)) {
            Log::warning('[Paylov] Non-JSON response', [
                'method' => $method,
                'path' => $path,
                'status' => $response->status(),
                'body' => mb_substr($response->body(), 0, 1000),
            ]);

            throw new RuntimeException('Paylov noto‘g‘ri javob qaytardi.');
        }

        if (!$response->successful() || !empty($body['error'])) {
            $message = $body['error']['message'] ?? $body['message'] ?? 'Paylov xatoligi';
            $code = $body['error']['code'] ?? (string) $response->status();

            Log::warning('[Paylov] Request failed', [
                'method' => $method,
                'url' => $url,
                'path' => $path,
                'status' => $response->status(),
                'error_code' => $code,
                'message' => $message,
                'authorization_sent' => $maskedAuthorization,
                'token_length' => mb_strlen(trim($accessToken)),
                'response_body' => $body,
                'response_headers' => $response->headers(),
            ]);

            throw new RuntimeException("{$message} ({$code})");
        }

        return $body;
    }

    private function resolveAccessToken(): string
    {
        if (filled($this->consumerKey) && filled($this->consumerSecret) && filled($this->username) && filled($this->password)) {
            return $this->oauthAccessToken();
        }

        if (blank($this->accessToken)) {
            throw new RuntimeException('Paylov access token yoki OAuth credentiallari sozlanmagan.');
        }

        return trim($this->accessToken);
    }

    private function oauthAccessToken(): string
    {
        $cacheKey = 'paylov.oauth.token.' . md5($this->baseUrl . '|' . $this->username . '|' . $this->consumerKey);

        /** @var string|null $cached */
        $cached = Cache::get($cacheKey);
        if (filled($cached)) {
            return $cached;
        }

        $url = rtrim($this->baseUrl, '/') . '/merchant/oauth2/token/';

        $response = Http::asForm()
            ->withBasicAuth(trim($this->consumerKey), trim($this->consumerSecret))
            ->acceptJson()
            ->timeout(20)
            ->post($url, [
                'grant_type' => 'password',
                'username' => trim($this->username),
                'password' => trim($this->password),
            ]);

        $body = $response->json();
        if (!is_array($body)) {
            Log::warning('[Paylov OAuth] Non-JSON response', [
                'url' => $url,
                'status' => $response->status(),
                'body' => mb_substr($response->body(), 0, 1000),
            ]);

            throw new RuntimeException('Paylov OAuth noto‘g‘ri javob qaytardi.');
        }

        if (!$response->successful()) {
            Log::warning('[Paylov OAuth] Token request failed', [
                'url' => $url,
                'status' => $response->status(),
                'response_body' => $body,
                'response_headers' => $response->headers(),
                'username' => $this->username,
                'consumer_key' => $this->maskToken($this->consumerKey),
            ]);

            $message = $body['error_description']
                ?? $body['message']
                ?? $body['detail']
                ?? $body['error']
                ?? 'Paylov OAuth xatoligi';

            throw new RuntimeException((string) $message);
        }

        $token = (string) ($body['access_token'] ?? $body['token'] ?? '');
        if ($token === '') {
            Log::warning('[Paylov OAuth] Access token missing', [
                'url' => $url,
                'response_body' => $body,
            ]);

            throw new RuntimeException('Paylov OAuth access token qaytarmadi.');
        }

        $ttlSeconds = max(60, ((int) ($body['expires_in'] ?? 3600)) - 60);
        Cache::put($cacheKey, $token, now()->addSeconds($ttlSeconds));

        if (filled($body['refresh_token'] ?? null)) {
            Cache::put($cacheKey . '.refresh', (string) $body['refresh_token'], now()->addDay());
        }

        return $token;
    }

    private function maskToken(string $token): string
    {
        $trimmed = trim($token);
        if ($trimmed === '') {
            return '[empty]';
        }

        if (mb_strlen($trimmed) <= 10) {
            return str_repeat('*', mb_strlen($trimmed));
        }

        return mb_substr($trimmed, 0, 6)
            . str_repeat('*', max(0, mb_strlen($trimmed) - 10))
            . mb_substr($trimmed, -4);
    }
}
