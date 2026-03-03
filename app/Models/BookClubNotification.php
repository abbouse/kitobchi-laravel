<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookClubNotification extends Model
{
    
    protected $fillable = [
        'user_id', 'type', 'post_id', 'group_key', 'data', 'is_read'
    ];

    protected $casts = [
        'data' => 'array', // JSONni avtomatik arrayga o'giradi
    ];
}