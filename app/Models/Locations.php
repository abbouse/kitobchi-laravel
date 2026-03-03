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
        'phone_number',
        'isDeleted'
    ];
    protected $casts = [
        'isDeleted' => 'boolean'
    ];
}
