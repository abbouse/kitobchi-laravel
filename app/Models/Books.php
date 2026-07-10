<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;

class Books extends Model
{
    use HasFactory;

    protected static ?bool $hasAuthorColumnCache = null;

    protected $table = 'books';

    protected $fillable = [
        'name',
        'artikul',
        'author',
        'author_id',
        'translator',
        'isbn',
        'category_id',
        'description',
        'images',
        'price',
        'discountPrice',
        'discountExpiresAt',
        'count',
        'lang',
        'langType',
        'coverType',
        'year',
        'pages',
        'status',
        'seller_id',
        'publisher_id',
        'is_hidden',
        'is_approved',
        'totalSales',
        'totalRevenue',
        'totalClients',
        'totalSalesWeek',
        'totalRevenueWeek',
        'totalClientsWeek',
        'vectorData',
        'vector_text_hash',
        'ofd_ikpu_code',
        'ofd_package_code',
        'recommended',
        'views',
        'recommendedExpiresAt',
        'kangaroo_listing_decision',
        'kangaroo_listing_checked_at',
        'kangaroo_listing_issues',
        'ugc_aggregate_score',
        'ugc_reviews_count',
        'ugc_last_scored_at',
    ];

    protected $casts = [
        'images' => 'json',
        'status' => 'boolean',
        'recommended' => 'boolean',
        'vectorData' => 'json',
        'kangaroo_listing_issues' => 'array',
        'kangaroo_listing_checked_at' => 'datetime',
        'ugc_last_scored_at' => 'datetime',
    ];

    protected $appends = ['first_image'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(BookCategories::class);
    }

    public function cartItems()
    {
        return $this->hasMany(MyCart::class, 'book_id');
    }

    public function getFirstImageAttribute(): ?string
    {
        $images = $this->images;

        if (empty($images) || ! is_array($images)) {
            return null;
        }

        return $images[0] ?? null;
    }

    public function tags()
    {
        return $this->belongsToMany(BookTag::class, 'book_tag_relations', 'book_id', 'tag_id');
    }

    public function seller()
    {
        return $this->belongsTo(Seller::class, 'seller_id')
            ->select('id', 'shop_name', 'lastname', 'firstname', 'phone_number', 'photo', 'isVerified', 'status', 'is_hidden');
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(Publisher::class, 'publisher_id');
    }

    public function authorProfile(): BelongsTo
    {
        return $this->belongsTo(Author::class, 'author_id');
    }

    public function scopeActiveForVector(Builder $query): Builder
    {
        // Eslatma: stock (count) sharti YO'Q — sotuvda qolmagan kitoblar ham
        // chatbot qidiruvida ko'rinadi (mijoz ko'rishi va "kelganda xabar ber"
        // bosishi uchun). Asosiy search o'zi in-stock filterini qo'llaydi.
        return $query
            ->where('status', true)
            ->where('is_approved', 1)
            ->where('is_hidden', 0)
            ->whereHas('seller', fn (Builder $sellerQuery) => $sellerQuery
                ->where('status', 'approved')
                ->where('is_hidden', 0));
    }

    public function scopeVectorReady(Builder $query): Builder
    {
        return $query
            ->whereNotNull('vectorData')
            ->whereRaw('JSON_LENGTH(vectorData) = 1536');
    }

    public function scopeVectorNeedsSync(Builder $query): Builder
    {
        return $query->where(function (Builder $innerQuery) {
            $innerQuery
                ->whereNull('vectorData')
                ->orWhereRaw('JSON_LENGTH(vectorData) <> 1536')
                // Bulk yangilanishlar (masalan, admin muallif nomini o'zgartirsa)
                // hash ni null qiladi — scheduler qayta embed qiladi
                ->orWhereNull('vector_text_hash');
        });
    }

    public static function hasAuthorColumn(): bool
    {
        if (self::$hasAuthorColumnCache === null) {
            self::$hasAuthorColumnCache = Schema::hasColumn('books', 'author');
        }

        return self::$hasAuthorColumnCache;
    }

    public function getAuthorAttribute($value): ?string
    {
        $related = $this->relationLoaded('authorProfile')
            ? $this->getRelation('authorProfile')
            : $this->authorProfile;

        if ($related?->name) {
            return $related->name;
        }

        $raw = is_string($value) ? trim($value) : '';
        return $raw !== '' ? $raw : null;
    }

    public function setAuthorAttribute($value): void
    {
        if (! self::hasAuthorColumn()) {
            return;
        }

        $raw = trim((string) $value);
        $this->attributes['author'] = $raw !== '' ? $raw : null;
    }

    /**
     * ISBN'ni kanonik shaklga keltiradi — chiziqlar, bo'shliqlar olib
     * tashlanadi, faqat raqam va X (ISBN-10 oxiri) qoladi, katta harf.
     *
     *   "978-9943-08-123-1"  →  "9789943081231"
     *   "0 306 40615 2"      →  "0306406152"
     *   "isbn 0306406152"    →  "0306406152"
     */
    public static function normalizeIsbn(?string $raw): ?string
    {
        if ($raw === null) return null;
        $clean = strtoupper(preg_replace('/[^0-9Xx]/', '', $raw) ?? '');
        if ($clean === '') return null;
        // Faqat 10 yoki 13 belgili variantni qabul qilamiz, oraliq qiymatlarni kesmaymiz.
        if (strlen($clean) === 10 || strlen($clean) === 13) return $clean;
        return null;
    }

    /**
     * Query scope: ISBN bo'yicha qidiruv (kanonik shaklga ham,
     * DB'da chiziq bilan saqlangan variantga ham mos keladi).
     */
    public function scopeWhereIsbn($query, string $isbn)
    {
        $canonical = self::normalizeIsbn($isbn) ?? $isbn;
        return $query->where(function ($q) use ($isbn, $canonical) {
            $q->where('isbn', $canonical)
              ->orWhere('isbn', $isbn);
        });
    }
}
