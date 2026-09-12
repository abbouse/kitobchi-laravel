<?php

namespace App\Jobs;

use App\Models\BookClub;
use App\Models\BookClubNotification;
use App\Models\User;
use App\Services\BookClubAiScoringService;
use App\Services\BookClubNotificationTextService;
use App\Services\FcmRecipientService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Http\Controllers\PushController;
use Illuminate\Http\Request;

class SendBookClubPushNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $notificationId;

    public function __construct($notificationId)
    {
        $this->notificationId = $notificationId;
    }

    public function handle()
    {
        $n = BookClubNotification::find($this->notificationId);
        if (!$n || $n->is_read) return;

        // YANGI QOIDA (2026-09-12): "yangi post" turidagi push AI sifat bahosi
        // past bo'lsa YUBORILMAYDI — lekin post o'zi baribir sayt/ilovada
        // ko'rinishda qoladi (AI postni yashira olmaydi, faqat SHU push'ni
        // to'xtata oladi). Bu job navbatda ishlagani uchun (foydalanuvchi
        // so'rovini bloklamaydi) baholashni shu yerda, kerak bo'lsa, darhol
        // qilamiz — kunlik `openai:score-book-club-content` jadvaliga
        // qaramaydi.
        if ($n->type === 'new_post' && $n->post_id && $this->shouldSuppressForLowAiScore((int) $n->post_id)) {
            return;
        }

        $receiver = User::find($n->user_id);
        if (!$receiver) return;

        $tokens = app(FcmRecipientService::class)->tokensFor('user', (int) $receiver->id);
        if (empty($tokens)) return;

        $data = $n->data;
        $formatter = app(BookClubNotificationTextService::class);
        $formatted = $formatter->format($n, $receiver->locale ?? 'uz');

        // Push xabarida ham qisqa ko'rinish bo'lsin — OS bildirishnomani
        // o'zi qatorlarga qarab kesadi, shuning uchun bu yerda faqat
        // matnni biriktiramiz, qo'shimcha kesish shart emas.
        $body = trim($formatted['body'] . ' ' . ($formatted['preview'] ?? ''));

        $pushRequest = new Request([
            'app_key' => 'kitobchi',
            'title'   => $formatted['title'],
            'body'    => $body,
            'tokens'  => $tokens,
            'data'    => [
                'type' => 'book_club_notification',
                'notification_type' => $n->type,
                'notification_id' => $n->id,
                'post_id' => $n->post_id,
                'sender_user_id' => $data['last_user_id'] ?? null,
                'sender_avatar' => $data['last_user_avatar'] ?? null,
            ]
        ]);

        app(PushController::class)->sendPush($pushRequest);
    }

    /**
     * Post hali AI tomonidan baholanmagan bo'lsa, shu yerda (navbatda,
     * foydalanuvchini kutdirmasdan) baholaymiz, so'ng natijani chegara bilan
     * solishtiramiz. AI xato bersa yoki baho hali yo'q bo'lsa — fail-open,
     * ya'ni push YUBORILADI (postni bekorga jimlashtirmaslik uchun).
     */
    private function shouldSuppressForLowAiScore(int $postId): bool
    {
        $post = BookClub::query()->find($postId);
        if (!$post || $post->is_deleted) {
            return false;
        }

        if ($post->ai_post_score === null) {
            try {
                app(BookClubAiScoringService::class)->scorePosts(collect([$post]));
                $post = $post->fresh();
            } catch (\Throwable $e) {
                // Baholay olmadik — fail-open, push to'xtatilmaydi.
                return false;
            }
        }

        if (!$post || $post->ai_post_score === null) {
            return false;
        }

        $minScore = (float) config('book_club_moderation.push_min_score', 3.0);

        return (float) $post->ai_post_score < $minScore;
    }
}
