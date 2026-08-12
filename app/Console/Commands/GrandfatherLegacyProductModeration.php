<?php

namespace App\Console\Commands;

use App\Models\Books;
use App\Models\Stationery;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * BIR MARTALIK tuzatish: 2026-07-14'da AI moderatsiya funksiyasi qo'shilganda
 * (`add_ai_moderation_fields_to_products` migratsiyasi) mavjud bo'lgan barcha
 * kitob/kanselyariyalar uchun `ai_moderation_status` ustuni bo'sh (NULL)
 * qoldirilgan edi — hech qanday backfill bo'lmagan. Natijada:
 *
 *   1) `ProductAiModerationService::queueQuery()` NULL statusni "hali
 *      tekshirilmagan" deb hisoblaydi, shuning uchun butun eski katalog
 *      (`products:moderate-ai` sched joyi orqali har 30 daqiqada) asta-sekin
 *      AI tekshiruvidan o'tkazilib kelmoqda — bu ataylab qilingan emas, faqat
 *      backfill unutilgan.
 *   2) Bu jarayonda AI "reject" yoki "human_review" desa, `storeDecision()`
 *      `is_approved`ni 0/2 ga tushiradi — anchadan beri tirik, sotuvlari bor
 *      eski listing HECH QANDAY seller harakatisiz birdaniga yo'qolib
 *      qoladi (`HasProductVisibility::visibleBooks()` `is_approved=1`ni
 *      talab qiladi).
 *
 * Bu buyruq ikki bosqichda tuzatadi:
 *   A) NULL statusli barcha eski qatorlarni navbatdan butunlay chiqarib
 *      qo'yadi (`legacy_exempt`) — `is_approved`ga TEGMAYDI, faqat kelajakda
 *      AI navbatiga qayta tushib qolmasligini ta'minlaydi. Shu nuqtadan
 *      keyin FAQAT yangi qo'shilgan va seller tahrirlagan (`updating()`
 *      observeri orqali qayta `pending` bo'lgan) mahsulotlar tekshiriladi.
 *   B) Yuqoridagi bug allaqachon "zarar yetkazgan" — ya'ni $cutoff'dan OLDIN
 *      yaratilgan, lekin hozir reject/human_review holatida turgan
 *      qatorlarni topib, ko'rinishini tiklaydi (`is_approved=1`) va ularni
 *      ham navbatdan chiqaradi. Bu yerda "seller haqiqatan tahrirladimi"
 *      degan holatni ustunlar asosida 100% aniq ajratib bo'lmaydi (stock
 *      kabi operatsion yangilanishlar ham `updated_at`ni suradi), shuning
 *      uchun eng xavfsiz yo'l: $cutoff'dan oldingi HAR QANDAY reject/
 *      human_review'ni tiklash — admin panelida (`Boshqaruv` yoki A122)
 *      xohlagan vaqt qo'lda qayta rad etish mumkin, bu qaytariladigan
 *      amal.
 */
class GrandfatherLegacyProductModeration extends Command
{
    protected $signature = 'products:grandfather-legacy-moderation
        {--dry-run : Hech narsa yozmasdan, faqat nechta qator ta\'sirlanishini ko\'rsatadi}
        {--cutoff=2026-07-14 : Shu sanadan OLDIN yaratilgan mahsulotlar "legacy" hisoblanadi (B bosqich uchun)}
        {--skip-restore : Faqat A bosqichni (NULL -> legacy_exempt) bajaradi, B bosqichni (tiklash) o\'tkazib yuboradi}';

    protected $description = 'AI moderatsiya joriy etilishidan oldingi mahsulotlarni navbatdan chetlashtiradi va bug tufayli noto\'g\'ri yashiringanlarni tiklaydi';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $cutoff = Carbon::parse((string) $this->option('cutoff'))->startOfDay();

        if ($dryRun) {
            $this->warn('--dry-run: hech narsa yozilmaydi, faqat hisoblanadi.');
        }

        foreach ([['book', Books::class], ['stationery', Stationery::class]] as [$label, $modelClass]) {
            $this->info("=== {$label} ===");
            $this->exemptNullStatus($label, $modelClass, $dryRun);

            if (! $this->option('skip-restore')) {
                $this->restoreWronglyHidden($label, $modelClass, $cutoff, $dryRun);
            }
        }

        return self::SUCCESS;
    }

    /** A bosqich: NULL statusli eski qatorlarni navbatdan chetlashtirish. */
    private function exemptNullStatus(string $label, string $modelClass, bool $dryRun): void
    {
        $query = $modelClass::query()->whereNull('ai_moderation_status');
        $count = (clone $query)->count();

        $this->line("  Navbatdan chetlashtiriladigan (status=NULL): {$count}");

        if ($count === 0 || $dryRun) {
            return;
        }

        $affected = 0;
        $query->chunkById(500, function ($rows) use (&$affected, $modelClass) {
            $ids = $rows->pluck('id')->all();
            $modelClass::query()->whereKey($ids)->update([
                'ai_moderation_status' => 'legacy_exempt',
                'ai_moderation_checked_at' => now(),
                'ai_moderation_note' => 'Legacy — AI moderatsiya joriy etilishidan oldin qo\'shilgan, avtomatik chetlashtirildi.',
                'ai_moderation_meta' => json_encode([
                    'source' => 'legacy_backfill',
                    'backfilled_at' => now()->toIso8601String(),
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]);
            $affected += count($ids);
        });

        Log::info('Legacy product moderation backfill (A: exempt null)', [
            'type' => $label,
            'affected' => $affected,
        ]);
        $this->info("  -> {$affected} ta chetlashtirildi.");
    }

    /** B bosqich: bug tufayli reject/human_review'ga tushib, yashiringan eski mahsulotlarni tiklash. */
    private function restoreWronglyHidden(string $label, string $modelClass, Carbon $cutoff, bool $dryRun): void
    {
        $query = $modelClass::query()
            ->where('created_at', '<', $cutoff)
            ->whereIn('ai_moderation_status', ['rejected', 'human_review']);

        $count = (clone $query)->count();
        $this->line("  Tiklanadigan (legacy, hozir rejected/human_review): {$count}");

        if ($count === 0) {
            return;
        }

        $restoredLog = [];
        $query->select(['id', 'name', 'seller_id', 'ai_moderation_status', 'created_at'])
            ->chunkById(200, function ($rows) use (&$restoredLog, $modelClass, $dryRun) {
                foreach ($rows as $row) {
                    $restoredLog[] = [
                        'id' => $row->id,
                        'name' => $row->name,
                        'seller_id' => $row->seller_id,
                        'previous_status' => $row->ai_moderation_status,
                    ];
                }

                if (! $dryRun) {
                    $modelClass::query()->whereKey($rows->pluck('id')->all())->update([
                        'is_approved' => 1,
                        'ai_moderation_status' => 'legacy_exempt',
                        'ai_moderation_checked_at' => now(),
                        'ai_moderation_note' => 'Legacy — migratsiyadan oldingi mahsulot AI tomonidan xato ravishda rad etilgan/ko\'rikka yuborilgan edi, tiklandi va moderatsiyadan chetlashtirildi.',
                        'ai_moderation_meta' => json_encode([
                            'source' => 'legacy_backfill_restore',
                            'backfilled_at' => now()->toIso8601String(),
                        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    ]);
                }
            });

        if (! $dryRun) {
            Log::info('Legacy product moderation backfill (B: restored wrongly hidden)', [
                'type' => $label,
                'restored' => $restoredLog,
            ]);
        }

        $this->info(($dryRun ? '  -> (dry-run) tiklangan bo\'lardi: ' : '  -> tiklandi: ').count($restoredLog));
        foreach (array_slice($restoredLog, 0, 20) as $row) {
            $this->line("     #{$row['id']} \"{$row['name']}\" (seller {$row['seller_id']}, avvalgi: {$row['previous_status']})");
        }
        if (count($restoredLog) > 20) {
            $this->line('     ... va yana '.(count($restoredLog) - 20).' ta (to\'liq ro\'yxat laravel.log da).');
        }
    }
}
