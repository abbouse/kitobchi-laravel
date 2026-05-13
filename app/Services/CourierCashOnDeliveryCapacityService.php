<?php

namespace App\Services;

use App\Models\CourierTask;
use App\Models\Couriers;
use Illuminate\Support\Facades\DB;

class CourierCashOnDeliveryCapacityService
{
    public function withdrawableBalance(Couriers $courier): int
    {
        return max(0, (int) ($courier->balance ?? 0) - (int) ($courier->cod_reserved_amount ?? 0));
    }

    public function availableCollateral(Couriers $courier): int
    {
        return $this->withdrawableBalance($courier);
    }

    public function canTakeCashOrder(Couriers $courier, int $cashCollectAmount): bool
    {
        return $this->availableCollateral($courier) >= max(0, $cashCollectAmount);
    }

    public function reserveForTask(CourierTask $task): void
    {
        if (!$task->is_cod || $task->courier_id === null || $task->cod_reserved_at) {
            return;
        }

        DB::transaction(function () use ($task) {
            /** @var Couriers $courier */
            $courier = Couriers::query()->lockForUpdate()->findOrFail($task->courier_id);
            $amount = max(0, (int) ($task->cash_collect_amount ?? 0));

            if ($amount === 0) {
                $task->forceFill(['cod_reserved_at' => now()])->save();
                return;
            }

            $available = max(0, (int) ($courier->balance ?? 0) - (int) ($courier->cod_reserved_amount ?? 0));
            if ($available < $amount) {
                throw new \RuntimeException('Courier COD collateral is insufficient.');
            }

            $courier->cod_reserved_amount = (int) ($courier->cod_reserved_amount ?? 0) + $amount;
            $courier->save();

            $task->forceFill(['cod_reserved_at' => now()])->save();
        });
    }

    public function releaseReservation(CourierTask $task): void
    {
        if (!$task->is_cod || $task->courier_id === null || !$task->cod_reserved_at || $task->cod_released_at) {
            return;
        }

        DB::transaction(function () use ($task) {
            /** @var Couriers|null $courier */
            $courier = Couriers::query()->lockForUpdate()->find($task->courier_id);
            $amount = max(0, (int) ($task->cash_collect_amount ?? 0));

            if ($courier) {
                $courier->cod_reserved_amount = max(0, (int) ($courier->cod_reserved_amount ?? 0) - $amount);
                $courier->save();
            }

            $task->forceFill(['cod_released_at' => now()])->save();
        });
    }

    public function settleDeliveredTask(CourierTask $task): void
    {
        if (!$task->is_cod || $task->courier_id === null || $task->wallet_debited_at) {
            return;
        }

        DB::transaction(function () use ($task) {
            /** @var Couriers $courier */
            $courier = Couriers::query()->lockForUpdate()->findOrFail($task->courier_id);
            $freshTask = CourierTask::query()->lockForUpdate()->findOrFail($task->id);
            $amount = max(0, (int) ($freshTask->cash_collect_amount ?? 0));

            if ($amount > 0) {
                if ((int) ($courier->balance ?? 0) < $amount) {
                    throw new \RuntimeException('Courier balance is insufficient for COD settlement.');
                }

                $courier->balance = (int) ($courier->balance ?? 0) - $amount;
            }

            if ($freshTask->cod_reserved_at && !$freshTask->cod_released_at) {
                $courier->cod_reserved_amount = max(
                    0,
                    (int) ($courier->cod_reserved_amount ?? 0) - $amount
                );
            }

            $courier->save();

            $freshTask->forceFill([
                'wallet_debited_at' => now(),
                'cash_reconciled_at' => now(),
                'cod_released_at' => $freshTask->cod_released_at ?: now(),
            ])->save();
        });
    }
}
