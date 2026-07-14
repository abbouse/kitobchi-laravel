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

    public function tokensForAudienceByLocale(string $userType): array
    {
        $query = ConnectedDevice::query()
            ->where('connected_devices.user_type', $userType)
            ->whereNotNull('connected_devices.fcm_token')
            ->where('connected_devices.fcm_token', '!=', '')
            ->orderByDesc('connected_devices.updated_at')
            ->orderByDesc('connected_devices.id');

        if ($userType === 'user') {
            $rows = $query
                ->leftJoin('users', 'users.id', '=', 'connected_devices.user_id')
                ->get(['connected_devices.fcm_token', 'users.locale']);
        } elseif ($userType === 'seller') {
            $rows = $query
                ->leftJoin('sellers', 'sellers.id', '=', 'connected_devices.user_id')
                ->get(['connected_devices.fcm_token', 'sellers.tg_lang as locale']);
        } else {
            $rows = $query->get(['connected_devices.fcm_token']);
        }

        $grouped = [];
        $seenTokens = [];

        foreach ($rows as $row) {
            $token = trim((string) $row->fcm_token);
            if ($token === '' || isset($seenTokens[$token])) {
                continue;
            }

            $seenTokens[$token] = true;
            $locale = $this->normalizeLocale($row->locale ?? null);
            $grouped[$locale] ??= [];
            $grouped[$locale][$token] = $token;
        }

        return collect($grouped)
            ->map(fn (array $tokens) => array_values($tokens))
            ->filter(fn (array $tokens) => $tokens !== [])
            ->all();
    }

    public function normalizeLocale(?string $locale): string
    {
        $locale = strtolower(trim((string) $locale));

        return in_array($locale, ['uz', 'ru', 'en', 'ja'], true) ? $locale : 'uz';
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
