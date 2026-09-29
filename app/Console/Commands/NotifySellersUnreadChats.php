<?php

namespace App\Console\Commands;

use App\Models\ConnectedDevice;
use App\Models\Seller;
use App\Services\FCMService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Do'konlarga javobsiz mijoz xabarlari haqida jamlangan push.
 *
 * Har xabarga alohida push o'rniga kuniga 3 marta (10:05, 14:05, 19:05)
 * faqat o'qilmagan xabar bo'lsa bitta xabar: "3 ta mijozdan 5 ta savol".
 */
class NotifySellersUnreadChats extends Command
{
    protected $signature = 'chats:notify-sellers-unread {--days=14 : Shu kunlardagi xabarlar hisobga olinadi}';

    protected $description = "Do'konlarga o'qilmagan mijoz xabarlari haqida jamlangan push yuboradi";

    public function handle(): int
    {
        $since = now()->subDays(max(1, (int) $this->option('days')));

        $rows = DB::table('messages')
            ->join('conversations', 'conversations.id', '=', 'messages.conversation_id')
            ->where('conversations.type', 'shop')
            ->whereNotNull('conversations.shop_id')
            ->whereColumn('messages.sender_id', 'conversations.user_id')
            ->where(fn ($q) => $q->whereNull('messages.sender_type')->orWhere('messages.sender_type', \App\Models\User::class))
            ->where('messages.is_read', 0)
            ->where(fn ($q) => $q->whereNull('messages.is_deleted')->orWhere('messages.is_deleted', 0))
            ->where('messages.created_at', '>=', $since)
            ->groupBy('conversations.shop_id')
            ->selectRaw('conversations.shop_id as shop_id, COUNT(*) as messages_count, COUNT(DISTINCT conversations.user_id) as customers_count')
            ->get();

        $sent = 0;
        foreach ($rows as $row) {
            $shopId = (int) $row->shop_id;
            $tokens = ConnectedDevice::query()
                ->where('user_type', 'seller')
                ->whereIn('user_id', Seller::query()
                    ->where('id', $shopId)
                    ->orWhere('parent_id', $shopId)
                    ->pluck('id'))
                ->whereNotNull('fcm_token')
                ->where('fcm_token', '!=', '')
                ->pluck('fcm_token')
                ->unique()
                ->values()
                ->all();
            if ($tokens === []) {
                continue;
            }

            $customers = (int) $row->customers_count;
            $messages = (int) $row->messages_count;
            $title = 'Mijozlar javobingizni kutmoqda';
            $body = $customers > 1
                ? "{$customers} ta mijozdan {$messages} ta javobsiz xabar bor."
                : "Mijozdan {$messages} ta javobsiz xabar bor.";

            try {
                (new FCMService('business'))->send($tokens, $title, $body, [
                    'type' => 'chat_unread_digest',
                    'shop_id' => (string) $shopId,
                    'unread_messages' => (string) $messages,
                    'unread_customers' => (string) $customers,
                ]);
                $sent++;
            } catch (\Throwable $e) {
                Log::warning('Unread chat digest push failed', ['shop_id' => $shopId, 'error' => $e->getMessage()]);
            }
        }

        $this->info("Unread chat digests sent: {$sent}");

        return self::SUCCESS;
    }
}
