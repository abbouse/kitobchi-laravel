<?php

namespace App\Services;

use App\Models\Sold;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class CashbackNotificationService
{
    public function sendAwarded(User $user, Sold $order, int $amount, bool $instant = false): bool
    {
        $token = trim((string) ($user->fcm_token ?? ''));
        if ($token === '') {
            return false;
        }

        [$title, $body] = $this->messageFor($user->locale ?? 'uz', $amount, $instant);

        $result = (new FCMService('kitobchi'))->send(
            [$token],
            $title,
            $body,
            [
                'type' => 'cashback_awarded',
                'order_id' => (string) $order->id,
                'amount' => (string) $amount,
                'instant' => $instant ? '1' : '0',
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            ],
        );

        if (!empty($result['error'])) {
            Log::warning('Cashback push send failed', [
                'user_id' => $user->id,
                'order_id' => $order->id,
                'error' => $result['error'],
            ]);
            return false;
        }

        return ((int) ($result['success'] ?? 0)) > 0;
    }

    public function sendAwardedBatch(User $user, int $amount, int $ordersCount): bool
    {
        $token = trim((string) ($user->fcm_token ?? ''));
        if ($token === '') {
            return false;
        }

        [$title, $body] = $this->batchMessageFor($user->locale ?? 'uz', $amount, $ordersCount);

        $result = (new FCMService('kitobchi'))->send(
            [$token],
            $title,
            $body,
            [
                'type' => 'cashback_awarded_batch',
                'amount' => (string) $amount,
                'orders_count' => (string) $ordersCount,
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            ],
        );

        if (!empty($result['error'])) {
            Log::warning('Cashback batch push send failed', [
                'user_id' => $user->id,
                'error' => $result['error'],
            ]);
            return false;
        }

        return ((int) ($result['success'] ?? 0)) > 0;
    }

    private function messageFor(string $locale, int $amount, bool $instant): array
    {
        $sum = number_format($amount, 0, '.', ' ');

        return match (strtolower($locale)) {
            'ru' => $instant
                ? ['Кэшбэк начислен', "Вам сразу начислен кэшбэк {$sum} сум за покупку в магазине. Продолжайте покупать книги в Kitobchi."]
                : ['Кэшбэк подтвержден', "Ваш кэшбэк {$sum} сум подтвержден и зачислен. Самое время вернуться за следующей книгой в Kitobchi."],
            'en' => $instant
                ? ['Cashback added', "You received {$sum} UZS cashback instantly for your in-store purchase. Keep shopping with Kitobchi."]
                : ['Cashback confirmed', "Your {$sum} UZS cashback has been confirmed and added. Time to come back for your next book on Kitobchi."],
            'ja' => $instant
                ? ['キャッシュバック付与', "店舗購入の特典として {$sum} UZS のキャッシュバックがすぐに追加されました。Kitobchi で次の本もぜひ。"]
                : ['キャッシュバック確定', "{$sum} UZS のキャッシュバックが確定して残高に追加されました。Kitobchi で次の一冊を探しましょう。"],
            default => $instant
                ? ['Keshbek tushdi', "Do'kon ichidagi xaridingiz uchun {$sum} so'm keshbek shu zahoti hisobingizga tushdi. Kitobchi bilan xaridni davom ettiring."]
                : ['Keshbek tasdiqlandi', "{$sum} so'm keshbek hisobingizga tushdi. Endi Kitobchi'dan keyingi kitobingizni tanlash vaqti keldi."],
        };
    }

    private function batchMessageFor(string $locale, int $amount, int $ordersCount): array
    {
        $sum = number_format($amount, 0, '.', ' ');

        return match (strtolower($locale)) {
            'ru' => ['Кэшбэк подтвержден', "{$ordersCount} покупок подтверждены, и вам начислен общий кэшбэк {$sum} сум. Возвращайтесь за следующими книгами в Kitobchi."],
            'en' => ['Cashback confirmed', "Your cashback for {$ordersCount} purchases has been confirmed: {$sum} UZS total. Come back for your next books on Kitobchi."],
            'ja' => ['キャッシュバック確定', "{$ordersCount}件の購入分として合計 {$sum} UZS のキャッシュバックが追加されました。Kitobchi で次の本もぜひ。"],
            default => ['Keshbek tasdiqlandi', "{$ordersCount} ta xaridingiz bo'yicha jami {$sum} so'm keshbek hisobingizga tushdi. Kitobchi bilan keyingi xaridni davom ettiring."],
        };
    }
}
