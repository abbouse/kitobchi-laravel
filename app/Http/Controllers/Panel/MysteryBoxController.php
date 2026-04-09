<?
namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\MysteryBoxPlan;
use App\Models\MysteryBoxSubscription;
use App\Models\MysteryBoxDelivery;
use App\Models\Books;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MysteryBoxController extends Controller
{
    // ── Tariflar ───────────────────────────────────────────
    public function plans()
    {
        $plans = MysteryBoxPlan::withCount('subscriptions')->orderBy('sort_order')->get();
        return view('panel.mystery-box.plans', compact('plans'));
    }

    public function planStore(Request $request)
    {
        $data = $request->validate([
            'name_uz'         => 'required|string|max:100',
            'name_ru'         => 'nullable|string|max:100',
            'months'          => 'required|integer|in:1,3,6,12',
            'price_uzs'       => 'required|integer|min:1000',
            'books_per_month' => 'required|integer|min:1|max:10',
            'description_uz'  => 'nullable|string',
            'sort_order'      => 'integer|min:0',
        ]);
        MysteryBoxPlan::create($data + ['is_active' => true]);
        return back()->with('success', 'Tarif yaratildi.');
    }

    public function planUpdate(Request $request, MysteryBoxPlan $plan)
    {
        $data = $request->validate([
            'name_uz'         => 'required|string|max:100',
            'name_ru'         => 'nullable|string|max:100',
            'price_uzs'       => 'required|integer|min:1000',
            'books_per_month' => 'required|integer|min:1|max:10',
            'description_uz'  => 'nullable|string',
            'sort_order'      => 'integer|min:0',
            'is_active'       => 'boolean',
        ]);
        $data['is_active'] = $request->boolean('is_active');
        $plan->update($data);
        return back()->with('success', 'Tarif yangilandi.');
    }

    public function planDestroy(MysteryBoxPlan $plan)
    {
        if ($plan->subscriptions()->where('status','active')->exists()) {
            return back()->with('error', 'Faol obunalar mavjud.');
        }
        $plan->delete();
        return back()->with('success', 'Tarif o\'chirildi.');
    }

    // ── Obunalar ro'yxati ──────────────────────────────────
    public function subscriptions(Request $request)
    {
        $q = MysteryBoxSubscription::with([
            'user:id,name,lastname,phone_number',
            'plan:id,name_uz,months',
        ]);

        $tab = $request->get('tab', 'active');
        if ($tab !== 'all') $q->where('status', $tab);

        if ($s = $request->search) {
            $q->whereHas('user', fn($u) => $u
                ->where('name',          'like', "%$s%")
                ->orWhere('phone_number','like', "%$s%")
            );
        }

        $subs = $q->orderBy('next_delivery_at')->paginate(25)->withQueryString();

        $counts = [
            'all'       => MysteryBoxSubscription::count(),
            'active'    => MysteryBoxSubscription::where('status','active')->count(),
            'paused'    => MysteryBoxSubscription::where('status','paused')->count(),
            'pending_payment' => MysteryBoxSubscription::where('status','pending_payment')->count(),
            'completed' => MysteryBoxSubscription::where('status','completed')->count(),
        ];

        // Dashboard uchun: bugun/bu hafta navbatga kelganlar
        $dueToday = MysteryBoxSubscription::where('status','active')
            ->whereDate('next_delivery_at', today())->count();
        $dueWeek  = MysteryBoxSubscription::where('status','active')
            ->whereBetween('next_delivery_at', [today(), today()->addDays(7)])->count();

        return view('panel.mystery-box.subscriptions',
            compact('subs','counts','tab','dueToday','dueWeek'));
    }

    // ── Obuna detail ───────────────────────────────────────
    public function showSubscription(MysteryBoxSubscription $subscription)
    {
        $subscription->load(['user','plan','deliveries']);
        return view('panel.mystery-box.subscription-show',
            compact('subscription'));
    }

    // ── Oylik yetkazishni tayyorlash (kitob tanlash) ───────
    public function prepareDelivery(Request $request, MysteryBoxDelivery $delivery)
    {
        $request->validate([
            'book_ids'      => 'required|array|min:1',
            'book_ids.*'    => 'integer|exists:books,id',
            'tracking_note' => 'nullable|string|max:500',
        ]);

        $delivery->update([
            'book_ids'    => $request->book_ids,
            'status'      => MysteryBoxDelivery::STATUS_PREPARING,
            'tracking_note' => $request->tracking_note,
            'prepared_at' => now(),
        ]);

        return back()->with('success', 'Kitoblar tanlandi. Jo\'natishga tayyor.');
    }

    // ── Jo'natildi ─────────────────────────────────────────
    public function shipDelivery(Request $request, MysteryBoxDelivery $delivery)
    {
        $request->validate(['tracking_note' => 'nullable|string|max:500']);

        $delivery->update([
            'status'       => MysteryBoxDelivery::STATUS_SHIPPED,
            'tracking_note'=> $request->tracking_note ?? $delivery->tracking_note,
            'shipped_at'   => now(),
        ]);

        return back()->with('success', 'Jo\'natildi deb belgilandi.');
    }

    // ── Yetkazildi ─────────────────────────────────────────
    public function deliverDelivery(MysteryBoxDelivery $delivery)
    {
        $sub = $delivery->subscription;

        DB::transaction(function () use ($delivery, $sub) {
            $delivery->update([
                'status'       => MysteryBoxDelivery::STATUS_DELIVERED,
                'delivered_at' => now(),
            ]);

            $sub->increment('delivered_months');
            $sub->refresh();

            if ($sub->delivered_months >= $sub->total_months) {
                // Barcha oylar tugadi
                $sub->update([
                    'status'           => MysteryBoxSubscription::STATUS_COMPLETED,
                    'next_delivery_at' => null,
                ]);
            } else {
                // Keyingi oy
                $sub->update([
                    'next_delivery_at' => now()->addMonth(),
                ]);
                // Keyingi delivery record
                $sub->createNextDelivery();
            }
        });

        return back()->with('success', 'Yetkazildi. Keyingi oy navbatga qo\'yildi.');
    }

    // ── Pause / Resume ────────────────────────────────────
    public function pauseSubscription(MysteryBoxSubscription $subscription)
    {
        $subscription->update(['status' => MysteryBoxSubscription::STATUS_PAUSED]);
        return back()->with('success', 'Obuna to\'xtatildi.');
    }

    public function resumeSubscription(MysteryBoxSubscription $subscription)
    {
        $subscription->update(['status' => MysteryBoxSubscription::STATUS_ACTIVE]);
        return back()->with('success', 'Obuna davom ettirildi.');
    }

    // ── Cancel ────────────────────────────────────────────
    public function cancelSubscription(MysteryBoxSubscription $subscription)
    {
        if ($subscription->status === 'completed') {
            return back()->with('error', 'Yakunlangan obuna bekor qilinmaydi.');
        }
        $subscription->update([
            'status'       => MysteryBoxSubscription::STATUS_CANCELLED,
            'cancelled_at' => now(),
        ]);
        return back()->with('success', 'Obuna bekor qilindi.');
    }

    // ── Dashboard widget: navbatdagilar ────────────────────
    public function dashboardWidget()
    {
        $due = MysteryBoxSubscription::with('user:id,name,lastname,phone_number')
            ->where('status', MysteryBoxSubscription::STATUS_ACTIVE)
            ->whereNotNull('next_delivery_at')
            ->orderBy('next_delivery_at')
            ->take(10)
            ->get();

        return response()->json(['ok' => true, 'data' => $due]);
    }
}