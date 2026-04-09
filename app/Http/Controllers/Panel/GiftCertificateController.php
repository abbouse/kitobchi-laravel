<?php
// app/Http/Controllers/Panel/GiftCertificateController.php

namespace App\Http\Controllers\Panel;

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
        if ($tab !== 'all') $q->where('status', $tab);

        if ($s = $request->search) {
            $q->where(fn($sq) => $sq
                ->where('code', 'like', "%$s%")
                ->orWhere('recipient_phone', 'like', "%$s%")
                ->orWhere('recipient_name',  'like', "%$s%")
                ->orWhereHas('buyer', fn($u) => $u
                    ->where('name',         'like', "%$s%")
                    ->orWhere('phone_number','like', "%$s%")
                )
            );
        }

        $certs = $q->latest()->paginate(25)->withQueryString();

        $counts = [
            'all'             => GiftCertificate::count(),
            'pending_payment' => GiftCertificate::where('status','pending_payment')->count(),
            'sent'            => GiftCertificate::where('status','sent')->count(),
            'used'            => GiftCertificate::where('status','used')->count(),
            'cancelled'       => GiftCertificate::where('status','cancelled')->count(),
        ];

        $totalPaid = GiftCertificate::where('status','used')->sum('nominal_uzs');

        return view('panel.gift-certificates.index',
            compact('certs','counts','tab','totalPaid'));
    }

    public function show(GiftCertificate $giftCertificate)
    {
        $giftCertificate->load(['buyer','recipient']);
        return view('panel.gift-certificates.show',
            compact('giftCertificate'));
    }

    public function updateStatus(Request $request, GiftCertificate $cert)
    {
        $request->validate(['status'=>'required|in:pending_payment,paid,sent,used,cancelled']);
        $new = $request->status;
        $cert->update([
            'status'  => $new,
            'paid_at' => ($new==='paid' && !$cert->paid_at) ? now() : $cert->paid_at,
            'sent_at' => ($new==='sent' && !$cert->sent_at) ? now() : $cert->sent_at,
            'used_at' => ($new==='used' && !$cert->used_at) ? now() : $cert->used_at,
        ]);
        return back()->with('success', 'Holat yangilandi.');
    }

    public function cancel(GiftCertificate $cert)
    {
        if (in_array($cert->status, ['used','cancelled'])) {
            return back()->with('error', 'Bu sertifikatni bekor qilib bo\'lmaydi.');
        }
        $cert->update(['status' => 'cancelled']);
        return back()->with('success', 'Bekor qilindi.');
    }
}