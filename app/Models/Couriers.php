<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
        'password',
        'fcm_token',
        'balance',
        'total_withdrawal',
        'payment_card',
        'status',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'balance' => 'integer',
    ];

    // ── Password ───────────────────────────────────────────────────
    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = bcrypt($value);
    }

    // ── Relationships ──────────────────────────────────────────────

    public function orders()
    {
        return $this->hasMany(CourierOrder::class, 'courier_id');
    }

    public function transactions()
    {
        return $this->hasMany(CourierTransaction::class, 'courier_id');
    }

    public function banLogs()
    {
        return $this->hasMany(CourierBanLog::class, 'courier_id')
            ->orderByDesc('created_at');
    }

    public function notifications()
    {
        return $this->hasMany(CourierNotification::class, 'courier_id')
            ->orderByDesc('created_at');
    }

    public function devices()
    {
        return $this->hasMany(ConnectedDevice::class, 'user_id', 'id')
            ->where('user_type', 'courier');
    }

    // ── Helpers ────────────────────────────────────────────────────

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    public function getIsActiveAttribute(): bool
    {
        return (int) $this->status === 1;
    }
}