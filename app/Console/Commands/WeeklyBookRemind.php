<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request as HttpRequest;

class WeeklyBookRemind extends Command
{
    protected $signature   = 'users:book-remind';
    protected $description = "Barcha foydalanuvchilarga haftalik kitob o'qish va AI savdolashuv haqida hazil-mutoyiba push xabar";

    // =========================================================================
    //  20 TA TURLI XABAR — har safar boshqasi (haftaning kuni + soatga qarab)
    // =========================================================================

    private const MESSAGES = [

        // ── 1 ──
        'uz' => [
            [
                'title' => "📚 Miyangiz dam olishni xohlaydimi?",
                'body'  => "Kitob o'qing! Miya uchun eng yaxshi sport shu. P.S. Kitobchi AI bilan savdolashib arzonroq oling 🤖💸",
            ],
            [
                'title' => "🤓 Bilimdon odam bo'lmoqchimisiz?",
                'body'  => "Bir kitob o'qing, miyangiz sizga rahmat aytadi. Yana arzon oling — AI bilan bahslashing, u ko'p bilmaydi 😄",
            ],
            [
                'title' => "😴 Uxlay olmayapsizmi?",
                'body'  => "Kitob o'qing, kafolat bilan uxlaysiz 😂 Avval Kitobchi AI dan arzon narxda oling!",
            ],
            [
                'title' => "📖 Bugun kitob o'qidingizmi?",
                'body'  => "Yo'q? Uyalish kerak 😄 Lekin kech emas — Kitobchi AI bilan savdolashib chegirmali oling!",
            ],
            [
                'title' => "🧠 Miya yangilanishga muhtoj!",
                'body'  => "Kitob — miyaning zaryad quvvatlagichi. Kitobchi AI esa hamyonning do'sti 🤖 Ikkalasini sinab ko'ring!",
            ],
            [
                'title' => "🦸 Qahramonlar ham kitob o'qiydi!",
                'body'  => "Siz ham qahramonsiz — Kitobchi AI bilan bahslashib chegirma olgan odam! Sinab ko'rganmisiz? 😏",
            ],
            [
                'title' => "☕ Choy ustida kitob — eng yaxshi kombinatsiya!",
                'body'  => "Choyingizni tayyorlang, biz kitob tayyorladik. AI bilan savdolashib yanada arzon oling 🤖☕",
            ],
            [
                'title' => "🌙 Kecha nima qildingiz?",
                'body'  => "Agar kitob o'qimagan bo'lsangiz — bugun o'qing! Kitobchi AI bilan narxni ham tushirib olasiz 😄",
            ],
            [
                'title' => "🎯 Bugungi maqsad: 10 bet o'qish!",
                'body'  => "10 bet — bu hech narsa emas. Avval Kitobchi AI dan chegirmali kitob oling, keyin boshlang 📚",
            ],
            [
                'title' => "🤖 Kitobchi AI sizni kutmoqda!",
                'body'  => "U bilan bahslashing, savdolashing, narxni tushiring — va g'olib bo'ling! Keyin kitob o'qing 😄",
            ],
            [
                'title' => "💡 Aqlli odam bo'lish sirlari:",
                'body'  => "1. Kitob o'qi 📚  2. Kitobchi AI bilan savdolash 🤖  3. Tejagan pulga yana kitob ol! 💰",
            ],
            [
                'title' => "🏆 Kitob o'quvchilar — g'oliblar!",
                'body'  => "Statistikaga ko'ra kitob o'quvchilar muvaffaqiyatliroq. Birinchi qadam: Kitobchi AI dan arzon oling 😄",
            ],
            [
                'title' => "📚 Kitob — eng yaxshi sarmoya!",
                'body'  => "Bitta kitob sizni o'zgartirib yuborishi mumkin. P.S. AI bilan savdolashib yanada tejang 🤑",
            ],
            [
                'title' => "🌟 Bu hafta nechta kitob o'qidingiz?",
                'body'  => "0 bo'lsa — bu xabar sizga yuborildi 😄 Kitobchi AI dan chegirmali kitob olib boshlang!",
            ],
            [
                'title' => "🎁 O'zingizga sovg'a qiling!",
                'body'  => "Eng yaxshi sovg'a — kitob. Eng yaxshi narx — Kitobchi AI bilan savdolashgandan keyin! 🤖💰",
            ],
            [
                'title' => "👀 Telefon o'rniga kitob o'qisangiz...",
                'body'  => "...bu xabarni o'qimagan bo'lardingiz 😂 Kitobchi AI bilan chegirma olib, kitobga o'ting!",
            ],
            [
                'title' => "🚀 Bilimlar kelajakka eltadi!",
                'body'  => "Raketa uchun yoqilg'i = kitob. Chegirmali yoqilg'i = Kitobchi AI 🤖 Ikkalasini sinang!",
            ],
            [
                'title' => "🤔 Bir savol: oxirgi marta qachon kitob o'qigansiz?",
                'body'  => "Javob uzoq vaqt oldin bo'lsa — bugun boshlang! AI bilan savdolashib arzon oling 😄",
            ],
            [
                'title' => "😎 Eng aqlli odam siz bo'lasiz!",
                'body'  => "Agar Kitobchi AI bilan bahslashib chegirma olgan bo'lsangiz 🤖 Yo'q bo'lsa — sinab ko'ring!",
            ],
            [
                'title' => "📚 Kitob o'qish — bepul terapi!",
                'body'  => "Stress, xavotir, zerikish — barchasiga davo: kitob. Arzon davo: Kitobchi AI bilan oling 😄",
            ],
        ],

        'ru' => [
            [
                'title' => "📚 Мозгу нужна тренировка?",
                'body'  => "Читайте книги! Лучший спорт для ума. P.S. Торгуйтесь с Kitobchi AI и берите дешевле 🤖💸",
            ],
            [
                'title' => "🤓 Хотите стать умнее?",
                'body'  => "Прочитайте одну книгу — мозг скажет спасибо. А AI не умеет торговаться — проверьте! 😄",
            ],
            [
                'title' => "😴 Не можете заснуть?",
                'body'  => "Читайте книгу — заснёте гарантированно 😂 Сначала купите со скидкой через Kitobchi AI!",
            ],
            [
                'title' => "📖 Читали сегодня книгу?",
                'body'  => "Нет? Стыд и позор 😄 Но не поздно — поторгуйтесь с AI и купите со скидкой!",
            ],
            [
                'title' => "🧠 Мозг требует обновления!",
                'body'  => "Книга — зарядное устройство для ума. Kitobchi AI — друг кошелька 🤖 Попробуйте оба!",
            ],
            [
                'title' => "🦸 Герои тоже читают книги!",
                'body'  => "Вы тоже герой — раз поторговались с AI и получили скидку! Пробовали? 😏",
            ],
            [
                'title' => "☕ Чай + книга = идеальный вечер!",
                'body'  => "Заварите чай, мы подготовили книги. Торгуйтесь с AI и берите ещё дешевле 🤖☕",
            ],
            [
                'title' => "🌙 Что делали вчера вечером?",
                'body'  => "Если не читали — читайте сегодня! С Kitobchi AI получите скидку 😄",
            ],
            [
                'title' => "🎯 Цель на сегодня: прочитать 10 страниц!",
                'body'  => "10 страниц — это ничего. Сначала купите со скидкой у Kitobchi AI, потом начинайте 📚",
            ],
            [
                'title' => "🤖 Kitobchi AI ждёт вас!",
                'body'  => "Поспорьте, поторгуйтесь, сбейте цену — и победите! Потом читайте 😄",
            ],
            [
                'title' => "💡 Секреты умного человека:",
                'body'  => "1. Читай книги 📚  2. Торгуйся с AI 🤖  3. На сэкономленное — снова книги! 💰",
            ],
            [
                'title' => "🏆 Читатели — победители!",
                'body'  => "По статистике, читающие люди успешнее. Первый шаг: купить книгу через AI со скидкой 😄",
            ],
            [
                'title' => "📚 Книга — лучшая инвестиция!",
                'body'  => "Одна книга может изменить вашу жизнь. P.S. Торгуйтесь с AI и экономьте ещё больше 🤑",
            ],
            [
                'title' => "🌟 Сколько книг прочитали на этой неделе?",
                'body'  => "Если 0 — это сообщение для вас 😄 Начните с книги по скидке от Kitobchi AI!",
            ],
            [
                'title' => "🎁 Сделайте себе подарок!",
                'body'  => "Лучший подарок — книга. Лучшая цена — после торгов с Kitobchi AI! 🤖💰",
            ],
            [
                'title' => "👀 Если бы вы читали вместо телефона...",
                'body'  => "...вы бы не увидели это сообщение 😂 Купите книгу у AI со скидкой и переключитесь!",
            ],
            [
                'title' => "🚀 Знания — путь в будущее!",
                'body'  => "Топливо для ракеты = книга. Дешёвое топливо = Kitobchi AI 🤖 Попробуйте оба!",
            ],
            [
                'title' => "🤔 Вопрос: когда вы последний раз читали книгу?",
                'body'  => "Если давно — начните сегодня! Торгуйтесь с AI и покупайте дешевле 😄",
            ],
            [
                'title' => "😎 Самый умный человек — это вы!",
                'body'  => "Если вы уже торговались с Kitobchi AI 🤖 Нет? Тогда самое время попробовать!",
            ],
            [
                'title' => "📚 Чтение — бесплатная терапия!",
                'body'  => "Стресс, тревога, скука — всему лекарство: книга. Дешевле с Kitobchi AI 😄",
            ],
        ],

        'en' => [
            [
                'title' => "📚 Does your brain need a workout?",
                'body'  => "Read books! Best sport for the mind. P.S. Haggle with Kitobchi AI and get it cheaper 🤖💸",
            ],
            [
                'title' => "🤓 Want to be smarter?",
                'body'  => "Read one book — your brain will thank you. Also, AI can't negotiate — or can it? Test it! 😄",
            ],
            [
                'title' => "😴 Can't sleep?",
                'body'  => "Read a book — guaranteed to knock you out 😂 First, grab one cheap from Kitobchi AI!",
            ],
            [
                'title' => "📖 Did you read today?",
                'body'  => "No? Shame on you 😄 But it's not too late — haggle with AI and get a discount!",
            ],
            [
                'title' => "🧠 Brain update needed!",
                'body'  => "Books = brain charger. Kitobchi AI = wallet's best friend 🤖 Try both!",
            ],
            [
                'title' => "🦸 Heroes read books too!",
                'body'  => "You're a hero — especially if you haggled with AI for a discount! Have you tried? 😏",
            ],
            [
                'title' => "☕ Tea + book = perfect evening!",
                'body'  => "Brew your tea, we've got books ready. Haggle with AI for an even better price 🤖☕",
            ],
            [
                'title' => "🌙 What did you do last night?",
                'body'  => "If not reading — start tonight! Kitobchi AI has your back with discounts 😄",
            ],
            [
                'title' => "🎯 Today's goal: read 10 pages!",
                'body'  => "10 pages is nothing. First, get a deal from Kitobchi AI, then start reading 📚",
            ],
            [
                'title' => "🤖 Kitobchi AI is waiting for you!",
                'body'  => "Argue, negotiate, bring the price down — and win! Then read 😄",
            ],
            [
                'title' => "💡 Secrets of a smart person:",
                'body'  => "1. Read books 📚  2. Haggle with AI 🤖  3. Use savings to buy more books! 💰",
            ],
            [
                'title' => "🏆 Readers are winners!",
                'body'  => "Stats say readers are more successful. Step one: buy a book cheap with Kitobchi AI 😄",
            ],
            [
                'title' => "📚 Books are the best investment!",
                'body'  => "One book can change your life. P.S. Haggle with AI and save even more 🤑",
            ],
            [
                'title' => "🌟 How many books this week?",
                'body'  => "If 0 — this message is for you 😄 Start with a discounted book from Kitobchi AI!",
            ],
            [
                'title' => "🎁 Give yourself a gift!",
                'body'  => "Best gift = book. Best price = after haggling with Kitobchi AI! 🤖💰",
            ],
            [
                'title' => "👀 If you read instead of scrolling...",
                'body'  => "...you wouldn't have seen this 😂 Get a book cheap from AI and make the switch!",
            ],
            [
                'title' => "🚀 Knowledge is the path forward!",
                'body'  => "Rocket fuel = books. Cheap fuel = Kitobchi AI 🤖 Try both!",
            ],
            [
                'title' => "🤔 When did you last read a book?",
                'body'  => "If it was a while ago — start today! Haggle with AI for a better price 😄",
            ],
            [
                'title' => "😎 The smartest person is YOU!",
                'body'  => "If you've already haggled with Kitobchi AI 🤖 Haven't? Time to find out!",
            ],
            [
                'title' => "📚 Reading is free therapy!",
                'body'  => "Stress, anxiety, boredom — books cure all. Even cheaper with Kitobchi AI 😄",
            ],
        ],

        'ja' => [
            [
                'title' => "📚 脳トレが必要ですか？",
                'body'  => "本を読みましょう！心のベストスポーツです。P.S. Kitobchi AIと交渉してもっと安く手に入れましょう 🤖💸",
            ],
            [
                'title' => "🤓 もっと賢くなりたいですか？",
                'body'  => "1冊読めば脳が喜びます。AIは交渉が苦手かも？試してみては！ 😄",
            ],
            [
                'title' => "😴 眠れないですか？",
                'body'  => "本を読めば確実に眠れます 😂 まずKitobchi AIで安く手に入れましょう！",
            ],
            [
                'title' => "📖 今日は本を読みましたか？",
                'body'  => "まだ？恥ずかしいですよ 😄 でも遅くない — AIと交渉して割引で買いましょう！",
            ],
            [
                'title' => "🧠 脳のアップデートが必要！",
                'body'  => "本 = 脳の充電器。Kitobchi AI = お財布の味方 🤖 両方試してみて！",
            ],
            [
                'title' => "🦸 ヒーローも本を読む！",
                'body'  => "あなたもヒーロー — AIと交渉して割引をゲットしたなら！試しましたか？ 😏",
            ],
            [
                'title' => "☕ お茶＋本 = 最高の夜！",
                'body'  => "お茶を準備して、本はこちらで。AIと交渉してもっとお得に 🤖☕",
            ],
            [
                'title' => "🌙 昨夜は何をしましたか？",
                'body'  => "読んでいなければ — 今夜読みましょう！Kitobchi AIで割引もあります 😄",
            ],
            [
                'title' => "🎯 今日の目標：10ページ読む！",
                'body'  => "10ページなんて簡単。まずKitobchi AIでお得に買って、それから読み始めましょう 📚",
            ],
            [
                'title' => "🤖 Kitobchi AIがお待ちしています！",
                'body'  => "議論して、交渉して、値引きして — そして勝利を！その後は読書タイム 😄",
            ],
            [
                'title' => "💡 賢い人の秘訣：",
                'body'  => "1. 本を読む 📚  2. AIと交渉する 🤖  3. 節約したお金でまた本を買う！ 💰",
            ],
            [
                'title' => "🏆 読者は勝者！",
                'body'  => "統計によると読書する人の方が成功しているとか。第一歩：Kitobchi AIで安く買う 😄",
            ],
            [
                'title' => "📚 本は最高の投資！",
                'body'  => "1冊の本があなたを変えるかも。P.S. AIと交渉してさらに節約 🤑",
            ],
            [
                'title' => "🌟 今週は何冊読みましたか？",
                'body'  => "0冊なら — このメッセージはあなたへ 😄 Kitobchi AIで割引本からスタート！",
            ],
            [
                'title' => "🎁 自分へのご褒美を！",
                'body'  => "最高のギフト = 本。最安値 = Kitobchi AIと交渉した後！ 🤖💰",
            ],
            [
                'title' => "👀 スマホの代わりに本を読んでいたら…",
                'body'  => "…このメッセージを見なかったはず 😂 AIで安く本をゲットして読書に切り替えましょう！",
            ],
            [
                'title' => "🚀 知識は未来への道！",
                'body'  => "ロケットの燃料 = 本。格安燃料 = Kitobchi AI 🤖 両方試して！",
            ],
            [
                'title' => "🤔 最後に本を読んだのはいつですか？",
                'body'  => "しばらく前なら — 今日から始めましょう！AIと交渉してお得に 😄",
            ],
            [
                'title' => "😎 一番賢いのはあなた！",
                'body'  => "Kitobchi AIとすでに交渉したなら 🤖 まだなら — ぜひ試してみて！",
            ],
            [
                'title' => "📚 読書は無料のセラピー！",
                'body'  => "ストレス、不安、退屈 — 全部本で解決。Kitobchi AIでさらにお得に 😄",
            ],
        ],
    ];

    // =========================================================================
    //  HANDLE
    // =========================================================================

    public function handle(): void
    {
        $this->info(now()->format('d.m.Y H:i:s') . " — Haftalik kitob eslatmasi boshlandi...");

        // Barcha aktiv foydalanuvchilar (FCM tokeni borlar)
        $userIds = DB::table('connected_devices')
            ->where('user_type', 'user')
            ->whereNotNull('fcm_token')
            ->distinct()
            ->pluck('user_id')
            ->toArray();

        if (empty($userIds)) {
            $this->info('FCM tokeni bor foydalanuvchi topilmadi.');
            return;
        }

        $this->info("Foydalanuvchilar soni: " . count($userIds));

        // Xabar indeksini haftaning kuni + soatga qarab tanlaymiz
        // Haftada 3 marta (sesh, pay, shan), har safar boshqacha xabar
        $dayOfWeek = (int) now()->format('N'); // 1=Du, 7=Ya
        $hour      = (int) now()->format('H');
        // 20 ta xabardan birini deterministik tanlash
        $msgIndex  = ($dayOfWeek * 3 + (int)($hour / 8)) % 20;

        $sent = 0;
        $skip = 0;

        foreach ($userIds as $userId) {
            $user = User::find($userId);
            if (!$user) { $skip++; continue; }

            $success = $this->sendPush($user, $msgIndex);
            $success ? $sent++ : $skip++;
        }

        $this->info("Yuborildi: {$sent} | O'tkazib yuborildi: {$skip}");
    }

    // =========================================================================
    //  PUSH YUBORISH
    // =========================================================================

    private function sendPush(User $user, int $msgIndex): bool
    {
        try {
            $lang = in_array($user->lang ?? 'uz', ['uz', 'ru', 'en', 'ja'])
                ? ($user->lang ?? 'uz')
                : 'uz';

            $msg   = self::MESSAGES[$lang][$msgIndex];

            $tokens = DB::table('connected_devices')
                ->where('user_type', 'user')
                ->where('user_id', $user->id)
                ->whereNotNull('fcm_token')
                ->pluck('fcm_token')
                ->unique()
                ->values()
                ->toArray();

            if (empty($tokens)) return false;

            $pushData = [
                'app_key' => 'kitobchi',
                'title'   => $msg['title'],
                'body'    => $msg['body'],
                'tokens'  => $tokens,
                'data'    => [
                    'type' => 'weekly_book_remind',
                ],
            ];

            $request = new HttpRequest();
            $request->replace($pushData);

            $pushController = app(\App\Http\Controllers\PushController::class);
            $response       = $pushController->sendPush($request);

            Log::info("WeeklyBookRemind: user #{$user->id}, lang={$lang}, msg={$msgIndex}");
            $this->line("  ✓ user #{$user->id} [{$lang}] msg#{$msgIndex}");

            return true;

        } catch (\Throwable $e) {
            Log::error("WeeklyBookRemind xatosi (user #{$user->id}): {$e->getMessage()}");
            $this->error("  ✗ user #{$user->id}: {$e->getMessage()}");
            return false;
        }
    }
}