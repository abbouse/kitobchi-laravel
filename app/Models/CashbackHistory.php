<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashbackHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'sold_id',
        'action',
        'amount',
        'balance_before',
        'balance_after',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Sold::class, 'sold_id');
    }
}
