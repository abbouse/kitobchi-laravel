<?php

namespace App\Http\Controllers\Boshqaruv;

use App\Http\Controllers\Controller;
use App\Models\CatalogSlotPurchase;
use App\Services\Catalog\CatalogSlotService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/**
 * KATALOG JOYI — boshqaruv tomoni: narx sozlamasi va moderatsiya navbati.
 *
 * Do'kon joyni sotib olganda pul balansidan darhol yechiladi va joy `pending`
 * holatida turadi. Admin tasdiqlasa muddat SHU PAYTDAN boshlanadi; rad etsa
 * pul to'liq qaytariladi.
 */
class CatalogSlotController extends Controller
{
    public function __construct(private CatalogSlotService $slots)
    {
    }

    public function index(Request $request): Response
    {
        $tab = (string) $request->query('tab', 'pending');
        $statuses = [
            CatalogSlotPurchase::STATUS_PENDING,
            CatalogSlotPurchase::STATUS_ACTIVE,
            CatalogSlotPurchase::STATUS_EXPIRED,
            CatalogSlotPurchase::STATUS_REJECTED,
            CatalogSlotPurchase::STATUS_CANCELLED,
        ];

        $items = CatalogSlotPurchase::query()
            ->with([
                'seller:id,shop_name,isVerified,rating',
                'book:id,name,seller_id,price,discountPrice,images',
                'edition:id,title,author,isbn13,offers_count,in_stock_offers_count',
            ])
            ->when(in_array($tab, $statuses, true), fn ($q) => $q->where('status', $tab))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $settings = $this->slots->settings();

        return Inertia::render('KatalogJoylari', [
            'items' => collect($items->items())->map(fn (CatalogSlotPurchase $p) => $this->row($p))->values(),
            'pagination' => [
                'page' => (int) $items->currentPage(),
                'totalPages' => (int) $items->lastPage(),
                'from' => (int) ($items->firstItem() ?? 0),
                'to' => (int) ($items->lastItem() ?? 0),
                'total' => (int) $items->total(),
            ],
            'filters' => ['tab' => $tab],
            'counts' => collect($statuses)
                ->mapWithKeys(fn ($s) => [$s => CatalogSlotPurchase::query()->where('status', $s)->count()])
                ->put('all', CatalogSlotPurchase::query()->count())
                ->all(),
            'settings' => [
                'price_per_month' => (int) $settings->price_per_month,
                'min_days' => (int) $settings->min_days,
                'max_days' => (int) $settings->max_days,
                'is_active' => (bool) $settings->is_active,
                'saveUrl' => route('boshqaruv.catalog-slots.settings'),
            ],
            'revenue' => [
                // Faqat rad etilmagan/bekor qilinmaganlar — haqiqiy tushum
                'total' => (int) CatalogSlotPurchase::query()
                    ->whereIn('status', [CatalogSlotPurchase::STATUS_ACTIVE, CatalogSlotPurchase::STATUS_EXPIRED])
                    ->sum('price_uzs'),
                'active' => (int) CatalogSlotPurchase::query()
                    ->where('status', CatalogSlotPurchase::STATUS_ACTIVE)
                    ->sum('price_uzs'),
            ],
        ]);
    }

    public function approve(CatalogSlotPurchase $slot): RedirectResponse
    {
        if ($slot->status !== CatalogSlotPurchase::STATUS_PENDING) {
            return back()->with('error', "Bu joy allaqachon ko'rib chiqilgan.");
        }

        // Boshqa do'kon ayni kartada faol joyga ega bo'lib qolmaganini tekshiramiz
        $conflict = CatalogSlotPurchase::query()
            ->where('edition_id', $slot->edition_id)
            ->where('id', '!=', $slot->id)
            ->where('status', CatalogSlotPurchase::STATUS_ACTIVE)
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            ->exists();

        if ($conflict) {
            return back()->with('error', 'Bu kartada allaqachon faol joy bor. Avval uni yoping.');
        }

        $this->slots->approve($slot, Auth::guard('panel')->id());

        return back()->with('success', 'Joy tasdiqlandi va ishga tushdi.');
    }

    public function reject(Request $request, CatalogSlotPurchase $slot): RedirectResponse
    {
        if ($slot->status !== CatalogSlotPurchase::STATUS_PENDING) {
            return back()->with('error', "Bu joy allaqachon ko'rib chiqilgan.");
        }

        $reason = trim((string) $request->input('reason', ''));
        $this->slots->reject($slot, Auth::guard('panel')->id(), $reason !== '' ? $reason : null);

        return back()->with('success', "Rad etildi, pul do'kon balansiga qaytarildi.");
    }

    /** Faol joyni muddatidan oldin to'xtatish (pul qaytmaydi — muddat sarflangan). */
    public function stop(CatalogSlotPurchase $slot): RedirectResponse
    {
        if ($slot->status !== CatalogSlotPurchase::STATUS_ACTIVE) {
            return back()->with('error', 'Faqat faol joyni to\'xtatish mumkin.');
        }

        $slot->forceFill([
            'status' => CatalogSlotPurchase::STATUS_EXPIRED,
            'ends_at' => now(),
        ])->save();

        app(\App\Services\Catalog\BuyBoxService::class)->recompute((int) $slot->edition_id);

        return back()->with('success', "Joy to'xtatildi.");
    }

    public function saveSettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'price_per_month' => 'required|integer|min:0|max:1000000000',
            'min_days' => 'required|integer|min:1|max:365',
            'max_days' => 'required|integer|min:1|max:365|gte:min_days',
            'is_active' => 'required|boolean',
        ]);

        $this->slots->settings()->forceFill($data)->save();

        return back()->with('success', 'Narx sozlamasi saqlandi.');
    }

    /** @return array<string, mixed> */
    private function row(CatalogSlotPurchase $p): array
    {
        return [
            'id' => (int) $p->id,
            'status' => $p->status,
            'seller' => $p->seller?->shop_name,
            'sellerId' => (int) $p->seller_id,
            'sellerVerified' => (bool) ($p->seller->isVerified ?? false),
            'sellerRating' => (float) ($p->seller->rating ?? 0),
            'bookId' => (int) $p->book_id,
            'bookName' => $p->book?->name,
            'editionId' => (int) $p->edition_id,
            'editionTitle' => $p->edition?->title,
            'editionAuthor' => $p->edition?->author,
            'isbn' => $p->edition?->isbn13,
            'offersCount' => (int) ($p->edition->offers_count ?? 0),
            'days' => (int) $p->days,
            'price' => (int) $p->price_uzs,
            'refunded' => $p->refunded_at !== null,
            'rejectReason' => $p->reject_reason,
            'startsAt' => $p->starts_at?->toDateString(),
            'endsAt' => $p->ends_at?->toDateString(),
            'createdAt' => $p->created_at?->toDateTimeString(),
            'editionUrl' => route('boshqaruv.catalog.show', $p->edition_id),
            'approveUrl' => route('boshqaruv.catalog-slots.approve', $p->id),
            'rejectUrl' => route('boshqaruv.catalog-slots.reject', $p->id),
            'stopUrl' => route('boshqaruv.catalog-slots.stop', $p->id),
        ];
    }
}
