<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SplitContract extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_OVERDUE = 'overdue';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_DEFAULTED = 'defaulted';

    protected $fillable = [
        'user_id',
        'order_id',
        'plan_id',
        'principal_amount',
        'interest_amount',
        'total_amount',
        'paid_amount',
        'remaining_amount',
        'months',
        'period_unit',
        'period_every',
        'monthly_interest_percent',
        'installments_count',
        'debit_day',
        'status',
        'starts_at',
        'activated_at',
        'overdue_since',
        'closed_at',
        'snapshot',
        'meta',
    ];

    protected $casts = [
        'monthly_interest_percent' => 'decimal:2',
        'starts_at' => 'datetime',
        'activated_at' => 'datetime',
        'overdue_since' => 'datetime',
        'closed_at' => 'datetime',
        'snapshot' => 'array',
        'meta' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SplitPlan::class, 'plan_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Sold::class, 'order_id');
    }

    public function installments(): HasMany
    {
        return $this->hasMany(SplitInstallment::class, 'contract_id')->orderBy('sequence');
    }

    public function isOpen(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_ACTIVE, self::STATUS_OVERDUE], true);
    }
}
