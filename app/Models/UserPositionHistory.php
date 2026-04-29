<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserPositionHistory extends Model
{
    protected $fillable = [
        'user_id',
        'from_position',
        'to_position',
        'metrics_snapshot',
        'reason',
        'awarded_at',
    ];

    protected $casts = [
        'metrics_snapshot' => 'array',
        'awarded_at' => 'datetime',
    ];
}
