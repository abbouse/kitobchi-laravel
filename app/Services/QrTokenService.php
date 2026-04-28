<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class QrTokenService
{
    private const PICKUP_PREFIX = 'KCP1';
    private const DELIVERY_PREFIX = 'KCD1';

    public function makePickupToken(int $sellerId, int $orderId, int $courierId, ?int $ttlSeconds = null): string
    {
        return $this->makeToken(self::PICKUP_PREFIX, [
            'seller_id' => $sellerId,
            'order_id' => $orderId,
            'courier_id' => $courierId,
        ], $ttlSeconds ?? 60 * 60 * 12);
    }

    public function makeDeliveryToken(int $soldId, int $userId, ?int $courierId, ?int $ttlSeconds = null): string
    {
        return $this->makeToken(self::DELIVERY_PREFIX, [
            'sold_id' => $soldId,
            'user_id' => $userId,
            'courier_id' => $courierId,
        ], $ttlSeconds ?? 60 * 60 * 24 * 14);
    }

    public function parsePickupToken(?string $token): ?array
    {
        $payload = $this->parseToken($token, self::PICKUP_PREFIX);
        if (!$payload) {
            return null;
        }

        $sellerId = $this->toInt(Arr::get($payload, 'seller_id'));
        $orderId = $this->toInt(Arr::get($payload, 'order_id'));
        $courierId = $this->toInt(Arr::get($payload, 'courier_id'));

        if (!$sellerId || !$orderId || !$courierId) {
            return null;
        }

        return [
            'seller_id' => $sellerId,
            'order_id' => $orderId,
            'courier_id' => $courierId,
        ];
    }

    public function parseDeliveryToken(?string $token): ?array
    {
        $payload = $this->parseToken($token, self::DELIVERY_PREFIX);
        if (!$payload) {
            return null;
        }

        $soldId = $this->toInt(Arr::get($payload, 'sold_id'));
        $userId = $this->toInt(Arr::get($payload, 'user_id'));
        $courierId = $this->toInt(Arr::get($payload, 'courier_id'));

        if (!$soldId || !$userId) {
            return null;
        }

        return [
            'sold_id' => $soldId,
            'user_id' => $userId,
            'courier_id' => $courierId,
        ];
    }

    private function makeToken(string $prefix, array $claims, int $ttlSeconds): string
    {
        $payload = array_merge($claims, [
            'iat' => now()->timestamp,
            'exp' => now()->addSeconds($ttlSeconds)->timestamp,
            'jti' => Str::random(10),
        ]);

        $encodedPayload = $this->base64UrlEncode(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $signature = $this->base64UrlEncode(hash_hmac('sha256', $prefix . '.' . $encodedPayload, $this->signingKey(), true));

        return $prefix . '.' . $encodedPayload . '.' . $signature;
    }

    private function parseToken(?string $token, string $expectedPrefix): ?array
    {
        if (!is_string($token) || $token === '') {
            return null;
        }

        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        [$prefix, $encodedPayload, $encodedSignature] = $parts;
        if ($prefix !== $expectedPrefix) {
            return null;
        }

        $expectedSignature = hash_hmac('sha256', $prefix . '.' . $encodedPayload, $this->signingKey(), true);
        $providedSignature = $this->base64UrlDecode($encodedSignature);

        if ($providedSignature === null || !hash_equals($expectedSignature, $providedSignature)) {
            return null;
        }

        $json = $this->base64UrlDecode($encodedPayload);
        if ($json === null) {
            return null;
        }

        $payload = json_decode($json, true);
        if (!is_array($payload)) {
            return null;
        }

        $exp = $this->toInt(Arr::get($payload, 'exp'));
        if (!$exp || $exp < now()->timestamp) {
            return null;
        }

        return $payload;
    }

    private function signingKey(): string
    {
        $appKey = (string) config('app.key', '');
        if (str_starts_with($appKey, 'base64:')) {
            $decoded = base64_decode(substr($appKey, 7), true);
            if ($decoded !== false) {
                return $decoded;
            }
        }

        return $appKey;
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $value): ?string
    {
        $padding = strlen($value) % 4;
        if ($padding > 0) {
            $value .= str_repeat('=', 4 - $padding);
        }

        $decoded = base64_decode(strtr($value, '-_', '+/'), true);
        return $decoded === false ? null : $decoded;
    }

    private function toInt(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        return null;
    }
}
