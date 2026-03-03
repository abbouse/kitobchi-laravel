<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SellerLocationWorkday extends Model
{
    use HasFactory;

    protected $fillable = [
        'location_id',
        'day_of_week',
        'open_time',
        'close_time',
    ];
    protected $casts = [
        'open_time' => 'datetime:H:i',
        'close_time' => 'datetime:H:i',
    ];
}
