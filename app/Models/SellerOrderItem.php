<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SellerOrderItem extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (SellerOrderItem $item): void {
            if ($item->type === 'gift' && (int) $item->seller_id === 1) {
                throw new \LogicException(
                    "Kitobchi platforma sovg'asi seller order itemiga qo'shilmaydi."
                );
            }
        });
    }

    protected $fillable = [
        'seller_id', 'order_id', 'product_id', 'variant_id', 'type', 'quantity', 'price',
        'cancelled_at', 'cancelled_by_seller_id', 'cancel_reason_code',
        'cancel_note_uz', 'cancel_note_ru', 'cancel_note_en', 'cancel_note_ja',
        'custom_cancel_note', 'refund_status', 'refunded_at',
        'cancel_requested_at', 'cancel_restore_until',
    ];

    protected $casts = [
        'cancelled_at' => 'datetime',
        'refunded_at' => 'datetime',
        'cancel_requested_at' => 'datetime',
        'cancel_restore_until' => 'datetime',
    ];

    // withAvailableTotal — toArray() `count`/`stock` accessorlari uchun stockni
    // eager subselect bilan yuklaydi (buyurtma ro'yxatlarida N+1 bo'lmaydi).
    public function book()
    {
        return $this->belongsTo(Books::class, 'product_id')->withAvailableTotal();
    }

    public function stationery()
    {
        return $this->belongsTo(Stationery::class, 'product_id')->withAvailableTotal();
    }

    public function variant()
    {
        return $this->belongsTo(StationeryVariant::class, 'variant_id')->withAvailableTotal();
    }

    public function gift()
    {
        return $this->belongsTo(Gifts::class, 'product_id')->withAvailableTotal();
    }

    public function order()
    {
        return $this->belongsTo(SellerOrder::class, 'order_id');
    }

    /**
     * 🔥 TYPE ga qarab to‘g‘ri product qaytaradi
     */
    public function getProductAttribute()
    {
        $product = match ($this->type) {
            'book' => $this->book,
            'stationery' => $this->stationery,
            'gift' => $this->gift,
            default => null,
        };

        return [
            'parent' => $product,
            'variant' => $this->variant,
        ];
    }
}
