<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductReviewPrompt extends Model
{
    use HasFactory;

    protected $fillable = [
        'sold_id',
        'user_id',
        'product_id',
        'product_type',
        'product_name',
        'first_due_at',
        'second_due_at',
        'first_sent_at',
        'second_sent_at',
        'closed_at',
        'close_reason',
    ];

    protected $casts = [
        'first_due_at' => 'datetime',
        'second_due_at' => 'datetime',
        'first_sent_at' => 'datetime',
        'second_sent_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Sold::class, 'sold_id');
    }

    public function isClosed(): bool
    {
        return $this->closed_at !== null;
    }
}
