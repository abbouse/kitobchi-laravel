<?php
namespace App\Console\Commands;

use App\Models\MysteryBoxSubscription;
use App\Models\MysteryBoxDelivery;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MysteryBoxDeliveryReminder extends Command
{
    protected $signature   = 'mystery-box:check-deliveries
                              {--dry-run : Haqiqatda o\'zgartirmaydi, faqat ko\'rsatadi}';
    protected $description = 'Navbati kelgan Mystery Box obunalarini tekshiradi va delivery record yaratadi';

    public function handle(): void
    {
        $dryRun = $this->option('dry-run');

        // 1. Navbati kelgan faol obunalar
        $due = MysteryBoxSubscription::with(['user:id,name,phone_number', 'plan:id,name_uz'])
            ->where('status', MysteryBoxSubscription::STATUS_ACTIVE)
            ->where(function ($q) {
                $q->whereNull('next_delivery_at')
                  ->orWhere('next_delivery_at', '<=', now());
            })
            ->get();

        if ($due->isEmpty()) {
            $this->info('Navbati kelgan obuna yo\'q.');
            return;
        }

        $this->info("{$due->count()} ta obuna navbatga keldi:\n");

        $table = [];
        foreach ($due as $sub) {
            $table[] = [
                $sub->id,
                $sub->user?->name ?? '—',
                $sub->user?->phone_number ?? '—',
                $sub->plan?->name_uz ?? '—',
                "{$sub->delivered_months}/{$sub->total_months} oy",
                $sub->next_delivery_at?->format('d.m.Y') ?? 'null',
            ];

            if (!$dryRun) {
                // Pending delivery record mavjud emas bo'lsa yaratamiz
                $exists = MysteryBoxDelivery::where('subscription_id', $sub->id)
                    ->where('status', '!=', MysteryBoxDelivery::STATUS_DELIVERED)
                    ->exists();

                if (!$exists && $sub->delivered_months < $sub->total_months) {
                    $sub->createNextDelivery();
                    $this->line("  [+] Delivery record yaratildi: sub #{$sub->id}");
                }
            }
        }

        $this->table(
            ['ID', 'Ism', 'Telefon', 'Tarif', 'Progress', 'Navbat sanasi'],
            $table
        );

        // 2. Juda kechikkan (7+ kun) lar
        $overdue = MysteryBoxSubscription::where('status', MysteryBoxSubscription::STATUS_ACTIVE)
            ->where('next_delivery_at', '<=', now()->subDays(7))
            ->count();

        if ($overdue > 0) {
            $this->error("\n⚠️  {$overdue} ta obuna 7+ kundan beri kechiktirilgan! Tezda jo'natish kerak.");
        }

        if ($dryRun) {
            $this->warn("\n[dry-run] Hech narsa o'zgartirilmadi.");
        } else {
            $this->info("\nBarcha tekshiruvlar yakunlandi.");
        }
    }
}