<?php

namespace App\Services;

use App\Models\ConnectedDevice;
use App\Models\Sold;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class OrderStatusPushService
{
    private const SUPPORTED_LOCALES = ['uz', 'ru', 'en', 'ja'];

    private const STATUS_STAGE = [
        'A' => 1,
        'P' => 2,
        'B' => 3,
        'C' => 4,
        'D' => 5,
        'F' => 6,
    ];

    private const STATUS_LABELS = [
        'uz' => [
            'A' => 'Kutilmoqda',
            'P' => "Qadoqlanmoqda",
            'B' => "Yo'lda",
            'C' => 'Yetib bordi',
            'D' => 'Mijoz qabul qildi',
            'F' => 'Bekor qilindi',
        ],
        'ru' => [
            'A' => 'Ожидает',
            'P' => 'Собирается',
            'B' => 'В пути',
            'C' => 'Прибыл',
            'D' => 'Получен клиентом',
            'F' => 'Отменён',
        ],
        'en' => [
            'A' => 'Pending',
            'P' => 'Packing',
            'B' => 'On the way',
            'C' => 'Arrived',
            'D' => 'Received by customer',
            'F' => 'Cancelled',
        ],
        'ja' => [
            'A' => '受付待ち',
            'P' => '梱包中',
            'B' => '配送中',
            'C' => '配達完了',
            'D' => '受け取り済み',
            'F' => 'キャンセルされました',
        ],
    ];

    private const DEFAULT_MESSAGES = [
        'uz' => [
            'title' => 'Buyurtma holati yangilandi',
            'body' => "Buyurtmangizning yangi holati: :status",
        ],
        'ru' => [
            'title' => 'Статус заказа обновлён',
            'body' => 'Новый статус вашего заказа: :status',
        ],
        'en' => [
            'title' => 'Order status updated',
            'body' => 'Your order status is now: :status',
        ],
        'ja' => [
            'title' => '注文ステータスが更新されました',
            'body' => 'ご注文の現在のステータス: :status',
        ],
    ];

    private const REGRESSION_MESSAGES = [
        'uz' => [
            'title' => 'Uzr, holatni to‘g‘riladik',
            'body' => "Kichik anglashilmovchilik bo'ldi 😅 Buyurtmangizning to'g'ri holati: :status",
        ],
        'ru' => [
            'title' => 'Извините, статус уточнили',
            'body' => 'Небольшое недоразумение 😅 Правильный статус вашего заказа: :status',
        ],
        'en' => [
            'title' => 'Sorry, we corrected the status',
            'body' => 'There was a small mix-up 😅 Your correct order status is: :status',
        ],
        'ja' => [
            'title' => 'ステータスを訂正しました',
            'body' => '少し行き違いがありました 😅 正しいご注文ステータス: :status',
        ],
    ];

    private const RECOVERED_PAYMENT_MESSAGES = [
        'uz' => [
            'title' => 'Uzr, to‘lovingizni hozir tasdiqladik',
            'body' => "To‘lov oldinroq qabul qilingan ekan. Endi buyurtmangiz muvaffaqiyatli tasdiqlandi 🙏",
        ],
        'ru' => [
            'title' => 'Извините, мы только что подтвердили оплату',
            'body' => 'Оплата прошла чуть раньше. Сейчас заказ успешно подтверждён 🙏',
        ],
        'en' => [
            'title' => 'Sorry, your payment has just been confirmed',
            'body' => 'Your payment had already gone through. We have now confirmed your order successfully 🙏',
        ],
        'ja' => [
            'title' => 'お支払いを確認しました',
            'body' => 'お支払いは先に完了していました。ご注文を正常に確定しました 🙏',
        ],
    ];

    private const ETA_CHANGED_MESSAGES = [
        'uz' => [
            'title' => 'Uzr, yetkazish vaqtini yangiladik',
            'body' => "Buyurtmangiz yo'lda, faqat kichik sarguzashtga chiqib qoldi 😅 Yangi taxminiy vaqt: :date",
            'body_without_date' => "Buyurtmangiz yo'lda, faqat kichik sarguzashtga chiqib qoldi 😅 Yetkazish muddatini biroz yangiladik.",
        ],
        'ru' => [
            'title' => 'Извините, мы обновили срок доставки',
            'body' => 'Ваш заказ уже в пути, просто заехал в маленькое приключение 😅 Новое ожидаемое время: :date',
            'body_without_date' => 'Ваш заказ уже в пути, просто заехал в маленькое приключение 😅 Мы немного обновили срок доставки.',
        ],
        'en' => [
            'title' => 'Sorry, we updated the delivery time',
            'body' => 'Your order is still on the way, it just took a tiny adventure detour 😅 New estimated time: :date',
            'body_without_date' => 'Your order is still on the way, it just took a tiny adventure detour 😅 We slightly updated the delivery time.',
        ],
        'ja' => [
            'title' => 'お届け予定を更新しました',
            'body' => 'ご注文は引き続き配送中ですが、少しだけ寄り道しています 😅 新しいお届け目安: :date',
            'body_without_date' => 'ご注文は引き続き配送中ですが、少しだけ寄り道しています 😅 お届け予定を少し更新しました。',
        ],
    ];

    private const SELLER_ITEM_UNAVAILABLE_MESSAGES = [
        'uz' => [
            'title_single' => 'Bitta mahsulot topilmay qoldi',
            'body_single' => ":itemni do'kon topib bera olmadi 😅 Xavotir olmang, qolganlari joyida — ularni sizga yetkazamiz.",
            'title_multi' => 'Buyurtmada bir nechta mahsulot topilmadi',
            'body_multi' => "Buyurtmadagi ayrim mahsulotlarni do'kon topib bera olmadi 😅 Xavotir olmang, qolganlari joyida — ularni sizga yetkazamiz.",
        ],
        'ru' => [
            'title_single' => 'Один товар не нашёлся',
            'body_single' => 'Магазин не смог найти товар «:item» 😅 Не переживайте, остальные позиции на месте — доставим их вам.',
            'title_multi' => 'Часть товаров не нашлась',
            'body_multi' => 'Магазин не смог найти часть товаров 😅 Не переживайте, остальные позиции на месте — доставим их вам.',
        ],
        'en' => [
            'title_single' => 'One item went missing',
            'body_single' => 'The store could not find ":item" 😅 No worries, the rest of your order is safe and on the way.',
            'title_multi' => 'A few items went missing',
            'body_multi' => 'The store could not find a few items from your order 😅 No worries, the rest is safe and on the way.',
        ],
        'ja' => [
            'title_single' => '1点ご用意できませんでした',
            'body_single' => '「:item」は店舗でご用意できませんでした 😅 でもご安心ください。残りの商品はそのままお届けします。',
            'title_multi' => '一部商品をご用意できませんでした',
            'body_multi' => 'ご注文の一部商品を店舗でご用意できませんでした 😅 でもご安心ください。残りの商品はそのままお届けします。',
        ],
    ];

    public function sendForTransition(Sold $order, ?string $previousStatus, string $newStatus): void
    {
        if ($previousStatus === $newStatus || $this->visibleStateKey($previousStatus) === $this->visibleStateKey($newStatus)) {
            return;
        }

        $user = $order->user()->first(['id', 'locale']);
        if (!$user) {
            return;
        }

        $tokens = $this->tokensForUser($user->id);
        if ($tokens->isEmpty()) {
            return;
        }

        $locale = $this->resolveLocale($user->locale ?? null);
        $statusLabel = $this->statusLabel($locale, $newStatus);
        $isRegression = $this->isRegression($previousStatus, $newStatus);
        $template = $isRegression
            ? self::REGRESSION_MESSAGES[$locale]
            : self::DEFAULT_MESSAGES[$locale];

        $title = $template['title'];
        $body = str_replace(':status', $statusLabel, $template['body']);

        $payload = [
            'type' => 'order_status',
            'order_id' => (string) $order->id,
            'status' => $newStatus,
            'previous_status' => (string) ($previousStatus ?? ''),
            'is_regression' => $isRegression ? '1' : '0',
        ];

        $result = (new FCMService('kitobchi'))->send($tokens->all(), $title, $body, $payload);

        Log::info('Order status push sent', [
            'order_id' => $order->id,
            'user_id' => $user->id,
            'locale' => $locale,
            'from' => $previousStatus,
            'to' => $newStatus,
            'is_regression' => $isRegression,
            'tokens' => $tokens->count(),
            'result' => $result,
        ]);
    }

    public function sendRecoveredPaymentNotice(Sold $order): void
    {
        $user = $order->user()->first(['id', 'locale']);
        if (!$user) {
            return;
        }

        $tokens = $this->tokensForUser($user->id);
        if ($tokens->isEmpty()) {
            return;
        }

        $locale = $this->resolveLocale($user->locale ?? null);
        $template = self::RECOVERED_PAYMENT_MESSAGES[$locale];

        $payload = [
            'type' => 'order_payment_recovered',
            'order_id' => (string) $order->id,
            'status' => (string) ($order->status_code ?? $order->status ?? ''),
            'payment_status' => (string) ($order->payment_status_code ?? $order->paymentStatus ?? ''),
        ];

        $result = (new FCMService('kitobchi'))->send(
            $tokens->all(),
            $template['title'],
            $template['body'],
            $payload,
        );

        Log::info('Recovered payment push sent', [
            'order_id' => $order->id,
            'user_id' => $user->id,
            'locale' => $locale,
            'tokens' => $tokens->count(),
            'result' => $result,
        ]);
    }

    public function sendEtaChangedNotice(
        Sold $order,
        ?CarbonInterface $previousEta,
        ?CarbonInterface $newEta,
    ): void {
        if ($previousEta && $newEta && $previousEta->equalTo($newEta)) {
            return;
        }

        $user = $order->user()->first(['id', 'locale']);
        if (!$user) {
            return;
        }

        $tokens = $this->tokensForUser($user->id);
        if ($tokens->isEmpty()) {
            return;
        }

        $locale = $this->resolveLocale($user->locale ?? null);
        $template = self::ETA_CHANGED_MESSAGES[$locale];
        $formattedEta = $this->formatEtaForLocale($newEta, $locale);

        $body = $formattedEta !== null
            ? str_replace(':date', $formattedEta, $template['body'])
            : $template['body_without_date'];

        $payload = [
            'type' => 'order_eta_changed',
            'order_id' => (string) $order->id,
            'previous_eta' => $previousEta?->toIso8601String() ?? '',
            'new_eta' => $newEta?->toIso8601String() ?? '',
            'status' => (string) ($order->status_code ?? $order->status ?? ''),
        ];

        $result = (new FCMService('kitobchi'))->send(
            $tokens->all(),
            $template['title'],
            $body,
            $payload,
        );

        Log::info('Order ETA change push sent', [
            'order_id' => $order->id,
            'user_id' => $user->id,
            'locale' => $locale,
            'previous_eta' => $previousEta?->toIso8601String(),
            'new_eta' => $newEta?->toIso8601String(),
            'tokens' => $tokens->count(),
            'result' => $result,
        ]);
    }

    public function sendSellerItemsUnavailableNotice(Sold $order, array $itemTitles): void
    {
        $itemTitles = collect($itemTitles)
            ->filter(fn ($title) => is_string($title) && trim($title) !== '')
            ->map(fn ($title) => trim($title))
            ->unique()
            ->values();

        if ($itemTitles->isEmpty()) {
            return;
        }

        $user = $order->user()->first(['id', 'locale']);
        if (!$user) {
            return;
        }

        $tokens = $this->tokensForUser($user->id);
        if ($tokens->isEmpty()) {
            return;
        }

        $locale = $this->resolveLocale($user->locale ?? null);
        $template = self::SELLER_ITEM_UNAVAILABLE_MESSAGES[$locale];
        $isSingle = $itemTitles->count() === 1;

        $title = $isSingle ? $template['title_single'] : $template['title_multi'];
        $body = $isSingle
            ? str_replace(':item', $itemTitles->first(), $template['body_single'])
            : $template['body_multi'];

        $payload = [
            'type' => 'seller_item_unavailable',
            'order_id' => (string) $order->id,
            'item_count' => (string) $itemTitles->count(),
            'item_titles' => $itemTitles->take(5)->implode(' | '),
            'status' => (string) ($order->status_code ?? $order->status ?? ''),
        ];

        $result = (new FCMService('kitobchi'))->send(
            $tokens->all(),
            $title,
            $body,
            $payload,
        );

        Log::info('Seller unavailable item push sent', [
            'order_id' => $order->id,
            'user_id' => $user->id,
            'locale' => $locale,
            'item_titles' => $itemTitles->all(),
            'tokens' => $tokens->count(),
            'result' => $result,
        ]);
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

    private function resolveLocale(?string $locale): string
    {
        return in_array($locale, self::SUPPORTED_LOCALES, true) ? $locale : 'uz';
    }

    private function statusLabel(string $locale, string $status): string
    {
        return self::STATUS_LABELS[$locale][$status]
            ?? self::STATUS_LABELS['uz'][$status]
            ?? $status;
    }

    private function isRegression(?string $previousStatus, string $newStatus): bool
    {
        if (!$previousStatus || !isset(self::STATUS_STAGE[$previousStatus], self::STATUS_STAGE[$newStatus])) {
            return false;
        }

        if ($previousStatus === 'F' && $newStatus !== 'F') {
            return true;
        }

        if ($newStatus === 'F') {
            return false;
        }

        return self::STATUS_STAGE[$newStatus] < self::STATUS_STAGE[$previousStatus];
    }

    private function visibleStateKey(?string $status): ?string
    {
        return match ($status) {
            'A' => 'pending',
            'P' => 'packing',
            'B' => 'shipping',
            'C' => 'delivered',
            'D' => 'done',
            'F' => 'cancelled',
            default => null,
        };
    }

    private function formatEtaForLocale(?CarbonInterface $eta, string $locale): ?string
    {
        if (!$eta) {
            return null;
        }

        $date = $eta->copy()->timezone(config('app.timezone'))->format('d.m.Y H:i');

        return match ($locale) {
            'ru' => $date,
            'en' => $date,
            'ja' => $date,
            default => $date,
        };
    }
}
