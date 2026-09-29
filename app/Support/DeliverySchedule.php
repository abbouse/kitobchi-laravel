<?php

namespace App\Support;

use App\Enums\CourierTaskLeg;
use App\Enums\CourierTaskStatusCode;
use App\Models\CourierTask;
use App\Models\Sold;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

/**
 * Kuryer orqali yetkazish kuni va vaqt oralig'i.
 *
 * Mijoz ertadan keyingi kundan boshlab 7 kundan birini va vaqt oralig'ini
 * tanlaydi. Tanlangan kun kelmaguncha mijozga olib boradigan (direct yoki
 * last-mile) topshiriq kuryerlarga chiqmaydi.
 */
class DeliverySchedule
{
    /** Jadval faqat shu yetkazish turida ishlaydi. */
    public const SERVICE_TYPES = ['courier_service'];

    public const DAYS = 7;

    /** Birinchi tanlanadigan kun: bugundan +2 (ertadan keyin). */
    public const FIRST_DAY_OFFSET = 2;

    public const SLOTS = [
        '10-14' => '10:00–14:00',
        '14-18' => '14:00–18:00',
        '18-22' => '18:00–22:00',
    ];

    private const MONTHS_UZ = ['yanvar', 'fevral', 'mart', 'aprel', 'may', 'iyun', 'iyul', 'avgust', 'sentabr', 'oktabr', 'noyabr', 'dekabr'];

    private const MONTHS_RU = ['января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря'];

    private static ?bool $hasColumns = null;

    public static function supported(): bool
    {
        return self::$hasColumns ??= Schema::hasColumn('solds', 'delivery_date');
    }

    /**
     * Tanlash mumkin bo'lgan kunlar.
     *
     * @param  Carbon|null  $earliest  predzakaz jo'natish kuni (undan oldin yetkazib bo'lmaydi)
     * @return array<int, array{date: string, label_uz: string, label_ru: string, weekday: int}>
     */
    public static function days(?Carbon $earliest = null): array
    {
        $start = today()->addDays(self::FIRST_DAY_OFFSET);
        if ($earliest && $earliest->copy()->startOfDay()->gt($start)) {
            $start = $earliest->copy()->startOfDay();
        }

        $days = [];
        for ($i = 0; $i < self::DAYS; $i++) {
            $date = $start->copy()->addDays($i);
            $isDayAfterTomorrow = $date->isSameDay(today()->addDays(2));
            $days[] = [
                'date' => $date->toDateString(),
                'label_uz' => $isDayAfterTomorrow ? 'Ertadan keyin' : $date->day.'-'.self::MONTHS_UZ[$date->month - 1],
                'label_ru' => $isDayAfterTomorrow ? 'Послезавтра' : $date->day.' '.self::MONTHS_RU[$date->month - 1],
                'weekday' => $date->dayOfWeekIso,
            ];
        }

        return $days;
    }

    /** @return array<int, array{key: string, label: string}> */
    public static function slots(): array
    {
        return collect(self::SLOTS)->map(fn ($label, $key) => ['key' => $key, 'label' => $label])->values()->all();
    }

    public static function isValid(?string $date, ?string $slot, ?Carbon $earliest = null): bool
    {
        if (! $date || ! $slot || ! array_key_exists($slot, self::SLOTS)) {
            return false;
        }

        return in_array($date, array_column(self::days($earliest), 'date'), true);
    }

    public static function deliveryDate(Sold $order): ?Carbon
    {
        if (! self::supported() || ! $order->delivery_date) {
            return null;
        }

        return Carbon::parse($order->delivery_date)->startOfDay();
    }

    /** "02.10 · 14:00–18:00" */
    public static function windowLabel(Sold $order): ?string
    {
        $date = self::deliveryDate($order);
        if (! $date) {
            return null;
        }

        return $date->format('d.m').' · '.(self::SLOTS[$order->delivery_slot] ?? (string) $order->delivery_slot);
    }

    /** Yetkazish kuni hali kelmagan. */
    public static function isFuture(Sold $order): bool
    {
        $date = self::deliveryDate($order);

        return $date !== null && $date->isAfter(today());
    }

    /**
     * Mijozga olib boradigan topshiriq (direct yoki last-mile) kuryerga
     * chiqishi kerak emasmi. Hub orqali birinchi bosqich (do'kondan hubga)
     * oldindan bajarilaveradi.
     */
    public static function customerLegHeld(Sold $order, iterable $tasks): bool
    {
        if (! self::isFuture($order)) {
            return false;
        }

        foreach ($tasks as $task) {
            if (in_array($task->leg, [CourierTaskLeg::DIRECT_DELIVERY->value, CourierTaskLeg::LAST_MILE->value], true)) {
                return true;
            }
        }

        return false;
    }

    /** Kuryerlarga push kerak emas: kun kelmagan va birinchi bosqich ham yo'q. */
    public static function broadcastHeld(Sold $order): bool
    {
        if (! self::isFuture($order)) {
            return false;
        }

        return ! CourierTask::query()
            ->where('order_id', $order->id)
            ->where('leg', CourierTaskLeg::FIRST_MILE->value)
            ->whereNull('courier_id')
            ->where('status_code', CourierTaskStatusCode::ASSIGNED->value)
            ->exists();
    }
}
