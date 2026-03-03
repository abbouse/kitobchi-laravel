<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourierNotification extends Model
{
    use HasFactory;
    protected $fillable = [
        'courier_id', 'title', 'content', 'isRead'
    ];
    protected $casts = [
        'isRead' => 'boolean',
    ];
}
