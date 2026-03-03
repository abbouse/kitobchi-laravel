<?php

namespace App\Services;

use Kreait\Laravel\Firebase\Facades\Firebase;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class FCMService
{
    protected $messaging;

    public function __construct(string $project = null)
    {
        $this->messaging = Firebase::project($project ?? config('firebase.default'))->messaging();
    }

    public function send(array $tokens, string $title, string $body, array $data = [])
    {
        $message = CloudMessage::new()
    ->withNotification(Notification::create($title, $body))
    ->withData($data)
    ->withHighestPossiblePriority()
    ->withAndroidConfig([
        'priority' => 'high',
        'notification' => [
            'channel_id' => 'high_importance_channel',
            'sound' => 'default',
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK'
        ]
    ])
    ->withApnsConfig([
        'headers' => ['apns-priority' => '10'],
        'payload' => [
            'aps' => [
                'alert' => ['title' => $title, 'body' => $body],
                'sound' => 'default',
                'badge' => 1,
                'content-available' => 1
            ]
        ]
    ]);

        try {
            $report = $this->messaging->sendMulticast($message, $tokens);

            $success = 0;
            $failure = 0;
            $invalidTokens = [];

            foreach ($report as $item) {
                if ($item->valid()) {
                    $success++;
                } else {
                    $failure++;
                    if ($item->target()->type() === 'token') {
                        $invalidTokens[] = $item->target()->value();
                    }
                }
            }

            return [
                'success' => $success,
                'failure' => $failure,
                'invalid_tokens' => $invalidTokens,
                'total' => count($tokens),
                'sent_at' => now()->toDateTimeString(),
            ];

        } catch (\Throwable $e) {
            \Log::error('FCM xatosi: ' . $e->getMessage());
            return [
                'error' => $e->getMessage(),
            ];
        }
    }
}