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
        'max_active_contracts',
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
        'last_refreshed_at',
        'snapshot',
    ];

    protected $casts = [
        'eligible' => 'boolean',
        'eligibility_reasons' => 'array',
        'confidence_score' => 'decimal:2',
        'reputation_score' => 'decimal:2',
        'cancel_rate_90d' => 'decimal:4',
        'last_refreshed_at' => 'datetime',
        'snapshot' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
