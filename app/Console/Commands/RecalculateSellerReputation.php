<?php

namespace App\Console\Commands;

use App\Models\Seller;
use App\Services\SellerReputationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RecalculateSellerReputation extends Command
{
    protected $signature = 'sellers:recalculate-reputation
        {--seller_id= : Faqat bitta seller uchun qayta hisoblaydi}
        {--dry-run : Natijani ko\'rsatadi, saqlamaydi}';

    protected $description = 'Seller public rating va internal reputation score ni operatsion omillar asosida qayta hisoblaydi.';

    public function handle(SellerReputationService $service): int
    {
        $sellerId = $this->option('seller_id');
        $dryRun = (bool) $this->option('dry-run');

        try {
            if ($sellerId) {
                $seller = Seller::query()->findOrFail((int) $sellerId);
                $result = $service->recalculateSeller($seller, !$dryRun);

                $this->table(
                    ['Seller', 'Public Rating', 'Internal Score', 'Completed'],
                    [[
                        $result['seller_id'],
                        number_format((float) $result['rating'], 2),
                        number_format((float) $result['reputation_score'], 2),
                        $result['rating_reviews_count'],
                    ]]
                );

                $this->line(json_encode($result['metrics'], JSON_UNESCAPED_UNICODE));
                $this->info($dryRun
                    ? 'Dry-run tugadi, saqlanmadi.'
                    : 'Seller reputatsiyasi yangilandi.');

                return self::SUCCESS;
            }

            $count = $service->recalculateAll(null, !$dryRun);
            $this->info($dryRun
                ? "{$count} ta seller reputatsiyasi hisoblandi, lekin saqlanmadi."
                : "{$count} ta seller reputatsiyasi yangilandi.");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            Log::error('sellers:recalculate-reputation', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
