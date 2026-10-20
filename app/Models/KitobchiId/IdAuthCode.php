<?php

namespace App\Models\KitobchiId;

use Illuminate\Database\Eloquent\Model;

class IdAuthCode extends Model
{
    protected $table = 'id_auth_codes';

    protected $guarded = ['id'];

    protected $casts = ['expires_at' => 'datetime', 'used_at' => 'datetime'];
}
