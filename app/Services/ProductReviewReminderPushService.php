<?php

namespace App\Services;

use App\Models\FcmNotifications;
use App\Models\ProductReviewPrompt;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ProductReviewReminderPushService
{
    private const SUPPORTED_LOCALES = ['uz', 'ru', 'en', 'ja'];

    private const TEXTS = [
        'uz' => [
            'first' => [
                'title' => 'Mahsulot sizga qanday tuyuldi?',
                'body' => '“:name” bilan tanishib ulgurdingizmi? Qisqagina fikr qoldirsangiz, boshqalar ham sizdan minnatdor bo‘ladi 😄',
            ],
            'second' => [
                'title' => 'Hali ham fikringizni kutyapmiz',
                'body' => '“:name” haqida ikki og‘iz gap ham katta yordam. Kitobchi ham, odamlar ham qiziqib turibdi 👀',
            ],
        ],
        'ru' => [
            'first' => [
                'title' => 'Как вам товар?',
                'body' => 'Уже успели познакомиться с “:name”? Короткий отзыв очень поможет другим 😄',
            ],
            'second' => [
                'title' => 'Мы всё ещё ждём ваше мнение',
                'body' => 'Пара слов о “:name” уже будет полезной. И людям, и Китобчи очень интересно 👀',
            ],
        ],
        'en' => [
            'first' => [
                'title' => 'How did the product feel?',
                'body' => 'Had a chance to try “:name” yet? A quick review would really help other readers 😄',
            ],
            'second' => [
                'title' => 'Still waiting for your take',
                'body' => 'Even a short thought on “:name” would help a lot. People and Kitobchi are curious 👀',
            ],
        ],
        'ja' => [
            'first' => [
                'title' => '商品はいかがでしたか？',
                'body' => '「:name」はもう試せましたか？ひとこと感想があると他の人の助けになります 😄',
            ],
            'second' => [
                'title' => 'まだ感想をお待ちしています',
                'body' => '「:name」について短いひとことでも大歓迎です。Kitobchi もみんなも気になっています 👀',
            ],
        ],
    ];

    public function send(ProductReviewPrompt $prompt): bool
    {
        $user = $prompt->user;
        if (!$user) {
            return false;
        }

        $tokens = $this->tokensForUser((int) $user->id);
        if ($tokens->isEmpty()) {
            return false;
        }

        $locale = $this->resolveLocale($user->locale ?? null);
        $stage = $prompt->first_sent_at === null ? 'first' : 'second';
        $productName = trim((string) ($prompt->product_name ?: 'Mahsulot'));

        $title = self::TEXTS[$locale][$stage]['title'];
        $body = str_replace(':name', $productName, self::TEXTS[$locale][$stage]['body']);

        $payload = [
            'type' => 'product',
            'product_id' => (string) $prompt->product_id,
            'product_type' => (string) $prompt->product_type,
            'review_prompt_stage' => $stage,
            'sold_id' => (string) $prompt->sold_id,
        ];

        $result = (new FCMService('kitobchi'))->send($tokens->all(), $title, $body, $payload);

        Log::info('Product review reminder push sent', [
            'prompt_id' => $prompt->id,
            'user_id' => $user->id,
            'product_id' => $prompt->product_id,
            'product_type' => $prompt->product_type,
            'stage' => $stage,
            'tokens' => $tokens->count(),
            'result' => $result,
        ]);

        $success = empty($result['error']) && ((int) ($result['success'] ?? 0)) > 0;
        if ($success) {
            FcmNotifications::query()->create([
                'name' => $title,
                'description' => $body,
                'who' => (string) $user->id,
                'source' => 'product_review_reminder',
                'delivery_status' => 'sent',
                'sent_count' => (int) ($result['success'] ?? 0),
                'failed_count' => (int) ($result['failure'] ?? 0),
                'is_read' => false,
            ]);
        }

        return $success;
    }

    private function tokensForUser(int $userId): Collection
    {
        return collect(app(FcmRecipientService::class)->tokensFor('user', $userId));
    }

    private function resolveLocale(?string $locale): string
    {
        return in_array($locale, self::SUPPORTED_LOCALES, true) ? $locale : 'uz';
    }
}
