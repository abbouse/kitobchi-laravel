<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;

class Couriers extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'couriers';

    protected $fillable = [
        // ── Asosiy ──────────────────────────────────────────────
        'first_name',
        'last_name',
        'region',
        // Do'kon kuryeri: seller_id to'la = shu do'konniki, null = platforma.
        'seller_id',
        'service_area',
        'store_courier_hidden_at',
        'photo',
        'phone_number',
        'password',
        'password_reset_limit',
        'password_reset_limit_reset_at',
        'fcm_token',
        'balance',
        'cod_reserved_amount',
        'total_withdrawal',
        'payment_card',
        'status',
        'is_online',
        'availability_updated_at',

        // ── Transport ───────────────────────────────────────────
        'transport_type',
        'vehicle_brand', 'vehicle_model', 'vehicle_color', 'vehicle_plate_number',

        // ── Identifikatsiya ─────────────────────────────────────
        'inn', 'birthdate',
        'passport_series', 'passport_number',
        'passport_issued_by', 'passport_issued_at',

        // ── Haydovchi guvohnomasi ───────────────────────────────
        'driver_license_number',
        'driver_license_issued_at', 'driver_license_expires_at',

        // ── Bank/karta egasi va manzil ──────────────────────────
        'card_holder', 'home_address', 'current_lat', 'current_lon', 'location_updated_at',

        // ── Verifikatsiya ───────────────────────────────────────
        'verification_status', 'verified_at', 'verification_notes',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'balance' => 'integer',
        'cod_reserved_amount' => 'integer',
        'password_reset_limit' => 'integer',
        'password_reset_limit_reset_at' => 'datetime',

        'birthdate'                  => 'date',
        'passport_issued_at'         => 'date',
        'driver_license_issued_at'   => 'date',
        'driver_license_expires_at'  => 'date',
        'verified_at'                => 'datetime',
        'current_lat'                => 'float',
        'current_lon'                => 'float',
        'location_updated_at'        => 'datetime',
        'is_online'                  => 'boolean',
        'availability_updated_at'    => 'datetime',
        'seller_id'                  => 'integer',
        'service_area'               => 'array',
        'store_courier_hidden_at'    => 'datetime',
    ];

    // ── Password ───────────────────────────────────────────────────
    public function setPasswordAttribute($value)
    {
        if ($value === null || $value === '') {
            $this->attributes['password'] = $value;
            return;
        }

        $stringValue = (string) $value;

        // Plain password kelsa hash qilamiz, allaqachon hash bo'lgan qiymatni
        // esa yana hash qilib yubormaymiz.
        $this->attributes['password'] = Hash::needsRehash($stringValue)
            ? bcrypt($stringValue)
            : $stringValue;
    }

    public function setPhoneNumberAttribute($value)
    {
        $digits = preg_replace('/\D+/', '', (string) $value) ?? '';
        $this->attributes['phone_number'] = $digits;
    }

    // ── Relationships ──────────────────────────────────────────────

    public function orders()
    {
        return $this->hasMany(CourierOrder::class, 'courier_id');
    }

    /** Do'kon kuryeri bo'lsa — tegishli do'kon (null = platforma kuryeri). */
    public function store()
    {
        return $this->belongsTo(Seller::class, 'seller_id');
    }

    /** Kuryer xizmat qiladigan filiallar (bir nechta bo'lishi mumkin). */
    public function branches()
    {
        return $this->belongsToMany(SellerLocation::class, 'courier_seller_location', 'courier_id', 'seller_location_id')
            ->withTimestamps();
    }

    /** Faqat platforma kuryerlari (do'konga bog'lanmagan). */
    public function scopePlatform($query)
    {
        return $query->whereNull('seller_id');
    }

    /** Faqat do'kon kuryerlari (ixtiyoriy do'kon bo'yicha). */
    public function scopeForStore($query, ?int $sellerId = null)
    {
        $query->whereNotNull('seller_id');

        return $sellerId ? $query->where('seller_id', $sellerId) : $query;
    }

    /** Do'kon kuryerimi? */
    public function getIsStoreCourierAttribute(): bool
    {
        return $this->seller_id !== null;
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

    public function documents(): HasMany
    {
        return $this->hasMany(CourierDocument::class, 'courier_id')->latest();
    }

    public function hubStaffRoles()
    {
        return $this->morphMany(HubStaff::class, 'staffable');
    }

    public function courierTasks()
    {
        return $this->hasMany(CourierTask::class, 'courier_id');
    }

    // ── Helpers ────────────────────────────────────────────────────

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Karta raqamining maskalangan ko'rinishi (xavfsizlik uchun).
     */
    public function getMaskedCardAttribute(): ?string
    {
        if (empty($this->payment_card)) return null;
        $card = preg_replace('/\D/', '', $this->payment_card);
        if (strlen($card) < 4) return $this->payment_card;
        return '**** **** **** ' . substr($card, -4);
    }

    /**
     * Transport turi uchun foydalanuvchi-do'stona yorliq.
     */
    public function getTransportLabelAttribute(): string
    {
        return match ($this->transport_type) {
            'foot'       => 'Piyoda',
            'bicycle'    => 'Velosiped',
            'motorcycle' => 'Mototsikl',
            'car'        => 'Avtomobil',
            default      => 'Piyoda',
        };
    }

    public function getTransportIconAttribute(): string
    {
        return match ($this->transport_type) {
            'foot'       => 'footprints',
            'bicycle'    => 'bike',
            'motorcycle' => 'bike',
            'car'        => 'car',
            default      => 'footprints',
        };
    }

    /**
     * Haydovchi guvohnomasi tugashiga necha kun qolganini hisoblaydi.
     */
    public function getLicenseDaysRemainingAttribute(): ?int
    {
        if (empty($this->driver_license_expires_at)) return null;
        return (int) now()->startOfDay()->diffInDays($this->driver_license_expires_at, false);
    }

    public function getVerificationLabelAttribute(): string
    {
        return match ($this->verification_status) {
            'unverified' => 'Tekshirilmagan',
            'pending'    => 'Ko\'rib chiqilmoqda',
            'verified'   => 'Tasdiqlangan',
            'rejected'   => 'Rad etilgan',
            default      => 'Tekshirilmagan',
        };
    }

    public function getVerificationColorAttribute(): string
    {
        return match ($this->verification_status) {
            'unverified' => 'gray',
            'pending'    => 'amber',
            'verified'   => 'emerald',
            'rejected'   => 'red',
            default      => 'gray',
        };
    }
}
