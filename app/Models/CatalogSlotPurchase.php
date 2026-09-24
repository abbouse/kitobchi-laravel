<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Do'kon sotib olgan katalog joyi (buy box slot).
 *
 * Bitta kartada bir vaqtning o'zida faqat bitta band joy bo'ladi: `pending`
 * (tasdiq kutilmoqda) yoki `active`. `CatalogSlotService::occupiedFor()` shuni
 * tekshiradi.
 */
class CatalogSlotPurchase extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';

    /** Kartani band qiladigan holatlar. */
    public const BLOCKING = [self::STATUS_PENDING, self::STATUS_ACTIVE];

    protected $fillable = [
        'edition_id', 'seller_id', 'book_id', 'status', 'reject_reason',
        'reviewer_id', 'reviewed_at', 'days', 'price_uzs',
        'charged_at', 'refunded_at', 'starts_at', 'ends_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'charged_at' => 'datetime',
        'refunded_at' => 'datetime',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'days' => 'integer',
        'price_uzs' => 'integer',
    ];

    public function edition(): BelongsTo
    {
        return $this->belongsTo(BookEdition::class, 'edition_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class, 'seller_id');
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Books::class, 'book_id');
    }

    /** Hozir kuchda bo'lgan (tasdiqlangan va muddati o'tmagan) joylar. */
    public function scopeRunning(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()));
    }

    /** Kartani band qilib turgan (kutilayotgan yoki faol) joylar. */
    public function scopeBlocking(Builder $query): Builder
    {
        return $query->whereIn('status', self::BLOCKING)
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()));
    }

    public function isRunning(): bool
    {
        return $this->status === self::STATUS_ACTIVE
            && (! $this->starts_at || $this->starts_at->lessThanOrEqualTo(now()))
            && (! $this->ends_at || $this->ends_at->greaterThan(now()));
    }
}
