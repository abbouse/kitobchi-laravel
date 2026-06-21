<?php

namespace App\Jobs;

use App\Models\BookClubNotification;
use App\Models\User;
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

        $receiver = User::find($n->user_id);
        if (!$receiver) return;

        $tokens = app(FcmRecipientService::class)->tokensFor('user', (int) $receiver->id);
        if (empty($tokens)) return;

        $data = $n->data;
        $formatter = app(BookClubNotificationTextService::class);
        $formatted = $formatter->format($n, $receiver->locale ?? 'uz');

        $pushRequest = new Request([
            'app_key' => 'kitobchi',
            'title'   => $formatted['title'],
            'body'    => $formatted['body'],
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
}
