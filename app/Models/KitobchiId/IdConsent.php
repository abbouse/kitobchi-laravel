<?php

namespace App\Models\KitobchiId;

use Illuminate\Database\Eloquent\Model;

class IdConsent extends Model
{
    protected $table = 'id_consents';

    protected $guarded = ['id'];

    protected $casts = ['scopes' => 'array'];
}
