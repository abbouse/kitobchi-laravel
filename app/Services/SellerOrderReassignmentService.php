<?php

namespace App\Services;

use App\Enums\OrderStatusCode;
use App\Enums\PaymentStatusCode;
use App\Enums\SellerOrderStatusCode;
use App\Models\Admin;
use App\Models\CourierTask;
use App\Models\Seller;
use App\Models\SellerOrder;
use App\Models\Sold;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Buyurtmaning bitta "seller order" qismini (ya'ni bitta do'konga tegishli
 * bo'lagini) boshqa do'konga qayta biriktiradi.
 *
 * FAQAT superadmin uchun (2026-09, foydalanuvchi talabi bilan): oddiy
 * admin — hatto 'sellers' moduliga ruxsati bo'lsa ham — buyurtmaning
 * egasi do'konini o'zgartira olmasligi kerak. Bu servis shu cheklovni
 * yana bir bor (chaqiruvchi tomonda ham tekshirilgan bo'lsa-da) tasdiqlaydi.
 *
 * Nima uchun bu operatsiya "to'liq CRUD" emas, balki cheklangan tuzatish
 * vositasi:
 *  - Faqat DO'KON egaligi (seller_id) almashtiriladi. Narx, mahsulot,
 *    manzil va h.k. o'zgarmaydi.
 *  - Ruxsat quyidagi real hayotiy holatga mo'ljallangan (2026-09,
 *    foydalanuvchi tasdiqlagan): mijoz A do'konidan buyurtma beradi, A
 *    buyurtmani QABUL QILADI, lekin keyin mahsulot omborida yo'qligi
 *    ma'lum bo'ladi va operatorlar uni B do'konidan topib yuboradi. Shu
 *    sabab reassignment SELLER_ORDER ACCEPTED bosqichida ham ruxsat
 *    etiladi — faqat kuryerga TOPSHIRILGANDAN keyin (yoki shu do'kon
 *    uchun kuryer topshirig'i allaqachon yaratilgan bo'lsa) taqiqlanadi,
 *    chunki shu paytdan boshlab logistika jismonan A do'koni bilan
 *    bog'langan bo'ladi.
 *  - Bosh buyurtma hali yetkazish/yakunlash bosqichiga o'tmagan bo'lishi
 *    kerak. Bu bilan moliyaviy hisob-kitob (baholar, komissiya, seller
 *    balansi — SellerOrderSettlementService::settleCompletedOrder() FAQAT
 *    buyurtma "yetkazilgan va to'langan" bo'lgandagina ishga tushadi,
 *    ya'ni ACCEPTED/HANDED_TO_COURIER bosqichida hali balansga hech qanday
 *    pul tushmagan bo'ladi) allaqachon yakunlangan buyurtmalarni
 *    "o'g'irlab olish" imkoniyati oldini olinadi — ular uchun mavjud
 *    bekor qilish/refund oqimlaridan foydalanish kerak.
 *  - 'gift' turidagi itemlar (platformaning o'z sovg'asi, seller_id=1)
 *    hech qachon qayta biriktirilmaydi — ular do'konga emas, platformaga
 *    tegishli.
 *  - Eski do'kon (A) checkout paytida o'z ombor zaxirasidan (branch stock)
 *    kamaytirilgan edi, lekin haqiqatda mahsulotni jismonan yubormaydi —
 *    shu sabab reassignment paytida bu miqdor A ning zaxirasiga AVTOMATIK
 *    qaytariladi (OrderService::incrementStock() orqali, checkout paytida
 *    olingan aynan o'sha filialga). Yangi do'kon (B) uchun alohida stock
 *    kamaytirilmaydi — chunki bu tizimda B ning o'z katalogida aynan shu
 *    mahsulot yozuvi yo'q (har bir mahsulot yozuvi bitta seller_id'ga
 *    biriktirilgan); B jismoniy yetkazib berishni tashqi/qo'lda
 *    kelishuv asosida amalga oshiradi.
 *  - Yangi do'konga o'tkazilgan seller order holati "yangi" (NEW, yoki
 *    to'lov karta orqali hali kutilayotgan bo'lsa PAYMENT_PENDING) ga
 *    qaytariladi va accepted_at tozalanadi — chunki B bu buyurtmani hali
 *    umuman ko'rmagan/qabul qilmagan, uni birinchi marta ko'rayotgandek
 *    o'zi qabul qilishi kerak.
 */
class SellerOrderReassignmentService
{
    public function __construct(
        private readonly OrderService $orderService,
    ) {}

    public function reassign(Admin $admin, SellerOrder $sellerOrder, Seller $newSeller, ?string $reason = null): SellerOrder
    {
        if (! $admin->isSuperAdmin()) {
            throw new RuntimeException("Bu amal faqat superadmin uchun ruxsat etilgan.");
        }

        $oldSellerId = (int) $sellerOrder->seller_id;
        $newSellerId = (int) $newSeller->id;

        if ($oldSellerId === $newSellerId) {
            throw new RuntimeException("Buyurtma allaqachon shu do'konga tegishli.");
        }

        if ($newSeller->parent_id) {
            throw new RuntimeException("Xodim (filial) hisobiga emas, faqat asosiy do'kon hisobiga buyurtma biriktirish mumkin.");
        }

        if ($newSeller->status !== 'approved' || $newSeller->is_hidden) {
            throw new RuntimeException("Tanlangan do'kon faol emas (tasdiqlanmagan yoki yashirilgan) — unga buyurtma o'tkazib bo'lmaydi.");
        }

        /** @var Sold|null $order */
        $order = Sold::query()->find($sellerOrder->order_id);
        if (! $order) {
            throw new RuntimeException('Bosh buyurtma topilmadi.');
        }

        if (! $this->canReassignOrder($order, $sellerOrder, $oldSellerId)) {
            throw new RuntimeException("Bu buyurtmani hozirgi bosqichida boshqa do'konga o'tkazib bo'lmaydi (seller kuryerga topshirgan, yoki bosh buyurtma yetkazish/yakunlash bosqichida).");
        }

        // Yangi seller-order holati: to'lov karta orqali hali kutilayotgan
        // bo'lsa PAYMENT_PENDING, aks holda NEW — xuddi buyurtma birinchi
        // marta yaratilgandagi kabi (AdminOrderStatusSyncService::
        // mapMainToSeller() dagi bir xil qoidaga mos).
        $paymentCode = PaymentStatusCode::fromLegacy($order->payment_status_code ?? $order->paymentStatus);
        $newSellerOrderStatus = $paymentCode === PaymentStatusCode::CARD_PENDING
            ? SellerOrderStatusCode::PAYMENT_PENDING
            : SellerOrderStatusCode::NEW;

        DB::transaction(function () use ($sellerOrder, $order, $oldSellerId, $newSellerId, $newSellerOrderStatus): void {
            $sellerOrder->forceFill([
                'seller_id' => $newSellerId,
                'status' => $newSellerOrderStatus->legacy(),
                'status_code' => $newSellerOrderStatus->value,
                'accepted_at' => null,
            ])->save();

            $sellerOrder->items()
                ->where('seller_id', $oldSellerId)
                ->where('type', '!=', 'gift')
                ->update(['seller_id' => $newSellerId]);

            $items = $order->items ?? [];
            $changed = false;
            foreach ($items as &$item) {
                if ((int) ($item['seller_id'] ?? 0) === $oldSellerId && ($item['type'] ?? null) !== 'gift') {
                    // Eski do'kon (A) checkout paytida shu miqdorni o'z
                    // filial-zaxirasidan yechgan edi, lekin jismonan
                    // yubormaydi — shu sabab reassignment paytida bu
                    // miqdorni A ga qaytaramiz (aynan o'sha filialga,
                    // item['location_id'] orqali — OrderService::
                    // incrementStock() shu maydonni o'qiydi).
                    $this->orderService->incrementStock($item);
                    $item['seller_id'] = $newSellerId;
                    $changed = true;
                }
            }
            unset($item);

            if ($changed) {
                $order->forceFill(['items' => $items])->save();
            }
        });

        return $sellerOrder->refresh();
    }

    /**
     * Boshqaruv paneli (AdminController::sellerOrderPayload() va
     * AdminController::orderPayload()) shu metod orqali "Do'konni
     * almashtirish" tugmasini ko'rsatish-ko'rsatmaslikni hal qiladi —
     * mantiq bitta joyda (shu yerda) saqlanadi, ikki marta yozilmaydi.
     */
    public function canReassign(SellerOrder $sellerOrder): bool
    {
        $order = Sold::query()->find($sellerOrder->order_id);
        if (! $order) {
            return false;
        }

        return $this->canReassignOrder($order, $sellerOrder, (int) $sellerOrder->seller_id);
    }

    private function canReassignOrder(Sold $order, SellerOrder $sellerOrder, int $oldSellerId): bool
    {
        // Seller order hali kuryerga TOPSHIRILMAGAN bosqichda bo'lishi
        // kerak. 2026-09 gacha bu yerda faqat PAYMENT_PENDING/NEW ruxsat
        // etilgan edi — ammo real hayotda do'kon ko'pincha buyurtmani
        // QABUL QILGANDAN keyin (tayyorlash jarayonida) mahsulot yo'qligini
        // aniqlaydi. Shu sabab ACCEPTED ham endi ruxsat etilgan holatlar
        // qatoriga qo'shildi — faqat HANDED_TO_COURIER dan keyin
        // taqiqlanadi (o'sha paytdan boshlab logistika jismonan shu do'kon
        // bilan bog'langan bo'ladi).
        $sellerOrderStatus = SellerOrderStatusCode::fromLegacy($sellerOrder->status_code ?? $sellerOrder->status);
        if (! in_array($sellerOrderStatus, [
            SellerOrderStatusCode::PAYMENT_PENDING,
            SellerOrderStatusCode::NEW,
            SellerOrderStatusCode::ACCEPTED,
        ], true)) {
            return false;
        }

        // Bosh buyurtma allaqachon yetkazish/yakunlash/bekor qilish
        // bosqichida bo'lsa ham taqiqlanadi.
        $orderStatus = OrderStatusCode::fromLegacy($order->status_code ?? $order->status);
        if (in_array($orderStatus, [
            OrderStatusCode::IN_DELIVERY,
            OrderStatusCode::DELIVERED,
            OrderStatusCode::CUSTOMER_RECEIVED,
            OrderStatusCode::RETURNED,
            OrderStatusCode::CANCELLED,
        ], true)) {
            return false;
        }

        // Qo'shimcha ehtiyot chorasi: shu do'kon uchun kuryer topshirig'i
        // allaqachon yaratilgan bo'lsa (logistika boshlangan), taqiqlanadi.
        $hasCourierTask = CourierTask::query()
            ->where('order_id', $order->id)
            ->where('seller_id', $oldSellerId)
            ->exists();

        return ! $hasCourierTask;
    }
}
