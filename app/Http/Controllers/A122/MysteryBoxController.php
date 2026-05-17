<?php

namespace App\Http\Controllers\A122;

use App\Http\Controllers\Controller;
use App\Models\Books;
use App\Models\MysteryBoxDelivery;
use App\Models\MysteryBoxPlan;
use App\Models\MysteryBoxSubscription;
use App\Services\MysteryBoxService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MysteryBoxController extends Controller
{
    public function __construct(
        private readonly MysteryBoxService $mysteryBoxService,
    ) {
    }

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
        $opsDueNow = $this->mysteryBoxService->opsQueueQuery()
            ->with(['subscription.user:id,name,lastname,phone_number', 'subscription.plan:id,name_uz'])
            ->take(8)
            ->get();

        $dueToday = MysteryBoxDelivery::query()
            ->whereNotIn('status', MysteryBoxDelivery::FINAL_STATUSES)
            ->whereDate('planned_for_date', '<=', today())
            ->count();

        $dueWeek = MysteryBoxDelivery::query()
            ->whereNotIn('status', MysteryBoxDelivery::FINAL_STATUSES)
            ->whereBetween('planned_for_date', [today(), today()->addDays(7)])
            ->count();

        return view('a122.mystery-box.index', compact('subs', 'counts', 'tab', 'plans', 'dueToday', 'dueWeek', 'opsDueNow'));
    }

    public function subscriptions(Request $request)
    {
        return $this->index($request);
    }

    public function show(MysteryBoxSubscription $subscription)
    {
        $this->mysteryBoxService->ensureDeliverySchedule($subscription->fresh(['plan', 'deliveries']));
        $subscription = $this->mysteryBoxService->syncSubscriptionProgress($subscription->fresh(['user', 'plan', 'deliveries']));

        $dispatchOptions = [
            MysteryBoxDelivery::DISPATCH_COURIER => 'Kuryer',
            MysteryBoxDelivery::DISPATCH_POSTAL => 'Pochta',
            MysteryBoxDelivery::DISPATCH_PICKUP => 'Pickup',
        ];

        $statusOptions = [
            MysteryBoxDelivery::DISPATCH_COURIER => [
                MysteryBoxDelivery::STATUS_PENDING => 'Kutilmoqda',
                MysteryBoxDelivery::STATUS_PREPARING => 'Tayyorlanmoqda',
                MysteryBoxDelivery::STATUS_READY_TO_SHIP => 'Jo\'natishga tayyor',
                MysteryBoxDelivery::STATUS_SHIPPED => 'Jo\'natildi',
                MysteryBoxDelivery::STATUS_OUT_FOR_DELIVERY => 'Kuryer yo\'lda',
                MysteryBoxDelivery::STATUS_CUSTOMER_RECEIVED => 'Mijoz qabul qildi',
                MysteryBoxDelivery::STATUS_CANCELLED => 'Bekor qilindi',
            ],
            MysteryBoxDelivery::DISPATCH_POSTAL => [
                MysteryBoxDelivery::STATUS_PENDING => 'Kutilmoqda',
                MysteryBoxDelivery::STATUS_PREPARING => 'Tayyorlanmoqda',
                MysteryBoxDelivery::STATUS_READY_TO_SHIP => 'Jo\'natishga tayyor',
                MysteryBoxDelivery::STATUS_SHIPPED => 'Jo\'natildi',
                MysteryBoxDelivery::STATUS_ARRIVED_TO_POST => 'Pochtaga yetib bordi',
                MysteryBoxDelivery::STATUS_CUSTOMER_RECEIVED => 'Mijoz qabul qildi',
                MysteryBoxDelivery::STATUS_CANCELLED => 'Bekor qilindi',
            ],
            MysteryBoxDelivery::DISPATCH_PICKUP => [
                MysteryBoxDelivery::STATUS_PENDING => 'Kutilmoqda',
                MysteryBoxDelivery::STATUS_PREPARING => 'Tayyorlanmoqda',
                MysteryBoxDelivery::STATUS_READY_TO_SHIP => 'Jo\'natishga tayyor',
                MysteryBoxDelivery::STATUS_DELIVERED => 'Olib ketishga tayyor',
                MysteryBoxDelivery::STATUS_CUSTOMER_RECEIVED => 'Mijoz qabul qildi',
                MysteryBoxDelivery::STATUS_CANCELLED => 'Bekor qilindi',
            ],
        ];

        return view('a122.mystery-box.show', compact('subscription', 'dispatchOptions', 'statusOptions'));
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

        if (in_array($delivery->status, MysteryBoxDelivery::FINAL_STATUSES, true)) {
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

        $delivery->update([
            'book_ids'       => $bookIds,
            'selection_mode' => 'manual',
            'status'         => $delivery->status === MysteryBoxDelivery::STATUS_PENDING
                ? MysteryBoxDelivery::STATUS_PREPARING
                : $delivery->status,
            'tracking_note'  => $request->tracking_note,
            'prepared_at'    => $delivery->prepared_at ?? now(),
            'selection_meta' => array_merge(
                is_array($delivery->selection_meta) ? $delivery->selection_meta : [],
                [
                    'selection_mode' => 'manual',
                    'manual_updated_at' => now()->toIso8601String(),
                ]
            ),
        ]);

        $this->mysteryBoxService->syncSubscriptionProgress($delivery->subscription()->firstOrFail());

        return back()->with('success', 'Oy tarkibi saqlandi.');
    }

    public function updateDeliverySettings(Request $request, MysteryBoxDelivery $delivery)
    {
        $data = $request->validate([
            'dispatch_type' => 'required|in:courier,postal,pickup',
            'planned_for_date' => 'nullable|date',
            'tracking_note' => 'nullable|string|max:500',
        ]);

        if (in_array($delivery->status, MysteryBoxDelivery::FINAL_STATUSES, true)) {
            return back()->with('error', 'Yakunlangan oy sozlamasini o‘zgartirib bo‘lmaydi.');
        }

        $delivery->update([
            'dispatch_type' => $data['dispatch_type'],
            'planned_for_date' => $data['planned_for_date'] ?: $delivery->planned_for_date,
            'tracking_note' => $data['tracking_note'] ?? $delivery->tracking_note,
        ]);

        $subscription = $delivery->subscription()->first();
        if ($subscription) {
            $subscription->update([
                'preferred_dispatch_type' => $data['dispatch_type'],
            ]);

            $subscription->deliveries()
                ->where('month_number', '>', $delivery->month_number)
                ->whereIn('status', [
                    MysteryBoxDelivery::STATUS_PENDING,
                    MysteryBoxDelivery::STATUS_PREPARING,
                    MysteryBoxDelivery::STATUS_READY_TO_SHIP,
                ])
                ->update(['dispatch_type' => $data['dispatch_type']]);
        }

        $this->mysteryBoxService->syncSubscriptionProgress($delivery->subscription()->firstOrFail());

        return back()->with('success', 'Yetkazish sozlamalari yangilandi.');
    }

    public function updateDeliveryStatus(Request $request, MysteryBoxDelivery $delivery)
    {
        $data = $request->validate([
            'status' => 'required|string',
            'tracking_note' => 'nullable|string|max:500',
        ]);

        try {
            $this->mysteryBoxService->transitionDelivery(
                $delivery,
                $data['status'],
                $data['tracking_note'] ?? null,
            );
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Delivery status yangilandi.');
    }

    public function shipDelivery(Request $request, MysteryBoxDelivery $delivery)
    {
        $request->merge(['status' => MysteryBoxDelivery::STATUS_SHIPPED]);

        return $this->updateDeliveryStatus($request, $delivery);
    }

    public function deliverDelivery(MysteryBoxDelivery $delivery)
    {
        $target = match ($delivery->dispatch_type) {
            MysteryBoxDelivery::DISPATCH_COURIER => MysteryBoxDelivery::STATUS_CUSTOMER_RECEIVED,
            MysteryBoxDelivery::DISPATCH_POSTAL => MysteryBoxDelivery::STATUS_ARRIVED_TO_POST,
            default => MysteryBoxDelivery::STATUS_DELIVERED,
        };

        request()->merge(['status' => $target]);

        return $this->updateDeliveryStatus(request(), $delivery);
    }
}
