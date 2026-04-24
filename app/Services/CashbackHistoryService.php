<?php

namespace App\Services;

use App\Models\CashbackHistory;
use App\Models\Sold;
use Illuminate\Support\Facades\DB;

class CashbackHistoryService
{
    public function record(
        int $userId,
        string $action,
        int $amount,
        ?Sold $order = null,
        ?int $balanceBefore = null,
        ?int $balanceAfter = null,
        array $meta = [],
    ): CashbackHistory {
        $balanceAfter ??= (int) DB::table('users')->where('id', $userId)->value('cashback');
        $balanceBefore ??= $balanceAfter - $amount;

        return CashbackHistory::create([
            'user_id' => $userId,
            'sold_id' => $order?->id,
            'action' => $action,
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'meta' => empty($meta) ? null : $meta,
        ]);
    }
}
