<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

// GET /api/external/books
// X-App-ID: app_xxxxxxxxxxxxxxxx
// X-App-Secret: xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx

class ApiClient extends Model
{
    protected $fillable = [
        'name',
        'seller_id',
        'app_id',
        'app_secret',
        'abilities',
        'allowed_ips',
        'is_active',
        'rate_limit_per_second',
        'rate_limit_per_minute',
    ];

    protected $casts = [
        'abilities' => 'array',
        'allowed_ips' => 'array',
        'is_active' => 'boolean',
        'seller_id' => 'integer',
        'rate_limit_per_second' => 'integer',
        'rate_limit_per_minute' => 'integer',
    ];

    protected static function booted()
    {
        static::creating(function ($apiClient) {
            $credentials = self::generateCredentials();

            $apiClient->app_id = $apiClient->app_id ?? $credentials['app_id'];
            $apiClient->app_secret = $apiClient->app_secret ?? $credentials['app_secret'];
        });
    }

    public static function generateCredentials(): array
    {
        return [
            'app_id' => 'app_' . Str::random(16),
            'app_secret' => Str::random(48),
        ];
    }

    public function requestLogs()
    {
        return $this->hasMany(ApiClientRequestLog::class, 'api_client_id');
    }

    public function webhooks()
    {
        return $this->hasMany(ApiWebhook::class, 'api_client_id');
    }

    public function seller()
    {
        return $this->belongsTo(Seller::class, 'seller_id');
    }
}
