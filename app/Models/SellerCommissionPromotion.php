<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class SellerCommissionPromotion extends Model
{
    public const TYPE_FREE = 'free';

    public const TYPE_FIXED_RATE = 'fixed_rate';

    protected $fillable = [
        'seller_id',
        'type',
        'value',
        'starts_at',
        'ends_at',
        'reason',
        'notes',
        'created_by',
        'revoked_at',
    ];

    protected $casts = [
        'seller_id' => 'integer',
        'value' => 'integer',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'created_by' => 'integer',
        'revoked_at' => 'datetime',
    ];

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class, 'seller_id');
    }

    public function scopeEffectiveAt(Builder $query, Carbon $at): Builder
    {
        return $query
            ->where('starts_at', '<=', $at)
            ->where('ends_at', '>', $at)
            ->where(function (Builder $builder) use ($at) {
                $builder->whereNull('revoked_at')->orWhere('revoked_at', '>', $at);
            });
    }
}
