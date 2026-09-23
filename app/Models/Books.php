<?php

namespace App\Models;

use App\Models\Concerns\HasBranchStock;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Laravel\Scout\Searchable;

class Books extends Model
{
    use HasBranchStock, HasFactory, Searchable;

    protected static ?bool $hasAuthorColumnCache = null;

    protected $table = 'books';

    protected $fillable = [
        'edition_id',
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
        'ai_moderation_status',
        'ai_moderation_checked_at',
        'ai_moderation_note',
        'ai_moderation_model',
        'ai_moderation_content_hash',
        'ai_moderation_attempts',
        'ai_moderation_next_retry_at',
        'ai_moderation_meta',
        'totalSales',
        'totalRevenue',
        'totalClients',
        'totalSalesWeek',
        'totalRevenueWeek',
        'totalClientsWeek',
        'vectorData',
        'vector_text_hash',
        'has_vector',
        'ofd_ikpu_code',
        'ofd_package_code',
        'recommended',
        'views',
        'recommendedExpiresAt',
        'ugc_aggregate_score',
        'ugc_reviews_count',
        'ugc_last_scored_at',
    ];

    /**
     * Legacy API kontrakt: `count` maydoni JSON javoblarda saqlanadi —
     * endi branch_stocks yig'indisidan hisoblanadi (HasBranchStock).
     * `first_image` — avvaldan mavjud append.
     */
    protected $appends = ['count', 'first_image'];

    /**
     * JSON javoblarda KERAKSIZ og'ir maydonlar chiqmasin:
     * - vectorData: 1536 ta float (embedding) — har mahsulotda ulkan payload
     * - vector_text_hash / branch_available_total: ichki texnik maydonlar
     * Bu API kontraktni buzmaydi (bu maydonlar app'ga hech qachon kerak emas edi).
     */
    protected $hidden = ['vectorData', 'vector_text_hash', 'has_vector', 'branch_available_total'];

    /**
     * KITOBNING O'ZINIKI bo'lgan maydonlar — ular katalog kartasiga (book_editions)
     * tegishli. Taklif kartaga ulangan bo'lsa (edition_id), bu ustunlarni HECH KIM
     * o'zgartira olmaydi: do'kon ham, admin ham, eski ilova ham, import ham.
     * Yagona yozuvchi — CatalogService::syncOffers() (katalog tahrirlanganda).
     */
    public const CATALOG_MANAGED = [
        'name', 'author', 'author_id', 'translator', 'isbn', 'publisher_id',
        'category_id', 'lang', 'langType', 'coverType', 'year', 'pages',
        'description', 'images',
    ];

    /** Faqat katalog sinxroni shu bayroq bilan yozadi. */
    private static bool $catalogWriteAllowed = false;

    /** Katalogdan yozish (CatalogService::syncOffers va kartadan taklif yaratish). */
    public static function writingFromCatalog(callable $callback)
    {
        $previous = self::$catalogWriteAllowed;
        self::$catalogWriteAllowed = true;

        try {
            return $callback();
        } finally {
            self::$catalogWriteAllowed = $previous;
        }
    }

    public static function catalogWritesAllowed(): bool
    {
        return self::$catalogWriteAllowed;
    }

    /**
     * Barcha model yozuvlari shu yerdan o'tadi (update/save/updateQuietly/
     * forceFill/saveQuietly ham) — kartaga ulangan taklifda kitob maydonlari
     * jimgina eski qiymatiga qaytariladi va log'ga yoziladi.
     */
    protected function performUpdate(\Illuminate\Database\Eloquent\Builder $query)
    {
        $this->discardCatalogManagedChanges();

        return parent::performUpdate($query);
    }

    protected function performInsert(\Illuminate\Database\Eloquent\Builder $query)
    {
        // Kartaga ulangan holda yaratilayotgan taklif ma'lumotni faqat kartadan oladi
        if ($this->edition_id && ! self::$catalogWriteAllowed) {
            // withTrashed(): o'chirilgan kartaga ulangan taklif ham kartadan
            // to'ldiriladi (aks holda do'kon kiritgan ma'lumot o'tib ketardi)
            $edition = BookEdition::withTrashed()->find($this->edition_id);
            if ($edition) {
                foreach (app(\App\Services\Catalog\CatalogService::class)->offerAttributes($edition) as $column => $value) {
                    $this->attributes[$column] = is_array($value)
                        ? json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                        : $value;
                }
            }
        }

        return parent::performInsert($query);
    }

    private function discardCatalogManagedChanges(): void
    {
        if (self::$catalogWriteAllowed || ! $this->edition_id) {
            return;
        }

        $blocked = [];
        foreach (self::CATALOG_MANAGED as $column) {
            if (! array_key_exists($column, $this->attributes) || ! $this->isDirty($column)) {
                continue;
            }
            $blocked[] = $column;
            // Model to'liq yuklanmagan bo'lsa (select() bilan), asl qiymat yo'q —
            // ustunni butunlay olib tashlaymiz. Aks holda u "dirty" bo'lib qolib,
            // butun UPDATE (narx ham) jimgina yo'qolib ketardi.
            if (array_key_exists($column, $this->original)) {
                $this->attributes[$column] = $this->getRawOriginal($column);
            } else {
                unset($this->attributes[$column]);
            }
        }

        if ($blocked !== []) {
            Log::info('Katalog kitob maydoni himoyalandi (yozuv e\'tiborsiz qoldirildi)', [
                'book_id' => $this->id,
                'edition_id' => $this->edition_id,
                'fields' => $blocked,
            ]);
        }
    }

    public function newEloquentBuilder($query): \App\Models\Builders\BooksBuilder
    {
        return new \App\Models\Builders\BooksBuilder($query);
    }

    public function branchStockType(): string
    {
        return 'book';
    }

    public function getCountAttribute(): int
    {
        return $this->totalAvailableStock();
    }

    /**
     * Legacy yozuvlarni himoya: `count` endi ustun emas. To'g'ridan-to'g'ri
     * o'rnatishlar DB xatosiga olib kelmasligi uchun yutiladi va log qilinadi.
     * Stock o'zgarishi FAQAT BranchStockService orqali.
     */
    public function setCountAttribute($value): void
    {
        Log::warning('Books.count setter ignored — use BranchStockService', [
            'book_id' => $this->id, 'value' => $value,
        ]);
    }

    protected $casts = [
        'images' => 'json',
        'status' => 'boolean',
        'recommended' => 'boolean',
        'vectorData' => 'json',
        'has_vector' => 'boolean',
        'ai_moderation_checked_at' => 'datetime',
        'ai_moderation_next_retry_at' => 'datetime',
        'ai_moderation_meta' => 'array',
        'ai_moderation_attempts' => 'integer',
        'ugc_last_scored_at' => 'datetime',
        'catalog_featured' => 'boolean',
        'archived_at' => 'datetime',
        'archived_state' => 'array',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(BookCategories::class);
    }

    public function cartItems()
    {
        // my_carts'da `book_id` ustuni yo'q — polimorf product_id/product_type
        return $this->hasMany(MyCart::class, 'product_id')->where('product_type', 'book');
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

    /** Global katalogdagi kitob (bu qator — do'konning shu kitobga taklifi). */
    public function edition(): BelongsTo
    {
        return $this->belongsTo(BookEdition::class, 'edition_id');
    }

    /**
     * Mijoz RO'YXATLARI uchun: har kitobdan faqat bitta taklif (buy box).
     * Katalogga ulanmagan qatorlarda catalog_featured doim 1.
     * Kitob sahifasi, do'kon sahifasi, savat va buyurtmalarda ISHLATILMAYDI.
     */
    public function scopeCatalogFeatured(Builder $query): Builder
    {
        if (! config('catalog.dedupe', true)) {
            return $query; // .env o'chirgichi: avvalgi xulq-atvor (har taklif alohida)
        }

        return $query->where($this->getTable() . '.catalog_featured', true);
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

    // ── Laravel Scout (Meilisearch) ─────────────────────────────────────────

    public function searchableAs(): string
    {
        return 'books_index';
    }

    public function shouldBeSearchable(): bool
    {
        $seller = $this->seller;

        if (! $seller || ($seller->status ?? null) !== 'approved' || (int) ($seller->is_hidden ?? 0) === 1) {
            return false;
        }

        return (bool) $this->status
            && (int) $this->is_hidden === 0
            && (int) $this->is_approved === 1;
    }

    public function makeAllSearchableUsing(Builder $query): Builder
    {
        return $query->with(['category', 'seller', 'tags', 'authorProfile'])->withAvailableTotal();
    }

    public function toSearchableArray(): array
    {
        $tagNames = $this->relationLoaded('tags')
            ? $this->tags->flatMap(fn ($tag) => [
                $tag->tag_name_uz, $tag->tag_name_ru, $tag->tag_name_en, $tag->tag_name_ja,
            ])->filter()->implode(' ')
            : '';

        return [
            'id'             => (int) $this->id,
            'name'           => (string) ($this->name ?? ''),
            'author'         => (string) ($this->authorProfile?->name ?: $this->author ?? ''),
            'artikul'        => (string) ($this->artikul ?? ''),
            'tags'           => $tagNames,
            'category_name'  => (string) ($this->category?->name_uz ?? $this->category?->title ?? ''),
            'description'    => (string) ($this->description ?? ''),
            'category_id'    => (int) ($this->category_id ?? 0),
            'seller_id'      => (int) ($this->seller_id ?? 0),
            'price'          => (float) ($this->discountPrice ?: $this->price ?? 0),
            'lang'           => (string) ($this->lang ?? ''),
            'in_stock'       => $this->totalAvailableStock() > 0,
            'totalSalesWeek' => (int) ($this->totalSalesWeek ?? 0),
            'totalSales'     => (int) ($this->totalSales ?? 0),
            'created_at'     => $this->created_at?->timestamp ?? 0,
            'edition_id'     => (int) ($this->edition_id ?? 0),
            'catalog_featured' => (bool) ($this->catalog_featured ?? true),
        ];
    }

    /**
     * Tezlik (2026-08-20 audit): oldin `whereRaw('JSON_LENGTH(vectorData) = 1536')`
     * ishlatilardi — funksiya ustunga qo'llanganda MySQL indeksdan foydalana
     * olmaydi, har so'rovda butun jadval skan qilinardi. `has_vector` —
     * yozish paytida (ProductVectorService) hisoblab qo'yiladigan indekslangan
     * boolean ustun, shu tekshiruvni bitta arzon indeks lookup'ga aylantiradi.
     */
    public function scopeVectorReady(Builder $query): Builder
    {
        return $query
            ->whereNotNull('vectorData')
            ->where('has_vector', true);
    }

    public function scopeVectorNeedsSync(Builder $query): Builder
    {
        return $query->where(function (Builder $innerQuery) {
            $innerQuery
                ->whereNull('vectorData')
                ->orWhere('has_vector', false)
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
        if ($raw === null) {
            return null;
        }
        $clean = strtoupper(preg_replace('/[^0-9Xx]/', '', $raw) ?? '');
        if ($clean === '') {
            return null;
        }
        // Faqat 10 yoki 13 belgili variantni qabul qilamiz, oraliq qiymatlarni kesmaymiz.
        if (strlen($clean) === 10 || strlen($clean) === 13) {
            return $clean;
        }

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
