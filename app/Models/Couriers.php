<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Couriers extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'couriers';
    protected $fillable = [
        'first_name',
        'last_name',
        'region',
        'photo',
        'phone_number',
        'fcm_token',
        'balance',
        'status',
        'fcm_token',
        'created_at'
    ];
        protected $hidden = [
        'password'
    ];

    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = bcrypt($value);
    }
    public function devices()
{
    return $this->hasMany(ConnectedDevice::class, 'user_id', 'id')->where('user_type', 'courier');
}
}
