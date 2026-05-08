<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Enums\OrderStatusCode;
use App\Enums\PaymentStatusCode;
use App\Models\Sold;
use App\Models\User;
use App\Services\OrderService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request as HttpRequest;

class CancelUnpaidOrders extends Command
{
    protected $signature   = 'orders:cancel-unpaid';
    protected $description = "To'lanmagan (paymentStatus=1) buyurtmalarni 1 soatdan keyin avtomatik bekor qilish + push xabar";

    private const PUSH_MESSAGES = [
        'uz' => [
            'title' => "Buyurtma #{id} bekor qilindi ❌",
            'body'  => "#{id}-raqamli buyurtmangiz to'lov amalga oshirilmagani sababli avtomatik bekor qilindi. Savolingiz bo'lsa, biz bilan bog'laning.",
        ],
        'ru' => [
            'title' => "Заказ #{id} отменён ❌",
            'body'  => "Ваш заказ #{id} был автоматически отменён в связи с неоплатой. Если у вас есть вопросы — свяжитесь с нами.",
        ],
        'en' => [
            'title' => "Order #{id} cancelled ❌",
            'body'  => "Your order #{id} has been automatically cancelled due to non-payment. If you have any questions, please contact us.",
        ],
        'ja' => [
            'title' => "注文 #{id} がキャンセルされました ❌",
            'body'  => "注文 #{id} は未払いのため自動的にキャンセルされました。ご不明な点がございましたら、お問い合わせください。",
        ],
    ];

    public function __construct(private readonly OrderService $orderService)
    {
        parent::__construct();
    }

    // =========================================================================
    //  HANDLE
    // =========================================================================

    public function handle(): void
    {
        $this->info(now()->format('d.m.Y H:i:s') . " — To'lanmagan buyurtmalarni bekor qilish boshlandi...");

        $unpaidOrders = Sold::where(function ($query) {
                $query->where('payment_status_code', PaymentStatusCode::CARD_PENDING->value)
                    ->orWhere(function ($fallback) {
                        $fallback->whereNull('payment_status_code')
                            ->where('paymentStatus', PaymentStatusCode::CARD_PENDING->legacy());
                    });
            })
            ->where(function ($query) {
                $query->where('status_code', OrderStatusCode::PENDING->value)
                    ->orWhere(function ($fallback) {
                        $fallback->whereNull('status_code')
                            ->where('status', OrderStatusCode::PENDING->legacy());
                    });
            })
            ->where('created_at', '<', Carbon::now()->subHour())
            ->get();

        if ($unpaidOrders->isEmpty()) {
            $this->info('Bekor qilinadigan buyurtma topilmadi.');
            return;
        }

        $this->info("Topilgan buyurtmalar soni: {$unpaidOrders->count()}");

        foreach ($unpaidOrders as $order) {
            try {
                // OrderService::cancelOrder — stock, cashback, gift cert,
                // promokod, seller/courier order — hammasi idempotent
                $result = $this->orderService->cancelOrder($order, strict: false);

                if ($result['ok']) {
                    $this->info("Buyurtma #{$order->id} bekor qilindi.");
                    $this->sendCancelPush($order);
                } else {
                    $this->warn("Buyurtma #{$order->id} — {$result['message']}");
                }

            } catch (\Throwable $e) {
                Log::error("CancelUnpaid xatosi (order #{$order->id})", [
                    'message' => $e->getMessage(),
                    'trace'   => $e->getTraceAsString(),
                ]);
                $this->error("Buyurtma #{$order->id} — xatolik: {$e->getMessage()}");
            }
        }

        $this->info('Jarayon tugadi.');
    }

    // =========================================================================
    //  PUSH XABAR — o'zgarishsiz
    // =========================================================================

    private function sendCancelPush(Sold $order): void
    {
        try {
            $user = User::find($order->user_id);
            if (!$user) {
                Log::warning("CancelUnpaid push: user #{$order->user_id} topilmadi.");
                return;
            }

            $lang = in_array($user->locale ?? 'uz', ['uz', 'ru', 'en', 'ja'])
                ? ($user->locale ?? 'uz')
                : 'uz';

            $msgs  = self::PUSH_MESSAGES[$lang];
            $id    = $order->id;
            $title = str_replace('{id}', $id, $msgs['title']);
            $body  = str_replace('{id}', $id, $msgs['body']);

            $tokens = DB::table('connected_devices')
                ->where('user_type', 'user')
                ->where('user_id', $user->id)
                ->whereNotNull('fcm_token')
                ->pluck('fcm_token')
                ->unique()
                ->values()
                ->toArray();

            if (empty($tokens)) {
                Log::info("CancelUnpaid push: user #{$user->id} uchun FCM token topilmadi.");
                return;
            }

            $request = new HttpRequest();
            $request->replace([
                'app_key' => 'kitobchi',
                'title'   => $title,
                'body'    => $body,
                'tokens'  => $tokens,
                'data'    => [
                    'type'     => 'order_cancelled',
                    'order_id' => (string) $id,
                ],
            ]);

            $response = app(\App\Http\Controllers\PushController::class)->sendPush($request);

            Log::info("CancelUnpaid push sent: order #{$id}, user #{$user->id}, lang={$lang}");

        } catch (\Throwable $e) {
            Log::error("CancelUnpaid push xatosi (order #{$order->id})", [
                'message' => $e->getMessage(),
            ]);
        }
    }
}
