<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Global katalogdagi kitob (bitta nashr). Do'konlar unga TAKLIF (`books`
 * qatori) bilan ulanadi; nom, muallif, muqova va tavsif shu yerda bir marta
 * saqlanadi.
 */
class BookEdition extends Model
{
    use SoftDeletes;

    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_MERGED = 'merged';

    protected $fillable = [
        'isbn13', 'isbn10', 'title', 'author', 'author_id', 'translator',
        'publisher_id', 'category_id', 'lang', 'langType', 'coverType', 'year',
        'pages', 'description', 'front_image', 'back_image', 'images', 'tag_ids',
        'status', 'merged_into_id', 'source', 'created_by_type', 'created_by_id',
        'verified_at', 'match_key',
    ];

    protected $casts = [
        'images' => 'array',
        'tag_ids' => 'array',
        'verified_at' => 'datetime',
        'year' => 'integer',
        'pages' => 'integer',
        'offers_count' => 'integer',
        'in_stock_offers_count' => 'integer',
        'min_price' => 'integer',
    ];

    public function offers(): HasMany
    {
        return $this->hasMany(Books::class, 'edition_id');
    }

    public function authorProfile(): BelongsTo
    {
        return $this->belongsTo(Author::class, 'author_id');
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(Publisher::class, 'publisher_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(BookCategories::class, 'category_id');
    }

    public function mergedInto(): BelongsTo
    {
        return $this->belongsTo(self::class, 'merged_into_id');
    }

    /** Do'konlar ulanishi mumkin bo'lgan kartalar (tasdiqlangan yoki tekshiruvdagi). */
    public function scopeUsable(Builder $query): Builder
    {
        return $query->whereIn('status', [self::STATUS_ACTIVE, self::STATUS_PENDING]);
    }

    /**
     * Do'kon ilovasida ko'rinadigan kartalar: tasdiqlanganlar, o'z arizalari va
     * eski/avtomatik ochilgan kartalar. Boshqa do'konning tekshiruvdagi arizasi
     * (uning rasmi va tavsifi) begona do'konga ko'rsatilmaydi.
     */
    public function scopeSelectableBySeller(Builder $query, int $sellerId): Builder
    {
        return $query->where(function (Builder $inner) use ($sellerId) {
            $inner->where('status', self::STATUS_ACTIVE)
                ->orWhere(fn (Builder $own) => $own->where('status', self::STATUS_PENDING)
                    ->where(fn (Builder $mine) => $mine
                        ->where(fn (Builder $q) => $q->where('created_by_type', 'seller')->where('created_by_id', $sellerId))
                        ->orWhereIn('source', ['legacy', 'backfill'])));
        });
    }

    public function isUsable(): bool
    {
        return in_array($this->status, [self::STATUS_ACTIVE, self::STATUS_PENDING], true) && ! $this->trashed();
    }

    public function getFrontImageAttribute($value): ?string
    {
        if (is_string($value) && str_contains($value, 'res.cloudinary.com/dd9xb0bqw/') && ! str_contains($value, 'book.uz/_next/image')) {
            return 'https://book.uz/_next/image?url='.urlencode($value).'&w=640&q=75';
        }

        return $value;
    }

    public function getImagesAttribute($value): array
    {
        $images = is_string($value) ? json_decode($value, true) : $value;
        if (! is_array($images)) {
            return [];
        }

        return array_values(array_filter(array_map(function ($img) {
            if (is_string($img) && str_contains($img, 'res.cloudinary.com/dd9xb0bqw/') && ! str_contains($img, 'book.uz/_next/image')) {
                return 'https://book.uz/_next/image?url='.urlencode($img).'&w=640&q=75';
            }

            return $img;
        }, $images)));
    }

    /** Muqova (old) rasmi: alohida ustun yoki images[0]. */
    public function coverPath(): ?string
    {
        return $this->front_image ?: ($this->images[0] ?? null);
    }
}
