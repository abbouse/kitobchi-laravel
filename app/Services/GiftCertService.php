<?php

namespace App\Services;

use App\Models\GiftCertificate;
use Illuminate\Support\Facades\Log;

class GiftCertService
{
    /**
     * To'lov tasdiqlangandan keyin sertifikatni aktivlashtirish.
     * Idempotent — ikki marta chaqirilsa xato bo'lmaydi.
     */
    public function activate(int $certId): bool
    {
        // Fresh DB read — stale model muammosini oldini oladi
        $cert = GiftCertificate::find($certId);

        if (!$cert) {
            Log::error("[GiftCertService] Sertifikat topilmadi: #{$certId}");
            return false;
        }

        // Allaqachon aktivlashgan — idempotent
        // Bu statuslarda qayta ishlatmaslik kerak
        if (in_array($cert->status, ['active', 'paid', 'used'])) {
            Log::info("[GiftCertService] Allaqachon aktivlashgan: #{$certId} status={$cert->status}");
            return true; // idempotent
        }

        if (in_array($cert->status, ['cancelled', 'payment_cancelled'])) {
            Log::warning("[GiftCertService] Bekor qilingan sertifikatni aktivlab bo'lmaydi: #{$certId}");
            return false;
        }

        if ($cert->status !== 'pending_payment') {
            Log::warning("[GiftCertService] Noto'g'ri status: #{$certId} status={$cert->status}");
            return false;
        }

        // message yo'q → o'zi uchun → birdan active
        // message bor  → do'stga sovg'a → paid (recipient activate qiladi)
        $isForSelf = empty($cert->message);

        $updated = $isForSelf
            ? $cert->update([
                'status'       => 'active',
                'paid_at'      => now(),
                'activated_at' => now(),
                'expires_at'   => now()->addDays(30),
            ])
            : $cert->update([
                'status'  => 'paid',
                'paid_at' => now(),
            ]);

        Log::info("[GiftCertService] Aktivlashtirildi: #{$certId}", [
            'isForSelf' => $isForSelf,
            'status'    => $isForSelf ? 'active' : 'paid',
        ]);

        return (bool) $updated;
    }

    /**
     * To'lovsiz bekor qilish (Payme timeout / user cancel).
     * Idempotent.
     */
    public function cancelPayment(int $certId): bool
    {
        $affected = \Illuminate\Support\Facades\DB::table('gift_certificates')
            ->where('id', $certId)
            ->where('status', 'pending_payment')
            ->update([
                'status'     => 'payment_cancelled',
                'updated_at' => now(),
            ]);

        Log::info("[GiftCertService] payment_cancelled: #{$certId}", ['affected' => $affected]);
        return $affected > 0;
    }
}