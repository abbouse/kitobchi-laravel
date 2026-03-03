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
        'card_number',
        'payme_token',
        'is_verified',
    ];

    /**
     * Ma'lumotlar turlarini o'zgartirish (Casting).
     */
    protected $casts = [
        'is_verified' => 'boolean',
        // Payme tokenini bazada shifrlangan holda saqlash juda muhim!
        'payme_token' => 'encrypted', 
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
}