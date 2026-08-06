<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Foydalanuvchi+mahsulot+til darajasidagi shaxsiy "nega mos" jumlasi keshi
 * (Holat A). `purchase_context_hash` foydalanuvchining did-vektori qaysi
 * xaridlar asosida hisoblanganini belgilaydi — yangi xarid bo'lsa hash
 * o'zgaradi va eski sabab avtomatik eskirgan hisoblanadi.
 *
 * @see \App\Services\ReadingIntelligence\ReadingIntelligenceService
 */
class UserBookMatchReason extends Model
{
    protected $fillable = [
        'user_id',
        'product_type',
        'product_id',
        'locale',
        'match_score',
        'reason_text',
        'purchase_context_hash',
        'generated_at',
    ];

    protected $casts = [
        'match_score' => 'float',
        'generated_at' => 'datetime',
    ];
}
