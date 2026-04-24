<?php

namespace App\Http\Controllers\A122;

use App\Http\Controllers\Controller;
use App\Models\Books;
use App\Models\MysteryBoxDelivery;
use App\Models\MysteryBoxPlan;
use App\Models\MysteryBoxSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MysteryBoxController extends Controller
{
    public function searchBooks(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $ids = collect(explode(',', (string) $request->get('ids', '')))
            ->map(fn ($id) => (int) trim($id))
            ->filter(fn ($id) => $id > 0)
            ->values()
            ->all();

        if ($q === '' && empty($ids)) {
            return response()->json(['status' => 'success', 'data' => []]);
        }

        $booksQuery = Books::query()
            ->where('is_hidden', 0)
            ->where('is_approved', 1)
            ->when(!empty($ids), fn ($query) => $query->whereIn('id', $ids))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('name', 'like', "%{$q}%")
                        ->orWhere('author', 'like', "%{$q}%");

                    if (ctype_digit($q)) {
                        $inner->orWhere('id', (int) $q);
                    }
                });
            });

        $books = $booksQuery
            ->orderByDesc('totalSales')
            ->limit(!empty($ids) ? count($ids) : 12)
            ->get(['id', 'name', 'author', 'count', 'images']);

        return response()->json([
            'status' => 'success',
            'data' => $books->map(function (Books $book) {
                $images = is_array($book->images) ? $book->images : json_decode($book->images ?? '[]', true);

                return [
                    'id' => $book->id,
                    'name' => $book->name,
                    'author' => $book->author,
                    'stock' => (int) ($book->count ?? 0),
                    'image' => $images[0] ?? null,
                ];
            })->values(),
        ]);
    }

    public function index(Request $request)
    {
        $q = MysteryBoxSubscription::with([
            'user:id,name,lastname,phone_number',
            'plan:id,name_uz,months',
        ]);

        $tab = $request->get('tab', 'active');
        if ($tab !== 'all') $q->where('status', $tab);

        if ($s = $request->search) {
            $q->whereHas('user', fn($u) => $u
                ->where('name', 'like', "%$s%")
                ->orWhere('phone_number', 'like', "%$s%")
            );
        }

        $subs = $q->orderBy('next_delivery_at')->paginate(25)->withQueryString();

        $counts = [
            'all'             => MysteryBoxSubscription::count(),
            'active'          => MysteryBoxSubscription::where('status', 'active')->count(),
            'paused'          => MysteryBoxSubscription::where('status', 'paused')->count(),
            'pending_payment' => MysteryBoxSubscription::where('status', 'pending_payment')->count(),
            'completed'       => MysteryBoxSubscription::where('status', 'completed')->count(),
        ];

        $plans = MysteryBoxPlan::orderBy('sort_order')->get();
        $dueToday = MysteryBoxSubscription::where('status', 'active')
            ->whereDate('next_delivery_at', today())
            ->count();
        $dueWeek = MysteryBoxSubscription::where('status', 'active')
            ->whereBetween('next_delivery_at', [today(), today()->addDays(7)])
            ->count();

        return view('a122.mystery-box.index', compact('subs', 'counts', 'tab', 'plans', 'dueToday', 'dueWeek'));
    }

    public function subscriptions(Request $request)
    {
        return $this->index($request);
    }

    public function show(MysteryBoxSubscription $subscription)
    {
        $subscription->load(['user', 'plan', 'deliveries']);
        return view('a122.mystery-box.show', compact('subscription'));
    }

    public function subscription(MysteryBoxSubscription $subscription)
    {
        return $this->show($subscription);
    }

    public function plans()
    {
        $plans = MysteryBoxPlan::withCount('subscriptions')->orderBy('sort_order')->get();

        return view('a122.mystery-box.plans', compact('plans'));
    }

    public function storePlan(Request $request)
    {
        $data = $request->validate([
            'name_uz'         => 'required|string|max:255',
            'name_ru'         => 'nullable|string|max:255',
            'months'          => 'required|integer|in:1,3,6,12',
            'price_uzs'       => 'required|integer|min:1000',
            'books_per_month' => 'required|integer|min:1|max:10',
            'sort_order'      => 'nullable|integer|min:0',
            'description_uz'  => 'nullable|string',
            'description_ru'  => 'nullable|string',
        ]);

        MysteryBoxPlan::create([
            ...$data,
            'is_active' => true,
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        return redirect()->route('admin.mystery-box.plans')->with('success', 'Tarif yaratildi.');
    }

    public function updatePlan(Request $request, MysteryBoxPlan $plan)
    {
        $data = $request->validate([
            'name_uz'         => 'required|string|max:255',
            'name_ru'         => 'nullable|string|max:255',
            'price_uzs'       => 'required|integer|min:1000',
            'books_per_month' => 'required|integer|min:1|max:10',
            'sort_order'      => 'nullable|integer|min:0',
            'description_uz'  => 'nullable|string',
            'description_ru'  => 'nullable|string',
            'is_active'       => 'nullable|boolean',
        ]);

        $plan->update([
            ...$data,
            'is_active' => $request->boolean('is_active'),
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        return back()->with('success', 'Tarif yangilandi.');
    }

    public function destroyPlan(MysteryBoxPlan $plan)
    {
        if ($plan->subscriptions()->exists()) {
            return back()->with('error', 'Obunalari bor tarifni o‘chirib bo‘lmaydi.');
        }

        $plan->delete();

        return back()->with('success', "Tarif o'chirildi.");
    }

    public function pauseSubscription(MysteryBoxSubscription $subscription)
    {
        if ($subscription->status !== MysteryBoxSubscription::STATUS_ACTIVE) {
            return back()->with('error', "Faqat faol obunani to'xtatish mumkin.");
        }
        $subscription->update(['status' => 'paused']);
        return back()->with('success', "Obuna to'xtatildi.");
    }

    public function resumeSubscription(MysteryBoxSubscription $subscription)
    {
        if ($subscription->status !== MysteryBoxSubscription::STATUS_PAUSED) {
            return back()->with('error', "Faqat to'xtatilgan obunani davom ettirish mumkin.");
        }
        $subscription->update(['status' => 'active']);
        return back()->with('success', 'Obuna davom ettirildi.');
    }

    public function cancelSubscription(MysteryBoxSubscription $subscription)
    {
        if (in_array($subscription->status, [MysteryBoxSubscription::STATUS_COMPLETED, MysteryBoxSubscription::STATUS_CANCELLED], true)) {
            return back()->with('error', 'Yakunlangan obuna bekor qilinmaydi.');
        }
        $subscription->update(['status' => 'cancelled', 'cancelled_at' => now()]);
        return back()->with('success', 'Obuna bekor qilindi.');
    }

    public function prepareDelivery(Request $request, MysteryBoxDelivery $delivery)
    {
        $request->validate([
            'book_ids_raw'   => 'required|string',
            'tracking_note'  => 'nullable|string|max:500',
        ]);

        $bookIds = collect(explode(',', $request->input('book_ids_raw', '')))
            ->map(fn ($id) => (int) trim($id))
            ->filter(fn ($id) => $id > 0)
            ->values()
            ->all();

        if (empty($bookIds)) {
            return back()->with('error', 'Kamida bitta kitob ID kiriting.');
        }

        if (!in_array($delivery->status, [MysteryBoxDelivery::STATUS_PENDING, MysteryBoxDelivery::STATUS_PREPARING, MysteryBoxDelivery::STATUS_DELIVERED], true)) {
            return back()->with('error', "Bu yetkazishni hozir tayyorlash mumkin emas.");
        }

        $subscription = $delivery->subscription;
        if ($subscription && count($bookIds) !== (int) $subscription->books_per_month) {
            return back()->with('error', "Aynan {$subscription->books_per_month} ta kitob tanlanishi kerak.");
        }

        $validBooksCount = \App\Models\Books::whereIn('id', $bookIds)
            ->where('is_hidden', 0)
            ->where('is_approved', 1)
            ->count();

        if ($validBooksCount !== count($bookIds)) {
            return back()->with('error', 'Kiritilgan kitob IDlar orasida yaroqsizlari bor.');
        }

        $isDeliveredEdit = $delivery->status === MysteryBoxDelivery::STATUS_DELIVERED;

        $delivery->update([
            'book_ids'       => $bookIds,
            'status'         => $isDeliveredEdit ? MysteryBoxDelivery::STATUS_DELIVERED : MysteryBoxDelivery::STATUS_PREPARING,
            'tracking_note'  => $request->tracking_note,
            'prepared_at'    => $isDeliveredEdit ? $delivery->prepared_at : now(),
        ]);

        return back()->with('success', $isDeliveredEdit
            ? 'Yetkazilgan oy tarkibi yangilandi.'
            : 'Yetkazish tayyorlash bosqichiga o‘tkazildi.');
    }

    public function shipDelivery(Request $request, MysteryBoxDelivery $delivery)
    {
        $request->validate(['tracking_note' => 'nullable|string|max:500']);
        if ($delivery->status !== MysteryBoxDelivery::STATUS_PREPARING) {
            return back()->with('error', "Faqat tayyorlanayotgan yetkazishni jo'natish mumkin.");
        }
        $delivery->update([
            'status'        => 'shipped',
            'tracking_note' => $request->tracking_note ?? $delivery->tracking_note,
            'shipped_at'    => now(),
        ]);
        return back()->with('success', "Jo'natildi deb belgilandi.");
    }

    public function deliverDelivery(MysteryBoxDelivery $delivery)
    {
        if ($delivery->status !== MysteryBoxDelivery::STATUS_SHIPPED) {
            return back()->with('error', "Faqat jo'natilgan yetkazishni yakunlash mumkin.");
        }
        $sub = $delivery->subscription;
        DB::transaction(function () use ($delivery, $sub) {
            $delivery->update(['status' => 'delivered', 'delivered_at' => now()]);
            $sub->increment('delivered_months');
            $sub->refresh();
            if ($sub->delivered_months >= $sub->total_months) {
                $sub->update(['status' => 'completed', 'next_delivery_at' => null]);
            } else {
                $sub->update(['next_delivery_at' => now()->addMonth()]);
                $sub->createNextDelivery();
            }
        });
        return back()->with('success', 'Yetkazildi. Keyingi oy navbatga qo\'yildi.');
    }
}
