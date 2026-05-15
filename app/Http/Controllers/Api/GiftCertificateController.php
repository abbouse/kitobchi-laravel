<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GiftCertificate;
use App\Models\Sold;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GiftCertificateController extends Controller
{
    private function err(string $msg, int $code = 400)
    {
        return response()->json(['status' => 'error', 'message' => $msg], $code);
    }

    // =========================================================================
    //  GET /api/gift-certificates
    //  User ko'radigan sertifikatlar:
    //    - recipient_user_id = user.id  (o'zi uchun yoki birovdan sovg'a olgan)
    //    - buyer_user_id = user.id AND status = pending_payment
    //      (to'lov tugallanmagan, qayta to'lash uchun)
    // =========================================================================

    public function index(Request $request)
    {
        $user = Auth::guard('user')->user();
        if (!$user) return $this->err('Unauthorized', 401);

        $certs = GiftCertificate::query()
            ->where(function ($query) use ($user) {
                $query
                    ->where(function ($mine) use ($user) {
                        $mine->where('recipient_user_id', $user->id)
                            ->whereNotIn('status', [
                                'payment_cancelled',
                                'cancelled',
                            ]);
                    })
                    ->orWhere(function ($pending) use ($user) {
                        $pending->where('buyer_user_id', $user->id)
                            ->where('status', 'pending_payment');
                    });
            })
            ->orderByRaw("
                CASE status
                    WHEN 'pending_payment' THEN 1
                    WHEN 'payment_cancelled' THEN 2
                    WHEN 'active' THEN 3
                    WHEN 'paid'   THEN 4
                    WHEN 'used'   THEN 5
                    ELSE 6
                END
            ")
            ->orderBy('created_at', 'desc')
            ->with(['buyer:id,name,lastname', 'recipient:id,name,lastname'])
            ->get();

        $data = $certs->map(function (GiftCertificate $cert) use ($user) {

            // Bu sertifikat ishlatilgan buyurtmalar tarixi
            $history = Sold::where('gift_certificate_id', $cert->id)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(fn($order) => [
                    'order_id'      => $order->id,
                    'used_amount'   => (int)($order->giftCertAmount ?? 0),
                    'order_total'   => (int)($order->amount ?? 0),
                    'order_status'  => $order->status,
                    'used_at'       => $order->created_at?->format('d.m.Y H:i'),
                    'items_preview' => collect($order->items ?? [])
                        ->where('type', '!=', 'gift')
                        ->take(2)
                        ->map(fn($i) => $i['name'] ?? '')
                        ->filter()
                        ->values(),
                ])
                ->toArray();

            $totalUsed = collect($history)->sum('used_amount');

            return [
                'id'              => $cert->id,
                'code'            => $cert->code,
                'status'          => $cert->status,
                'status_label'    => $cert->status_label,
                'is_expired'      => (bool) $cert->is_expired,
                'is_gifted'       => $cert->buyer_user_id !== $user->id, // birovdan sovg'a kelgan
                'is_gifted_by_me' => $cert->buyer_user_id === $user->id
                    && $cert->recipient_user_id
                    && $cert->recipient_user_id !== $user->id,

                // Nominal
                'nominal_uzs'     => (int) $cert->nominal_uzs,
                'original_nominal'=> (int) $cert->nominal_uzs + $totalUsed,

                // Sanalar
                'paid_at'         => $cert->paid_at?->format('d.m.Y'),
                'activated_at'    => $cert->activated_at?->format('d.m.Y'),
                'expires_at'      => $cert->expires_at?->format('d.m.Y'),
                'used_at'         => $cert->used_at?->format('d.m.Y H:i'),
                'gift_message'    => $cert->message ?: null,
                'buyer_name'      => trim(($cert->buyer?->name ?? '') . ' ' . ($cert->buyer?->lastname ?? '')) ?: null,
                'recipient_name'  => trim(($cert->recipient?->name ?? '') . ' ' . ($cert->recipient?->lastname ?? '')) ?: null,

                // Tarix
                'history'         => $history,
                'times_used'      => count($history),
            ];
        });

        $active = $certs->where('status', GiftCertificate::STATUS_ACTIVE);

        return response()->json([
            'status' => 'success',
            'data'   => $data,
            'meta'   => [
                'total'        => $certs->count(),
                'active_count' => $active->count(),
                'active_total' => (int) $active->where('nominal_uzs', '>', 0)->sum('nominal_uzs'),
            ],
        ]);
    }
}
