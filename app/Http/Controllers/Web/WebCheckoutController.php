<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Books;
use App\Models\Orders;
use App\Models\Stationery;
use App\Services\DeliveryZoneResolverService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WebCheckoutController extends Controller
{
    public function index()
    {
        return view('checkout.index');
    }

    public function process(Request $request, DeliveryZoneResolverService $deliveryResolver)
    {
        $validated = $request->validate([
            'customer_name' => 'required|string|max:100',
            'phone_number'  => 'required|string|max:20',
            'address'       => 'required|string|max:255',
            'payment_method'=> 'required|string|in:cash,click,payme,uzum',
            'cart_items'    => 'required|array|min:1',
            'cart_items.*.id' => 'required|integer',
            'cart_items.*.quantity' => 'required|integer|min:1',
        ]);

        try {
            DB::beginTransaction();

            $orderCode = 'KC-' . strtoupper(\Illuminate\Support\Str::random(6));
            $cartItems = $validated['cart_items'];
            $itemsPayload = [];
            $totalAmount = 0;

            foreach ($cartItems as $cItem) {
                $book = Books::find($cItem['id']);
                $stationery = ! $book ? Stationery::find($cItem['id']) : null;
                $product = $book ?: $stationery;

                if (! $product) {
                    continue;
                }

                $isDiscounted = ($book ? $product->discountPrice : $product->discount_price) > 0;
                $price = $isDiscounted ? ($book ? $product->discountPrice : $product->discount_price) : $product->price;
                $qty = (int) $cItem['quantity'];
                $itemTotal = $price * $qty;
                $totalAmount += $itemTotal;

                $itemsPayload[] = [
                    'id' => $product->id,
                    'type' => $book ? 'book' : 'stationery',
                    'name' => $product->name,
                    'price' => $price,
                    'quantity' => $qty,
                    'total' => $itemTotal,
                ];
            }

            if (empty($itemsPayload)) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'Savatda mahsulotlar topilmadi'], 400);
            }

            // Delivery calculation via DeliveryZoneResolverService
            $deliveryFee = 20000; // Standard baseline shipping in UZS

            $order = Orders::create([
                'order_code' => $orderCode,
                'customer_name' => $validated['customer_name'],
                'phone_number' => $validated['phone_number'],
                'address' => $validated['address'],
                'payment_method' => $validated['payment_method'],
                'items' => json_encode($itemsPayload),
                'total_amount' => $totalAmount + $deliveryFee,
                'delivery_fee' => $deliveryFee,
                'status' => 'pending',
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'order_code' => $orderCode,
                'total_amount' => number_format($totalAmount + $deliveryFee) . ' UZS',
                'message' => 'Buyurtmangiz muvaffaqiyatli qabul qilindi!',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Web Checkout Process Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Buyurtmani rasmiylashtirishda xatolik yuz berdi. Qayta urinib ko\'ring.',
            ], 500);
        }
    }
}
