<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sold;
use App\Models\SellerOrder;
use App\Models\CourierOrder;
use App\Models\Kirim;
use App\Models\User;
use App\Models\Transaction;
use App\Models\CashbackSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Resources\TransactionResource;
use Nutgram\Laravel\Facades\Telegram;

class PaymeController extends Controller
{
    public function index(Request $req)
    {
        $method = $req->method;
        
        switch ($method) {
            case "CheckPerformTransaction":
                return $this->checkPerformTransaction($req);

            case "CreateTransaction":
                return $this->createTransaction($req);

            case "CheckTransaction":
                return $this->checkTransaction($req);

            case "PerformTransaction":
                return $this->performTransaction($req);

            case "CancelTransaction":
                return $this->cancelTransaction($req);

            case "GetStatement":
                return $this->getStatement($req);

            case "ChangePassword":
                return $this->changePasswordError($req);

            default:
                return response()->json(['error' => 'Method not supported'], 400);
        }
    }

    private function checkPerformTransaction($req)
    {
        $account = $req->params['account'] ?? null;

        if (!$account) {
            return $this->errorResponse($req->id, -32504, "Недостаточно привилегий для выполнения метода");
        }

        $order = Sold::where('id', $account['order_id'])->where('paymentStatus', '1')->where('status', 'A')->first();

        if (!$order) {
            return $this->errorResponse($req->id, -31050, [
                "uz" => "Buyurtma topilmadi yoki to'lov qilishga berilgan vaqt tugagan",
                "ru" => "Заказ не найден или время оплаты истекло",
                "en" => "Order not found or payment time has expired",
            ]);
        }

        if ($order->amount * 100 != $req->params['amount']) {
            return $this->errorResponse($req->id, -31001, [
                "uz" => "Notogri summa",
                "ru" => "Неверная сумма",
                "en" => "Incorrect amount",
            ]);
        }

        return response()->json(['result' => ['allow' => true]]);
    }

    private function createTransaction($req)
    {
        $account = $req->params['account'] ?? null;

        if (!$account) {
            return $this->errorResponse($req->id, -32504, "Bajarish usuli uchun imtiyozlar etarli emas.");
        }

        $order = Sold::where('id', $account['order_id'])->where('paymentStatus', '1')->where('status', 'A')->first();
        $existingTransaction = Transaction::where('order_id', $account['order_id'])->where('state', 1)->get();

        if (!$order) {
            return $this->errorResponse($req->id, -31050, [
                "uz" => "Buyurtma topilmadi",
                "ru" => "Заказ не найден",
                "en" => "Order not found",
            ]);
        }

        if ($order->amount * 100 != $req->params['amount']) {
            return $this->errorResponse($req->id, -31001, [
                "uz" => "Notogri summa",
                "ru" => "Неверная сумма",
                "en" => "Incorrect amount",
            ]);
        }

        if (count($existingTransaction) == 0) {
            $transaction = new Transaction();
                    $transaction->paycom_transaction_id = $req->params['id'];
                    $transaction->paycom_time = $req->params['time'];
                    $transaction->paycom_time_datetime = now();
                    $transaction->amount = $req->params['amount'];
                    $transaction->state = 1;
                    $transaction->order_id = $account['order_id'];
                    $transaction->save();

            return response()->json([
                'result' => [
                    'create_time' => $req->params['time'],
                    'transaction' => (string) $transaction->id,
                    'state' => $transaction->state,
                ],
            ]);
        }

        if ((count($existingTransaction) == 1) and ($existingTransaction->first()->paycom_time == $req->params['time']) and ($existingTransaction->first()->paycom_transaction_id == $req->params['id'])) {
            
            
            return response()->json([
                'result' => [
                    'create_time' => $req->params['time'],
                    'transaction' => (string) $existingTransaction->first()->id,
                    'state' => $existingTransaction->first()->state,
                ],
            ]);
        }

        return $this->errorResponse($req->id, -31099, [
            "uz" => "Buyurtma tolovi hozirda amalga oshrilmoqda",
            "ru" => "Оплата заказа в данный момент обрабатывается",
            "en" => "Order payment is currently being processed",
        ]);
    }

    private function checkTransaction($req)
    {
        $transaction = Transaction::where('paycom_transaction_id', $req->params['id'])->first();

        if (!$transaction) {
            return $this->errorResponse($req->id, -31003, "Транзакция не найдена.");
        }

        return response()->json(['result' => [
            'create_time' => (int) $transaction->paycom_time,
            'perform_time' => (int) $transaction->perform_time_unix,
            'cancel_time' => $transaction->cancel_time ? (int) $transaction->cancel_time : 0,
            'transaction' => (string) $transaction->id,
            'state' => $transaction->state,
            'reason' => $transaction->reason,
        ]]);
    }

    private function performTransaction($req)
{
    $ldate = date('Y-m-d H:i:s');
    $transaction = Transaction::where('paycom_transaction_id', $req->params['id'])->first();

    if (!$transaction) {
        return $this->errorResponse($req->id, -31003, "Транзакция не найдена.");
    }

    if ($transaction->state == 1) {
        $currentMillis = intval(microtime(true) * 1000);
        $transaction->state = 2;
        $transaction->perform_time = $ldate;
        $transaction->perform_time_unix = str_replace('.', '', $currentMillis);
        $transaction->update();

        $completed_order = Sold::where('id', $transaction->order_id)->first();
        $completed_order->paymentStatus = '2';
        $completed_order->update();
        
        $seller_order = SellerOrder::where('order_id', $completed_order->id)->first();
        $seller_order->status = '1';
        $seller_order->update();
        
        $courier_order = CourierOrder::where('order_id', $completed_order->id)->first();
        $courier_order->status = 'pending';
        $courier_order->update();
        
        $orderAmountUzs = $completed_order->amount;
        $cashbackPercent = CashbackSetting::getCashbackPercentage($orderAmountUzs);
        if ($cashbackPercent > 0) {
            $cashbackAmount = ($orderAmountUzs * $cashbackPercent) / 100;
            $user = User::find($completed_order->user_id);
            if ($user) {
                $user->cashback += $cashbackAmount; 
                $user->save();
                Log::info("User ID: {$user->id} for Order ID: {$completed_order->id}. Cashback added: {$cashbackAmount} UZS ({$cashbackPercent}%)");
            }
        }
        
        $kirim = new Kirim();
        $kirim->user_id = $completed_order->user_id;
        $kirim->order_id = $completed_order->id;
        $kirim->paymentStatus = '2';
        $kirim->amount = $completed_order->amount;
        $kirim->save();

        return response()->json(['result' => [
            'transaction' => (string) $transaction->id,
            'perform_time' => (int) $transaction->perform_time_unix,
            'state' => $transaction->state,
        ]]);
    }

    return response()->json(['result' => [
        'transaction' => (string) $transaction->id,
        'perform_time' => (int) $transaction->perform_time_unix,
        'state' => $transaction->state,
    ]]);
}

    private function cancelTransaction($req)
{
    $transaction = Transaction::where('paycom_transaction_id', $req->params['id'])->first();

    if (!$transaction) {
        return $this->errorResponse($req->id, -31003, "Транзакция не найдена.");
    }
    
    if (!in_array($transaction->state, [-1, -2])) {
        if ($transaction->state == 1) {
            $state = -1;
        } elseif ($transaction->state == 2) {
            $state = -2;
        } else {
            return $this->errorResponse($req->id, -31008, "Неверное состояние транзакции.");
        }

        $transaction->update([
            'state' => $state,
            'reason' => $req->params['reason'],
            'cancel_time' => (int)(microtime(true) * 1000),
        ]);

        $order = Sold::find($transaction->order_id);
        $seller_order = SellerOrder::where('order_id', $order->id)->first();
        $seller_order->status = '3';
        $seller_order->update();
        $courier_order = CourierOrder::where('order_id', $order->id)->first();
        $courier_order->status = 'rejected';
        $courier_order->update();
        $order->update([
            'paymentStatus' => '3',
            'status' => 'F'
        ]);
    }

    return response()->json([
        'result' => [
            'state' => $transaction->state,
            'cancel_time' => (int)$transaction->cancel_time,
            'transaction' => (string)$transaction->id,
        ]
    ]);
}

    private function getStatement($req)
    {
        $from = $req->params['from'];
        $to = $req->params['to'];
        $transactions = Transaction::getTransactionsByTimeRange($from, $to);

        return response()->json(['result' => [
            'transactions' => TransactionResource::collection($transactions),
        ]]);
    }

    private function changePasswordError($req)
    {
        return $this->errorResponse($req->id, -32504, "Недостаточно привилегий для выполнения метода");
    }

    private function errorResponse($id, $code, $message)
    {
        return response()->json([
            'id' => $id,
            'error' => ['code' => $code, 'message' => $message],
        ]);
    }
}
