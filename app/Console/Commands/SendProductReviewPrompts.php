<?php

namespace App\Console\Commands;

use App\Models\ProductReviewPrompt;
use App\Services\ProductReviewPromptService;
use App\Services\ProductReviewReminderPushService;
use Illuminate\Console\Command;

class SendProductReviewPrompts extends Command
{
    protected $signature = 'products:send-review-prompts';
    protected $description = 'Sotib olingan mahsulotlar uchun review eslatma pushlarini yuborish';

    public function handle(
        ProductReviewPromptService $promptService,
        ProductReviewReminderPushService $pushService,
    ): int {
        $promptService->syncRecentCompletedOrders(14);

        $now = now();
        $sentUsers = [];
        $sent = 0;
        $closed = 0;
        $skipped = 0;

        foreach ($promptService->duePrompts($now) as $prompt) {
            if (isset($sentUsers[$prompt->user_id])) {
                $skipped++;
                continue;
            }

            if ($promptService->hasReviewedProduct(
                (int) $prompt->user_id,
                (int) $prompt->product_id,
                (string) $prompt->product_type,
            )) {
                $prompt->forceFill([
                    'closed_at' => $now,
                    'close_reason' => 'review_posted',
                ])->save();
                $closed++;
                continue;
            }

            if (!$promptService->isProductStillPublic((int) $prompt->product_id, (string) $prompt->product_type)) {
                $prompt->forceFill([
                    'closed_at' => $now,
                    'close_reason' => 'product_unavailable',
                ])->save();
                $closed++;
                continue;
            }

            if (!$pushService->send($prompt)) {
                $skipped++;
                continue;
            }

            if ($prompt->first_sent_at === null) {
                $prompt->first_sent_at = $now;
            } else {
                $prompt->second_sent_at = $now;
                $prompt->closed_at = $now;
                $prompt->close_reason = 'sent_twice';
            }

            $prompt->save();
            $sentUsers[$prompt->user_id] = true;
            $sent++;
        }

        ProductReviewPrompt::query()
            ->whereNull('closed_at')
            ->whereNotNull('first_sent_at')
            ->whereNotNull('second_sent_at')
            ->update([
                'closed_at' => $now,
                'close_reason' => 'sent_twice',
                'updated_at' => $now,
            ]);

        $this->info("Review prompts: sent={$sent}, closed={$closed}, skipped={$skipped}");

        return self::SUCCESS;
    }
}
