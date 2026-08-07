<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Promocode extends Model
{
    use HasFactory;

    protected $table = 'promocodes';

    protected $fillable = [
        'user_id',
        'code',
        'type',
        'amount',
        'max_discount_amount',
        'min_order_amount',
        'per_user_limit',
        'eligible_order_count',
        'usesLimit',
        'usedCount',
        'status',
        'expires_at',
    ];

    protected $casts = [
        'status'     => 'boolean',
        'expires_at' => 'datetime',
        'amount'     => 'integer',
        'max_discount_amount' => 'integer',
        'per_user_limit' => 'integer',
        'eligible_order_count' => 'integer',
        'usesLimit'  => 'integer',
        'usedCount'  => 'integer',
        'min_order_amount' => 'integer',
    ];

    // ── Relationships ──────────────────────────────────────────

    /**
     * Promokodni ishlatganlar tarixi
     * promocode_histories jadvali orqali
     */
    public function histories()
    {
        return $this->hasMany(PromocodeHistory::class, 'promocode_id');
    }

    /**
     * Promokod faqat bitta foydalanuvchiga tegishli bo'lsa
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // ── Helpers ────────────────────────────────────────────────

    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at <= now();
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->status
            && !$this->is_expired
            && ($this->usesLimit === 0 || $this->usedCount < $this->usesLimit);
    }
}
