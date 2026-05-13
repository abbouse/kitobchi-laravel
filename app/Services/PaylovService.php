<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class PaylovService
{
    public function __construct(
        private readonly string $baseUrl = '',
        private readonly string $accessToken = '',
        private readonly ?string $merchantId = null,
    ) {
    }

    public static function make(): self
    {
        return new self(
            (string) config('services.paylov.base_url'),
            (string) config('services.paylov.access_token'),
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
        if (blank($this->accessToken)) {
            throw new RuntimeException('PAYLOV_ACCESS_TOKEN sozlanmagan.');
        }
        if (blank($this->baseUrl)) {
            throw new RuntimeException('PAYLOV_BASE_URL sozlanmagan.');
        }

        $url = rtrim($this->baseUrl, '/') . '/' . ltrim($path, '/');

        $request = Http::acceptJson()
            ->asJson()
            ->withToken($this->accessToken)
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
                'path' => $path,
                'status' => $response->status(),
                'error_code' => $code,
                'message' => $message,
            ]);

            throw new RuntimeException("{$message} ({$code})");
        }

        return $body;
    }
}
