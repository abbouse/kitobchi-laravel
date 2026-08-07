<?php

namespace App\Console\Commands;

use App\Models\BookClub;
use App\Models\BookClubComment;
use App\Models\BookClubNotification;
use Illuminate\Console\Command;

/**
 * Bir martalik backfill: bu o'zgarishdan OLDIN yaratilgan Book Club
 * bildirishnomalarida `data->target_text` yo'q (chunki o'sha paytda hali
 * yozilmagan edi). Shu buyruq mavjud bildirishnomalarni post_id/comment_id
 * bo'yicha tekshirib, tegishli post/izoh matnini orqaga qarab to'ldiradi —
 * shunda ro'yxatdagi ESKI bildirishnomalarda ham preview ko'rinadi.
 *
 * Ishlatish:  php artisan book-club:backfill-notification-text
 *   --force bilan target_text ALLAQACHON bor bo'lsa ham qayta yoziladi
 *   (masalan, post keyinchalik tahrirlangan bo'lsa, yangi matn bilan).
 */
class BackfillNotificationTargetText extends Command
{
    protected $signature = 'book-club:backfill-notification-text {--force : target_text mavjud bo\'lsa ham qayta yozib qo\'yish}';

    protected $description = "Mavjud Book Club bildirishnomalariga post/izoh matnini (target_text) orqaga qarab to'ldiradi.";

    public function handle(): int
    {
        $force = (bool) $this->option('force');

        $query = BookClubNotification::query()->where('type', '!=', 'follow');
        $total = $query->count();

        if ($total === 0) {
            $this->info("To'ldiriladigan bildirishnoma topilmadi.");
            return self::SUCCESS;
        }

        $this->info("{$total} ta bildirishnoma tekshirilmoqda...");

        $updated = 0;
        $skipped = 0;

        $query->chunkById(200, function ($notifications) use ($force, &$updated, &$skipped) {
            // Bitta chunk ichidagi barcha post/izoh ID'larini yig'ib,
            // ikkita so'rov bilan (har bildirishnoma uchun alohida emas —
            // N+1'siz) oldindan yuklab olamiz.
            $postIds = [];
            $commentIds = [];

            foreach ($notifications as $n) {
                $data = $n->data ?? [];
                if (!$force && !empty($data['target_text'])) {
                    continue;
                }

                if (in_array($n->type, ['comment_like', 'reply'], true) && !empty($data['comment_id'])) {
                    $commentIds[] = (int) $data['comment_id'];
                } elseif ($n->type === 'mention' && !empty($data['comment_id'])) {
                    $commentIds[] = (int) $data['comment_id'];
                } elseif ($n->post_id) {
                    $postIds[] = (int) $n->post_id;
                }
            }

            $posts = BookClub::whereIn('id', array_unique($postIds))->pluck('text', 'id');
            $comments = BookClubComment::whereIn('id', array_unique($commentIds))->pluck('content', 'id');

            foreach ($notifications as $n) {
                $data = $n->data ?? [];
                if (!$force && !empty($data['target_text'])) {
                    $skipped++;
                    continue;
                }

                // MUHIM QOIDA: comment_like/reply/mention(izohda) — IZOH
                // matni; like/vote/repost/new_post/comment/mention(postda)
                // — POST matni. Bu qoida BookClubNotificationTextService
                // qismida ishlatilgan mantiq bilan bir xil bo'lishi shart.
                if (in_array($n->type, ['comment_like', 'reply'], true) && !empty($data['comment_id'])) {
                    $targetText = $comments->get((int) $data['comment_id']);
                } elseif ($n->type === 'mention') {
                    $targetText = !empty($data['comment_id'])
                        ? $comments->get((int) $data['comment_id'])
                        : ($n->post_id ? $posts->get((int) $n->post_id) : null);
                } elseif ($n->post_id) {
                    $targetText = $posts->get((int) $n->post_id);
                } else {
                    $targetText = null;
                }

                if (empty($targetText)) {
                    $skipped++;
                    continue;
                }

                $data['target_text'] = $targetText;
                $n->update(['data' => $data]);
                $updated++;
            }
        });

        $this->info("Tayyor: {$updated} ta yangilandi, {$skipped} ta o'tkazib yuborildi (kontent topilmadi yoki allaqachon bor edi).");

        return self::SUCCESS;
    }
}
