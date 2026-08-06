<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Mahsulot darajasidagi (foydalanuvchidan mustaqil) Reading Intelligence
 * keshi — qiyinlik, kayfiyat, kimlar uchun mos/emas, sharhlar xulosasi.
 *
 * @see \App\Services\ReadingIntelligence\ReadingInsightGenerator
 */
class BookReadingInsight extends Model
{
    protected $fillable = [
        'product_type',
        'product_id',
        'content_hash',
        'difficulty',
        'mood_tags',
        'audience_fit',
        'audience_avoid',
        'review_synthesis',
        'generated_at',
    ];

    protected $casts = [
        'mood_tags' => 'array',
        'audience_fit' => 'array',
        'audience_avoid' => 'array',
        'review_synthesis' => 'array',
        'generated_at' => 'datetime',
    ];

    public function localizedAudienceFit(string $locale): ?string
    {
        return $this->pickLocale($this->audience_fit, $locale);
    }

    public function localizedAudienceAvoid(string $locale): ?string
    {
        return $this->pickLocale($this->audience_avoid, $locale);
    }

    public function localizedReviewSynthesis(string $locale): ?string
    {
        return $this->pickLocale($this->review_synthesis, $locale);
    }

    /**
     * Berilgan lokal uchun matn topilmasa, 'uz' ga qaytadi (default kontent
     * tili) — hech qachon bo'sh karta ko'rsatilmasin.
     */
    private function pickLocale(?array $bag, string $locale): ?string
    {
        if (empty($bag)) {
            return null;
        }

        return $bag[$locale] ?? $bag['uz'] ?? array_values($bag)[0] ?? null;
    }

    public function hasLocale(string $field, string $locale): bool
    {
        $bag = $this->{$field};

        return is_array($bag) && array_key_exists($locale, $bag) && trim((string) $bag[$locale]) !== '';
    }
}
