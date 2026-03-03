<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class SellerLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'seller_id',
        'lat',
        'lon',
        'fullAddress',
        'description',
        'is_main',
        'is_deleted'
    ];
    
    protected $casts = [
        'is_main' => 'boolean',
        'is_deleted' => 'boolean',
    ];

    public function workdays()
    {
        return $this->hasMany(SellerLocationWorkday::class, 'location_id', 'id');
    }
public function getIsOpenNowAttribute()
{
    $now = Carbon::now();
    $dayOfWeek = strtolower($now->format('l'));
    $workday = $this->workdays()
        ->where('day_of_week', $dayOfWeek)
        ->first();
    if (!$workday) return false;
    $open = Carbon::parse($workday->open_time);
    $close = Carbon::parse($workday->close_time);
    return $now->between($open, $close);
}
}
