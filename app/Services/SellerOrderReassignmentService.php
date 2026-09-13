<?php

namespace App\Services;

use App\Enums\OrderStatusCode;
use App\Enums\PaymentStatusCode;
use App\Enums\SellerOrderStatusCode;
use App\Models\Admin;
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
 *    buyurtmani QABUL QILADI (hatto KURYERGA TOPSHIRADI ham), lekin keyin
 *    mahsulot omborida yo'qligi ma'lum bo'ladi va operatorlar uni B
 *    do'konidan topib yuboradi. Shu sabab reassignment endi SELLER_ORDER
 *    ACCEPTED va hatto HANDED_TO_COURIER bosqichida ham ruxsat etiladi.
 *  - MUHIM (2026-09, foydalanuvchi aniq tasdiqlagan qaror): agar seller
 *    order ALLAQACHON kuryerga topshirilgan bo'lsa, reassignment paytida
 *    mavjud CourierTask/CourierOrder yozuvlari, kuryerning o'zi, va unga
 *    tegishli narx/bonus HECH QANDAY o'zgartirilmaydi va qayta
 *    hisoblanmaydi — ular eski (A) do'kon ma'lumoti bilan qolaveradi.
 *    Kuryerni jismonan xabardor qilish operatorning o'zi zimmasida,
 *    tizimdan tashqarida amalga oshiriladi. Bu holatda FAQAT buyurtma
 *    egaligi (kim to'lov/komissiya olishi) almashtiriladi — shu sababli
 *    bunday holatda seller order holati ham "handed_to_courier" bo'lib
 *    QOLAVERADI (pastga, reassign() ichidagi izohga qarang) — faqat
 *    hali kuryerga topshirilmagan (PAYMENT_PENDING/NEW/ACCEPTED)
 *    bosqichdagi reassignmentlarda holat "yangi"ga qaytariladi.
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
 *  - Yangi do'konga o'tkazilgan seller order holati odatda "yangi" (NEW,
 *    yoki to'lov karta orqali hali kutilayotgan bo'lsa PAYMENT_PENDING) ga
 *    qaytariladi va accepted_at tozalanadi — chunki B bu buyurtmani hali
 *    umuman ko'rmagan/qabul qilmagan, uni birinchi marta ko'rayotgandek
 *    o'zi qabul qilishi kerak. YAGONA ISTISNO — yuqorida tasvirlangan
 *    "allaqachon kuryerga topshirilgan" holat: unda holat o'zgartirilmaydi
 *    (yuqoridagi izohga qarang).
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
            throw new RuntimeException("Bu buyurtmani hozirgi bosqichida boshqa do'konga o'tkazib bo'lmaydi (seller order bekor qilingan, yoki bosh buyurtma yetkazish/yakunlash bosqichida).");
        }

        // MUHIM (2026-09, foydalanuvchi aniq tasdiqlagan qaror): agar
        // seller order ALLAQACHON kuryerga topshirilgan bo'lsa — holat
        // ("handed_to_courier") va accepted_at ATAYLAB O'ZGARTIRILMAYDI,
        // chunki kuryer topshirig'i haqiqatan ham eskicha (A do'kon bilan)
        // qolyapti, buni "yangi"ga qaytarish yolg'on ma'lumot bo'lardi.
        // Faqat egalik (seller_id) almashtiriladi.
        //
        // Aks holda (hali PAYMENT_PENDING/NEW/ACCEPTED bosqichida) — yangi
        // do'kon buyurtmani birinchi marta ko'rayotgandek "yangi" holatda
        // qabul qilishi kerak: to'lov karta orqali hali kutilayotgan bo'lsa
        // PAYMENT_PENDING, aks holda NEW (AdminOrderStatusSyncService::
        // mapMainToSeller() dagi bir xil qoidaga mos).
        $currentStatus = SellerOrderStatusCode::fromLegacy($sellerOrder->status_code ?? $sellerOrder->status);
        $alreadyHandedToCourier = $currentStatus === SellerOrderStatusCode::HANDED_TO_COURIER;

        $newSellerOrderStatus = null;
        if (! $alreadyHandedToCourier) {
            $paymentCode = PaymentStatusCode::fromLegacy($order->payment_status_code ?? $order->paymentStatus);
            $newSellerOrderStatus = $paymentCode === PaymentStatusCode::CARD_PENDING
                ? SellerOrderStatusCode::PAYMENT_PENDING
                : SellerOrderStatusCode::NEW;
        }

        DB::transaction(function () use ($sellerOrder, $order, $oldSellerId, $newSellerId, $alreadyHandedToCourier, $newSellerOrderStatus): void {
            $sellerOrder->seller_id = $newSellerId;
            if (! $alreadyHandedToCourier) {
                $sellerOrder->status = $newSellerOrderStatus->legacy();
                $sellerOrder->status_code = $newSellerOrderStatus->value;
                $sellerOrder->accepted_at = null;
            }
            $sellerOrder->save();

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
        // Seller order hali BEKOR QILINMAGAN bo'lishi kerak — bekor
        // qilingan (refund qilingan) seller orderni "almashtirish"
        // ma'nosiz, u uchun mavjud bekor qilish/refund oqimi bor.
        //
        // 2026-09 gacha bu yerda HANDED_TO_COURIER taqiqlangan edi (va
        // shu do'kon uchun CourierTask mavjudligi ham qo'shimcha to'siq
        // edi) — chunki logistika jismonan shu do'kon bilan bog'langan
        // deb hisoblangan. Foydalanuvchi aniq tasdiqlagan qaror bilan bu
        // cheklov olib tashlandi: amalda mahsulot yo'qligi ko'pincha
        // aynan kuryer allaqachon yo'lga chiqqandan keyin ham ma'lum
        // bo'ladi, va bu holatda kuryer topshirig'ini o'zgartirish/qayta
        // hisoblash SHART EMAS (operator buni tizimdan tashqarida hal
        // qiladi) — faqat pul kim OLISHI (buyurtma egaligi) to'g'ri
        // bo'lishi kerak. Shu sabab CourierTask tekshiruvi ham olib
        // tashlandi.
        $sellerOrderStatus = SellerOrderStatusCode::fromLegacy($sellerOrder->status_code ?? $sellerOrder->status);
        if ($sellerOrderStatus === SellerOrderStatusCode::CANCELLED) {
            return false;
        }

        // Bosh buyurtma allaqachon yetkazish/yakunlash/bekor qilish
        // bosqichida bo'lsa taqiqlanadi. Odatda bu yerga faqat "direct
        // courier" rejimida va aynan OXIRGI seller order kuryerga
        // topshirilgan paytda tushiladi (AdminOrderStatusSyncService::
        // updateSellerOrder()) — hub orqali yuboriladigan buyurtmalarda
        // HANDED_TO_COURIER bosqichida ham bosh buyurtma odatda hali
        // "Qadoqlanmoqda" holatida qoladi.
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

        return true;
    }
}
