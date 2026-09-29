<?php

namespace App\Services;

use App\Enums\CourierOrderStatusCode;
use App\Enums\CourierTaskLeg;
use App\Enums\CourierTaskStatusCode;
use App\Models\CourierOrder;
use App\Models\CourierTask;
use App\Models\Couriers;
use App\Models\Sold;
use App\Support\CourierDeliveryAttempts;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * "Yetkazib bo'lmadi" — ovqatdan farqli, kitob buzilmaydi: buyurtma bekor
 * qilinmaydi, kitob kuryerda qoladi va keyingi urinishga rejalashtiriladi.
 * Chegara oshsa yoki mijoz rad etsa — boshqaruvga "qaytarish kerak" bo'ladi.
 */
class CourierDeliveryAttemptService
{
    /** Mijozga yetkazish topshirig'i kuryer qo'lida (kitob kuryerda)mi. */
    public function courierHoldsBook(int $orderId, int $courierId): bool
    {
        return CourierTask::query()
            ->where('order_id', $orderId)
            ->where('courier_id', $courierId)
            ->whereIn('leg', [CourierTaskLeg::LAST_MILE->value, CourierTaskLeg::DIRECT_DELIVERY->value])
            ->where('status_code', CourierTaskStatusCode::PICKED_UP->value)
            ->exists();
    }

    /**
     * @return array{courier_order: CourierOrder, return_required: bool, next_attempt_at: ?\Carbon\CarbonInterface, attempt_no: int}
     *
     * @throws \RuntimeException
     */
    public function recordFailed(
        Sold $sold,
        Couriers $courier,
        string $reason,
        ?string $note = null,
        ?\Carbon\CarbonInterface $nextAttemptAt = null,
        ?float $lat = null,
        ?float $lon = null,
    ): array {
        if (! array_key_exists($reason, CourierDeliveryAttempts::REASONS)) {
            throw new \RuntimeException('Noma\'lum sabab.');
        }
        if (! $this->courierHoldsBook((int) $sold->id, (int) $courier->id)) {
            throw new \RuntimeException("Avval kitobni olib keting — yetkazish urinishi faqat kitob sizda bo'lganda qayd etiladi.");
        }

        $result = DB::transaction(function () use ($sold, $courier, $reason, $note, $nextAttemptAt, $lat, $lon) {
            $courierOrder = CourierOrder::query()
                ->where('order_id', $sold->id)
                ->where('courier_id', $courier->id)
                ->where('status_code', CourierOrderStatusCode::IN_DELIVERY->value)
                ->lockForUpdate()
                ->latest('id')
                ->first();
            if (! $courierOrder) {
                throw new \RuntimeException('Buyurtma sizda faol emas.');
            }
            if ($courierOrder->next_attempt_at && $courierOrder->next_attempt_at->isFuture()
                && $courierOrder->updated_at?->gt(now()->subMinutes(10))) {
                throw new \RuntimeException('Bu urinish allaqachon qayd etilgan.');
            }

            $attemptNo = (int) $courierOrder->delivery_attempts + 1;
            $returnRequired = in_array($reason, CourierDeliveryAttempts::RETURN_REASONS, true)
                || $attemptNo >= CourierDeliveryAttempts::maxAttempts();

            $next = null;
            if (! $returnRequired) {
                $next = $nextAttemptAt && $nextAttemptAt->isFuture() && $nextAttemptAt->lt(now()->addDays(7))
                    ? $nextAttemptAt
                    : $this->defaultNextAttempt();
            }

            DB::table('courier_delivery_attempts')->insert([
                'courier_order_id' => $courierOrder->id,
                'order_id' => $sold->id,
                'courier_id' => $courier->id,
                'attempt_no' => $attemptNo,
                'reason' => $reason,
                'note' => $note ? mb_substr($note, 0, 500) : null,
                'next_attempt_at' => $next,
                'lat' => $lat,
                'lon' => $lon,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $courierOrder->delivery_attempts = $attemptNo;
            $courierOrder->last_attempt_reason = $reason;
            $courierOrder->next_attempt_at = $next;
            $courierOrder->return_required_at = $returnRequired ? now() : null;
            $courierOrder->save();

            return [
                'courier_order' => $courierOrder,
                'return_required' => $returnRequired,
                'next_attempt_at' => $next,
                'attempt_no' => $attemptNo,
            ];
        });

        Log::info('Courier delivery attempt failed', [
            'order_id' => $sold->id,
            'courier_id' => $courier->id,
            'reason' => $reason,
            'attempt' => $result['attempt_no'],
            'return_required' => $result['return_required'],
        ]);

        DB::afterCommit(fn () => app(OrderStatusPushService::class)
            ->sendDeliveryAttemptFailedNotice($sold->fresh(), $result['next_attempt_at'], $result['return_required']));

        return $result;
    }

    /** Boshqaruv: "qaytarish kerak" belgisini olib, yana bir urinish beradi. */
    public function grantRetry(CourierOrder $courierOrder, ?\Carbon\CarbonInterface $at = null): void
    {
        $courierOrder->return_required_at = null;
        $courierOrder->next_attempt_at = $at && $at->isFuture() ? $at : $this->defaultNextAttempt();
        $courierOrder->save();
    }

    /** Ertangi kun soat 10:00 (ish vaqti boshlanishi). */
    public function defaultNextAttempt(): Carbon
    {
        return now()->addDay()->setTime(10, 0);
    }
}
