<?php

namespace App\Services;

use App\Enums\OrderStatusCode;
use App\Models\Sold;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Buyurtma hayotidagi muhim bosqichlarda mijozga SMS yuboradi.
 *
 * MUHIM: Quyidagi shablon matnlari Eskiz'da RO'YXATDAN O'TGAN — ularni
 * O'ZGARTIRISH MUMKIN EMAS (faqat {o'zgaruvchi} qiymatlari almashadi).
 * Yangi matn kerak bo'lsa avval Eskiz'ga shablon qo'shiladi, keyin bu yer.
 *
 * Oqim:
 *   IN_DELIVERY (delivery)             → TPL_ON_THE_WAY
 *   DELIVERED   (delivery)             → TPL_RECEIVED (kuryer topshirdi — rahmat)
 *   DELIVERED   (postal)               → TPL_POSTAL_* (manzil/trek borligiga qarab)
 *   DELIVERED   (pickup)               → SMS yo'q (mijoz do'konning o'zida)
 *   CUSTOMER_RECEIVED (postal/pickup)  → TPL_RECEIVED (delivery'da takrorlanmaydi)
 */
class OrderSmsService
{
    /** Kuryer buyurtmani qo'liga olib yo'lga chiqqanda */
    public const TPL_ON_THE_WAY = 'Kitobchi: {order_id} raqamli buyurtmangiz sizga qarab yo\'lga chiqdi.';

    /** Pochta: manzil + trek raqami bilan */
    public const TPL_POSTAL_FULL = 'Kitobchi: {order_id} raqamli buyurtmangiz {address} manzilidagi pochta bo\'limiga yetib keldi va sizni kutmoqda. Trek raqami: {tracking}. Buyurtmani olib ketishingizni so\'raymiz.';

    /** Pochta: faqat manzil bilan */
    public const TPL_POSTAL_ADDRESS = 'Kitobchi: {order_id} raqamli buyurtmangiz {address} manzilidagi pochta bo\'limiga yetib keldi va sizni kutmoqda. Buyurtmani olib ketishingizni so\'raymiz.';

    /** Pochta: faqat trek raqami bilan */
    public const TPL_POSTAL_TRACKED = 'Kitobchi: {order_id} raqamli buyurtmangiz sizga eng yaqin pochta bo\'limiga yetib keldi va sizni kutmoqda. Trek raqami: {tracking}. Buyurtmani olib ketishingizni so\'raymiz.';

    /** Pochta: manzil ham, trek ham yo'q */
    public const TPL_POSTAL_PLAIN = 'Kitobchi: {order_id} raqamli buyurtmangiz sizga eng yaqin pochta bo\'limiga yetib keldi va sizni kutmoqda. Buyurtmani olib ketishingizni so\'raymiz.';

    /** Mijoz buyurtmani qo'lga olganda — minnatdorchilik + chek */
    public const TPL_RECEIVED = 'Kitobchi: xaridingiz uchun rahmat! {order_id} raqamli buyurtmangiz yakunlandi. Chekni ilovadagi Buyurtmalarim bo\'limidan topishingiz mumkin.';

    public function __construct(private readonly SmsService $smsService) {}

    /**
     * Status o'tishini kuzatib, kerakli SMS'ni yuboradi. Xato hech qachon
     * tashqariga otilmaydi — SMS yiqilsa ham buyurtma oqimi to'xtamaydi.
     */
    public function handleTransition(Sold $order, ?string $previousStatus, string $newStatus): void
    {
        try {
            $new = OrderStatusCode::fromLegacy($newStatus)->value;
            $previous = $previousStatus !== null ? OrderStatusCode::fromLegacy($previousStatus)->value : null;

            if ($new === $previous) {
                return;
            }

            $type = $order->deliveryType; // delivery | pickup | postal

            if ($new === OrderStatusCode::IN_DELIVERY->value && $type === 'delivery') {
                $this->sendOnce($order, 'on_the_way', self::TPL_ON_THE_WAY);
            }

            if ($new === OrderStatusCode::DELIVERED->value) {
                if ($type === 'delivery') {
                    // Kuryer mijozga topshirdi — darhol rahmat + chek
                    $this->sendOnce($order, 'received', self::TPL_RECEIVED);
                } elseif ($type === 'postal') {
                    $this->sendOnce($order, 'postal_arrived', $this->postalTemplate($order));
                }
                // pickup: SMS shart emas — mijoz do'konning o'zida
            }

            if ($new === OrderStatusCode::CUSTOMER_RECEIVED->value) {
                // 'received' milestone delivery'da allaqachon ketgan bo'lsa,
                // sendOnce guard dublikatni o'tkazmaydi.
                $this->sendOnce($order, 'received', self::TPL_RECEIVED);
            }
        } catch (\Throwable $e) {
            Log::warning('[OrderSms] Transition SMS yuborilmadi', [
                'order_id' => $order->id,
                'to' => $newStatus,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function postalTemplate(Sold $order): string
    {
        $fulfillment = $order->fulfillment;
        $tracking = trim((string) ($fulfillment?->postal_tracking_number ?? ''));
        $address = trim((string) ($fulfillment?->postal_office_address ?? ''));

        $template = match (true) {
            $address !== '' && $tracking !== '' => self::TPL_POSTAL_FULL,
            $address !== '' => self::TPL_POSTAL_ADDRESS,
            $tracking !== '' => self::TPL_POSTAL_TRACKED,
            default => self::TPL_POSTAL_PLAIN,
        };

        return strtr($template, [
            '{address}' => $address,
            '{tracking}' => $tracking,
        ]);
    }

    /**
     * Har bir buyurtma+bosqich uchun SMS faqat 1 marta ketadi (status
     * orqaga qaytarilib qayta o'tkazilsa ham dublikat bo'lmaydi).
     */
    private function sendOnce(Sold $order, string $milestone, string $template): void
    {
        $phone = preg_replace('/\D+/', '', (string) ($order->user?->phone_number ?? ''));

        if ($phone === '' || strlen($phone) < 9) {
            return;
        }

        $guardKey = "order-sms:{$order->id}:{$milestone}";

        if (! Cache::add($guardKey, 1, now()->addDays(60))) {
            return; // allaqachon yuborilgan
        }

        try {
            $this->smsService->send(
                $phone,
                strtr($template, ['{order_id}' => '#'.$order->id]),
            );
        } catch (\Throwable $e) {
            // Yiqilsa guardni qaytaramiz — keyingi urinishda qayta yuborilsin
            Cache::forget($guardKey);

            Log::warning('[OrderSms] SMS yuborilmadi', [
                'order_id' => $order->id,
                'milestone' => $milestone,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
