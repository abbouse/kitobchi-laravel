<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SharedCart extends Model
{
    protected $fillable = [
        'user_id',
        'slug',
        'items',
        'source',
        'order_id',
        'view_count',
        'expires_at',
    ];

    protected $casts = [
        'items'      => 'array',
        'expires_at' => 'datetime',
    ];

    // ── Muddati o'tganmi ─────────────────────────────────────────────
    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}