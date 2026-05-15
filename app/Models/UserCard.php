<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserCard extends Model
{
    use HasFactory;

    /**
     * Ommaviy to'ldirilishi mumkin bo'lgan maydonlar.
     */
    protected $fillable = [
        'user_id',
        'provider',
        'provider_card_id',
        'card_fingerprint',
        'card_name',
        'card_number',
        'expire_date',
        'phone_number',
        'vendor',
        'processing',
        'token',
        'is_verified',
        'is_default',
        'is_temporary',
        'pending_order_id',
        'provider_meta',
    ];

    /**
     * Ma'lumotlar turlarini o'zgartirish (Casting).
     */
    protected $casts = [
        'is_verified' => 'boolean',
        'is_default' => 'boolean',
        'is_temporary' => 'boolean',
        'provider_meta' => 'array',
        // Provider tokeni bazada shifrlangan holda saqlanadi.
        'token' => 'encrypted',
    ];

    /**
     * Kartaga tegishli foydalanuvchini olish.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Faqat tasdiqlangan kartalarni olish uchun Scope.
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function getMaskedNumberAttribute(): string
    {
        return (string) ($this->card_number ?? '');
    }
}
