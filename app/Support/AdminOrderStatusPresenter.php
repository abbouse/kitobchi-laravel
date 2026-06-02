<?php

namespace App\Support;

use App\Enums\CourierOrderStatusCode;
use App\Enums\OrderStatusCode;
use App\Enums\PaymentStatusCode;
use App\Enums\SellerOrderStatusCode;

class AdminOrderStatusPresenter
{
    public static function mainOrder(string|int|null $status): string
    {
        return match (OrderStatusCode::fromLegacy($status)) {
            OrderStatusCode::PENDING => 'Kutilmoqda',
            OrderStatusCode::PACKING => 'Qadoqlanmoqda',
            OrderStatusCode::IN_DELIVERY => "Yo'lda",
            OrderStatusCode::DELIVERED => 'Yetib bordi',
            OrderStatusCode::CUSTOMER_RECEIVED => 'Mijoz qabul qildi',
            OrderStatusCode::CANCELLED => 'Bekor qilingan',
            OrderStatusCode::RETURNED => 'Qaytgan',
        };
    }

    public static function payment(string|int|null $status): string
    {
        return match (PaymentStatusCode::fromLegacy($status)) {
            PaymentStatusCode::PAID => "To'langan",
            PaymentStatusCode::CARD_PENDING => 'Karta kutilmoqda',
            PaymentStatusCode::CASH_PENDING => 'Naqd kutilmoqda',
            PaymentStatusCode::CANCELLED => "To'lov bekor qilingan",
        };
    }

    public static function paymentDetail(string|int|null $status): string
    {
        return match (PaymentStatusCode::fromLegacy($status)) {
            PaymentStatusCode::PAID => 'Karta orqali to‘langan',
            PaymentStatusCode::CARD_PENDING => 'Karta orqali, tasdiq kutilmoqda',
            PaymentStatusCode::CASH_PENDING => 'Naqd to‘lov',
            PaymentStatusCode::CANCELLED => 'To‘lov bekor qilingan',
        };
    }

    public static function paymentMethod(string|int|null $status): string
    {
        return match (PaymentStatusCode::fromLegacy($status)) {
            PaymentStatusCode::PAID, PaymentStatusCode::CARD_PENDING => 'Karta / Paylov',
            PaymentStatusCode::CASH_PENDING => 'Naqd',
            PaymentStatusCode::CANCELLED => 'To‘lov bekor qilingan',
        };
    }

    public static function sellerOrder(string|int|null $status): string
    {
        return match (SellerOrderStatusCode::fromLegacy($status)) {
            SellerOrderStatusCode::PAYMENT_PENDING => "To'lov jarayonida",
            SellerOrderStatusCode::NEW => 'Yangi buyurtma',
            SellerOrderStatusCode::ACCEPTED => 'Qabul qilingan',
            SellerOrderStatusCode::HANDED_TO_COURIER => 'Kuryerga topshirilgan',
            SellerOrderStatusCode::CANCELLED => 'Bekor qilingan',
        };
    }

    public static function courierOrder(string|int|null $status): string
    {
        return match (CourierOrderStatusCode::fromLegacy($status)) {
            CourierOrderStatusCode::PAYMENT_PENDING => "To'lov kutilmoqda",
            CourierOrderStatusCode::PENDING => 'Kutilmoqda',
            CourierOrderStatusCode::IN_DELIVERY => "Yo'lda",
            CourierOrderStatusCode::DELIVERED => 'Yetib bordi',
            CourierOrderStatusCode::CUSTOMER_RECEIVED => 'Mijoz qabul qildi',
            CourierOrderStatusCode::CANCELLED => 'Bekor qilingan',
            CourierOrderStatusCode::RETURNED => 'Qaytgan',
        };
    }

    public static function courierProfile(string|int|null $status): string
    {
        return match ((string) $status) {
            'approved' => 'Tasdiqlangan',
            'pending' => "Ko'rib chiqilmoqda",
            'rejected' => 'Rad etilgan',
            'blocked' => 'Bloklangan',
            default => ((string) $status) !== '' ? (string) $status : '—',
        };
    }
}
