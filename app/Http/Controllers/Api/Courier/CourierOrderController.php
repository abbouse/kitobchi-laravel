<?php

namespace App\Http\Controllers\Api\Courier;

use App\Http\Controllers\Controller;
use App\Models\Sold;
use App\Models\User;
use App\Models\Seller;
use App\Models\Couriers;
use App\Models\CourierOrder;
use App\Models\CourierOrderItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CourierOrderController extends Controller
{
    
public function getAvailableOrders(Request $request)
{
    $courier = Auth::guard('courier')->user();
    if (!$courier) {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized'
        ], 401);
    }

$orders = CourierOrder::where('status', 'pending')
    ->whereNull('courier_id')
    ->with([
        'paymentStatus',
        'items.product',
        'items.orderStatus',
        'items.sellerLocation.workdays',
        'customer.location'
    ])
    ->latest()
    ->get()
    ->map(function ($order) {
        foreach ($order->items as $item) {
            if ($item->product && $item->product->seller && $item->sellerLocation) {
                $item->product->seller->location = $item->sellerLocation;
            }
        }
        return $order;
    });


    return response()->json([
        'success' => true,
        'data' => $orders
    ], 200);
}

public function showOrder(Request $request, $id)
{
    $courier = Auth::guard('courier')->user();
    if (!$courier) {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized'
        ], 401);
    }
    $show = CourierOrder::where('order_id', $id)
    ->where('courier_id', $courier->id)
    ->with([
        'paymentStatus',
        'items.product',
        'items.orderStatus',
        'items.sellerLocation.workdays',
        'customer.location'
    ])
    ->get()
    ->map(function ($orderShow) {
        foreach ($orderShow->items as $item) {
            if ($item->product && $item->product->seller && $item->sellerLocation) {
                $item->product->seller->location = $item->sellerLocation;
            }
        }
        return $orderShow;
    });
    return response()->json([
        'success' => true,
        'data' => $show
    ], 200);
}

public function toCustomer(Request $request, $qr)
{
    $courier = Auth::guard('courier')->user();
    if (!$courier) {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized'
        ], 401);
    }
    $orderCustomer = Sold::where('status', 'B')
        ->where('qr', $qr)
        ->where('courier_id', $courier->id)
        ->first();
    if (!$orderCustomer) {
        return response()->json(['success' => false, 'message' => 'Buyurtma topilmadi'], 404);
    }
    $order = CourierOrder::where('order_id', $orderCustomer->id)
        ->where('status', 'in_delivery')
        ->where('courier_id', $courier->id)
        ->first();
    if (!$order) {
        return response()->json(['success' => false, 'message' => 'Buyurtma mavjud emas yoki allaqachon yetkazilgan'], 404);
    }
    try {
        $order->status = 'delivered';
        $order->save();
        
        $orderCustomer->status = "C";
        $orderCustomer->qr = "";
        $orderCustomer->save();
        
        $courier->balance += ($order->courierPrice + $order->courierBonus);
        $courier->save();
        return response()->json([
            'success' => true,
            'message' => 'Buyurtma yetkazildi',
            'order_id' => $order->order_id,
        ], 200);
    } catch (\Throwable $th) {
        return response()->json(['success' => false, 'message' => 'Xatolik: '.$th->getMessage()], 500);
    }
}


    public function confirmOrder(Request $request, $id)
{
    $courier = Auth::guard('courier')->user();
    if (!$courier) {
        return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
    }
     $order = CourierOrder::where('order_id', $id)->where('status', 'pending')
    ->whereNull('courier_id')->first();
    $sold = Sold::where('id', $id)->whereNull('courier_id')->first();
    if (!$order || $order->status !== 'pending') {
        return response()->json(['success' => false, 'message' => 'Buyurtma mavjud emas yoki allaqachon qabul qilingan'], 404);
    }
    try {
        $sold->courier_id = $courier->id;
        $sold->courierName = $courier->first_name.' '.$courier->last_name;
        $sold->status = 'P';
        $sold->save();
        $order->status = 'pending';
        $order->courier_id = $courier->id;
        $order->save();
        return response()->json([
            'success' => true,
            'message' => 'Buyurtma kuryerga biriktirildi',
            'order_id' => $order->id,
        ], 200);
    } catch (\Throwable $th) {
        return response()->json(['success' => false, 'message' => 'Xatolik: '.$th->getMessage()], 500);
    }
}
public function myOrders(Request $request)
{
    $courier = Auth::guard('courier')->user();
    if (!$courier) {
        return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
    }
    $orders = CourierOrder::where('courier_id', $courier->id)
    ->with([
        'paymentStatus',
        'items.product.seller',
        'items.orderStatus',
        'items.sellerLocation.workdays',
        'customer.location'
    ])
    ->latest()
    ->get()
    ->map(function ($order) {
        foreach ($order->items as $item) {
            if ($item->product && $item->product->seller && $item->sellerLocation) {
                $item->product->seller->location = $item->sellerLocation;
            }
        }
        return $order;
    });
    return response()->json([
        'success' => true,
        'data' => $orders,
    ], 200);
}

}