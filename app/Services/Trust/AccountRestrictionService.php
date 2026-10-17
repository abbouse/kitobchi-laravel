<?php

namespace App\Services\Trust;

use App\Enums\CourierOrderStatusCode;
use App\Models\Admin;
use App\Models\ConnectedDevice;
use App\Models\CourierBanLog;
use App\Models\CourierOrder;
use App\Models\CourierTransaction;
use App\Models\Couriers;
use App\Models\Seller;
use App\Models\SellerBanLog;
use App\Models\SellerBalanceEntry;
use App\Models\Sold;
use App\Models\User;
use App\Services\CourierAssignmentService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Do'kon va kuryerni cheklash markazi: sabab va muddat bilan bloklash,
 * blokdan chiqarish, pul yechishni muzlatish va balansni daftar orqali
 * tuzatish. Har amal iz qoldiradi (ban log / ledger / audit log).
 */
class AccountRestrictionService
{
    public const DURATIONS = [
        '1_day' => '1 kun',
        '3_days' => '3 kun',
        '7_days' => '7 kun',
        '30_days' => '30 kun',
        'forever' => 'Muddatsiz',
    ];

    public static function untilFor(string $duration): ?Carbon
    {
        return match ($duration) {
            '1_day' => now()->addDay(),
            '3_days' => now()->addDays(3),
            '7_days' => now()->addDays(7),
            '30_days' => now()->addDays(30),
            default => null,
        };
    }

    // ── Do'kon ────────────────────────────────────────────────────────────

    public function blockSeller(Seller $seller, Admin $admin, string $reason, ?Carbon $until): Seller
    {
        $store = $seller->storeAccount();

        DB::transaction(function () use ($store, $admin, $reason, $until) {
            $store->forceFill([
                'status' => 'blocked',
                'blocked_at' => now(),
                'blocked_until' => $until,
                'block_reason' => $reason,
                'blocked_by_admin_id' => $admin->id,
            ])->save(); // Seller::updated — do'kon va xodimlar tokenlari o'chadi

            SellerBanLog::create([
                'seller_id' => $store->id,
                'title' => $until ? 'Vaqtincha cheklandi' : 'Bloklandi',
                'message' => $reason.($until ? ' (muddat: '.$until->format('d.m.Y H:i').')' : ''),
                'type' => 'block',
                'is_read' => false,
            ]);

            $staffIds = Seller::query()->where('parent_id', $store->id)->pluck('id')->push($store->id);
            ConnectedDevice::query()->where('user_type', 'seller')->whereIn('user_id', $staffIds)->delete();
        });

        return $store->fresh();
    }

    public function unblockSeller(Seller $seller, ?string $message = null): Seller
    {
        $store = $seller->storeAccount();
        DB::transaction(function () use ($store, $message) {
            $store->forceFill([
                'status' => 'approved',
                'blocked_at' => null,
                'blocked_until' => null,
                'block_reason' => null,
                'blocked_by_admin_id' => null,
            ])->save();

            SellerBanLog::create([
                'seller_id' => $store->id,
                'title' => 'Blokdan chiqarildi',
                'message' => $message ?: 'Cheklov olib tashlandi. Ogohlantirish hisobi qayta boshlandi.',
                'type' => SellerBanLog::TYPE_UNBAN,
                'is_read' => false,
            ]);
        });

        return $store->fresh();
    }

    public function setSellerPayoutFreeze(Seller $seller, bool $frozen, ?string $reason): Seller
    {
        $store = $seller->storeAccount();
        $store->forceFill([
            'payouts_frozen_at' => $frozen ? now() : null,
            'payout_freeze_reason' => $frozen ? $reason : null,
        ])->save();

        return $store;
    }

    /** Balansni qo'lda tuzatish — faqat daftar orqali (iz qoldiradi). */
    public function adjustSellerBalance(Seller $seller, Admin $admin, int $amount, string $reason): SellerBalanceEntry
    {
        if ($amount === 0) {
            throw new RuntimeException('Summa 0 bo‘lishi mumkin emas.');
        }

        return DB::transaction(function () use ($seller, $admin, $amount, $reason) {
            $store = Seller::query()->lockForUpdate()->findOrFail($seller->storeAccount()->id);
            if ($amount < 0 && (int) $store->balance + $amount < 0) {
                throw new RuntimeException('Balans manfiy bo‘lib qoladi: joriy balans '.number_format((int) $store->balance, 0, '.', ' ').' so‘m.');
            }

            return SellerBalanceEntry::record($store, $amount, 'admin_adjustment', 'admin', (int) $admin->id, mb_substr($reason.' — '.$admin->name, 0, 500));
        });
    }

    // ── Kuryer ────────────────────────────────────────────────────────────

    /**
     * @return array{released: int, kept: array<int, int>} kept — kitobni olib
     *   bo'lgan (qo'lida turgan) buyurtmalar: admin alohida hal qiladi
     */
    public function blockCourier(Couriers $courier, Admin $admin, string $reason, ?Carbon $until): array
    {
        $courier->forceFill([
            'status' => 'blocked',
            'blocked_at' => now(),
            'blocked_until' => $until,
            'block_reason' => $reason,
            'blocked_by_admin_id' => $admin->id,
        ])->save(); // Couriers::updated — tokenlar o'chadi, oflayn bo'ladi

        CourierBanLog::create([
            'courier_id' => $courier->id,
            'title' => $until ? 'Vaqtincha cheklandi' : 'Bloklandi',
            'message' => $reason.($until ? ' (muddat: '.$until->format('d.m.Y H:i').')' : ''),
            'type' => 'block',
            'is_read' => false,
        ]);
        ConnectedDevice::query()->where('user_type', 'courier')->where('user_id', $courier->id)->delete();

        // Hali olib ketilmagan buyurtmalar boshqa kuryerlarga qaytariladi
        $released = 0;
        $kept = [];
        $active = CourierOrder::query()
            ->where('courier_id', $courier->id)
            ->where('status_code', CourierOrderStatusCode::IN_DELIVERY->value)
            ->get(['id', 'order_id']);
        foreach ($active as $row) {
            $sold = Sold::query()->find($row->order_id);
            if (! $sold) {
                continue;
            }
            try {
                app(CourierAssignmentService::class)->release($sold, (int) $courier->id, 'courier_blocked', [
                    'actor' => 'admin', 'admin_id' => $admin->id,
                ]);
                $released++;
            } catch (\Throwable $e) {
                $kept[] = (int) $row->order_id;
                Log::warning('Blocked courier order kept', ['courier_id' => $courier->id, 'order_id' => $row->order_id, 'error' => $e->getMessage()]);
            }
        }

        return ['released' => $released, 'kept' => $kept];
    }

    public function unblockCourier(Couriers $courier, ?string $message = null): Couriers
    {
        $courier->forceFill([
            'status' => 'approved',
            'blocked_at' => null,
            'blocked_until' => null,
            'block_reason' => null,
            'blocked_by_admin_id' => null,
        ])->save();

        CourierBanLog::create([
            'courier_id' => $courier->id,
            'title' => 'Blokdan chiqarildi',
            'message' => $message ?: 'Cheklov olib tashlandi. Ogohlantirish hisobi qayta boshlandi.',
            'type' => CourierBanLog::TYPE_UNBAN,
            'is_read' => false,
        ]);

        return $courier->fresh();
    }

    public function setCourierPayoutFreeze(Couriers $courier, bool $frozen, ?string $reason): Couriers
    {
        $courier->forceFill([
            'payouts_frozen_at' => $frozen ? now() : null,
            'payout_freeze_reason' => $frozen ? $reason : null,
        ])->save();

        return $courier;
    }

    public function adjustCourierBalance(Couriers $courier, Admin $admin, int $amount, string $reason): CourierTransaction
    {
        if ($amount === 0) {
            throw new RuntimeException('Summa 0 bo‘lishi mumkin emas.');
        }

        return DB::transaction(function () use ($courier, $admin, $amount, $reason) {
            $locked = Couriers::query()->lockForUpdate()->findOrFail($courier->id);
            if ($amount < 0 && (int) $locked->balance + $amount < 0) {
                throw new RuntimeException('Balans manfiy bo‘lib qoladi: joriy balans '.number_format((int) $locked->balance, 0, '.', ' ').' so‘m.');
            }
            $locked->balance = (int) $locked->balance + $amount;
            $locked->save();

            return CourierTransaction::create([
                'courier_id' => $locked->id,
                'card' => '',
                'type' => $amount > 0 ? 'income' : 'expense',
                'category' => 'admin_adjustment',
                'amount' => abs($amount),
                'commissionPercent' => 0,
                'commissionPrice' => 0,
                'netAmount' => abs($amount),
                'status' => 'approved',
                'description' => mb_substr('Admin tuzatishi: '.$reason.' — '.$admin->name, 0, 500),
            ]);
        });
    }

    // ── Muddati tugagan bloklar ───────────────────────────────────────────

    /** @return array{sellers: int, couriers: int, users: int} */
    public function expireBlocks(): array
    {
        $sellers = 0;
        Seller::query()->where('status', 'blocked')->whereNotNull('blocked_until')->where('blocked_until', '<=', now())
            ->each(function (Seller $seller) use (&$sellers) {
                $this->unblockSeller($seller, 'Cheklov muddati tugadi — avtomatik blokdan chiqarildi.');
                $sellers++;
            });

        $couriers = 0;
        Couriers::query()->where('status', 'blocked')->whereNotNull('blocked_until')->where('blocked_until', '<=', now())
            ->each(function (Couriers $courier) use (&$couriers) {
                $this->unblockCourier($courier, 'Cheklov muddati tugadi — avtomatik blokdan chiqarildi.');
                $couriers++;
            });

        $users = User::query()->where('status', 'blocked')->whereNotNull('blocked_until')->where('blocked_until', '<=', now())
            ->update(['status' => null, 'blocked_until' => null, 'blocked_at' => null, 'block_reason' => null, 'blocked_by_admin_id' => null]);

        return ['sellers' => $sellers, 'couriers' => $couriers, 'users' => $users];
    }
}
