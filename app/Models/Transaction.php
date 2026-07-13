<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Transaction extends Model
{
    use HasFactory;
    protected $fillable = [
        'owner_id',
        'provider',
        'provider_transaction_id',
        'provider_card_id',
        'paycom_transaction_id',
        'paycom_time',
        'paycom_time_datetime',
        'create_time',
        'perform_time',
        'cancel_time',
        'amount',
        'payment_type',
        'state',
        'reason',
        'receivers',
        'order_id',
        'payable_id',
        'perform_time_unix',
        'perform_fiscal_data',
        'cancel_fiscal_data',
        'provider_response',
    ];

    protected $casts = [
        'perform_fiscal_data' => 'array',
        'cancel_fiscal_data' => 'array',
        'provider_response' => 'array',
    ];
    public static function getTransactionsByTimeRange($from, $to)
    {
        return self::whereBetween('paycom_time', [$from, $to])
            ->get();
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function order()
    {
        return $this->belongsTo(Sold::class, 'order_id');
    }
}
