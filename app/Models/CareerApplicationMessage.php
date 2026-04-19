<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CareerApplicationMessage extends Model
{
    public const SENDER_ADMIN = 'admin';

    public const SENDER_SYSTEM = 'system';

    protected $fillable = [
        'career_application_id',
        'admin_id',
        'sender',
        'body',
    ];

    public function careerApplication(): BelongsTo
    {
        return $this->belongsTo(CareerApplication::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }
}
