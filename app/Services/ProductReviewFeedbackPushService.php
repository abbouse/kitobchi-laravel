<?php

namespace App\Services;

use App\Models\BookClub;
use App\Models\ConnectedDevice;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ProductReviewFeedbackPushService
{
    private const SUPPORTED_LOCALES = ['uz', 'ru', 'en', 'ja'];

    public function sendForPost(BookClub $post, float $score): bool
    {
        if (!$post->product_id || !in_array((string) $post->product_type, ['book', 'stationery'], true)) {
            return false;
        }

        $user = $post->user()->first(['id', 'locale']);
        if (!$user) {
            return false;
        }

        $tokens = $this->tokensForUser((int) $user->id);
        if ($tokens->isEmpty()) {
            return false;
        }

        [$title, $body] = $this->messageFor($this->resolveLocale($user->locale ?? null), $score);

        $result = (new FCMService('kitobchi'))->send(
            $tokens->all(),
            $title,
            $body,
            [
                'type' => 'product_review_feedback',
                'product_id' => (string) $post->product_id,
                'product_type' => (string) $post->product_type,
                'post_id' => (string) $post->id,
                'score' => number_format($score, 2, '.', ''),
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            ],
        );

        Log::info('Product review feedback push sent', [
            'post_id' => $post->id,
            'user_id' => $user->id,
            'product_id' => $post->product_id,
            'product_type' => $post->product_type,
            'score' => $score,
            'tokens' => $tokens->count(),
            'result' => $result,
        ]);

        return empty($result['error']) && ((int) ($result['success'] ?? 0)) > 0;
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

    private function messageFor(string $locale, float $score): array
    {
        $rounded = number_format($score, 1, '.', '');

        return match ($locale) {
            'ru' => [
                'title' => 'Kitobchi AI заглянул в отзыв',
                'body' => "Ваш отзыв получил {$rounded}/5. Без паники: AI просто любит умничать 😄 Нажмите и посмотрите товар.",
            ],
            'en' => [
                'title' => 'Kitobchi AI reviewed your post',
                'body' => "Your review got {$rounded}/5. No stress, our AI is just being dramatic 😄 Tap to revisit the product.",
            ],
            'ja' => [
                'title' => 'Kitobchi AI が感想を見ました',
                'body' => "あなたの投稿は {$rounded}/5 でした。AI がちょっと真面目すぎるだけです 😄 商品ページを開いてみましょう。",
            ],
            default => [
                'title' => 'Kitobchi AI izohingizga ko‘z tashladi',
                'body' => "Izohingiz {$rounded}/5 baho oldi. Xavotir yo‘q, AI biroz sinchkov xolos 😄 Bosib, mahsulotga qaytib ko‘ring.",
            ],
        };
    }
}
