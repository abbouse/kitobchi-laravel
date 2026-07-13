<?php

namespace App\Services;

use App\Models\ConnectedDevice;
use App\Models\SplitContract;
use App\Models\SplitInstallment;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Split bo'yicha foydalanuvchi pushlari:
 * - to'lovdan oldin eslatma (kartada pul bo'lsin)
 * - oylik to'lov bekor qilingan mahsulot krediti hisobidan yopilgani haqida xabar
 *
 * Qoida: agar oylik to'lov kredit bilan to'liq yopilgan bo'lsa, "to'lov qiling"
 * eslatmasi YUBORILMAYDI — o'rniga "yopildi" xabari boradi.
 */
class SplitPushService
{
    private const SUPPORTED_LOCALES = ['uz', 'ru', 'en', 'ja'];

    private const REMINDER_MESSAGES = [
        'uz' => [
            'title' => "Split to'lovi yaqinlashdi",
            'body' => ":date kuni kartangizdan :amount so'm yechiladi. Kartada mablag' bo'lishini ta'minlang.",
        ],
        'ru' => [
            'title' => 'Скоро платёж по рассрочке',
            'body' => ':date с вашей карты спишется :amount сум. Убедитесь, что на карте достаточно средств.',
        ],
        'en' => [
            'title' => 'Installment payment coming up',
            'body' => ':amount UZS will be charged to your card on :date. Please make sure funds are available.',
        ],
        'ja' => [
            'title' => '分割払いのお支払い日が近づいています',
            'body' => ':date にカードから :amount スムが引き落とされます。残高をご確認ください。',
        ],
    ];

    private const COVERED_MESSAGES = [
        'uz' => [
            'title' => "Oylik to'lovingiz yopildi",
            'body' => "Bekor qilingan mahsulot hisobidan :date dagi to'lovingiz to'liq qoplandi — bu oy pul yechilmaydi.",
        ],
        'ru' => [
            'title' => 'Ежемесячный платёж закрыт',
            'body' => 'Платёж на :date полностью покрыт за счёт отменённого товара — в этом месяце списания не будет.',
        ],
        'en' => [
            'title' => 'Your installment is covered',
            'body' => 'Your payment due :date was fully covered by a cancelled item credit — nothing will be charged.',
        ],
        'ja' => [
            'title' => '今月のお支払いは不要です',
            'body' => 'キャンセルされた商品分のクレジットにより :date のお支払いは全額カバーされました。',
        ],
    ];

    private const LIMIT_GRANTED_MESSAGES = [
        'uz' => [
            'title' => "Sizga nasiya limiti ochildi! 🎉",
            'body' => "Tabriklaymiz! Sizga :amount so'mlik nasiya limiti berildi. Endi kitoblarni bo'lib to'lash bilan xarid qilishingiz mumkin.",
        ],
        'ru' => [
            'title' => 'Вам открыт лимит рассрочки! 🎉',
            'body' => 'Поздравляем! Вам доступен лимит рассрочки :amount сум. Теперь вы можете покупать книги в рассрочку.',
        ],
        'en' => [
            'title' => 'Installment limit unlocked! 🎉',
            'body' => 'Congrats! You now have an installment limit of :amount UZS. Buy books and pay over time.',
        ],
        'ja' => [
            'title' => '分割払い枠が開放されました！🎉',
            'body' => 'おめでとうございます！:amount スムの分割払い枠が利用可能になりました。',
        ],
    ];

    private const LIMIT_PROMO_MESSAGES = [
        'uz' => [
            'title' => "Nasiya limitingiz kutmoqda 📚",
            'body' => ":amount so'mlik bo'sh nasiya limitingiz bor. Yoqqan kitobni hoziroq olib, bo'lib to'lang!",
        ],
        'ru' => [
            'title' => 'Ваш лимит рассрочки ждёт 📚',
            'body' => 'У вас свободный лимит рассрочки :amount сум. Возьмите любимую книгу сейчас и платите по частям!',
        ],
        'en' => [
            'title' => 'Your installment limit awaits 📚',
            'body' => 'You have :amount UZS of unused installment limit. Grab a book now and pay over time!',
        ],
        'ja' => [
            'title' => '分割払い枠が利用可能です 📚',
            'body' => ':amount スムの分割払い枠が未使用です。今すぐ本を選んで分割払いで購入しましょう！',
        ],
    ];

    /**
     * Limit berilganda bir martalik tabrik push (skoring yoki admin orqali).
     */
    public function sendLimitGranted(?User $user, int $limit): bool
    {
        if ($limit <= 0) {
            return false;
        }

        return $this->send(
            $user,
            self::LIMIT_GRANTED_MESSAGES,
            [':amount' => number_format($limit, 0, '.', ' ')],
            [
                'type' => 'split_limit_granted',
                'limit' => (string) $limit,
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            ],
        );
    }

    /**
     * Haftalik promo: bo'sh limitni eslatish.
     */
    public function sendLimitPromo(?User $user, int $availableLimit): bool
    {
        if ($availableLimit <= 0) {
            return false;
        }

        return $this->send(
            $user,
            self::LIMIT_PROMO_MESSAGES,
            [':amount' => number_format($availableLimit, 0, '.', ' ')],
            [
                'type' => 'split_limit_promo',
                'available_limit' => (string) $availableLimit,
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            ],
        );
    }

    public function sendInstallmentReminder(SplitInstallment $installment): bool
    {
        $remaining = max(0, (int) $installment->amount - (int) $installment->paid_amount);
        if ($remaining <= 0 || $installment->status !== SplitInstallment::STATUS_PENDING) {
            return false;
        }

        return $this->send(
            $installment->user,
            self::REMINDER_MESSAGES,
            [
                ':date' => optional($installment->due_at)->format('d.m.Y') ?? '',
                ':amount' => number_format($remaining, 0, '.', ' '),
            ],
            [
                'type' => 'split_installment_reminder',
                'contract_id' => (string) $installment->contract_id,
                'installment_id' => (string) $installment->id,
                'amount' => (string) $remaining,
            ],
        );
    }

    public function sendCoveredByCredit(SplitContract $contract, SplitInstallment $installment): bool
    {
        return $this->send(
            $contract->user,
            self::COVERED_MESSAGES,
            [
                ':date' => optional($installment->due_at)->format('d.m.Y') ?? '',
            ],
            [
                'type' => 'split_installment_covered',
                'contract_id' => (string) $contract->id,
                'installment_id' => (string) $installment->id,
            ],
        );
    }

    private function send(?User $user, array $messages, array $replacements, array $payload): bool
    {
        if (! $user) {
            return false;
        }

        $tokens = $this->tokensForUser((int) $user->id);
        if ($tokens->isEmpty()) {
            return false;
        }

        $locale = in_array($user->locale ?? null, self::SUPPORTED_LOCALES, true) ? $user->locale : 'uz';
        $template = $messages[$locale];
        $body = strtr($template['body'], $replacements);

        try {
            (new FCMService('kitobchi'))->send($tokens->all(), $template['title'], $body, $payload);

            return true;
        } catch (\Throwable $e) {
            Log::warning('[Split] Push send failed', [
                'user_id' => $user->id,
                'type' => $payload['type'] ?? '',
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function tokensForUser(int $userId): Collection
    {
        return ConnectedDevice::query()
            ->where('user_type', 'user')
            ->where('user_id', $userId)
            ->whereNotNull('fcm_token')
            ->where('fcm_token', '!=', '')
            ->pluck('fcm_token')
            ->filter(fn ($token) => is_string($token) && $token !== '')
            ->unique()
            ->values();
    }
}
