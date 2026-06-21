<?php

namespace App\Services;

use App\Models\ConnectedDevice;

class FcmRecipientService
{
    public function claimToken(string $userType, int $userId, ?string $fcmToken): void
    {
        $fcmToken = trim((string) $fcmToken);
        if ($fcmToken === '') {
            return;
        }

        ConnectedDevice::query()
            ->where('user_type', $userType)
            ->where('fcm_token', $fcmToken)
            ->where('user_id', '!=', $userId)
            ->update(['fcm_token' => null]);
    }

    public function tokensFor(string $userType, int $userId): array
    {
        $tokens = ConnectedDevice::query()
            ->where('user_type', $userType)
            ->where('user_id', $userId)
            ->whereNotNull('fcm_token')
            ->where('fcm_token', '!=', '')
            ->pluck('fcm_token')
            ->map(fn ($token) => trim((string) $token))
            ->filter()
            ->unique()
            ->values();

        return $tokens
            ->filter(fn (string $token) => $this->latestOwnerId($userType, $token) === $userId)
            ->values()
            ->all();
    }

    public function tokensForAudience(string $userType): array
    {
        return ConnectedDevice::query()
            ->where('user_type', $userType)
            ->whereNotNull('fcm_token')
            ->where('fcm_token', '!=', '')
            ->pluck('fcm_token')
            ->map(fn ($token) => trim((string) $token))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function latestOwnerId(string $userType, string $token): ?int
    {
        $ownerId = ConnectedDevice::query()
            ->where('user_type', $userType)
            ->where('fcm_token', $token)
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->value('user_id');

        return $ownerId === null ? null : (int) $ownerId;
    }
}
