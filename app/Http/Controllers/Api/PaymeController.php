<?php

namespace App\Http\Controllers\Api;

use App\Enums\PaymentStatusCode;
use App\Http\Controllers\Controller;
use App\Models\Sold;
use App\Models\User;
use App\Models\Kirim;
use App\Models\Transaction;
use App\Models\GiftCertificate;
use App\Models\MysteryBoxSubscription;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Resources\TransactionResource;

class PaymeController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService,
    ) {}

    public function index(Request $req)
    {
        $method = $req->input('method');

        Log::info('[Payme] keldi', [
            'method'   => $method,
            'account'  => $req->input('params.account'),
            'amount'   => $req->input('params.amount'),
            'payme_id' => $req->input('params.id'),
        ]);

        switch ($method) {
            case "CheckPerformTransaction": return $this->checkPerformTransaction($req);
            case "CreateTransaction":       return $this->createTransaction($req);
            case "CheckTransaction":        return $this->checkTransaction($req);
            case "PerformTransaction":      return $this->performTransaction($req);
            case "CancelTransaction":       return $this->cancelTransaction($req);
            case "SetFiscalData":           return $this->setFiscalData($req);
            case "GetStatement":            return $this->getStatement($req);
            case "ChangePassword":          return $this->changePasswordError($req);
            default: return response()->json(['error' => 'Method not supported'], 400);
        }
    }

    // =========================================================================
    //  RESOLVER
    //  Baza: order_id ustunida saqlanadi, payment_type ajratib turadi
    //    order          → order_id = solds.id
    //    gift_certificate → order_id = gift_certificates.id
    //    mystery_box    → order_id = mystery_box_subscriptions.id
    // =========================================================================

    private function resolvePayable(string $rawId): ?array
    {
        if (str_starts_with($rawId, 'NDR-')) {
            $id   = (int) substr($rawId, 4);
            $cert = GiftCertificate::find($id);
            if (!$cert) return null;
            return ['type' => 'gift_certificate', 'model' => $cert, 'amount' => $cert->nominal_uzs];
        }

        if (str_starts_with($rawId, 'MQD-')) {
            $id  = (int) substr($rawId, 4);
            $sub = MysteryBoxSubscription::find($id);
            if (!$sub) return null;
            return ['type' => 'mystery_box', 'model' => $sub, 'amount' => $sub->price_uzs];
        }

        // "NRK-123" yoki "123" — buyurtma
        $id    = str_starts_with($rawId, 'NRK-') ? (int) substr($rawId, 4) : (int) $rawId;
        $order = Sold::find($id);
        if (!$order) return null;
        return ['type' => 'order', 'model' => $order, 'amount' => $order->amount];
    }

    // =========================================================================
    //  CHECK PERFORM TRANSACTION
    // =========================================================================

    private function checkPerformTransaction($req)
    {
        $account = $req->input('params.account');

        if (!$account) {
            return $this->err($req->input('id'), -32504, "Недостаточно привилегий для выполнения метода");
        }

        $rawId   = (string) ($account['order_id'] ?? '');
        $payable = $this->resolvePayable($rawId);

        if (!$payable) {
            return $this->err($req->input('id'), -31050, [
                "uz" => "Buyurtma topilmadi yoki to'lov vaqti tugagan",
                "ru" => "Заказ не найден или время оплаты истекло",
            ]);
        }

        // Status tekshiruvi
        $ok = match($payable['type']) {
            'order'            => ($payable['model']->payment_status_code ?? null) === PaymentStatusCode::CARD_PENDING->value
                || (int) $payable['model']->paymentStatus === PaymentStatusCode::CARD_PENDING->legacy(),
            'gift_certificate' => $payable['model']->status === 'pending_payment',
            'mystery_box'      => $payable['model']->status === 'pending_payment',
            default            => false,
        };

        if (!$ok) {
            return $this->err($req->input('id'), -31050, [
                "uz" => "To'lov holati mos emas",
                "ru" => "Статус оплаты не подходит",
            ]);
        }

        $ourAmount   = (int) $payable['amount'] * 100;
        $paymeAmount = (int) $req->input('params.amount');

        Log::info('[Payme] CheckPerform amount', [
            'our_uzs'    => $payable['amount'],
            'our_tiyin'  => $ourAmount,
            'payme_tiyin'=> $paymeAmount,
            'match'      => $ourAmount === $paymeAmount,
        ]);

        if ($ourAmount !== $paymeAmount) {
            return $this->err($req->input('id'), -31001, [
                "uz" => "Noto'g'ri summa", "ru" => "Неверная сумма",
            ]);
        }

        Log::info('[Payme] CheckPerform: allow=true', [
            'type'     => $payable['type'],
            'model_id' => $payable['model']->id,
            'amount'   => $payable['amount'],
        ]);

        return response()->json(['id' => $req->input('id'), 'result' => ['allow' => true]]);
    }

    // =========================================================================
    //  CREATE TRANSACTION
    // =========================================================================

    private function createTransaction($req)
    {
        $account = $req->input('params.account');

        if (!$account) {
            return $this->err($req->input('id'), -32504, "Bajarish usuli uchun imtiyozlar etarli emas.");
        }

        $rawId   = (string) ($account['order_id'] ?? '');
        $payable = $this->resolvePayable($rawId);

        if (!$payable) {
            Log::warning('[Payme] CreateTransaction: topilmadi', ['rawId' => $rawId]);
            return $this->err($req->input('id'), -31050, [
                "uz" => "Buyurtma topilmadi", "ru" => "Заказ не найден",
            ]);
        }

        if ((int)$payable['amount'] * 100 !== (int)$req->input('params.amount')) {
            Log::warning('[Payme] CreateTransaction: summa mos emas', [
                'our'   => (int)$payable['amount'] * 100,
                'payme' => (int)$req->input('params.amount'),
            ]);
            return $this->err($req->input('id'), -31001, ["uz" => "Noto'g'ri summa"]);
        }

        $modelId = $payable['model']->id;
        $type    = $payable['type'];

        // Mavjud transaction tekshiruvi
        $existing = Transaction::where('order_id', $modelId)
            ->where('payment_type', $type)
            ->where('state', 1)
            ->first();

        if (!$existing) {
            $tx                        = new Transaction();
            $tx->paycom_transaction_id = $req->input('params.id');
            $tx->paycom_time           = $req->input('params.time');
            $tx->paycom_time_datetime  = now();
            $tx->amount                = $req->input('params.amount');
            $tx->state                 = 1;
            $tx->order_id              = $modelId;
            $tx->payment_type          = $type;
            $tx->save();

            Log::info('[Payme] CreateTransaction: yaratildi', [
                'tx_id'   => $tx->id,
                'type'    => $type,
                'model_id'=> $modelId,
            ]);

            return response()->json(['id' => $req->input('id'), 'result' => [
                'create_time' => $req->input('params.time'),
                'transaction' => (string) $tx->id,
                'state'       => $tx->state,
            ]]);
        }

        // Idempotent — bir xil so'rov qaytadan keldi
        if ($existing->paycom_transaction_id == $req->input('params.id')) {
            return response()->json(['id' => $req->input('id'), 'result' => [
                'create_time' => $existing->paycom_time,
                'transaction' => (string) $existing->id,
                'state'       => $existing->state,
            ]]);
        }

        return $this->err($req->input('id'), -31099, [
            "uz" => "To'lov amalga oshirilmoqda", "ru" => "Оплата обрабатывается",
        ]);
    }

    // =========================================================================
    //  CHECK TRANSACTION
    // =========================================================================

    private function checkTransaction($req)
    {
        $tx = Transaction::where('paycom_transaction_id', $req->input('params.id'))->first();

        if (!$tx) {
            return $this->err($req->input('id'), -31003, "Транзакция не найдена.");
        }

        return response()->json(['id' => $req->input('id'), 'result' => [
            'create_time'  => (int) $tx->paycom_time,
            'perform_time' => (int) ($tx->perform_time_unix ?? 0),
            'cancel_time'  => $tx->cancel_time ? (int) $tx->cancel_time : 0,
            'transaction'  => (string) $tx->id,
            'state'        => $tx->state,
            'reason'       => $tx->reason,
        ]]);
    }

    // =========================================================================
    //  PERFORM TRANSACTION
    // =========================================================================

    private function performTransaction($req)
    {
        $tx = Transaction::where('paycom_transaction_id', $req->input('params.id'))->first();

        Log::info('[Payme] PerformTransaction', [
            'payme_id' => $req->input('params.id'),
            'tx'       => $tx?->id,
            'state'    => $tx?->state,
            'type'     => $tx?->payment_type,
            'order_id' => $tx?->order_id,
        ]);

        if (!$tx) {
            return $this->err($req->input('id'), -31003, "Транзакция не найдена.");
        }

        // Allaqachon bajarilgan — idempotent javob
        if ($tx->state === 2) {
            // Lekin model hali pending bo'lishi mumkin — service qayta ishlaydi (idempotent)
            $this->doPerform($tx);

            return response()->json(['result' => [
                'transaction'  => (string) $tx->id,
                'perform_time' => (int) $tx->perform_time_unix,
                'state'        => $tx->state,
            ]]);
        }

        if ($tx->state !== 1) {
            return $this->err($req->input('id'), -31008, "Неверное состояние транзакции.");
        }

        // state=1 → state=2
        $tx->state             = 2;
        $tx->perform_time      = now()->format('Y-m-d H:i:s');
        $tx->perform_time_unix = (string) intval(microtime(true) * 1000);
        $tx->save();

        // Service orqali model aktivlashtirish
        $this->doPerform($tx);

        return response()->json(['id' => $req->input('id'), 'result' => [
            'transaction'  => (string) $tx->id,
            'perform_time' => (int) $tx->perform_time_unix,
            'state'        => $tx->state,
        ]]);
    }

    private function doPerform(Transaction $tx): void
    {
        $type = $tx->payment_type ?? 'order';

        Log::info('[Payme] doPerform start', [
            'tx_id'    => $tx->id,
            'type'     => $type,
            'order_id' => $tx->order_id,
        ]);

        try {
            $this->doPerformInner($tx, $type);
        } catch (\Throwable $e) {
            Log::error('[Payme] doPerform XATO', [
                'tx_id'   => $tx->id,
                'type'    => $type,
                'error'   => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);
        }
    }

    private function doPerformInner(Transaction $tx, string $type): void
    {
        if ($type === 'gift_certificate') {
            (new \App\Services\GiftCertService())->activate((int) $tx->order_id);

        } elseif ($type === 'mystery_box') {
            (new \App\Services\MysteryBoxService())->activate((int) $tx->order_id);

        } else {
            // Buyurtma
            $order = Sold::find($tx->order_id);
            if (!$order) {
                Log::error('[Payme] doPerform: order topilmadi', ['order_id' => $tx->order_id]);
                return;
            }
            // Allaqachon to'langan — ikki marta ishlamamaslik uchun
            if ((int) $order->paymentStatus === 2) {
                return;
            }
            $user = User::find($order->user_id);
            if (!$user) return;

            $this->orderService->handleOrderPaid($order, $user);

            // Kirim yozuvi
            Kirim::create([
                'user_id'       => $order->user_id,
                'order_id'      => $order->id,
                'paymentStatus' => '2',
                'amount'        => $order->amount,
            ]);

            Log::info('[Payme] Order paid', ['order_id' => $order->id]);
        }
    }

    // =========================================================================
    //  CANCEL TRANSACTION
    // =========================================================================

    private function cancelTransaction($req)
    {
        $tx = Transaction::where('paycom_transaction_id', $req->input('params.id'))->first();

        Log::info('[Payme] CancelTransaction', [
            'payme_id' => $req->input('params.id'),
            'tx'       => $tx?->id,
            'state'    => $tx?->state,
            'reason'   => $req->input('params.reason'),
        ]);

        if (!$tx) {
            return $this->err($req->input('id'), -31003, "Транзакция не найдена.");
        }

        // Allaqachon bekor — idempotent
        if (in_array($tx->state, [-1, -2])) {
            return response()->json(['id' => $req->input('id'), 'result' => [
                'state'       => $tx->state,
                'cancel_time' => (int) $tx->cancel_time,
                'transaction' => (string) $tx->id,
            ]]);
        }

        $type = $tx->payment_type ?? 'order';

        if ($tx->state == 1) {
            $newState = -1;

        } elseif ($tx->state == 2) {
            // Gift cert va mystery box to'lovdan keyin BEKOR BO'LMAYDI
            if ($type !== 'order') {
                return $this->err($req->input('id'), -31008, [
                    "uz" => "Bu to'lov turini bekor qilib bo'lmaydi",
                    "ru" => "Этот тип оплаты нельзя отменить",
                ]);
            }
            // Buyurtma — 1 daqiqa oyna
            $elapsed = intval(microtime(true) * 1000) - (int) $tx->perform_time_unix;
            if ($elapsed > 60_000) {
                return $this->err($req->input('id'), -31008, [
                    "uz" => "Bekor qilish muddati o'tdi (1 daqiqa)",
                    "ru" => "Срок отмены истёк (1 минута)",
                ]);
            }
            $newState = -2;

        } else {
            return $this->err($req->input('id'), -31008, "Неверное состояние транзакции.");
        }

        $tx->update([
            'state'       => $newState,
            'reason'      => $req->input('params.reason'),
            'cancel_time' => intval(microtime(true) * 1000),
        ]);

        // Service orqali model bekor qilish
        if ($type === 'gift_certificate') {
            (new \App\Services\GiftCertService())->cancelPayment((int) $tx->order_id);
        } elseif ($type === 'mystery_box') {
            (new \App\Services\MysteryBoxService())->cancelPayment((int) $tx->order_id);
        } else {
            $order = Sold::find($tx->order_id);
            if ($order) {
                $this->orderService->cancelOrder($order, strict: false);
                Log::info('[Payme] Order cancelled', ['order_id' => $order->id]);
            }
        }

        return response()->json(['id' => $req->input('id'), 'result' => [
            'state'       => $tx->state,
            'cancel_time' => (int) $tx->cancel_time,
            'transaction' => (string) $tx->id,
        ]]);
    }

    // =========================================================================
    //  GET STATEMENT
    // =========================================================================

    private function getStatement($req)
    {
        $txs = Transaction::getTransactionsByTimeRange(
            $req->input('params.from'),
            $req->input('params.to')
        );
        return response()->json(['result' => [
            'transactions' => TransactionResource::collection($txs),
        ]]);
    }

    private function changePasswordError($req)
    {
        return $this->err($req->input('id'), -32504, "Недостаточно привилегий для выполнения метода");
    }

    private function setFiscalData(Request $req)
    {
        $params = $req->input('params');

        if (!is_array($params)) {
            return response()->json([
                'error' => [
                    'code' => -32602,
                    'message' => 'params is required',
                ],
            ]);
        }

        $paymeId = trim((string) ($params['id'] ?? ''));
        if ($paymeId === '') {
            return response()->json([
                'error' => [
                    'code' => -32602,
                    'message' => 'id is required',
                ],
            ]);
        }

        $type = strtoupper(trim((string) ($params['type'] ?? '')));
        if (!in_array($type, ['PERFORM', 'CANCEL'], true)) {
            return response()->json([
                'error' => [
                    'code' => -32602,
                    'message' => 'type must be PERFORM or CANCEL',
                ],
            ]);
        }

        $fiscalData = $params['fiscal_data'] ?? null;
        if (!is_array($fiscalData)) {
            return response()->json([
                'error' => [
                    'code' => -32602,
                    'message' => 'fiscal_data must be an object',
                ],
            ]);
        }

        $tx = Transaction::where('paycom_transaction_id', $paymeId)->first();
        if (!$tx) {
            return response()->json([
                'error' => [
                    'code' => -32001,
                    'message' => 'Чек с таким id не найден',
                ],
            ]);
        }

        $normalized = [
            'receipt_id' => isset($fiscalData['receipt_id']) ? (string) $fiscalData['receipt_id'] : null,
            'status_code' => isset($fiscalData['status_code']) ? (int) $fiscalData['status_code'] : null,
            'message' => isset($fiscalData['message']) ? (string) $fiscalData['message'] : null,
            'terminal_id' => isset($fiscalData['terminal_id']) ? (string) $fiscalData['terminal_id'] : null,
            'fiscal_sign' => isset($fiscalData['fiscal_sign']) ? (string) $fiscalData['fiscal_sign'] : null,
            'qr_code_url' => isset($fiscalData['qr_code_url']) ? (string) $fiscalData['qr_code_url'] : null,
            'date' => isset($fiscalData['date']) ? (string) $fiscalData['date'] : null,
        ];

        $field = $type === 'CANCEL' ? 'cancel_fiscal_data' : 'perform_fiscal_data';
        $tx->forceFill([$field => $normalized])->save();

        Log::info('[Payme] SetFiscalData accepted', [
            'payme_id' => $paymeId,
            'transaction_id' => $tx->id,
            'type' => $type,
            'status_code' => $normalized['status_code'],
            'receipt_id' => $normalized['receipt_id'],
        ]);

        return response()->json([
            'result' => [
                'success' => true,
            ],
        ]);
    }

    private function err($id, int $code, $message)
    {
        return response()->json(['id' => $id, 'error' => ['code' => $code, 'message' => $message]]);
    }
}
