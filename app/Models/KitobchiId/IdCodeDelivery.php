<?php

namespace App\Models\KitobchiId;

use Illuminate\Database\Eloquent\Model;

class IdCodeDelivery extends Model
{
    protected $table = 'id_code_deliveries';

    protected $guarded = ['id'];

    protected $hidden = ['code_enc'];

    protected $casts = ['expires_at' => 'datetime', 'used_at' => 'datetime'];
}
