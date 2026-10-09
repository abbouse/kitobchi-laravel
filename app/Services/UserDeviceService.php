<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Mijozning "Ulangan qurilmalar" ro'yxati (connected_devices).
 *
 * Har bir qator = bitta faol sessiya (Sanctum token). Token o'chirilgan
 * (chiqib ketilgan, muddati o'tgan, boshqa login bilan almashtirilgan)
 * qatorlar ro'yxatda "o'lik" bo'lib qolmasligi uchun shu yerda tozalanadi.
 */
class UserDeviceService
{
    /** Tokeni endi mavjud bo'lmagan qatorlarni o'chiradi. */
    public function prune(int $userId): int
    {
        return DB::table('connected_devices')
            ->where('user_id', $userId)
            ->where('user_type', 'user')
            ->where(function ($q) use ($userId) {
                $q->whereNull('token')
                    ->orWhereNotIn('token', DB::table('personal_access_tokens')
                        ->where('tokenable_id', $userId)
                        ->where('tokenable_type', (new \App\Models\User)->getMorphClass())
                        ->select('token'));
            })
            ->delete();
    }

    /** Joriy sessiyaga tegishli qatorni o'chiradi (logout). */
    public function forgetToken(int $userId, ?string $hashedToken): void
    {
        if (! $hashedToken) {
            return;
        }
        DB::table('connected_devices')
            ->where('user_id', $userId)
            ->where('user_type', 'user')
            ->where('token', $hashedToken)
            ->delete();
    }
}
