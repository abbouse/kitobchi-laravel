<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserInterestProfile extends Model
{
    protected $fillable = [
        'user_id',
        'profile',
        'signals',
        'views_count',
        'confidence_score',
        'last_viewed_at',
        'generated_at',
    ];

    protected $casts = [
        'profile' => 'array',
        'signals' => 'array',
        'views_count' => 'integer',
        'confidence_score' => 'float',
        'last_viewed_at' => 'datetime',
        'generated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
