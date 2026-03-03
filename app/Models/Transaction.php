<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Transaction extends Model
{
    use HasFactory;
    protected $fillable = [
    'paycom_transaction_id',
    'paycom_time',
    'paycom_time_datetime',
    'create_time',
    'perform_time',
    'cancel_time',
    'amount',
    'state',
    'reason',
    'receivers',
    'order_id',
    'perform_time_unix',
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
}