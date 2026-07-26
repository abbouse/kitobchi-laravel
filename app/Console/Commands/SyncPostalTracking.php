<?php

namespace App\Console\Commands;

use App\Enums\OrderStatusCode;
use App\Models\OrderFulfillment;
use App\Services\PostalTrackingService;
use Illuminate\Console\Command;

class SyncPostalTracking extends Command
{
    protected $signature = 'postal:sync-tracking
        {--limit=250 : Bir ishga tushishda tekshiriladigan maksimal jo‘natma}
        {--force : Tracking keshini kutmasdan providerga so‘rov yuborish}';

    protected $description = 'Faol pochta buyurtmalarining tracking tarixi va ichki statusini yangilaydi.';

    public function handle(PostalTrackingService $trackingService): int
    {
        $limit = max(1, min(2000, (int) $this->option('limit')));
        $force = (bool) $this->option('force');

        $fulfillments = OrderFulfillment::query()
            ->with('order')
            ->whereNotNull('postal_provider')
            ->where('postal_provider', '!=', '')
            ->whereNotNull('postal_tracking_number')
            ->where('postal_tracking_number', '!=', '')
            ->whereHas('order', function ($query) {
                $query->where(function ($statuses) {
                    $statuses
                        ->where(function ($normalized) {
                            $normalized->whereNotNull('status_code')
                                ->whereNotIn('status_code', [
                                    OrderStatusCode::CUSTOMER_RECEIVED->value,
                                    OrderStatusCode::CANCELLED->value,
                                    OrderStatusCode::RETURNED->value,
                                ]);
                        })
                        ->orWhere(function ($legacy) {
                            $legacy->whereNull('status_code')
                                ->whereNotIn('status', [
                                    OrderStatusCode::CUSTOMER_RECEIVED->legacy(),
                                    OrderStatusCode::CANCELLED->legacy(),
                                    'R',
                                    OrderStatusCode::CUSTOMER_RECEIVED->value,
                                    OrderStatusCode::CANCELLED->value,
                                    OrderStatusCode::RETURNED->value,
                                ]);
                        });
                });
            })
            ->orderBy('updated_at')
            ->orderBy('id')
            ->limit($limit)
            ->get();

        $checked = 0;
        $advanced = 0;

        foreach ($fulfillments as $fulfillment) {
            $before = $fulfillment->order?->status_code;
            $trackingService->sync($fulfillment, $force);
            $after = $fulfillment->order?->fresh()?->status_code ?? $before;

            $checked++;
            if ($before !== $after) {
                $advanced++;
            }
        }

        $this->info("Tekshirildi: {$checked}; ichki statusi yangilandi: {$advanced}.");

        return self::SUCCESS;
    }
}
