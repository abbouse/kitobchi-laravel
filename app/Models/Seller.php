<?php
/**
 * Seller model ga quyidagi accessor qo'shing:
 * app/Models/Seller.php ichida, casts dan keyin
 */

// ── Qo'shiladigan accessor ────────────────────────────────────────
//
//    public function getActivityTypesAttribute($value): array
//    {
//        if (is_array($value))  return $value;
//        if (empty($value))     return [];
//        $decoded = json_decode($value, true);
//        if (is_array($decoded)) return $decoded;
//        // CSV format: "Kitob,Kanstovar"
//        return array_filter(array_map('trim', explode(',', $value)));
//    }
//
// ── Shuningdek casts dan 'activity_types' => 'array' ni OLIB TASHLANG ──
// Chunki accessor va cast birga ishlaganda conflict bo'ladi

// ═══════════════════════════════════════════════════════════════════
// To'liq Seller model (accessor + casts tuzatilgan):
// ═══════════════════════════════════════════════════════════════════

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Seller extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'shop_name', 'firstname', 'lastname',
        'phone_number', 'password', 'photo',
        'region', 'balance', 'rating',
        'status', 'is_hidden', 'is_verified',
        'activity_types', 'successful_orders',
        'parent_id', 'role', 'staff_status',
        'commission_percent', 'fcm_token',
        'response_time_hours',
    ];

    protected $hidden = ['password'];

    protected $casts = [
        'isVerified' => 'boolean',
        'isSupport'  => 'boolean',
        'is_hidden'  => 'boolean',
        'password'   => 'hashed',
    ];

    /**
     * activity_types ni har qanday formatdan xavfsiz array qaytaradi.
     * DB: null | '' | '["Kitob"]' | 'Kitob,Kanstovar' | ["Kitob"] (already array)
     */
    public function getActivityTypesAttribute($value): array
    {
        if (is_array($value))   return $value;
        if (empty($value))      return [];
        $decoded = json_decode($value, true);
        if (is_array($decoded)) return $decoded;
        return array_values(array_filter(array_map('trim', explode(',', $value))));
    }

    public function setActivityTypesAttribute($value): void
    {
        $this->attributes['activity_types'] = is_array($value)
            ? json_encode($value)
            : $value;
    }

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
        return $this->hasMany(ConnectedDevice::class, 'user_id', 'id')
            ->where('user_type', 'seller');
    }
}