<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SplitUserProfile extends Model
{
    protected $fillable = [
        'user_id',
        'eligible',
        'eligibility_reasons',
        'confidence_score',
        'computed_limit',
        'available_limit',
        'active_exposure',
        'active_contract_count',
        'reputation_score',
        'cod_return_strikes',
        'account_age_days',
        'verified_card_age_days',
        'verified_cards_count',
        'successful_card_payments_180d',
        'completed_orders_90d',
        'completed_orders_all',
        'completed_gmv_180d',
        'cancel_rate_90d',
        'device_count_90d',
        'card_churn_90d',
        'active_warning_count',
        'manual_blocked_at',
        'manual_block_reason',
        'manual_blocked_by_admin_id',
        'manual_limit',
        'manual_limit_set_by',
        'manual_limit_set_at',
        'limit_granted_notified_at',
        'last_promo_push_at',
        'last_refreshed_at',
        'snapshot',
    ];

    protected $casts = [
        'eligible' => 'boolean',
        'eligibility_reasons' => 'array',
        'confidence_score' => 'decimal:2',
        'reputation_score' => 'decimal:2',
        'cancel_rate_90d' => 'decimal:4',
        'manual_blocked_at' => 'datetime',
        'manual_limit_set_at' => 'datetime',
        'limit_granted_notified_at' => 'datetime',
        'last_promo_push_at' => 'datetime',
        'last_refreshed_at' => 'datetime',
        'snapshot' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
