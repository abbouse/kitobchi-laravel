<?php

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
        // ── Asosiy ────────────────────────────────────────────────
        'shop_name', 'firstname', 'lastname',
        'phone_number', 'password', 'photo',
        'password_reset_limit', 'password_reset_limit_reset_at',
        'region', 'balance', 'rating',
        'status', 'is_hidden', 'isVerified', 'isPremiumShop', 'isPremiumExpiresAt',
        'activity_types', 'successful_orders',
        'parent_id', 'role', 'staff_status',
        'commission_percent', 'fcm_token',
        'response_time_hours',

        // ── Shartnoma ─────────────────────────────────────────────
        'contract_number', 'contract_signed', 'contract_signed_at', 'contract_expires_at',
        'contract_status', 'contract_notes',

        // ── Huquqiy shakl + hujjatlar ─────────────────────────────
        'legal_type',
        'inn', 'passport_series', 'passport_number',
        'passport_issued_by', 'passport_issued_at',

        // ── Bank rekvizitlari ─────────────────────────────────────
        'bank_name', 'bank_account', 'bank_mfo', 'bank_swift',
        'payment_card', 'card_holder',

        // ── Manzil ────────────────────────────────────────────────
        'legal_address',
    ];

    protected $hidden = ['password'];

    protected $casts = [
        'isVerified' => 'boolean',
        'isSupport'  => 'boolean',
        'isPremiumShop' => 'boolean',
        'isPremiumExpiresAt' => 'datetime',
        'is_hidden'  => 'boolean',
        'password_reset_limit' => 'integer',
        'password_reset_limit_reset_at' => 'datetime',
        'password'   => 'hashed',

        'contract_signed'     => 'boolean',
        'contract_signed_at'  => 'date',
        'contract_expires_at' => 'date',
        'passport_issued_at'  => 'date',
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

    // ── Helpers ──────────────────────────────────────────────────

    /**
     * Bank hisob raqami yoki karta oxirgi 4 raqami bilan maskalangan ko'rinish.
     * Admin show sahifasida to'liq karta raqamini ko'rsatmaslik uchun.
     */
    public function getMaskedCardAttribute(): ?string
    {
        if (empty($this->payment_card)) return null;
        $card = preg_replace('/\D/', '', $this->payment_card);
        if (strlen($card) < 4) return $this->payment_card;
        return '**** **** **** ' . substr($card, -4);
    }

    public function getMaskedBankAccountAttribute(): ?string
    {
        if (empty($this->bank_account)) return null;
        $acc = preg_replace('/\D/', '', $this->bank_account);
        if (strlen($acc) < 4) return $this->bank_account;
        return str_repeat('*', strlen($acc) - 4) . substr($acc, -4);
    }

    /**
     * Shartnoma tugashiga necha kun qolgani (manfiy bo'lsa — tugagan).
     */
    public function getContractDaysRemainingAttribute(): ?int
    {
        if (empty($this->contract_expires_at)) return null;
        return (int) now()->startOfDay()->diffInDays($this->contract_expires_at, false);
    }

    /**
     * contract_status ni `contract_expires_at` ga qarab avtomatik hisoblaydi.
     * DB da saqlangan qiymat ham ishlaydi, lekin bu accessor — jonli holat.
     */
    public function getContractComputedStatusAttribute(): string
    {
        if (empty($this->contract_expires_at)) return 'none';
        $days = $this->contract_days_remaining;
        if ($days === null) return 'none';
        if ($days < 0)   return 'expired';
        if ($days <= 30) return 'expiring';
        return 'active';
    }

    // ── Relationships ────────────────────────────────────────────

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

    public function premiumSubscriptions()
    {
        return $this->hasMany(SellerPremiumSubscription::class, 'seller_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(SellerDocument::class, 'seller_id')->latest();
    }

    public function contractHistory(): HasMany
    {
        return $this->hasMany(SellerContractHistory::class, 'seller_id')->latest('created_at');
    }
}
