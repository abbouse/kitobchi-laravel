<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HubApplication extends Model
{
    public const STATUS_NEW = 'new';
    public const STATUS_REVIEWED = 'reviewed';
    public const STATUS_CONTACTED = 'contacted';
    public const STATUS_CLOSED = 'closed';

    public const STATUSES = [
        self::STATUS_NEW,
        self::STATUS_REVIEWED,
        self::STATUS_CONTACTED,
        self::STATUS_CLOSED,
    ];

    public const TASHKENT_OPTIONS = ['yes', 'no', 'unsure'];

    protected $fillable = [
        'full_name',
        'phone',
        'region',
        'position',
        'tashkent_availability',
        'status',
        'note',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];
}
