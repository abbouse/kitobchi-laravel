<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FcmNotifications extends Model
{
    use HasFactory;

    protected $table = 'fcm_notifications';
    protected $fillable = [
        'title',
        'body',
        'who'
    ];
}
