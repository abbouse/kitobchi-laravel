<?php

namespace App\Telegram\Commands;

use App\Enums\OrderStatusCode;
use SergiX44\Nutgram\Handlers\Type\Command;
use SergiX44\Nutgram\Nutgram;
use App\Models\User;
use App\Models\Sold;

class MyPurchasesCommand extends Command
{
    protected string $command = 'mypurchases';
    protected ?string $description = 'Sotib olgan mahsulotlarimni ko\'rish';

    public function handle(Nutgram $bot): void
    {
        $userId = $bot->message()->from->id;
        $user = User::where('telegram_id', $userId)->first();

        if (!$user) {
            $bot->sendMessage("⚠️ Siz hali ro'yxatdan o'tmagansiz! Avval telefon raqamingizni yuboring.");
            return;
        }
        $purchases = Sold::where('user_id', $user->id)
            ->where(function ($query) {
                $query->where('status_code', '!=', OrderStatusCode::CANCELLED->value)
                    ->orWhere(function ($fallback) {
                        $fallback->whereNull('status_code')
                            ->where('status', '!=', OrderStatusCode::CANCELLED->legacy());
                    });
            })
            ->orderBy('id', 'desc')
            ->limit(3)
            ->get();
        $purchasesCount = Sold::where('user_id', $user->id)
            ->where(function ($query) {
                $query->where('status_code', '!=', OrderStatusCode::CANCELLED->value)
                    ->orWhere(function ($fallback) {
                        $fallback->whereNull('status_code')
                            ->where('status', '!=', OrderStatusCode::CANCELLED->legacy());
                    });
            })
            ->count();
        $totalAmount = Sold::where('user_id', $user->id)
            ->where(function ($query) {
                $query->where('status_code', '!=', OrderStatusCode::CANCELLED->value)
                    ->orWhere(function ($fallback) {
                        $fallback->whereNull('status_code')
                            ->where('status', '!=', OrderStatusCode::CANCELLED->legacy());
                    });
            })
            ->sum('amount'); 
        if ($purchases->isEmpty()) {
            $bot->sendMessage("❌ Sizda hali hech qanday mahsulot mavjud emas.");
            return;
        }
        $purchaseText = "🛍 *Sotib olishlar tarixi:*\n\n";
        $purchaseText .= "📦 Umumiy mahsulotlar soni: *$purchasesCount ta*\n";
        $purchaseText .= "💰 Umumiy summa: *" . number_format($totalAmount, 0, ',', ' ') . " UZS*";

        foreach ($purchases as $purchase) {
            $totalQuantity = array_sum(array_column($purchase->items, 'count_item'));
            $purchaseText .= "\n-------------------\n";
            $purchaseText .= "🆔 *Buyurtma raqami:* " . $purchase->id . "\n";
            $purchaseText .= "📅 *Sana:* " . $purchase->created_at->format('d.m.Y, H:i') . "\n";
            $purchaseText .= "📦 *Mahsulotlar ({$totalQuantity} ta):* " . number_format($purchase->amount, 0, ',', ' ') . " UZS\n";
            $purchaseText .= "🚚 *Status:* " . $this->getStatusText($purchase->status_code ?? $purchase->status);
        }
        
            $bot->sendMessage(
                text: $purchaseText, 
                parse_mode: 'markdown');
    }
    private function getStatusText($status): string
    {
        return match ($status) {
            'pending', 'A' => 'Kutilmoqda',
            'packing', 'P' => 'Qadoqlanmoqda',
            'in_delivery', 'B' => 'Yo\'lda',
            'delivered', 'C' => 'Yetib bordi',
            'customer_received', 'D' => 'Mijoz qabul qildi',
            'returned' => 'Pochta qaytargan',
            'cancelled', 'F' => 'Bekor qilindi',
            default => 'Noma’lum',
        };
    }
}
