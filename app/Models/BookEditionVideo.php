<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * Global kitobning mahsulot videosi (bitta nashrga bitta).
 *
 * Tez ochilishi uchun ikki sifat tayyorlanadi: SD (480p, ~0.8 Mbit/s) —
 * sekin internet va mobil tarmoq uchun, HD (720p) — Wi‑Fi uchun. Ikkalasi
 * ham `+faststart` bilan: fayl to'liq yuklanishini kutmasdan o'ynay boshlaydi.
 */
class BookEditionVideo extends Model
{
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_READY = 'ready';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'edition_id', 'status', 'original_path', 'sd_path', 'hd_path', 'poster_path',
        'duration', 'width', 'height', 'sd_size', 'hd_size', 'error', 'uploaded_by',
    ];

    protected $casts = [
        'duration' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'sd_size' => 'integer',
        'hd_size' => 'integer',
    ];

    protected static function booted(): void
    {
        $forget = fn (BookEditionVideo $v) => Cache::forget(self::cacheKey((int) $v->edition_id));
        static::saved($forget);
        static::deleted($forget);
    }

    public function edition(): BelongsTo
    {
        return $this->belongsTo(BookEdition::class, 'edition_id');
    }

    public static function cacheKey(int $editionId): string
    {
        return "edition_video:{$editionId}";
    }

    public static function url(?string $path): ?string
    {
        return $path ? Storage::disk('public')->url($path) : null;
    }

    /** Ilova uchun: faqat tayyor video, bo'lmasa null. */
    public function toApi(): ?array
    {
        if ($this->status !== self::STATUS_READY || (! $this->sd_path && ! $this->hd_path)) {
            return null;
        }

        return [
            'sd' => self::url($this->sd_path ?: $this->hd_path),
            'hd' => self::url($this->hd_path ?: $this->sd_path),
            'poster' => self::url($this->poster_path),
            'duration' => $this->duration,
            'width' => $this->width,
            'height' => $this->height,
            'sd_size' => $this->sd_size,
            'version' => $this->updated_at?->timestamp,
        ];
    }

    public static function payloadFor(?int $editionId): ?array
    {
        if (! $editionId) {
            return null;
        }

        return Cache::remember(self::cacheKey($editionId), 600, function () use ($editionId) {
            return self::query()->where('edition_id', $editionId)->first()?->toApi();
        });
    }
}
