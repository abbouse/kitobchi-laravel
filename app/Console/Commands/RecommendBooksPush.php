<?php

namespace App\Console\Commands;

use App\Models\Books;
use App\Models\User;
use App\Services\FCMService;
use App\Services\ReadingIntelligence\UserTasteProfileService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Foydalanuvchilarga davriy ravishda kitob tavsiya qiluvchi push xabar:
 *   - Xarid tarixi BOR foydalanuvchiga — eng ko'p xarid qilgan
 *     kategoriyasidan, u hali sotib olmagan, eng ko'p sotilgan kitob
 *     tavsiya qilinadi ("avvalgi xaridlaringizga o'xshash").
 *   - Xarid tarixi YO'Q (yoki kategoriyasi aniqlanmagan) foydalanuvchiga —
 *     umumiy eng ko'p sotilgan kitob tavsiya qilinadi (zaxira).
 *
 * Push bosilganda foydalanuvchi to'g'ridan-to'g'ri shu kitob sahifasiga
 * o'tadi — bu allaqachon ishlatilayotgan `type=product` deep-link
 * kontraktidan foydalanadi (qarang: `ProductStockAlertService`,
 * Flutter tomonda `NotificationService.dart`), shuning uchun ilova
 * tomonida hech qanday o'zgarish shart emas.
 */
class RecommendBooksPush extends Command
{
    protected $signature = 'users:recommend-books';

    protected $description = "Foydalanuvchilarga avvalgi xaridlariga o'xshash yoki eng ko'p sotilgan kitoblarni tavsiya qiluvchi push xabar";

    private const TEXTS = [
        'uz' => [
            'purchase_based' => [
                'title' => '📚 Sizga yoqishi mumkin',
                'body'  => '":name" — avval sotib olgan kitoblaringizga juda o\'xshaydi. Ko\'rib qo\'ying!',
            ],
            'bestseller' => [
                'title' => '🔥 Eng ko\'p sotilayotgan kitob',
                'body'  => '":name" — hozir eng ko\'p sotib olinayotgan kitoblardan biri. O\'zingiz ham sinab ko\'ring!',
            ],
        ],
        'ru' => [
            'purchase_based' => [
                'title' => '📚 Это может вам понравиться',
                'body'  => '":name" — очень похоже на книги, которые вы уже покупали. Загляните!',
            ],
            'bestseller' => [
                'title' => '🔥 Самая продаваемая книга',
                'body'  => '":name" — сейчас один из самых популярных бестселлеров. Попробуйте и вы!',
            ],
        ],
        'en' => [
            'purchase_based' => [
                'title' => '📚 You might like this',
                'body'  => '":name" is a lot like the books you\'ve bought before. Take a look!',
            ],
            'bestseller' => [
                'title' => "🔥 Today's best-seller",
                'body'  => '":name" is one of the best-selling books right now. Give it a try!',
            ],
        ],
        'ja' => [
            'purchase_based' => [
                'title' => '📚 気に入るかもしれません',
                'body'  => '「:name」は、これまで購入した本とよく似ています。ぜひご覧ください！',
            ],
            'bestseller' => [
                'title' => '🔥 今売れている本',
                'body'  => '「:name」は今、最も売れている本の一つです。ぜひお試しください！',
            ],
        ],
    ];

    public function handle(): void
    {
        $this->info(now()->format('d.m.Y H:i:s') . " — Kitob tavsiya push boshlandi...");

        $tasteProfile = app(UserTasteProfileService::class);

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

        $this->info('Foydalanuvchilar soni: ' . count($userIds));

        // Eng ko'p sotilgan kitoblar — zaxira (bestseller) tavsiyasi uchun
        // bir marta oldindan olinadi, har bir foydalanuvchi uchun qayta
        // so'ralmaydi.
        $globalBestsellers = Books::query()
            ->activeForVector()
            ->orderByDesc('totalSales')
            ->limit(30)
            ->get(['id', 'name', 'category_id']);

        $sent = 0;
        $skip = 0;

        foreach ($userIds as $userId) {
            $user = User::find($userId);
            if (! $user) {
                $skip++;
                continue;
            }

            $purchasedIds = $tasteProfile->purchasedProductIds($user->id, 'book');

            $candidate = null;
            $variant = 'bestseller';

            $categoryId = $tasteProfile->dominantCategoryId($user->id, 'book');
            if ($categoryId) {
                $candidate = Books::query()
                    ->activeForVector()
                    ->where('category_id', $categoryId)
                    ->when(! empty($purchasedIds), fn ($q) => $q->whereNotIn('id', $purchasedIds))
                    ->orderByDesc('totalSales')
                    ->first(['id', 'name']);

                if ($candidate) {
                    $variant = 'purchase_based';
                }
            }

            if (! $candidate) {
                $candidate = $globalBestsellers->first(
                    fn ($book) => ! in_array((int) $book->id, $purchasedIds, true)
                );
            }

            if (! $candidate) {
                $skip++;
                continue;
            }

            $this->sendRecommendation($user, $candidate, $variant) ? $sent++ : $skip++;
        }

        $this->info("Yuborildi: {$sent} | O'tkazib yuborildi: {$skip}");
    }

    private function sendRecommendation(User $user, Books $book, string $variant): bool
    {
        try {
            $lang = in_array($user->locale ?? 'uz', ['uz', 'ru', 'en', 'ja'])
                ? ($user->locale ?? 'uz')
                : 'uz';

            $msg = self::TEXTS[$lang][$variant];
            $title = $msg['title'];
            $body = str_replace(':name', (string) $book->name, $msg['body']);

            $tokens = DB::table('connected_devices')
                ->where('user_type', 'user')
                ->where('user_id', $user->id)
                ->whereNotNull('fcm_token')
                ->pluck('fcm_token')
                ->unique()
                ->values()
                ->toArray();

            if (empty($tokens)) {
                return false;
            }

            $payload = [
                'type' => 'product',
                'product_id' => (string) $book->id,
                'product_type' => 'book',
            ];

            $result = (new FCMService('kitobchi'))->send($tokens, $title, $body, $payload);

            Log::info("RecommendBooksPush: user #{$user->id}, lang={$lang}, variant={$variant}, book #{$book->id}", [
                'result' => $result,
            ]);

            $this->line("  ✓ user #{$user->id} [{$lang}] [{$variant}] → \"{$book->name}\"");

            return true;
        } catch (\Throwable $e) {
            Log::error("RecommendBooksPush xatosi (user #{$user->id}): {$e->getMessage()}");
            $this->error("  ✗ user #{$user->id}: {$e->getMessage()}");

            return false;
        }
    }
}
