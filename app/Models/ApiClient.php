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
        'app_id',
        'app_secret',
        'abilities',
        'is_active',
    ];

    protected $casts = [
        'abilities' => 'array',
        'is_active' => 'boolean',
    ];

    // Yangi mijoz uchun app_id va app_secret avtomatik yaratish
    protected static function booted()
{
    static::creating(function ($apiClient) {
        $credentials = self::generateCredentials();
        
        // Agar qo'lda berilmagan bo'lsa, avtomatik to'ldiradi
        $apiClient->app_id = $apiClient->app_id ?? $credentials['app_id'];
        $apiClient->app_secret = $apiClient->app_secret ?? $credentials['app_secret'];
    });
}
    public static function generateCredentials(): array
    {
        return [
            'app_id'     => 'app_' . Str::random(16),
            'app_secret' => Str::random(48),
        ];
    }
}