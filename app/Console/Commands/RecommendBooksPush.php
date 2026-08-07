<?php

namespace App\Console\Commands;

use App\Models\Books;
use App\Models\Stationery;
use App\Models\User;
use App\Services\FCMService;
use App\Services\ReadingIntelligence\UserTasteProfileService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Foydalanuvchilarga davriy ravishda mahsulot tavsiya qiluvchi push xabar.
 *
 * MUHIM (arxitektura): bu butunlay "Reading Intelligence" AI kartasidan
 * (`ReadingIntelligenceService`/`BookReadingInsight`) MUSTAQIL — hech
 * qanday AI chaqiruvi yo'q, faqat oddiy statistik qoida:
 *   1. Har bir foydalanuvchi uchun avval qaysi TURDAN (kitob yoki
 *      kanselyariya) ko'proq xarid qilgani aniqlanadi
 *      (`UserTasteProfileService::dominantProductType()`). Xarid tarixi
 *      umuman yo'q bo'lsa — standart 'book' (ilova kitob-markazli).
 *   2. Shu TUR ichida — foydalanuvchining eng ko'p xarid qilgan
 *      kategoriyasidan, u hali sotib olmagan, eng ko'p sotilgan mahsulot
 *      tavsiya qilinadi ("avvalgi xaridlaringizga o'xshash").
 *   3. Toping bo'lmasa (kategoriya aniqlanmagan/barchasini sotib olgan) —
 *      o'sha TURDAGI umumiy eng ko'p sotilgan mahsulot (zaxira).
 *
 * Shuning uchun kanselyariya (stationery) uchun ham to'liq ishlaydi —
 * "Reading Intelligence" kartasi (qiyinlik/kayfiyat) faqat kitobga xos
 * bo'lgani uchun item sahifasida cheklangan bo'lsa-da, bu push oddiy
 * "sizga mos mahsulot" tavsiyasi bo'lgani uchun ikkala turga ham baravar
 * tegishli.
 *
 * Push bosilganda foydalanuvchi to'g'ridan-to'g'ri shu mahsulot sahifasiga
 * o'tadi — bu allaqachon ishlatilayotgan `type=product` deep-link
 * kontraktidan foydalanadi (qarang: `ProductStockAlertService`,
 * Flutter tomonda `NotificationService.dart`), shuning uchun ilova
 * tomonida hech qanday o'zgarish shart emas.
 */
class RecommendBooksPush extends Command
{
    protected $signature = 'users:recommend-books';

    protected $description = "Foydalanuvchilarga avvalgi xaridlariga o'xshash yoki eng ko'p sotilgan mahsulotlarni (kitob yoki kanselyariya) tavsiya qiluvchi push xabar";

    private const TEXTS = [
        'uz' => [
            'book' => [
                'purchase_based' => [
                    'title' => '📚 Sizga yoqishi mumkin',
                    'body'  => '":name" — avval sotib olgan kitoblaringizga juda o\'xshaydi. Ko\'rib qo\'ying!',
                ],
                'bestseller' => [
                    'title' => '🔥 Eng ko\'p sotilayotgan kitob',
                    'body'  => '":name" — hozir eng ko\'p sotib olinayotgan kitoblardan biri. O\'zingiz ham sinab ko\'ring!',
                ],
            ],
            'stationery' => [
                'purchase_based' => [
                    'title' => '🖊️ Sizga yoqishi mumkin',
                    'body'  => '":name" — avval sotib olgan kanselyariya buyumlaringizga juda o\'xshaydi. Ko\'rib qo\'ying!',
                ],
                'bestseller' => [
                    'title' => '🔥 Eng ko\'p sotilayotgan mahsulot',
                    'body'  => '":name" — hozir eng ko\'p sotib olinayotganlardan biri. O\'zingiz ham sinab ko\'ring!',
                ],
            ],
        ],
        'ru' => [
            'book' => [
                'purchase_based' => [
                    'title' => '📚 Это может вам понравиться',
                    'body'  => '":name" — очень похоже на книги, которые вы уже покупали. Загляните!',
                ],
                'bestseller' => [
                    'title' => '🔥 Самая продаваемая книга',
                    'body'  => '":name" — сейчас один из самых популярных бестселлеров. Попробуйте и вы!',
                ],
            ],
            'stationery' => [
                'purchase_based' => [
                    'title' => '🖊️ Это может вам понравиться',
                    'body'  => '":name" — очень похоже на канцтовары, которые вы уже покупали. Загляните!',
                ],
                'bestseller' => [
                    'title' => '🔥 Самый продаваемый товар',
                    'body'  => '":name" — сейчас один из самых популярных товаров. Попробуйте и вы!',
                ],
            ],
        ],
        'en' => [
            'book' => [
                'purchase_based' => [
                    'title' => '📚 You might like this',
                    'body'  => '":name" is a lot like the books you\'ve bought before. Take a look!',
                ],
                'bestseller' => [
                    'title' => "🔥 Today's best-seller",
                    'body'  => '":name" is one of the best-selling books right now. Give it a try!',
                ],
            ],
            'stationery' => [
                'purchase_based' => [
                    'title' => '🖊️ You might like this',
                    'body'  => '":name" is a lot like the stationery items you\'ve bought before. Take a look!',
                ],
                'bestseller' => [
                    'title' => "🔥 Today's best-seller",
                    'body'  => '":name" is one of the best-selling items right now. Give it a try!',
                ],
            ],
        ],
        'ja' => [
            'book' => [
                'purchase_based' => [
                    'title' => '📚 気に入るかもしれません',
                    'body'  => '「:name」は、これまで購入した本とよく似ています。ぜひご覧ください！',
                ],
                'bestseller' => [
                    'title' => '🔥 今売れている本',
                    'body'  => '「:name」は今、最も売れている本の一つです。ぜひお試しください！',
                ],
            ],
            'stationery' => [
                'purchase_based' => [
                    'title' => '🖊️ 気に入るかもしれません',
                    'body'  => '「:name」は、これまで購入した文房具とよく似ています。ぜひご覧ください！',
                ],
                'bestseller' => [
                    'title' => '🔥 今売れている商品',
                    'body'  => '「:name」は今、最も売れている商品の一つです。ぜひお試しください！',
                ],
            ],
        ],
    ];

    public function handle(): void
    {
        $this->info(now()->format('d.m.Y H:i:s') . " — Mahsulot tavsiya push boshlandi...");

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

        // Eng ko'p sotilganlar — zaxira (bestseller) tavsiyasi uchun har
        // ikki tur bo'yicha bir marta oldindan olinadi, har bir
        // foydalanuvchi uchun qayta so'ralmaydi.
        $globalBestsellers = [
            'book' => Books::query()
                ->activeForVector()
                ->orderByDesc('totalSales')
                ->limit(30)
                ->get(['id', 'name', 'category_id']),
            'stationery' => Stationery::query()
                ->activeForVector()
                ->orderByDesc('totalSales')
                ->limit(30)
                ->get(['id', 'name', 'category_id']),
        ];

        $sent = 0;
        $skip = 0;

        foreach ($userIds as $userId) {
            $user = User::find($userId);
            if (! $user) {
                $skip++;
                continue;
            }

            // 1-qadam: foydalanuvchi qaysi TURDAN ko'proq xarid qilgan —
            // shu tur bo'yicha tavsiya beramiz. Xarid tarixi yo'q bo'lsa,
            // standart 'book' (ilova kitob-markazli).
            $type = $tasteProfile->dominantProductType($user->id) ?? 'book';

            $purchasedIds = $tasteProfile->purchasedProductIds($user->id, $type);

            $candidate = null;
            $variant = 'bestseller';

            $categoryId = $tasteProfile->dominantCategoryId($user->id, $type);
            if ($categoryId) {
                $query = $type === 'book' ? Books::query() : Stationery::query();
                $candidate = $query
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
                $candidate = $globalBestsellers[$type]->first(
                    fn ($product) => ! in_array((int) $product->id, $purchasedIds, true)
                );
            }

            if (! $candidate) {
                $skip++;
                continue;
            }

            $this->sendRecommendation($user, $candidate, $variant, $type) ? $sent++ : $skip++;
        }

        $this->info("Yuborildi: {$sent} | O'tkazib yuborildi: {$skip}");
    }

    private function sendRecommendation(User $user, Books|Stationery $product, string $variant, string $type): bool
    {
        try {
            $lang = in_array($user->locale ?? 'uz', ['uz', 'ru', 'en', 'ja'])
                ? ($user->locale ?? 'uz')
                : 'uz';

            $msg = self::TEXTS[$lang][$type][$variant];
            $title = $msg['title'];
            $body = str_replace(':name', (string) $product->name, $msg['body']);

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
                'product_id' => (string) $product->id,
                'product_type' => $type,
            ];

            $result = (new FCMService('kitobchi'))->send($tokens, $title, $body, $payload);

            Log::info("RecommendBooksPush: user #{$user->id}, lang={$lang}, type={$type}, variant={$variant}, product #{$product->id}", [
                'result' => $result,
            ]);

            $this->line("  ✓ user #{$user->id} [{$lang}] [{$type}/{$variant}] → \"{$product->name}\"");

            return true;
        } catch (\Throwable $e) {
            Log::error("RecommendBooksPush xatosi (user #{$user->id}): {$e->getMessage()}");
            $this->error("  ✗ user #{$user->id}: {$e->getMessage()}");

            return false;
        }
    }
}
