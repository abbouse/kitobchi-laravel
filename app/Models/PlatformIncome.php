<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformIncome extends Model
{
    public const TYPES = [
        'order_payment'   => 'Buyurtma tolovi',
        'courier_rent'    => 'Kuryer arenda tolovi',
        'delivery_income' => 'Yetkazib berish tushumi',
        'other'           => 'Boshqa tushum',
    ];

    protected $fillable = [
        'type',
        'amount',
        'received_at',
        'title',
        'note',
        'order_id',
        'courier_id',
        'reference',
        'created_by',
        'telegram_user_id',
        'telegram_username',
        'telegram_name',
        'telegram_message_id',
        'source',
    ];

    protected $casts = [
        'amount'      => 'integer',
        'received_at' => 'date',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Sold::class, 'order_id');
    }

    public function courier(): BelongsTo
    {
        return $this->belongsTo(Couriers::class, 'courier_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }
}

