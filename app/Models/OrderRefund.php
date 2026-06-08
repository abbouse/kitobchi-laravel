<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderRefund extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'seller_order_id',
        'seller_order_item_id',
        'seller_id',
        'user_id',
        'type',
        'provider',
        'card_refund_amount',
        'cashback_restore_amount',
        'gift_cert_restore_amount',
        'delivery_refund_amount',
        'packaging_refund_amount',
        'total_customer_value',
        'status',
        'provider_transaction_id',
        'receiver_card_ref',
        'reason_code',
        'reason_note_uz',
        'reason_note_ru',
        'reason_note_en',
        'reason_note_ja',
        'custom_reason_note',
        'provider_payload',
        'processed_by_seller_id',
        'processed_by_admin_id',
        'processed_at',
    ];

    protected $casts = [
        'provider_payload' => 'array',
        'processed_at' => 'datetime',
    ];
}
