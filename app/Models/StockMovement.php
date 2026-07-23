<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Inventar ledger — append-only. Har stock o'zgarishi: kim, nega, qancha.
 */
class StockMovement extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'branch_stock_id',
        'delta',
        'quantity_after',
        'reason',
        'actor_type',
        'actor_id',
        'ref_type',
        'ref_id',
        'note',
    ];

    protected $casts = [
        'delta' => 'integer',
        'quantity_after' => 'integer',
    ];

    public function branchStock(): BelongsTo
    {
        return $this->belongsTo(BranchStock::class);
    }
}
