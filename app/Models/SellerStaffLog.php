<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class SellerStaffLog extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'text',
        'seller_staff_id',
    ];
}