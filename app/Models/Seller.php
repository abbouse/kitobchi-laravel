<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Seller extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'parent_id',
        'staff_status',
        'role',
        'lastname',
        'firstname',
        'phone_number',
        'shop_name',
        'balance',
        'total_withdrawal',
        'payment_card',
        'region',
        'activity_types',
        'photo',
        'password',
        'password_reste_limit',
        'status',
        'is_hidden',
        'fcm_token',
        'isVerified',
        'isSupport',
        'rating',
        'successful_orders',
        'total_reviews',
        'response_time_hours'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'activity_types' => 'array',
        'isVerified' => 'boolean',
        'isSupport' => 'boolean',
        'password' => 'hashed',
    ];
    public function books(): HasMany
    {
        return $this->hasMany(Books::class, 'seller_id');
    }
    public function stationeries(): HasMany
    {
        return $this->hasMany(Stationery::class, 'seller_id');
    }
    public function orders()
    {
        return $this->hasMany(SellerOrder::class, 'seller_id');
    }
    public function location()
    {
        return $this->hasOne(SellerLocation::class, 'seller_id')->where('is_main', true);
    }
    public function devices()
{
    return $this->hasMany(ConnectedDevice::class, 'user_id', 'id')->where('user_type', 'seller');
}
}