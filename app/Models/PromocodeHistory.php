<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class PromocodeHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'promocode_id',
    ];

}
