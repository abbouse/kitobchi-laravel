<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminAuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'admin_id',
        'admin_name',
        'method',
        'route_name',
        'path',
        'action',
        'target_type',
        'target_id',
        'request_data',
        'ip_address',
        'user_agent',
        'status_code',
    ];

    protected $casts = [
        'request_data' => 'array',
        'status_code' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
