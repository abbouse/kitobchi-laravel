<?php

namespace App\Services\FinanceBot;

use App\Models\Admin;
use App\Models\PlatformExpense;
use App\Models\PlatformIncome;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Moliya va Chiqimlar Telegram boti xizmati.
 * Guruhdagi xabarlarni tahlil qilish, to'ldirish, bazaga saqlash va hisobotlar yuborish.
 */
class FinanceBotService
{
    public function __construct(
        protected FinanceBotClient $client,
        protected FinanceAiParserService $aiParser,
    ) {}

    /**
     * Telegram webhook orqali kelgan xabarni qayta ishlash
     */
    public function handleMessage(array $message): void
    {
        $chat = $message['chat'] ?? [];
        $from = $message['from'] ?? [];
        $text = trim((string) ($message['text'] ?? ''));
        $messageId = (int) ($message['message_id'] ?? 0);
        $chatId = $chat['id'] ?? null;

        // Bot o'z xabarlarini yoki bo'sh xabarlarni qayta ishlamaydi
        if (($from['is_bot'] ?? false) || $text === '' || ! $chatId) {
            return;
        }

        // Guruh chat_id sini avtomatik saqlab qo'yish (agar .env da hali bo'sh bo'lsa)
        if (in_array($chat['type'] ?? '', ['group', 'supergroup'])) {
            if (! config('services.finance_bot.chat_id')) {
                Cache::forever('finance_bot:chat_id', (string) $chatId);
            }
        }

        $userId = (int) ($from['id'] ?? 0);
        $replyToId = (int) ($message['reply_to_message']['message_id'] ?? 0);

        // 1. Pending (avval so'ralgan ma'lumot) tekshiruvi
        $pending = null;
        if ($replyToId > 0 && Cache::has("finance_bot:pending_msg:{$replyToId}")) {
            $pending = Cache::pull("finance_bot:pending_msg:{$replyToId}");
        } elseif ($userId > 0 && Cache::has("finance_bot:pending_user:{$userId}")) {
            $pending = Cache::pull("finance_bot:pending_user:{$userId}");
        }

        // Agar pending bo'lmasa va matnda son/moliyaviy narsa bo'lmasa -> ignore (oddiy suhbatga xalaqit bermaymiz)
        if (! $pending && ! $this->aiParser->couldBeFinancial($text)) {
            return;
        }

        // 2. OpenAI tahlili
        $parsed = $this->aiParser->parse($text, $pending);

        // Agar matn moliyaviy bo'lmasa
        if (! $parsed['is_financial']) {
            if ($pending) {
                // Foydalanuvchiga yana muloyim eslatamiz
                $this->client->sendMessage($chatId, "Kechirasiz, tushunmadim. Iltimos, xarajat/kirim sababi yoki summasini aniqroq yozing.", [
                    'reply_parameters' => ['message_id' => $messageId],
                ]);
            }
            return;
        }

        // 3. Ma'lumot to'liq emas bo'lsa — foydalanuvchidan yetishmayotgan qismini so'raymiz
        if (! $parsed['is_complete'] || empty($parsed['amount']) || empty($parsed['title'])) {
            $question = $parsed['clarification_question']
                ?: ($parsed['amount'] === null
                    ? "Iltimos, summani (necha so'm) ham yozib yuboring."
                    : "Summani tushundim. Bu nimaga sarflandi yoki qayerdan keldi? Iltimos, sababini yozing.");

            $botResponse = $this->client->sendMessage($chatId, "❓ {$question}", [
                'reply_parameters' => ['message_id' => $messageId],
            ]);

            $botMsgId = (int) ($botResponse['message_id'] ?? 0);
            $cacheData = [
                'amount'   => $parsed['amount'],
                'type'     => $parsed['type'],
                'category' => $parsed['category'],
                'title'    => $parsed['title'],
                'note'     => $parsed['note'] ?: $text,
                'user_id'  => $userId,
            ];

            if ($botMsgId > 0) {
                Cache::put("finance_bot:pending_msg:{$botMsgId}", $cacheData, now()->addMinutes(30));
            }
            if ($userId > 0) {
                Cache::put("finance_bot:pending_user:{$userId}", $cacheData, now()->addMinutes(30));
            }

            return;
        }

        // 4. Ma'lumot to'liq! Bazaga saqlaymiz.
        // Kim kiritganini aniqlaymiz:
        $admin = Admin::query()->where('telegram_user_id', $userId)->where('is_active', true)->first();
        $adminId = $admin?->id;

        $username = $from['username'] ?? null;
        $fullName = trim(($from['first_name'] ?? '') . ' ' . ($from['last_name'] ?? ''));
        $spentAt = Carbon::today()->toDateString();

        if ($parsed['type'] === 'expense') {
            // Chiqim (PlatformExpense)
            $expense = PlatformExpense::create([
                'category'            => $parsed['category'] ?: 'other',
                'amount'              => $parsed['amount'],
                'spent_at'            => $spentAt,
                'title'               => $parsed['title'],
                'note'                => $parsed['note'] ?: $text,
                'created_by'          => $adminId,
                'telegram_user_id'    => $userId ?: null,
                'telegram_username'   => $username,
                'telegram_name'       => $fullName,
                'telegram_message_id' => $messageId,
                'source'              => 'telegram_bot',
            ]);

            $categoryLabel = PlatformExpense::CATEGORIES[$expense->category] ?? $expense->category;
            $userDisplay = $username ? "@{$username}" : ($fullName ?: "ID: {$userId}");

            $replyText = "✅ <b>Chiqim bazaga kiritildi!</b>\n\n"
                . "🔴 <b>Summa:</b> -" . number_format($expense->amount, 0, '.', ' ') . " so'm\n"
                . "🏷 <b>Kategoriya:</b> {$categoryLabel}\n"
                . "📝 <b>Maqsad:</b> " . htmlspecialchars($expense->title, ENT_QUOTES) . "\n"
                . "👤 <b>Kiritdi:</b> {$userDisplay}\n"
                . "📅 <b>Sana:</b> {$spentAt}";

            $this->client->sendMessage($chatId, $replyText, [
                'reply_parameters' => ['message_id' => $messageId],
            ]);
        } else {
            // Kirim / Foyda (PlatformIncome)
            $income = PlatformIncome::create([
                'type'                => $parsed['category'] ?: 'other',
                'amount'              => $parsed['amount'],
                'received_at'         => $spentAt,
                'title'               => $parsed['title'],
                'note'                => $parsed['note'] ?: $text,
                'created_by'          => $adminId,
                'telegram_user_id'    => $userId ?: null,
                'telegram_username'   => $username,
                'telegram_name'       => $fullName,
                'telegram_message_id' => $messageId,
                'source'              => 'telegram_bot',
            ]);

            $typeLabel = PlatformIncome::TYPES[$income->type] ?? $income->type;
            $userDisplay = $username ? "@{$username}" : ($fullName ?: "ID: {$userId}");

            $replyText = "✅ <b>Kirim (tushum) bazaga kiritildi!</b>\n\n"
                . "🟢 <b>Summa:</b> +" . number_format($income->amount, 0, '.', ' ') . " so'm\n"
                . "🏷 <b>Turi:</b> {$typeLabel}\n"
                . "📝 <b>Maqsad:</b> " . htmlspecialchars($income->title, ENT_QUOTES) . "\n"
                . "👤 <b>Kiritdi:</b> {$userDisplay}\n"
                . "📅 <b>Sana:</b> {$spentAt}";

            $this->client->sendMessage($chatId, $replyText, [
                'reply_parameters' => ['message_id' => $messageId],
            ]);
        }
    }

    /**
     * KUNLIK MOLIYAVIY HISOBOT (Har kuni kechasi yuboriladi)
     */
    public function sendDailyReport(?string $chatId = null, ?string $date = null): bool
    {
        $targetDate = $date ? Carbon::parse($date)->toDateString() : Carbon::today()->toDateString();

        $expenses = PlatformExpense::query()->whereDate('spent_at', $targetDate)->get();
        $incomes = PlatformIncome::query()->whereDate('received_at', $targetDate)->get();

        $totalExpense = (int) $expenses->sum('amount');
        $totalIncome = (int) $incomes->sum('amount');
        $netBalance = $totalIncome - $totalExpense;

        // Kategoriya bo'yicha chiqimlar
        $expenseByCategory = [];
        foreach ($expenses as $exp) {
            $catLabel = PlatformExpense::CATEGORIES[$exp->category] ?? $exp->category;
            $expenseByCategory[$catLabel] = ($expenseByCategory[$catLabel] ?? 0) + $exp->amount;
        }

        // Kim qancha chiqim kiritgan
        $usersExpense = [];
        foreach ($expenses as $exp) {
            $name = $exp->telegram_username ? "@{$exp->telegram_username}" : ($exp->telegram_name ?: 'Boshqaruv');
            $usersExpense[$name] = ($usersExpense[$name] ?? 0) + $exp->amount;
        }

        $formattedDate = Carbon::parse($targetDate)->locale('uz')->translatedFormat('d-F, Y');
        $netSign = $netBalance >= 0 ? '+' : '';
        $netEmoji = $netBalance >= 0 ? '🟢' : '🔴';

        $text = "📊 <b>KUNLIK MOLIYAVIY HISOBOT</b>\n"
            . "📅 <b>Sana:</b> {$formattedDate}\n"
            . "──────────────────\n"
            . "🟢 <b>Jami kirim:</b> +" . number_format($totalIncome, 0, '.', ' ') . " so'm\n"
            . "🔴 <b>Jami chiqim:</b> -" . number_format($totalExpense, 0, '.', ' ') . " so'm\n"
            . "{$netEmoji} <b>Kunlik sof kassa:</b> {$netSign}" . number_format($netBalance, 0, '.', ' ') . " so'm\n"
            . "──────────────────\n";

        if (! empty($expenseByCategory)) {
            $text .= "\n📂 <b>Chiqimlar moddasi:</b>\n";
            foreach ($expenseByCategory as $cat => $sum) {
                $text .= " • {$cat}: -" . number_format($sum, 0, '.', ' ') . " so'm\n";
            }
        }

        if (! empty($usersExpense)) {
            $text .= "\n👥 <b>Xarajat kiritganlar:</b>\n";
            foreach ($usersExpense as $user => $sum) {
                $text .= " • {$user}: -" . number_format($sum, 0, '.', ' ') . " so'm\n";
            }
        }

        if ($expenses->isEmpty() && $incomes->isEmpty()) {
            $text .= "\n<i>Bugun hech qanday kirim yoki chiqim amalga oshirilmadi.</i>";
        }

        $text .= "\n\n<i>#kunlik_hisobot #moliya</i>";

        $targetChat = $chatId ?: $this->client->defaultChatId();
        if (! $targetChat) {
            Log::warning('FinanceBot: Kunlik hisobot yuborish uchun chat_id topilmadi');
            return false;
        }

        $res = $this->client->sendMessage($targetChat, $text);

        return $res !== null;
    }

    /**
     * HAFTALIK MOLIYAVIY HISOBOT (Har hafta oxirida yuboriladi)
     */
    public function sendWeeklyReport(?string $chatId = null): bool
    {
        $startOfWeek = Carbon::now()->startOfWeek()->toDateString();
        $endOfWeek = Carbon::now()->endOfWeek()->toDateString();

        $expenses = PlatformExpense::query()->whereBetween('spent_at', [$startOfWeek, $endOfWeek])->get();
        $incomes = PlatformIncome::query()->whereBetween('received_at', [$startOfWeek, $endOfWeek])->get();

        $totalExpense = (int) $expenses->sum('amount');
        $totalIncome = (int) $incomes->sum('amount');
        $netBalance = $totalIncome - $totalExpense;

        $expenseByCategory = [];
        foreach ($expenses as $exp) {
            $catLabel = PlatformExpense::CATEGORIES[$exp->category] ?? $exp->category;
            $expenseByCategory[$catLabel] = ($expenseByCategory[$catLabel] ?? 0) + $exp->amount;
        }

        $netSign = $netBalance >= 0 ? '+' : '';
        $netEmoji = $netBalance >= 0 ? '🟢' : '🔴';

        $text = "📈 <b>HAFTALIK MOLIYAVIY HISOBOT</b>\n"
            . "🗓 <b>Davr:</b> {$startOfWeek} dan {$endOfWeek} gacha\n"
            . "──────────────────\n"
            . "🟢 <b>Haftalik kirim:</b> +" . number_format($totalIncome, 0, '.', ' ') . " so'm\n"
            . "🔴 <b>Haftalik chiqim:</b> -" . number_format($totalExpense, 0, '.', ' ') . " so'm\n"
            . "{$netEmoji} <b>Haftalik saldo:</b> {$netSign}" . number_format($netBalance, 0, '.', ' ') . " so'm\n"
            . "──────────────────\n";

        if (! empty($expenseByCategory)) {
            $text .= "\n📂 <b>Asosiy xarajat moddalari:</b>\n";
            arsort($expenseByCategory);
            foreach ($expenseByCategory as $cat => $sum) {
                $text .= " • {$cat}: -" . number_format($sum, 0, '.', ' ') . " so'm\n";
            }
        }

        $text .= "\n\n<i>#haftalik_hisobot #moliya</i>";

        $targetChat = $chatId ?: $this->client->defaultChatId();
        if (! $targetChat) {
            return false;
        }

        return $this->client->sendMessage($targetChat, $text) !== null;
    }

    /**
     * OYLIK MOLIYAVIY HISOBOT (Har oy oxirida yoki yangi oy boshida yuboriladi)
     */
    public function sendMonthlyReport(?string $chatId = null, ?string $month = null): bool
    {
        $targetMonth = $month ? Carbon::parse($month) : Carbon::now();
        $startOfMonth = $targetMonth->copy()->startOfMonth()->toDateString();
        $endOfMonth = $targetMonth->copy()->endOfMonth()->toDateString();

        $expenses = PlatformExpense::query()->whereBetween('spent_at', [$startOfMonth, $endOfMonth])->get();
        $incomes = PlatformIncome::query()->whereBetween('received_at', [$startOfMonth, $endOfMonth])->get();

        $totalExpense = (int) $expenses->sum('amount');
        $totalIncome = (int) $incomes->sum('amount');
        $netBalance = $totalIncome - $totalExpense;

        $expenseByCategory = [];
        foreach ($expenses as $exp) {
            $catLabel = PlatformExpense::CATEGORIES[$exp->category] ?? $exp->category;
            $expenseByCategory[$catLabel] = ($expenseByCategory[$catLabel] ?? 0) + $exp->amount;
        }

        $monthName = $targetMonth->locale('uz')->translatedFormat('F Y');
        $netSign = $netBalance >= 0 ? '+' : '';
        $netEmoji = $netBalance >= 0 ? '🟢' : '🔴';

        $text = "🏛 <b>OYLIK MOLIYAVIY HISOBOT</b>\n"
            . "📅 <b>Oy:</b> {$monthName}\n"
            . "──────────────────\n"
            . "🟢 <b>Jami oylik tushum:</b> +" . number_format($totalIncome, 0, '.', ' ') . " so'm\n"
            . "🔴 <b>Jami oylik chiqim:</b> -" . number_format($totalExpense, 0, '.', ' ') . " so'm\n"
            . "{$netEmoji} <b>Oylik sof natija:</b> {$netSign}" . number_format($netBalance, 0, '.', ' ') . " so'm\n"
            . "──────────────────\n";

        if (! empty($expenseByCategory)) {
            $text .= "\n📂 <b>Kategoriyalar bo'yicha tahlil:</b>\n";
            arsort($expenseByCategory);
            foreach ($expenseByCategory as $cat => $sum) {
                $percent = $totalExpense > 0 ? round(($sum / $totalExpense) * 100, 1) : 0;
                $text .= " • {$cat}: -" . number_format($sum, 0, '.', ' ') . " so'm ({$percent}%)\n";
            }
        }

        $text .= "\n\n<i>#oylik_hisobot #moliya</i>";

        $targetChat = $chatId ?: $this->client->defaultChatId();
        if (! $targetChat) {
            return false;
        }

        return $this->client->sendMessage($targetChat, $text) !== null;
    }
}

