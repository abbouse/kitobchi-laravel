<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Locations extends Model
{
    protected $table = 'locations';
    use HasFactory;
    protected $fillable = [
        'user_id',
        'lat',
        'lon',
        'fullAddress',
        'country_code',
        'region_slug',
        'region_name',
        'district_name',
        'city_name',
        'phone_number',
        'isDeleted'
    ];
    protected $casts = [
        'isDeleted' => 'boolean'
    ];
}
