<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SplitEvent extends Model
{
    protected $fillable = [
        'contract_id',
        'installment_id',
        'user_id',
        'type',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public static function record(
        string $type,
        ?int $contractId = null,
        ?int $installmentId = null,
        ?int $userId = null,
        array $payload = [],
    ): void {
        try {
            static::query()->create([
                'type' => $type,
                'contract_id' => $contractId,
                'installment_id' => $installmentId,
                'user_id' => $userId,
                'payload' => $payload,
            ]);
        } catch (\Throwable) {
            // Audit yozuvi asosiy oqimni to'xtatmasligi kerak.
        }
    }
}
