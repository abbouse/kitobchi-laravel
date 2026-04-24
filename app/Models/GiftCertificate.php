<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GiftCertificate extends Model
{
    protected $table = 'gift_certificates';

    protected $fillable = [
        'buyer_user_id', 'recipient_user_id', 'code',
        'nominal_uzs', 'status', 'message',
        'paid_at', 'sent_at', 'activated_at', 'used_at', 'expires_at',
    ];

    protected $casts = [
        'nominal_uzs'  => 'integer',
        'paid_at'      => 'datetime',
        'sent_at'      => 'datetime',
        'activated_at' => 'datetime',
        'used_at'      => 'datetime',
        'expires_at'   => 'datetime',
    ];

    const STATUS_PENDING   = 'pending_payment';
    const STATUS_PAID      = 'paid';
    const STATUS_ACTIVE    = 'active';
    const STATUS_USED      = 'used';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_SENT      = 'active'; // backward compat

    // ── Relationlar ───────────────────────────────────────────────────────
    public function buyer()     { return $this->belongsTo(User::class, 'buyer_user_id'); }
    public function recipient() { return $this->belongsTo(User::class, 'recipient_user_id'); }

    // ── Labellar ──────────────────────────────────────────────────────────
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING   => "To'lov kutilmoqda",
            self::STATUS_PAID      => "To'langan (aktivlanmagan)",
            self::STATUS_ACTIVE    => 'Faol',
            self::STATUS_USED      => 'Ishlatilgan',
            self::STATUS_CANCELLED => 'Bekor qilingan',
            default                => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PAID      => 'info',
            self::STATUS_ACTIVE    => 'success',
            self::STATUS_USED      => 'muted',
            self::STATUS_CANCELLED => 'danger',
            default                => 'warning',
        };
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at
            && $this->expires_at->isPast()
            && $this->status === self::STATUS_ACTIVE;
    }

    // ── Kod generatsiya ───────────────────────────────────────────────────
    public static function generateCode(): string
    {
        do {
            $code = 'GIFT-' . strtoupper(Str::random(8));
        } while (static::where('code', $code)->exists());
        return $code;
    }

    // ── AKTIVLASHTIRISH ───────────────────────────────────────────────────
    public function activate(User $user): array
    {
        if ($this->status === self::STATUS_ACTIVE && $this->recipient_user_id === $user->id) {
            return ['ok' => false, 'message' => 'Bu sertifikat allaqachon sizda faol.'];
        }
        if ($this->status === self::STATUS_USED) {
            return ['ok' => false, 'message' => 'Bu sertifikat allaqachon ishlatilgan.'];
        }
        if ($this->status === self::STATUS_CANCELLED) {
            return ['ok' => false, 'message' => 'Bu sertifikat bekor qilingan.'];
        }
        if ($this->status === self::STATUS_PENDING) {
            return ['ok' => false, 'message' => "Bu sertifikat hali to'lanmagan."];
        }

        DB::transaction(function () use ($user) {
            $this->update([
                'status'            => self::STATUS_ACTIVE,
                'recipient_user_id' => $user->id,
                'activated_at'      => now(),
                'expires_at'        => now()->addDays(30),
            ]);
        });

        return [
            'ok'      => true,
            'message' => number_format($this->nominal_uzs) . " UZS sertifikat faollashtirildi!",
            'cert'    => [
                'id'      => $this->id,
                'code'    => $this->code,
                'nominal' => $this->nominal_uzs,
                'expires' => $this->fresh()->expires_at?->format('d.m.Y'),
            ],
        ];
    }

    // ── PURCHASE DA ISHLATISH ─────────────────────────────────────────────
    //
    //  $usedAmount — shu buyurtmada ayiriladigan summa (certDiscount).
    //
    //  Logika:
    //    DB dan FRESH nominal_uzs o'qiymiz (stale model muammo yo'q).
    //    fresh_nominal > usedAmount → qoldiq bor, ACTIVE qoladi, nominal kamayadi.
    //    fresh_nominal <= usedAmount → to'liq ishlatildi, USED bo'ladi.
    //
    //  Atomic DB::raw ishlatilmaydi (aniq qiymat yoziladi) — bu xavfsiz
    //  chunki PurchaseController da DB::beginTransaction ichida chaqiriladi
    //  va cert validation (status=active, recipient=user) avval tekshiriladi.
    //
    public function useInPurchase(int $usedAmount): void
    {
        if ($usedAmount <= 0) return;

        // DB dan fresh qiymat olamiz — model stale bo'lmasin
        $freshRow = DB::table('gift_certificates')
            ->where('id', $this->id)
            ->first();

        if (!$freshRow || $freshRow->status !== self::STATUS_ACTIVE) {
            // Sertifikat active emas — ishlatmaymiz
            Log::warning("useInPurchase: cert #{$this->id} status={$freshRow?->status}, skip");
            return;
        }

        $freshNominal = (int)$freshRow->nominal_uzs;
        $remaining    = $freshNominal - $usedAmount;

        if ($remaining > 0) {
            // Qisman ishlatildi — ACTIVE qoladi, nominal kamayadi
            DB::table('gift_certificates')
                ->where('id',     $this->id)
                ->where('status', self::STATUS_ACTIVE)
                ->update([
                    'nominal_uzs' => $remaining,
                    'updated_at'  => now(),
                ]);
        } else {
            // To'liq ishlatildi — USED bo'ladi
            DB::table('gift_certificates')
                ->where('id',     $this->id)
                ->where('status', self::STATUS_ACTIVE)
                ->update([
                    'status'      => self::STATUS_USED,
                    'nominal_uzs' => 0,
                    'used_at'     => now(),
                    'updated_at'  => now(),
                ]);
        }
    }
}
