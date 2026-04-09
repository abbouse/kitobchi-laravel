<?php
namespace App\Http\Controllers;

use App\Models\Sold;
use App\Models\GiftCertificate;
use App\Models\MysteryBoxSubscription;

class PaymentController extends Controller
{
    private const MERCHANT_ID   = '67988cbfdbc8d1a8dc0d74a7';
    private const CALLBACK_BASE = 'https://kitobchi.com/payment/success/';

    public function payWithPayme(int $order_id)
    {
        $sold = Sold::find($order_id);
        if (!$sold) return redirect()->back()->with('error', 'Buyurtma topilmadi!');

        return redirect($this->url('NRK-' . $sold->id, $sold->amount * 100,
            self::CALLBACK_BASE . 'order/' . $order_id));
    }

    public function payGiftCert(int $cert_id)
    {
        $cert = GiftCertificate::find($cert_id);
        if (!$cert || $cert->status != GiftCertificate::STATUS_PENDING)
            return redirect()->back()->with('error', 'Sertifikat topilmadi!');

        return redirect($this->url('NDR-' . $cert->id, $cert->nominal_uzs * 100,
            self::CALLBACK_BASE . 'gift-cert/' . $cert_id));
    }

    public function payMysteryBox(int $sub_id)
    {
        $sub = MysteryBoxSubscription::find($sub_id);
        if (!$sub || $sub->status != MysteryBoxSubscription::STATUS_PENDING)
            return redirect()->back()->with('error', 'Obuna topilmadi!');

        return redirect($this->url('MQD-' . $sub->id, $sub->price_uzs * 100,
            self::CALLBACK_BASE . 'mystery-box/' . $sub_id));
    }

    private function url(string $orderId, int $amount, string $callback): string
    {
        $data = 'm='  . self::MERCHANT_ID
              . ';ac.order_id=' . $orderId
              . ';a='  . $amount
              . ';c='  . urlencode($callback);
        return 'https://checkout.paycom.uz/' . base64_encode($data);
    }

    public function successOrder(int $id)    { return redirect()->away("kitobchi://payment-success?type=order&id=$id"); }
    public function successGiftCert(int $id) { return redirect()->away("kitobchi://payment-success?type=gift_certificate&id=$id"); }
    public function successMysteryBox(int $id){ return redirect()->away("kitobchi://payment-success?type=mystery_box&id=$id"); }
}