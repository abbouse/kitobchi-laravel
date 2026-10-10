<?php

namespace App\Console\Commands;

use App\Models\Sold;
use App\Services\AiBuyerWishService;
use Illuminate\Console\Command;

class GenerateAiBuyerWishesCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'orders:generate-ai-wishes {--order= : Muayyan buyurtma ID} {--limit=40 : Qayta ishlanadigan buyurtmalar soni}';

    /**
     * The console command description.
     */
    protected $description = '30 daqiqa oldin xarid qilingan, tilak yozilmagan buyurtmalarga OpenAI orqali samimiy tilak generatsiya qiladi';

    public function handle(AiBuyerWishService $wishService): int
    {
        $specificOrderId = $this->option('order');

        if ($specificOrderId) {
            $order = Sold::find((int) $specificOrderId);
            if (! $order) {
                $this->error("Buyurtma #{$specificOrderId} topilmadi.");
                return self::FAILURE;
            }

            $this->info("Buyurtma #{$order->id} uchun OpenAI tilak generatsiya qilinmoqda...");
            $wish = $wishService->generateForOrder($order);

            if ($wish) {
                $this->info("Yaratilgan tilak: '{$wish}'");
            } else {
                $this->warn("Buyurtma talablarga to'g'ri kelmadi yoki tilak allaqachon mavjud.");
            }

            return self::SUCCESS;
        }

        $limit = (int) $this->option('limit');
        $this->info("30 daqiqa oldingi tilaksiz yangi/qadoqlanmoqda buyurtmalar tekshirilmoqda (limit: {$limit})...");

        $count = $wishService->processEligibleOrders($limit);

        $this->info("Jami {$count} ta buyurtmaga OpenAI tilaklari muvaffaqiyatli saqlandi.");

        return self::SUCCESS;
    }
}
