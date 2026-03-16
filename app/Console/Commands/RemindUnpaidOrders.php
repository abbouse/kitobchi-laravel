<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Sold;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request as HttpRequest;

class RemindUnpaidOrders extends Command
{
    protected $signature   = 'orders:remind-unpaid';
    protected $description = "To'lanmagan buyurtmalar uchun hazil-mutoyiba push eslatma yuborish (har 10 daqiqada chaqiriladi)";

    // =========================================================================
    //  KO'P TILLIK HAZIL-MUTOYIBA XABARLARI
    //  Har safar turli xabar ko'rinishi uchun massiv — vaqtga qarab tanlanadi
    // =========================================================================

    private const REMINDERS = [
        'uz' => [
            [
                'title' => "💳 #{id}-buyurtma sizni kutmoqda!",
                'body'  => "To'lovni unutdingizmi? Buyurtmangiz stol ustida yig'lab o'tiribdi. Muammo bo'lsa — bizga yozing! 😅",
            ],
            [
                'title' => "🛒 Hoy, #{id}-buyurtma qayerdasiz?",
                'body'  => "Buyurtmangiz to'lovni kutib, uyqusini yo'qotdi. Agar karta bilan muammo bo'lsa — biz yordam beramiz! 😄",
            ],
            [
                'title' => "⏰ #{id}-buyurtma hali to'lanmagan",
                'body'  => "Kitoblaringiz qo'llaringizni sog'inmoqda! To'lovda qiyinchilik bo'lsa, bizga xabar bering 😊",
            ],
            [
                'title' => "📦 #{id}-buyurtma tayyor, lekin...",
                'body'  => "...to'lov kelishini kutmoqda 🙈 Texnik muammo bo'lsa bizga yozing, hal qilamiz!",
            ],
            [
                'title' => "🤔 #{id}-raqamli buyurtmangiz haqida",
                'body'  => "To'lovni bajardingizmi? Agar ha bo'lsa — tekshiring, yo'q bo'lsa — keling hal qilaylik! 😄",
            ],
        ],
        'ru' => [
            [
                'title' => "💳 Заказ #{id} ждёт вас!",
                'body'  => "Кажется, вы забыли про оплату? Ваш заказ скучает. Если что-то пошло не так — напишите нам! 😅",
            ],
            [
                'title' => "🛒 Заказ #{id}: где вы?",
                'body'  => "Ваши книги уже упакованы и смотрят в окошко 📚 Проблемы с картой? Мы поможем! 😄",
            ],
            [
                'title' => "⏰ Заказ #{id} всё ещё не оплачен",
                'body'  => "Не переживайте, бывает! Если возникли трудности с оплатой — просто напишите нам 😊",
            ],
            [
                'title' => "📦 Заказ #{id} готов к отправке, но...",
                'body'  => "...ждёт оплаты 🙈 Если что-то пошло не так — свяжитесь с нами, решим!",
            ],
            [
                'title' => "🤔 Напоминание по заказу #{id}",
                'body'  => "Оплата прошла? Отлично! Нет? Не беда — пишите, разберёмся вместе 😄",
            ],
        ],
        'en' => [
            [
                'title' => "💳 Order #{id} is waiting for you!",
                'body'  => "Looks like you forgot to pay? Your order is sitting there, lonely. If something went wrong — just let us know! 😅",
            ],
            [
                'title' => "🛒 Hey, where are you? Order #{id} misses you!",
                'body'  => "Your books are packed and ready 📚 Having trouble with payment? We're here to help! 😄",
            ],
            [
                'title' => "⏰ Order #{id} is still unpaid",
                'body'  => "No worries, it happens! If you're having payment issues — reach out to us 😊",
            ],
            [
                'title' => "📦 Order #{id} is ready, but...",
                'body'  => "...still waiting for payment 🙈 Any trouble? Drop us a message and we'll sort it out!",
            ],
            [
                'title' => "🤔 Quick reminder about order #{id}",
                'body'  => "Payment went through? Great! It didn't? No problem — contact us and we'll figure it out together 😄",
            ],
        ],
        'ja' => [
            [
                'title' => "💳 注文 #{id} がお待ちしています！",
                'body'  => "お支払いをお忘れですか？ご注文が寂しそうにしています 😅 何かお困りでしたらご連絡ください！",
            ],
            [
                'title' => "🛒 注文 #{id} より：どこですか？",
                'body'  => "本たちが届くのを楽しみに待っています 📚 お支払いに問題がありましたらお気軽にどうぞ！ 😄",
            ],
            [
                'title' => "⏰ 注文 #{id} はまだ未払いです",
                'body'  => "よくあることです！お支払いでお困りの際は、ぜひご連絡ください 😊",
            ],
            [
                'title' => "📦 注文 #{id} は準備完了ですが…",
                'body'  => "…お支払いを待っています 🙈 何か問題があればメッセージをください！",
            ],
            [
                'title' => "🤔 注文 #{id} についてのお知らせ",
                'body'  => "お支払いは完了しましたか？まだの場合はお気軽にご連絡ください 😄",
            ],
        ],
    ];

    // =========================================================================
    //  HANDLE
    // =========================================================================

    public function handle(): void
    {
        $this->info(now()->format('d.m.Y H:i:s') . " — To'lanmagan buyurtmalarga eslatma yuborish boshlandi...");

        // Faqat 1 soat ichida yaratilgan, hali to'lanmagan aktiv buyurtmalar
        // (1 soatdan oshgach CancelUnpaidOrders bekor qiladi)
        $oneHourAgo = Carbon::now()->subHour();

        $unpaidOrders = Sold::where('paymentStatus', 1)
            ->where('status', 'A')
            ->where('created_at', '>=', $oneHourAgo)
            ->get();

        if ($unpaidOrders->isEmpty()) {
            $this->info('Eslatma yuborish uchun buyurtma topilmadi.');
            return;
        }

        $this->info("Topilgan buyurtmalar: {$unpaidOrders->count()}");

        // Xabar indeksini vaqtga qarab tanlaymiz — har 10 daqiqada boshqasi
        // 0,1,2,3,4 — 5 ta xabar, 10 daqiqa intervalda sikl qiladi
        $messageIndex = (int) floor(now()->minute / 10) % 5;

        foreach ($unpaidOrders as $order) {
            $this->sendRemindPush($order, $messageIndex);
        }

        $this->info('Eslatmalar yuborildi.');
    }

    // =========================================================================
    //  PUSH YUBORISH
    // =========================================================================

    private function sendRemindPush(Sold $order, int $msgIndex): void
    {
        try {
            $user = User::find($order->user_id);
            if (!$user) {
                Log::warning("RemindUnpaid: user #{$order->user_id} topilmadi.");
                return;
            }

            // Til aniqlash
            $lang = in_array($user->lang ?? 'uz', ['uz', 'ru', 'en', 'ja'])
                ? ($user->lang ?? 'uz')
                : 'uz';

            $msgs  = self::REMINDERS[$lang][$msgIndex];
            $id    = $order->id;
            $title = str_replace('{id}', $id, $msgs['title']);
            $body  = str_replace('{id}', $id, $msgs['body']);

            // FCM tokenlar
            $tokens = DB::table('connected_devices')
                ->where('user_type', 'user')
                ->where('user_id', $user->id)
                ->whereNotNull('fcm_token')
                ->pluck('fcm_token')
                ->unique()
                ->values()
                ->toArray();

            if (empty($tokens)) {
                Log::info("RemindUnpaid: user #{$user->id} uchun token topilmadi, order #{$id}.");
                return;
            }

            $pushData = [
                'app_key' => 'kitobchi',
                'title'   => $title,
                'body'    => $body,
                'tokens'  => $tokens,
                'data'    => [
                    'type'     => 'payment_reminder',
                    'order_id' => (string) $id,
                ],
            ];

            $request = new HttpRequest();
            $request->replace($pushData);

            $pushController = app(\App\Http\Controllers\PushController::class);
            $response       = $pushController->sendPush($request);

            $this->info("Push yuborildi: order #{$id}, user #{$user->id}, lang={$lang}, msg={$msgIndex}");
            Log::info("RemindUnpaid push sent: order #{$id}, user #{$user->id}, lang={$lang}", [
                'response' => $response->getContent(),
            ]);

        } catch (\Throwable $e) {
            Log::error("RemindUnpaid push xatosi (order #{$order->id})", [
                'message' => $e->getMessage(),
            ]);
            $this->error("Push xatosi order #{$order->id}: {$e->getMessage()}");
        }
    }
}