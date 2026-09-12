<?php

namespace App\Services;

use App\Enums\OrderStatusCode;
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
 *  - Faqat buyurtma hali "yosh" bo'lganda ruxsat beriladi — seller order
 *    hali qabul qilinmagan/kuryerga topshirilmagan, va bosh buyurtma hali
 *    yetkazish/yakunlash bosqichiga o'tmagan bo'lishi kerak. Bu bilan
 *    moliyaviy hisob-kitob (baholar, komissiya, seller balansi) va
 *    logistika (kuryer topshiriqlari) allaqachon boshlangan buyurtmalarni
 *    "o'g'irlab olish" imkoniyati oldini olinadi — ular uchun mavjud
 *    bekor qilish/refund oqimlaridan foydalanish kerak.
 *  - 'gift' turidagi itemlar (platformaning o'z sovg'asi, seller_id=1)
 *    hech qachon qayta biriktirilmaydi — ular do'konga emas, platformaga
 *    tegishli.
 */
class SellerOrderReassignmentService
{
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
            throw new RuntimeException("Bu buyurtmani hozirgi bosqichida boshqa do'konga o'tkazib bo'lmaydi (seller allaqachon qabul qilgan/kuryerga topshirilgan, yoki bosh buyurtma yetkazish/yakunlash bosqichida).");
        }

        DB::transaction(function () use ($sellerOrder, $order, $oldSellerId, $newSellerId): void {
            $sellerOrder->forceFill(['seller_id' => $newSellerId])->save();

            $sellerOrder->items()
                ->where('seller_id', $oldSellerId)
                ->where('type', '!=', 'gift')
                ->update(['seller_id' => $newSellerId]);

            $items = $order->items ?? [];
            $changed = false;
            foreach ($items as &$item) {
                if ((int) ($item['seller_id'] ?? 0) === $oldSellerId && ($item['type'] ?? null) !== 'gift') {
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
     * Boshqaruv paneli (AdminController::sellerOrderPayload()) shu metod
     * orqali "Do'konni almashtirish" tugmasini ko'rsatish-ko'rsatmaslikni
     * hal qiladi — mantiq bitta joyda (shu yerda) saqlanadi, ikki marta
     * yozilmaydi.
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
        // Seller order o'zi hali "yangi" bosqichda bo'lishi kerak — do'kon
        // buyurtmani qabul qilib, tayyorlashni boshlagandan keyin (yoki
        // kuryerga topshirilgandan keyin) egalikni almashtirish xavfli.
        $sellerOrderStatus = SellerOrderStatusCode::fromLegacy($sellerOrder->status_code ?? $sellerOrder->status);
        if (! in_array($sellerOrderStatus, [SellerOrderStatusCode::PAYMENT_PENDING, SellerOrderStatusCode::NEW], true)) {
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
