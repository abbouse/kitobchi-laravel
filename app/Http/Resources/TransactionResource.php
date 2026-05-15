<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $providerTransactionId = $this->provider_transaction_id ?: $this->paycom_transaction_id;
        $createTime = $this->perform_time_unix ?: $this->paycom_time;

        return [
            'id' => $providerTransactionId,
            'provider' => $this->provider ?: 'paylov',
            'time' => $createTime,
            'amount' => $this->amount,
            'account' => [
                'order_id' => $this->order_id,
                'payment_type' => $this->payment_type,
            ],
            'create_time' => intval($createTime),
            'perform_time' => intval($this->perform_time_unix),
            'cancel_time' => intval($this->cancel_time ?? 0),
            'transaction' => $this->id,
            'state' => $this->state,
            'reason' => $this->reason
        ];
    }
}
