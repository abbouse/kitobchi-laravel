<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sold;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function payWithPayme($order_id)
{
    $sold = Sold::find($order_id);

    if (!$sold) {
        return redirect()->back()->with('error', "Buyurtma topilmadi!");
    }
    $merchantId = "67988cbfdbc8d1a8dc0d74a7";
    $orderId = $sold->id;
    $amount = $sold->amount * 100;
    $callbackUrl = urlencode("https://bilim24.uz/payment/success/" . $order_id);

    $data = "m=$merchantId;ac.order_id=$orderId;a=$amount;c=$callbackUrl";
    $encodedData = base64_encode($data);

    return redirect("https://checkout.paycom.uz/$encodedData");
}

public function redirectToApp($order_id)
{
    return redirect()->away("myapp://payment-success?order_id=$order_id");
}

}
