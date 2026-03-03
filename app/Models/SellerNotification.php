<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SellerNotification extends Model
{
    use HasFactory;
    protected $fillable = [
        'seller_id', 'title', 'content', 'isRead'
    ];
    protected $casts = [
        'isRead' => 'boolean',
    ];
}
