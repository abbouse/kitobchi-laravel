<?php

namespace App\Http\Controllers\A122;

use App\Http\Controllers\Controller;
use App\Models\SellerAd;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;

class SellerAdController extends Controller
{
    private const TYPES = ['top_banner', 'middle_banner', 'story'];

    public function index(Request $request)
    {
        $q = SellerAd::with(['seller:id,shop_name']);

        $tab = $request->get('tab', 'pending');
        match ($tab) {
            'pending'  => $q->where('moderation', 'pending'),
            'approved' => $q->where('moderation', 'approved'),
            'rejected' => $q->where('moderation', 'rejected'),
            'active'   => $q->where('moderation', 'approved')->where('expire_at', '>', now()),
            'expired'  => $q->where('expire_at', '<=', now()),
            default    => null,
        };

        if ($s = $request->search) {
            $q->where(fn($x) => $x->where('id', $s)->orWhere('seller_id', $s));
        }

        if ($request->filled('type')) $q->where('type', $request->type);

        $ads    = $q->latest()->paginate(20)->withQueryString();
        $counts = [
            'pending'  => SellerAd::where('moderation', 'pending')->count(),
            'approved' => SellerAd::where('moderation', 'approved')->count(),
            'rejected' => SellerAd::where('moderation', 'rejected')->count(),
            'active'   => SellerAd::where('moderation', 'approved')->where('expire_at', '>', now())->count(),
            'expired'  => SellerAd::where('expire_at', '<=', now())->count(),
        ];

        $types = self::TYPES;

        return view('a122.ads.index', compact('ads', 'counts', 'tab', 'types'));
    }

    public function show(SellerAd $ad)
    {
        $ad->load('seller');

        $product = null;
        if ($ad->product_type && $ad->product_id) {
            $class = Relation::getMorphedModel($ad->product_type) ?? $ad->product_type;
            if (class_exists($class)) {
                $product = $class::query()->find($ad->product_id);
            }
        }

        $timeline = collect([
            ['label' => 'Yaratilgan', 'value' => optional($ad->created_at)?->format('d.m.Y H:i')],
            ['label' => 'Moderatsiya', 'value' => $ad->moderation],
            ['label' => 'To\'lov', 'value' => $ad->paymentStatus],
            ['label' => 'Tugash sanasi', 'value' => optional($ad->expire_at)?->format('d.m.Y H:i') ?: '—'],
        ]);

        return view('a122.ads.show', compact('ad', 'product', 'timeline'));
    }

    public function moderate(Request $request, SellerAd $ad)
    {
        $validated = $request->validate([
            'action' => 'required|in:approve,reject',
        ]);

        $moderation = $validated['action'] === 'approve' ? 'approved' : 'rejected';
        $ad->update(['moderation' => $moderation]);

        return back()->with('success', $moderation === 'approved' ? 'Reklama tasdiqlandi.' : 'Reklama rad etildi.');
    }

    public function destroy(SellerAd $ad)
    {
        $ad->delete();
        return redirect()->route('admin.ads.index')->with('success', "Reklama o'chirildi.");
    }
}
