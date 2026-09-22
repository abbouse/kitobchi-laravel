<?php

namespace App\Models;

use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

/**
 * TEZLIK: Sanctum har bir API so'rovida `last_used_at` ni yozadi — har so'rovga
 * bitta UPDATE. Qiymat 5 daqiqadan yangi bo'lsa yozuv o'tkazib yuboriladi
 * (tokenning o'zi, muddati va huquqlari hech o'zgarmaydi).
 */
class PersonalAccessToken extends SanctumPersonalAccessToken
{
    protected $table = 'personal_access_tokens';

    public function save(array $options = []): bool
    {
        $dirty = array_keys($this->getDirty());
        $previous = $this->getOriginal('last_used_at');

        if ($this->exists && $dirty === ['last_used_at'] && $previous && now()->diffInSeconds($previous, true) < 300) {
            $this->syncOriginalAttribute('last_used_at');

            return true;
        }

        return parent::save($options);
    }
}
