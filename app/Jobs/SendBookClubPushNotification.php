<?php

namespace App\Jobs;

use App\Models\BookClubNotification;
use App\Models\User;
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
        // 1. Bildirishnomani yuklash
        $n = BookClubNotification::find($this->notificationId);

        // 2. Tekshiruv: O'qilgan bo'lsa yoki topilmasa push yubormaymiz
        if (!$n || $n->is_read) return;

        $receiver = User::find($n->user_id);
        if (!$receiver || !$receiver->fcm_token) return;

        $data = $n->data;
        $senderName = $data['last_user_name'] ?? "Kimdir";
        $count = $data['count'] ?? 1;

        // 3. Xabar matnini tayyorlash (Match orqali)
        $title = "Kitobchi";
        $body = match($n->type) {
            'like' => ($count > 1) 
                ? "$senderName va yana " . ($count - 1) . " kishi postingizga like bosdi" 
                : "$senderName postingizga like bosdi",
            'new_post' => "$senderName yangi post qoldirdi",
            'follow' => "$senderName sizga obuna bo'ldi",
            'comment' => "$senderName postingizga izoh qoldirdi",
            default => "Sizda yangi bildirishnoma bor"
        };

        // 4. Push so'rovini yuborish
        $pushRequest = new Request([
            'app_key' => 'kitobchi',
            'title'   => $title,
            'body'    => $body,
            'tokens'  => [$receiver->fcm_token],
            'data'    => [
                'type' => 'book_club_notification',
                'notification_type' => $n->type,
                'post_id' => $n->post_id,
                'sender_avatar' => $data['last_user_avatar'] ?? null,
            ]
        ]);

        app(PushController::class)->sendPush($pushRequest);
    }
}