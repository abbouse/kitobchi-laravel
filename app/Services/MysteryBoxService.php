<?php

namespace App\Services;

use App\Models\MysteryBoxSubscription;
use App\Models\MysteryBoxDelivery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MysteryBoxService
{
    /**
     * To'lov tasdiqlangandan keyin obunani aktivlashtirish.
     * Idempotent — ikki marta chaqirilsa xato bo'lmaydi.
     */
    public function activate(int $subId): bool
    {
        // Fresh DB read
        $sub = MysteryBoxSubscription::find($subId);

        if (!$sub) {
            Log::error("[MysteryBoxService] Obuna topilmadi: #{$subId}");
            return false;
        }

        // Allaqachon aktiv — idempotent
        if ($sub->status === 'active') {
            Log::info("[MysteryBoxService] Allaqachon aktiv: #{$subId}");
            return true;
        }

        if (in_array($sub->status, ['cancelled', 'paused', 'completed'])) {
            Log::warning("[MysteryBoxService] Bekor/tugagan obunani aktivlab bo'lmaydi: #{$subId}");
            return false;
        }

        if ($sub->status !== 'pending_payment') {
            Log::warning("[MysteryBoxService] Noto'g'ri status: #{$subId} status={$sub->status}");
            return false;
        }

        DB::transaction(function () use ($sub) {
            $sub->update([
                'status'           => 'active',
                'paid_at'          => now(),
                'started_at'       => now(),
                'next_delivery_at' => now()->addMonth(),
                'ends_at'          => now()->addMonths($sub->total_months),
            ]);

            // Birinchi delivery — faqat mavjud bo'lmasa yaratiladi (idempotent)
            $exists = MysteryBoxDelivery::where('subscription_id', $sub->id)
                ->where('month_number', 1)
                ->exists();

            if (!$exists) {
                MysteryBoxDelivery::create([
                    'subscription_id' => $sub->id,
                    'month_number'    => 1,
                    'status'          => 'pending',
                ]);
            }
        });

        Log::info("[MysteryBoxService] Aktivlashtirildi: sub#{$subId} user#{$sub->user_id}");
        return true;
    }

    /**
     * To'lovsiz bekor qilish.
     * Idempotent.
     */
    public function cancelPayment(int $subId): bool
    {
        $affected = DB::table('mystery_box_subscriptions')
            ->where('id', $subId)
            ->where('status', 'pending_payment')
            ->update([
                'status'       => 'cancelled',
                'cancelled_at' => now(),
                'updated_at'   => now(),
            ]);

        if ($affected > 0) {
            MysteryBoxDelivery::where('subscription_id', $subId)
                ->where('status', 'pending')
                ->delete();
        }

        Log::info("[MysteryBoxService] payment_cancelled: #{$subId}", ['affected' => $affected]);
        return $affected > 0;
    }
}