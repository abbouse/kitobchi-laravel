<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\MyCart;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request as HttpRequest;

class RemindCartItems extends Command
{
    // ── FIX: configure() o'chirildi ───────────────────────────
    // RemindUnpaidOrders kabi --time option to'g'ridan $signature ga yozildi
    // configure() override qilinganda parent::configure() chaqirilmaydi,
    // $signature parse qilinmaydi va option ro'yxatdan o'tmaydi
    protected $signature   = 'cart:remind {--time= : morning | afternoon | evening}';
    protected $description = "Savatchada mahsuloti bor foydalanuvchilarga kuniga 3 marta push xabar yuborish";

    private const REMINDERS = [
        'morning' => [
            'uz' => [
                'title' => "☀️ Xayrli tong! Savatchangi unutmadingizmi?",
                'body'  => "Savatingizdagi {count} ta mahsulot siz bilan ertalabki choy ichmoqchi edi 🍵 Bugun sotib olasizmi?",
            ],
            'ru' => [
                'title' => "☀️ Доброе утро! Ваша корзина скучает!",
                'body'  => "{count} товар в корзине мечтают о новом доме 🏠 Начнём день с покупки? ☕",
            ],
            'en' => [
                'title' => "☀️ Good morning! Your cart is waiting!",
                'body'  => "{count} item(s) in your cart are having their morning coffee without you ☕ Ready to check out today?",
            ],
            'ja' => [
                'title' => "☀️ おはようございます！カートが待っています！",
                'body'  => "カートの{count}点の商品が朝のコーヒーを一緒に飲みたそうにしています ☕ 今日はいかがでしょうか？",
            ],
        ],
        'afternoon' => [
            'uz' => [
                'title' => "🌤️ Tushlik payti! Savat hali kutmoqda...",
                'body'  => "Tushlik qilayotganda {count} ta mahsulot savatingizda yolg'iz o'tiribdi 🥺 Ularni xursand qiling!",
            ],
            'ru' => [
                'title' => "🌤️ Обеденный перерыв — самое время!",
                'body'  => "Пока вы обедаете, {count} товар в корзине грустит в одиночестве 🥺 Порадуйте их!",
            ],
            'en' => [
                'title' => "🌤️ Lunch break shopping time!",
                'body'  => "While you're eating, {count} item(s) in your cart are just sitting there, hoping 🥺 Don't leave them hanging!",
            ],
            'ja' => [
                'title' => "🌤️ お昼休みですよ！カートを見てみては？",
                'body'  => "お昼ご飯の間、カートの{count}点の商品がひとりぼっちです 🥺 一緒に連れて帰りましょう！",
            ],
        ],
        'evening' => [
            'uz' => [
                'title' => "🌙 Kechqurun — xarid qilish vaqti!",
                'body'  => "Bugun ham {count} ta mahsulot savatingizda uxlab qoldi 😴 Ertaga ham kutadimi? Yoki bugun hal qilamizmi?",
            ],
            'ru' => [
                'title' => "🌙 Вечер — лучшее время для шопинга!",
                'body'  => "Сегодня {count} товар снова засыпает в корзине 😴 Может, сегодня наконец-то заберём их домой?",
            ],
            'en' => [
                'title' => "🌙 Evening vibes = shopping time!",
                'body'  => "It's getting late and {count} item(s) are still in your cart 😴 Will they wait another day? Or is tonight the night?",
            ],
            'ja' => [
                'title' => "🌙 夜のお買い物タイムです！",
                'body'  => "今夜もカートの{count}点の商品が眠れずにいます 😴 今日こそ一緒に連れて帰りませんか？",
            ],
        ],
    ];

    // ── HANDLE ────────────────────────────────────────────────
    // RemindUnpaidOrders kabi — configure() yo'q, option() to'g'ridan ishlatiladi
    public function handle(): void
    {
        $timeSlot = $this->option('time');

        if (!in_array($timeSlot, ['morning', 'afternoon', 'evening'])) {
            $hour     = (int) now()->format('H');
            $timeSlot = match (true) {
                $hour >= 6  && $hour < 12 => 'morning',
                $hour >= 12 && $hour < 17 => 'afternoon',
                default                   => 'evening',
            };
        }

        $this->info(now()->format('d.m.Y H:i:s') . " — Savatcha eslatmasi boshlandi [{$timeSlot}]...");

        $userIdsWithCart = MyCart::select('user_id')
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) >= 1')
            ->pluck('user_id')
            ->toArray();

        if (empty($userIdsWithCart)) {
            $this->info('Savatchasida mahsulot bor foydalanuvchi topilmadi.');
            return;
        }

        $this->info("Savatchali foydalanuvchilar: " . count($userIdsWithCart));

        $sent = 0;
        $skip = 0;

        foreach ($userIdsWithCart as $userId) {
            $cartCount = MyCart::where('user_id', $userId)->sum('count_item') ?: 1;
            $user      = User::find($userId);
            if (!$user) { $skip++; continue; }

            $this->sendCartPush($user, (int) $cartCount, $timeSlot) ? $sent++ : $skip++;
        }

        $this->info("Yuborildi: {$sent} | O'tkazib yuborildi: {$skip}");
    }

    private function sendCartPush(User $user, int $cartCount, string $timeSlot): bool
    {
        try {
            $lang = in_array($user->lang ?? 'uz', ['uz', 'ru', 'en', 'ja'])
                ? ($user->lang ?? 'uz') : 'uz';

            $msgs  = self::REMINDERS[$timeSlot][$lang];
            $title = str_replace('{count}', $cartCount, $msgs['title']);
            $body  = str_replace('{count}', $cartCount, $msgs['body']);

            $tokens = DB::table('connected_devices')
                ->where('user_type', 'user')
                ->where('user_id', $user->id)
                ->whereNotNull('fcm_token')
                ->pluck('fcm_token')
                ->unique()->values()->toArray();

            if (empty($tokens)) {
                Log::info("CartRemind: user #{$user->id} uchun token topilmadi.");
                return false;
            }

            $pushData = [
                'app_key' => 'kitobchi',
                'title'   => $title,
                'body'    => $body,
                'tokens'  => $tokens,
                'data'    => ['type' => 'cart_reminder'],
            ];

            $request = new HttpRequest();
            $request->replace($pushData);

            $pushController = app(\App\Http\Controllers\PushController::class);
            $response       = $pushController->sendPush($request);

            Log::info("CartRemind push sent: user #{$user->id}, lang={$lang}, slot={$timeSlot}, count={$cartCount}", [
                'response' => $response->getContent(),
            ]);

            $this->line("  ✓ user #{$user->id} [{$lang}] — {$cartCount} ta mahsulot");
            return true;

        } catch (\Throwable $e) {
            Log::error("CartRemind push xatosi (user #{$user->id})", [
                'message' => $e->getMessage(),
            ]);
            $this->error("  ✗ user #{$user->id}: {$e->getMessage()}");
            return false;
        }
    }
}