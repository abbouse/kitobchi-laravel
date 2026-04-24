<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use RuntimeException;

class PasswordResetService
{
    public function refreshDailyLimit(Model $user): void
    {
        $resetAt = $user->password_reset_limit_reset_at
            ? Carbon::parse($user->password_reset_limit_reset_at)
            : null;

        if (!$resetAt || !$resetAt->isToday()) {
            $user->forceFill([
                'password_reset_limit' => 3,
                'password_reset_limit_reset_at' => now(),
            ])->save();
        }
    }

    public function remainingAttempts(Model $user): int
    {
        $this->refreshDailyLimit($user);

        return max(0, (int) ($user->fresh()->password_reset_limit ?? 0));
    }

    public function ensureHasAttempts(Model $user): void
    {
        if ($this->remainingAttempts($user) <= 0) {
            throw new RuntimeException('Bugun parolni tiklash limiti tugagan. Ertaga qayta urinib ko‘ring.');
        }
    }

    public function generatePassword(int $length = 10): string
    {
        return Str::upper(Str::random($length));
    }

    public function applyNewPassword(Model $user, string $password): int
    {
        $remaining = $this->remainingAttempts($user);

        if ($remaining <= 0) {
            throw new RuntimeException('Bugun parolni tiklash limiti tugagan. Ertaga qayta urinib ko‘ring.');
        }

        $user->forceFill([
            'password' => $password,
            'password_reset_limit' => $remaining - 1,
            'password_reset_limit_reset_at' => $user->password_reset_limit_reset_at ?? now(),
        ])->save();

        return $remaining - 1;
    }
}
