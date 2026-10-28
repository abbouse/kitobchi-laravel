<?php

namespace App\Services\KitobchiId;

use App\Events\ConversationUpdated;
use App\Events\MessageSent;
use App\Http\Controllers\PushController;
use App\Models\Conversation;
use App\Models\KitobchiId\IdCodeDelivery;
use App\Models\Message;
use App\Models\Seller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Kirish kodini SMS o'rniga Kitobchi ilovasidagi rasmiy (seller #1) chatga yuboradi.
 *
 * Xavfsizlik:
 *  - `messages` jadvaliga va websocket'ga faqat YASHIRILGAN matn yoziladi — seller
 *    paneli, boshqaruv va chat kanali kodni ko'rmaydi;
 *  - kod `id_code_deliveries` da APP_KEY bilan shifrlangan, faqat chat egasi
 *    (delivery.user_id) o'z ilovasida ko'radi; ishlatilgach yoki muddati o'tgach o'chiriladi;
 *  - kod faqat shu foydalanuvchining FAOL sessiyasi bor qurilmalariga (push) ketadi.
 */
class AppCodeChannel
{
    public const OFFICIAL_SELLER_ID = 1;

    public const ACTIVE_DAYS = 30;

    /** Foydalanuvchining Kitobchi ilovasi faol bo'lgan qurilmalari push tokenlari. */
    public function activeTokens(User $user): array
    {
        try {
            $since = now()->subDays(self::ACTIVE_DAYS);

            return DB::table('connected_devices as d')
                ->join('personal_access_tokens as t', function ($j) use ($user) {
                    $j->on('t.token', '=', 'd.token')
                        ->where('t.tokenable_id', '=', $user->id)
                        ->where('t.tokenable_type', '=', $user->getMorphClass());
                })
                ->where('d.user_id', $user->id)
                ->where(fn ($q) => $q->where('d.user_type', 'user')->orWhereNull('d.user_type'))
                ->whereNotNull('d.fcm_token')
                ->where('d.fcm_token', '!=', '')
                ->where(fn ($q) => $q->where('t.last_used_at', '>=', $since)
                    ->orWhere(fn ($q) => $q->whereNull('t.last_used_at')->where('t.created_at', '>=', $since)))
                ->where(fn ($q) => $q->whereNull('t.expires_at')->orWhere('t.expires_at', '>', now()))
                ->pluck('d.fcm_token')
                ->map(fn ($t) => trim((string) $t))
                ->filter()
                // Token boshqa akkauntga o'tib ketgan bo'lsa yubormaymiz
                ->filter(fn ($t) => (int) DB::table('connected_devices')->where('fcm_token', $t)
                    ->where(fn ($q) => $q->where('user_type', 'user')->orWhereNull('user_type'))
                    ->orderByDesc('updated_at')->orderByDesc('id')->value('user_id') === (int) $user->id)
                ->unique()
                ->values()
                ->all();
        } catch (Throwable $e) {
            Log::warning('kitobchi-id: active device lookup failed', ['error' => $e->getMessage()]);

            return [];
        }
    }

    public const DELIVERED_PUSH = 'push';

    public const DELIVERED_CHAT = 'chat';

    /**
     * Kodni chatga yozadi va push yuboradi.
     *
     * @return string|null `push` — chatga yozildi va push kamida bitta qurilmaga ketdi;
     *                     `chat` — chatga yozildi, lekin push yetmadi (kod baribir chatda);
     *                     null — ilovaga umuman yetkazilmadi.
     */
    public function deliver(User $user, string $code, string $clientName, int $ttl): ?string
    {
        $tokens = $this->activeTokens($user);
        if (! $tokens) {
            return null;
        }

        try {
            [$conversation, $message, $delivery] = DB::transaction(function () use ($user, $code, $clientName, $ttl) {
                $conversation = $this->officialConversation($user);

                $message = $conversation->messages()->create([
                    'sender_id' => self::OFFICIAL_SELLER_ID,
                    'sender_type' => Seller::class,
                    'message' => self::maskedText($clientName),
                    'is_read' => 0,
                    'is_edited' => 0,
                    'is_deleted' => 0,
                ]);
                $conversation->update(['last_message_at' => now(), 'hidden_by' => null]);

                // Oldingi ishlatilmagan kodlar bekor (bir vaqtda faqat bitta faol kod)
                IdCodeDelivery::where('user_id', $user->id)->whereNull('used_at')->whereNotNull('code_enc')
                    ->update(['code_enc' => null, 'expires_at' => now()]);

                $delivery = IdCodeDelivery::create([
                    'user_id' => $user->id,
                    'conversation_id' => $conversation->id,
                    'message_id' => $message->id,
                    'client_name' => mb_substr($clientName, 0, 120),
                    'code_enc' => Crypt::encryptString($code),
                    'expires_at' => now()->addSeconds($ttl),
                ]);

                return [$conversation, $message, $delivery];
            });
        } catch (Throwable $e) {
            Log::warning('kitobchi-id: app delivery failed', ['error' => $e->getMessage()]);

            return null;
        }

        $this->broadcast($conversation, $message);

        return $this->push($tokens, $conversation, $code, $clientName) ? self::DELIVERED_PUSH : self::DELIVERED_CHAT;
    }

    /** Kod tasdiqlangach chatdagi kodni yashirish. */
    public function markUsed(int $userId): void
    {
        try {
            IdCodeDelivery::where('user_id', $userId)->whereNull('used_at')
                ->update(['used_at' => now(), 'code_enc' => null]);
        } catch (Throwable $e) {
            Log::warning('kitobchi-id: markUsed failed', ['error' => $e->getMessage()]);
        }
    }

    /** Muddati o'tgan kodlarni bazadan tozalash (prune buyrug'i chaqiradi). */
    public static function scrubExpired(): int
    {
        return IdCodeDelivery::whereNotNull('code_enc')->where('expires_at', '<', now())->update(['code_enc' => null]);
    }

    /**
     * Ilova uchun xabarga qo'shiladigan ma'lumot (faqat chat EGASIGA).
     *
     * @param  array<int>  $messageIds
     * @return array<int, array> message_id => payload
     */
    public static function payloadsFor(array $messageIds, int $viewerId): array
    {
        if (! $messageIds || ! Schema::hasTable('id_code_deliveries')) {
            return [];
        }

        $out = [];
        foreach (IdCodeDelivery::whereIn('message_id', $messageIds)->get() as $d) {
            if ((int) $d->user_id !== $viewerId) {
                continue; // do'kon egasi/xodimi user API orqali ham kodni ko'rmaydi
            }
            $status = $d->used_at ? 'used' : ($d->expires_at->isPast() || ! $d->code_enc ? 'expired' : 'active');
            $code = null;
            if ($status === 'active') {
                try {
                    $code = Crypt::decryptString($d->code_enc);
                } catch (Throwable) {
                    $status = 'expired';
                }
            }
            $out[(int) $d->message_id] = [
                'type' => 'login_code',
                'status' => $status,
                'code' => $code,
                'client_name' => $d->client_name,
                'expires_at' => $d->expires_at->toIso8601String(),
            ];
        }

        return $out;
    }

    public static function maskedText(string $clientName): string
    {
        return "🔐 Kitobchi ID: «{$clientName}» ga kirish kodi.\n"
            ."Kod: ••••••\n\n"
            ."Kodni hech kimga bermang — Kitobchi xodimlari ham uni hech qachon so'ramaydi. "
            ."Agar siz kirmoqchi bo'lmagan bo'lsangiz, bu xabarga e'tibor bermang.";
    }

    private function officialConversation(User $user): Conversation
    {
        $conversation = Conversation::query()
            ->where('type', 'shop')
            ->where('user_id', $user->id)
            ->where('shop_id', self::OFFICIAL_SELLER_ID)
            ->lockForUpdate()
            ->first();

        return $conversation ?? Conversation::create([
            'type' => 'shop',
            'user_id' => $user->id,
            'shop_id' => self::OFFICIAL_SELLER_ID,
            'last_message_at' => now(),
        ]);
    }

    private function broadcast(Conversation $conversation, Message $message): void
    {
        try {
            broadcast(new MessageSent($message));
            broadcast(new ConversationUpdated($conversation, (int) $conversation->user_id, 'user'));
        } catch (Throwable $e) {
            Log::info('kitobchi-id: broadcast skipped', ['error' => $e->getMessage()]);
        }
    }

    /** Push faqat foydalanuvchining o'z qurilmalariga — matnida kod bor (SMS kabi). */
    protected function push(array $tokens, Conversation $conversation, string $code, string $clientName): bool
    {
        try {
            $response = app(PushController::class)->sendPush(new Request([
                'secret' => PushController::sharedSecret(),
                'app_key' => 'kitobchi',
                'title' => 'Kitobchi ID',
                'body' => "Kirish kodi: {$code} — «{$clientName}». Kodni hech kimga bermang.",
                'tokens' => $tokens,
                'data' => [
                    'type' => 'chat',
                    'kind' => 'kitobchi_id_code',
                    'conversation_id' => (string) $conversation->id,
                    'conversation_type' => 'shop',
                    'shop_id' => (string) self::OFFICIAL_SELLER_ID,
                    'other_party_name' => 'Kitobchi',
                ],
            ]));
            $data = method_exists($response, 'getData') ? (array) $response->getData(true) : [];
            $ok = (bool) ($data['success'] ?? false);
            if (! $ok) {
                // Sababini ko'rish uchun: FCM javobi (kod matni log'ga yozilmaydi)
                Log::warning('kitobchi-id: push not delivered', [
                    'conversation_id' => $conversation->id,
                    'tokens' => count($tokens),
                    'sent' => $data['sent'] ?? null,
                    'failed' => $data['failed'] ?? null,
                    'invalid_tokens' => count((array) ($data['invalid_tokens'] ?? [])),
                    'message' => $data['message'] ?? null,
                ]);
            }

            // Push yetmasa — chaqiruvchi SMS ham yuboradi (kod chatda baribir turadi)
            return $ok;
        } catch (Throwable $e) {
            Log::warning('kitobchi-id: push failed', ['error' => $e->getMessage()]);

            return false;
        }
    }
}
