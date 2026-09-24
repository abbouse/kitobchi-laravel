<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Do'kon balansi daftari — balansga har qanday tegish shu yerda iz qoldiradi.
 */
class SellerBalanceEntry extends Model
{
    public const TYPE_CATALOG_SLOT = 'catalog_slot';
    public const TYPE_CATALOG_SLOT_REFUND = 'catalog_slot_refund';
    public const TYPE_PREMIUM = 'premium';
    public const TYPE_PREMIUM_RENEWAL = 'premium_renewal';

    protected $fillable = [
        'seller_id', 'amount', 'balance_after', 'type',
        'reference_type', 'reference_id', 'note',
    ];

    protected $casts = [
        'amount' => 'integer',
        'balance_after' => 'integer',
    ];

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class, 'seller_id');
    }

    /**
     * Balansni o'zgartirish + daftarga yozish. Chaqiruvchi tranzaksiya ichida
     * bo'lishi kerak; `$seller` yangi balans bilan saqlanadi.
     */
    public static function record(Seller $seller, int $amount, string $type, ?string $referenceType = null, ?int $referenceId = null, ?string $note = null): self
    {
        $seller->balance = (int) $seller->balance + $amount;
        $seller->save();

        return static::create([
            'seller_id' => $seller->id,
            'amount' => $amount,
            'balance_after' => (int) $seller->balance,
            'type' => $type,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'note' => $note,
        ]);
    }
}
