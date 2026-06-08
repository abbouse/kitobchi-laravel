<?php

namespace App\Services;

use App\Models\UserCard;
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

    public function getSingleCard(string $cardId): array
    {
        return $this->get('/merchant/userCard/getCard/' . trim($cardId) . '/');
    }

    public function syncUserCard(UserCard $card): array
    {
        if (blank($card->provider_card_id)) {
            throw new RuntimeException('card_not_found');
        }

        $response = $this->getSingleCard((string) $card->provider_card_id);
        $payload = $response['result']['card'] ?? [];
        $meta = $card->provider_meta ?? [];
        $meta['single_card'] = $response;
        $meta['single_card_fetched_at'] = now()->toIso8601String();

        $card->fill([
            'card_name' => $payload['cardName'] ?? ($card->card_name ?: null),
            'card_number' => $payload['number'] ?? ($card->card_number ?: null),
            'expire_date' => $this->normalizeExpireToDisplay(
                (string) ($payload['expireDate'] ?? $card->expire_date ?? '')
            ),
            'vendor' => $payload['vendor'] ?? ($card->vendor ?: null),
            'processing' => $payload['processing'] ?? ($card->processing ?: null),
            'provider_meta' => $meta,
        ]);
        $card->save();

        return $response;
    }

    public function cardPaymentState(UserCard $card, bool $refresh = true): array
    {
        if ($refresh) {
            $this->syncUserCard($card);
            $card->refresh();
        }

        $singleCard = $card->provider_meta['single_card']['result']['card'] ?? [];
        $status = $singleCard['status']['result'] ?? [];
        $statusMessage = (string) ($status['status_message'] ?? '');
        $isActive = (bool) ($status['is_active'] ?? true);
        $smsInfo = array_key_exists('sms_info', $status) ? (bool) $status['sms_info'] : null;
        $isExpired = $this->isDisplayExpireExpired((string) ($card->expire_date ?? ''));
        $remoteUserId = isset($singleCard['userId']) ? (string) $singleCard['userId'] : null;

        $errorCode = null;
        if ($isExpired) {
            $errorCode = 'card_expired';
        } elseif (!$isActive) {
            $errorCode = 'card_not_active';
        } elseif ($remoteUserId !== null && $remoteUserId !== '' && (string) $card->user_id !== $remoteUserId) {
            $errorCode = 'card_not_match';
        }

        return [
            'is_active' => $isActive,
            'is_expired' => $isExpired,
            'sms_info' => $smsInfo,
            'status_message' => $statusMessage,
            'remote_user_id' => $remoteUserId,
            'error_code' => $errorCode,
            'can_pay' => $errorCode === null,
        ];
    }

    public function ensureCardReadyForPayment(UserCard $card): void
    {
        $state = $this->cardPaymentState($card, true);
        if (!$state['can_pay']) {
            throw new RuntimeException((string) $state['error_code']);
        }
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

    public function cancelPayment(string $transactionId): array
    {
        return $this->post('/merchant/payment/cancel/', [
            'transactionId' => $transactionId,
        ]);
    }

    public function p2pReceiver(string $cardNumberOrRef): array
    {
        return $this->post('/merchant/p2p/receiver/', [
            'cardNumber' => $cardNumberOrRef,
        ]);
    }

    public function p2pTransferCreate(
        string $receiverCardNumberOrRef,
        int $amount,
        ?string $senderCardId = null,
        ?string $serviceId = null,
    ): array {
        $payload = [
            'cardNumber' => $receiverCardNumberOrRef,
            'amount' => $amount,
        ];

        if ($senderCardId) {
            $payload['cardId'] = $senderCardId;
        }

        if ($serviceId) {
            $payload['serviceId'] = $serviceId;
        }

        return $this->post('/merchant/p2p/transfer/create/', $payload);
    }

    public function p2pTransferConfirm(string $transactionId, string $senderCardId): array
    {
        return $this->post('/merchant/p2p/transfer/confirm/', [
            'transactionId' => $transactionId,
            'cardId' => $senderCardId,
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

    private function normalizeExpireToDisplay(string $expireDate): string
    {
        $digits = preg_replace('/\D+/', '', $expireDate) ?? '';
        if (strlen($digits) !== 4) {
            return $expireDate;
        }

        return substr($digits, 2, 2) . substr($digits, 0, 2);
    }

    private function isDisplayExpireExpired(string $expireDate): bool
    {
        $digits = preg_replace('/\D+/', '', $expireDate) ?? '';
        if (strlen($digits) !== 4) {
            return false;
        }

        $month = (int) substr($digits, 0, 2);
        $year = (int) substr($digits, 2, 2);
        if ($month < 1 || $month > 12) {
            return false;
        }

        $fullYear = 2000 + $year;
        $expiryBoundary = now()->copy()->startOfMonth();
        $cardExpiryMonth = now()->setDate($fullYear, $month, 1)->addMonth();

        return $cardExpiryMonth->lte($expiryBoundary);
    }
}
