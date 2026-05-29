<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Author extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'image',
        'external_id',
        'slug',
        'source_url',
    ];

    protected $appends = [
        'image_url',
        'default_image_url',
        'display_image_url',
        'has_multiple_authors',
        'needs_ai_portrait',
    ];

    public function books()
    {
        return $this->hasMany(Books::class, 'author_id');
    }

    public function getImageUrlAttribute(): ?string
    {
        if (! is_string($this->image) || trim($this->image) === '') {
            return null;
        }

        if (str_starts_with($this->image, 'http://') || str_starts_with($this->image, 'https://')) {
            return $this->image;
        }

        return asset('storage/' . ltrim($this->image, '/'));
    }

    public function getDefaultImageUrlAttribute(): string
    {
        $label = $this->has_multiple_authors
            ? 'CO'
            : $this->initialsFromName($this->name);

        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="160" height="160" viewBox="0 0 160 160" fill="none">
  <rect width="160" height="160" rx="36" fill="#F4F7FB"/>
  <rect x="10" y="10" width="140" height="140" rx="30" fill="#E9EEF8"/>
  <circle cx="80" cy="62" r="24" fill="#C9D5EC"/>
  <path d="M44 124C48.8827 101.686 62.6941 90.5 80 90.5C97.3059 90.5 111.117 101.686 116 124" fill="#C9D5EC"/>
  <text x="80" y="144" text-anchor="middle" fill="#5B7CFA" font-family="Inter, Arial, sans-serif" font-size="28" font-weight="700">{$label}</text>
</svg>
SVG;

        return 'data:image/svg+xml;utf8,' . rawurlencode($svg);
    }

    public function getDisplayImageUrlAttribute(): string
    {
        return $this->image_url ?: $this->default_image_url;
    }

    public function getHasMultipleAuthorsAttribute(): bool
    {
        return $this->looksLikeMultipleAuthors($this->name);
    }

    public function getNeedsAiPortraitAttribute(): bool
    {
        return $this->image_url === null && ! $this->has_multiple_authors;
    }

    private function looksLikeMultipleAuthors(?string $name): bool
    {
        $value = trim((string) $name);

        if ($value === '') {
            return false;
        }

        return preg_match('/\s*(,|\/|&|\+|;|\bx\b|\bva\b|\band\b|\bfeat\.?\b|\bft\.?\b)\s*/iu', $value) === 1;
    }

    private function initialsFromName(?string $name): string
    {
        $words = collect(preg_split('/\s+/u', trim((string) $name)) ?: [])
            ->filter()
            ->take(2)
            ->map(fn (string $word) => mb_strtoupper(mb_substr($word, 0, 1)));

        return $words->isNotEmpty() ? $words->implode('') : 'AU';
    }
}
