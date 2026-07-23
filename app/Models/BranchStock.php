<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Filial-darajali stock qatori.
 * (seller_location_id × product_type × product_id × variant_id) → unique.
 * variant_id = 0 → mahsulot darajasi.
 */
class BranchStock extends Model
{
    public const TYPE_BOOK = 'book';
    public const TYPE_STATIONERY = 'stationery';
    public const TYPE_GIFT = 'gift';

    protected $fillable = [
        'seller_id',
        'seller_location_id',
        'product_type',
        'product_id',
        'variant_id',
        'quantity',
        'reserved',
        'low_stock_threshold',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'reserved' => 'integer',
        'variant_id' => 'integer',
        'low_stock_threshold' => 'integer',
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(SellerLocation::class, 'seller_location_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /** Sotuvga tayyor qoldiq. */
    public function getAvailableAttribute(): int
    {
        return max(0, (int) $this->quantity - (int) $this->reserved);
    }

    /**
     * Skalyar subquery SQL: berilgan jadval qatori uchun barcha filiallardagi
     * mavjud qoldiq yig'indisi. where/orderBy ichida ishlatiladi.
     *
     * @param string $type       book|stationery|gift
     * @param string $idColumn   tashqi jadval ustuni, masalan: books.id
     * @param string $variantExpr variant sharti ifodasi (default '= 0';
     *                            barcha variantlar bilan: 'IS NOT NULL' o'rniga '>= 0')
     */
    public static function availableSql(string $type, string $idColumn, string $variantExpr = '= 0'): string
    {
        return "(SELECT COALESCE(SUM(bs.quantity - bs.reserved), 0)
                 FROM branch_stocks bs
                 WHERE bs.product_type = '{$type}'
                   AND bs.product_id = {$idColumn}
                   AND bs.variant_id {$variantExpr})";
    }
}
