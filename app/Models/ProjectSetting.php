<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectSetting extends Model
{
    protected $fillable = [
        'business_version_ios', 'business_version_android',
        'market_version_ios',   'market_version_android',
        'courier_version_ios',  'courier_version_android',
        // Kontakt
        'kitobchi_phone', 'kitobchi_email',
        'business_phone', 'business_email',
        'courier_phone',  'courier_email',
        // Telegram login
        'telegram_login_enabled', 'telegram_client_id',
        'telegram_redirect_uri_ios', 'telegram_redirect_uri_android', 'telegram_scopes',
        // App flaglar
        'on_premium', 'on_reels', 'ramadan', 'stop_sales',
        // Qadoqlash
        'packaging_price_small', 'packaging_price_large', 'packaging_threshold',
        // Marketplace moliyaviy sozlamalari
        'tax_mode', 'tax_fixed_uzs', 'tax_profit_percent', 'payment_provider_percent',
        // Gift certificate
        'gift_certificate_options',
        // Kuryer km-based to'lov va bonus tizimi
        'courier_base_fee', 'courier_price_per_km', 'courier_min_fee', 'courier_bonus_rules',
        // Split v1 prep
        'split_enabled', 'split_public_enabled', 'split_upfront_percent', 'split_term_days',
        'split_global_min_order_sum', 'split_global_max_order_sum',
        'split_global_min_limit', 'split_global_max_limit',
        'split_min_completed_orders', 'split_min_account_age_days',
        'split_min_card_age_days', 'split_min_reputation_score',
        'split_max_active_contracts', 'split_default_fee_percent',
        'split_card_delete_lock_enabled',
        // Refund
        'paylov_refund_sender_card_id', 'paylov_refund_service_id',
    ];

    protected $casts = [
        'on_premium'  => 'boolean',
        'on_reels'    => 'boolean',
        'ramadan'     => 'boolean',
        'stop_sales'  => 'boolean',
        'telegram_login_enabled' => 'boolean',
        'gift_certificate_options' => 'array',
        'tax_fixed_uzs'            => 'integer',
        'tax_profit_percent'       => 'decimal:3',
        'payment_provider_percent' => 'decimal:3',
        'courier_base_fee'        => 'integer',
        'courier_price_per_km'    => 'integer',
        'courier_min_fee'         => 'integer',
        'courier_bonus_rules'     => 'array',
        'split_enabled' => 'boolean',
        'split_public_enabled' => 'boolean',
        'split_upfront_percent' => 'integer',
        'split_term_days' => 'integer',
        'split_global_min_order_sum' => 'integer',
        'split_global_max_order_sum' => 'integer',
        'split_global_min_limit' => 'integer',
        'split_global_max_limit' => 'integer',
        'split_min_completed_orders' => 'integer',
        'split_min_account_age_days' => 'integer',
        'split_min_card_age_days' => 'integer',
        'split_min_reputation_score' => 'decimal:2',
        'split_max_active_contracts' => 'integer',
        'split_default_fee_percent' => 'decimal:2',
        'split_card_delete_lock_enabled' => 'boolean',
    ];
}
