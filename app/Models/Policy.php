<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Policy extends Model
{
    protected $fillable = [
        'title', 'slug', 'content', 'sort_order', 'is_active', 'show_in_app',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'show_in_app' => 'boolean',
        'sort_order' => 'integer',
    ];

    public static function boot(): void
    {
        parent::boot();

        static::creating(function (self $policy) {
            if (empty($policy->slug)) {
                $policy->slug = Str::slug($policy->title);
            }
        });
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getUrlAttribute(): string
    {
        return route('legal.policy', $this->slug);
    }

    public function translations(): HasMany
    {
        return $this->hasMany(PolicyTranslation::class);
    }

    public function localizedTitle(): string
    {
        $loc = app()->getLocale();
        if ($loc === 'uz') {
            return (string) $this->title;
        }

        $tr = $this->relationLoaded('translations')
            ? $this->translations->firstWhere('locale', $loc)
            : $this->translations()->where('locale', $loc)->first();

        if ($tr && is_string($tr->title) && $tr->title !== '') {
            return $tr->title;
        }

        return (string) $this->title;
    }

    public function localizedContent(): string
    {
        $loc = app()->getLocale();
        if ($loc === 'uz') {
            return (string) $this->content;
        }

        $tr = $this->relationLoaded('translations')
            ? $this->translations->firstWhere('locale', $loc)
            : $this->translations()->where('locale', $loc)->first();

        if ($tr && is_string($tr->content) && $tr->content !== '') {
            return $tr->content;
        }

        return (string) $this->content;
    }
}
