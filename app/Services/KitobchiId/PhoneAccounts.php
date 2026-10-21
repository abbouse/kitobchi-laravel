<?php

namespace App\Services\KitobchiId;

use App\Models\KitobchiId\IdClient;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Tasdiqlangan telefon raqam bo'yicha Kitobchi akkauntini topadi yoki yaratadi.
 * Barcha ilovalar YAGONA `users` jadvalidan foydalanadi.
 */
class PhoneAccounts
{
    public function resolve(string $phone, IdClient $client): User
    {
        $user = User::where('phone_number', $phone)->where('isDeleted', 'no')->first();
        if (! $user) {
            $user = User::create([
                'phone_number' => $phone,
                'password' => bcrypt(Str::random(40)),
                'phone_verified_at' => now(),
            ]);
            Log::info('kitobchi-id: user registered', ['user' => $user->id, 'client' => $client->client_id]);
        } elseif ($user->phone_verified_at === null) {
            $user->forceFill(['phone_verified_at' => now()])->save();
        }

        return $user;
    }
}
