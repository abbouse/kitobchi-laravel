<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstagramInquiry extends Model
{
    use HasFactory;

    protected $table = 'instagram_inquiries';

    protected $fillable = [
        'instagram_user_id',
        'username',
        'type',
        'message',
        'admin_reply',
        'status',
        'replied_at',
    ];

    protected $casts = [
        'replied_at' => 'datetime',
    ];
}
