<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SplitInstallment extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_PAID = 'paid';

    public const STATUS_OVERDUE = 'overdue';

    public const STATUS_WAIVED = 'waived';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'contract_id',
        'user_id',
        'sequence',
        'amount',
        'paid_amount',
        'is_upfront',
        'due_at',
        'paid_at',
        'status',
        'attempt_count',
        'last_attempt_at',
        'next_attempt_at',
        'transaction_id',
        'provider_transaction_id',
        'meta',
    ];

    protected $casts = [
        'is_upfront' => 'boolean',
        'due_at' => 'datetime',
        'paid_at' => 'datetime',
        'last_attempt_at' => 'datetime',
        'next_attempt_at' => 'datetime',
        'meta' => 'array',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(SplitContract::class, 'contract_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    }
}
