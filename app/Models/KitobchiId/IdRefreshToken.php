<?php

namespace App\Models\KitobchiId;

use Illuminate\Database\Eloquent\Model;

class IdRefreshToken extends Model
{
    protected $table = 'id_refresh_tokens';

    protected $guarded = ['id'];

    protected $casts = [
        'expires_at' => 'datetime',
        'family_expires_at' => 'datetime',
        'used_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];
}
