<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SellerPremiumSubscription extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_PAUSED = 'paused';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'seller_id',
        'plan',
        'duration_months',
        'price_uzs',
        'status',
        'auto_renew',
        'cancel_at_period_end',
        'stop_reason',
        'started_at',
        'expires_at',
        'last_renewed_at',
        'cancel_requested_at',
        'cancelled_at',
        'stopped_at',
    ];

    protected $casts = [
        'auto_renew' => 'boolean',
        'cancel_at_period_end' => 'boolean',
        'started_at' => 'datetime',
        'expires_at' => 'datetime',
        'last_renewed_at' => 'datetime',
        'cancel_requested_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'stopped_at' => 'datetime',
    ];

    public function seller()
    {
        return $this->belongsTo(Seller::class, 'seller_id');
    }
}
