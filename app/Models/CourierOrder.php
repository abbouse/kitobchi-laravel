<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourierOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'courier_id',
        'order_id',
        'user_id',
        'status',
        'amount',
        'courierPrice'
    ];

    public function items()
    {
        return $this->hasMany(CourierOrderItem::class, 'order_id', 'order_id');
    }
    public function customer()
    {
        return $this->belongsTo(User::class, 'user_id')
        ->select('id', 'name', 'lastname', 'avatar', 'phone_number', 'mainAddressID');
    }
    public function paymentStatus()
    {
        return $this->belongsTo(Sold::class, 'order_id')
        ->select('id', 'paymentStatus');
    }
}
