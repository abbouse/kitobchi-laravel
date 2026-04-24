<?php

namespace App\Http\Controllers\A122;

use App\Http\Controllers\Controller;
use App\Models\GiftCertificate;
use Illuminate\Http\Request;

class GiftCertificateController extends Controller
{
    public function index(Request $request)
    {
        $q = GiftCertificate::with([
            'buyer:id,name,lastname,phone_number',
            'recipient:id,name,lastname,phone_number',
        ]);

        $tab = $request->get('tab', 'all');
        if ($tab === 'sent') {
            $tab = GiftCertificate::STATUS_ACTIVE;
        }
        if ($tab !== 'all') $q->where('status', $tab);

        if ($s = $request->search) {
            $q->where(fn($x) => $x
                ->where('code', 'like', "%$s%")
                ->orWhere('recipient_phone', 'like', "%$s%")
                ->orWhere('recipient_name', 'like', "%$s%")
            );
        }

        $certs = $q->latest()->paginate(25)->withQueryString();

        $counts = [
            'all'             => GiftCertificate::count(),
            'pending_payment' => GiftCertificate::where('status', 'pending_payment')->count(),
            'active'          => GiftCertificate::where('status', GiftCertificate::STATUS_ACTIVE)->count(),
            'used'            => GiftCertificate::where('status', 'used')->count(),
            'cancelled'       => GiftCertificate::where('status', 'cancelled')->count(),
        ];

        return view('a122.gift-certificates.index', compact('certs', 'counts', 'tab'));
    }

    public function show(GiftCertificate $giftCertificate)
    {
        $giftCertificate->load(['buyer', 'recipient']);
        return view('a122.gift-certificates.show', compact('giftCertificate'));
    }

    public function updateStatus(Request $request, GiftCertificate $giftCertificate)
    {
        $request->validate(['status' => 'required|in:pending_payment,paid,active,sent,used,cancelled']);
        $new = $request->status === 'sent'
            ? GiftCertificate::STATUS_ACTIVE
            : $request->status;
        $giftCertificate->update([
            'status'  => $new,
            'paid_at' => ($new === 'paid' && !$giftCertificate->paid_at) ? now() : $giftCertificate->paid_at,
            'sent_at' => ($new === GiftCertificate::STATUS_ACTIVE && !$giftCertificate->sent_at) ? now() : $giftCertificate->sent_at,
            'used_at' => ($new === 'used' && !$giftCertificate->used_at) ? now() : $giftCertificate->used_at,
        ]);
        return back()->with('success', 'Holat yangilandi.');
    }

    public function cancel(GiftCertificate $giftCertificate)
    {
        if (in_array($giftCertificate->status, ['used', 'cancelled'])) {
            return back()->with('error', "Bu sertifikatni bekor qilib bo'lmaydi.");
        }
        $giftCertificate->update(['status' => 'cancelled']);
        return back()->with('success', 'Bekor qilindi.');
    }
}
