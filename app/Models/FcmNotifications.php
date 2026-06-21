<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FcmNotifications extends Model
{
    use HasFactory;

    /**
     * DB ustunlari bilan aynan mos kelishi kerak. Ilgari bu yerda
     * `title`/`body` yozilgan edi, lekin schema'da `name`/`description`
     * bor — natijada mass-assignment ularni indamay tashlab yuborib,
     * "Field 'name' doesn't have a default value" xatosini keltirib
     * chiqarar edi.
     */
    protected $fillable = [
        'name',
        'description',
        'who',
        'source',
        'delivery_status',
        'sent_count',
        'failed_count',
        'is_read',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'sent_count' => 'integer',
        'failed_count' => 'integer',
    ];
}
