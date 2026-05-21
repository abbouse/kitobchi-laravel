<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Blogger extends Model
{
    use HasFactory;

    protected $fillable = [
        'first_name',
        'last_name',
        'phone_number',
        'address',
        'instagram_url',
        'telegram_url',
        'youtube_url',
        'tiktok_url',
        'active_until',
    ];

    protected $casts = [
        'active_until' => 'datetime',
    ];

    protected $appends = [
        'full_name',
        'is_active_now',
        'status_label',
    ];

    public function shipments(): HasMany
    {
        return $this->hasMany(BloggerShipment::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim(implode(' ', array_filter([
            $this->first_name,
            $this->last_name,
        ]))) ?: 'Ism kiritilmagan';
    }

    public function getIsActiveNowAttribute(): bool
    {
        return $this->active_until instanceof CarbonInterface && $this->active_until->isFuture();
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->is_active_now ? 'Faol' : 'Nofaol';
    }

    public function scopeActive($query)
    {
        return $query->whereNotNull('active_until')->where('active_until', '>', now());
    }

    public function socialLinks(): array
    {
        return array_filter([
            'Instagram' => $this->instagram_url,
            'Telegram' => $this->telegram_url,
            'YouTube' => $this->youtube_url,
            'TikTok' => $this->tiktok_url,
        ], fn ($value) => is_string($value) && trim($value) !== '');
    }
}
