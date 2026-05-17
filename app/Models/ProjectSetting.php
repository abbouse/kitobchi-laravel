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
        // Gift certificate
        'gift_certificate_options',
        // Phase 3 — Kuryer bonus tizimi
        'courier_surge_step', 'courier_surge_max', 'courier_surge_threshold',
        'courier_sla_minutes', 'courier_penalty_step',
    ];

    protected $casts = [
        'on_premium'  => 'boolean',
        'on_reels'    => 'boolean',
        'ramadan'     => 'boolean',
        'stop_sales'  => 'boolean',
        'telegram_login_enabled' => 'boolean',
        'gift_certificate_options' => 'array',
        'courier_surge_step'      => 'integer',
        'courier_surge_max'       => 'integer',
        'courier_surge_threshold' => 'integer',
        'courier_sla_minutes'     => 'integer',
        'courier_penalty_step'    => 'integer',
    ];
}
