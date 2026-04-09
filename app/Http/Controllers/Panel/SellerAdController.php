<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\SellerAd;
use App\Models\SellerAdSettings;
use Illuminate\Http\Request;

class SellerAdController extends Controller
{
    private const TYPES = ['top_banner', 'middle_banner', 'story'];

    public function index(Request $request)
    {
        $q = SellerAd::with(['seller']);

        if ($s = $request->search) {
            $q->where(fn($x) => $x
                ->where('id', $s)
                ->orWhere('seller_id', $s)
            );
        }

        $tab = $request->get('tab', 'pending');
        match ($tab) {
            'pending'  => $q->where('moderation', 'pending'),
            'approved' => $q->where('moderation', 'approved'),
            'rejected' => $q->where('moderation', 'rejected'),
            'active'   => $q->where('moderation', 'approved')->where('expire_at', '>', now()),
            'expired'  => $q->where('expire_at', '<=', now()),
            default    => null,
        };

        if ($request->filled('type')) {
            $q->where('type', $request->type);
        }

        $ads    = $q->latest()->paginate(20)->withQueryString();
        $counts = [
            'pending'  => SellerAd::where('moderation', 'pending')->count(),
            'approved' => SellerAd::where('moderation', 'approved')->count(),
            'rejected' => SellerAd::where('moderation', 'rejected')->count(),
            'active'   => SellerAd::where('moderation', 'approved')->where('expire_at', '>', now())->count(),
            'expired'  => SellerAd::where('expire_at', '<=', now())->count(),
        ];

        $types = self::TYPES;

        return view('panel.seller-ads.index', compact('ads', 'counts', 'tab', 'types'));
    }

    public function show(SellerAd $sellerAd)
    {
        $sellerAd->load(['seller']);
        return view('panel.seller-ads.show', compact('sellerAd'));
    }

    public function moderate(Request $request, SellerAd $sellerAd)
    {
        $request->validate([
            'action' => 'required|in:approve,reject',
        ]);

        $sellerAd->update(['moderation' => $request->action === 'approve' ? 'approved' : 'rejected']);
        $label = $request->action === 'approve' ? 'tasdiqlandi' : 'rad etildi';

        return back()->with('success', "Reklama $label.");
    }

    public function destroy(SellerAd $sellerAd)
    {
        $sellerAd->delete();
        return redirect()->route('panel.seller-ads.index')->with('success', "Reklama o'chirildi.");
    }

    // ── Reklama narx sozlamalari ──────────────────────────────────
    public function settings()
    {
        $settings = SellerAdSettings::all();
        $types    = self::TYPES;
        return view('panel.seller-ads.settings', compact('settings', 'types'));
    }

    public function updateSettings(Request $request)
    {
        $request->validate([
            'settings'         => 'required|array',
            'settings.*.type'  => 'required|string',
            'settings.*.price' => 'required|integer|min:0',
        ]);

        foreach ($request->settings as $item) {
            SellerAdSettings::where('type', $item['type'])->update([
                'price'  => $item['price'],
                'region' => $item['region'] ?? null,
            ]);
        }

        return back()->with('success', 'Reklama narxlari yangilandi.');
    }
}