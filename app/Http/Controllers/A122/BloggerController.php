<?php

namespace App\Http\Controllers\A122;

use App\Http\Controllers\Controller;
use App\Models\Blogger;
use App\Models\BloggerShipment;
use App\Services\BloggerShipmentPrintService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class BloggerController extends Controller
{
    public function __construct(
        private readonly BloggerShipmentPrintService $printService,
    ) {}

    public function index(Request $request)
    {
        $query = Blogger::query()->withCount('shipments');

        if ($search = trim((string) $request->input('search'))) {
            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('first_name', 'like', '%' . $search . '%')
                    ->orWhere('last_name', 'like', '%' . $search . '%')
                    ->orWhere('phone_number', 'like', '%' . $search . '%');
            });
        }

        $tab = (string) $request->input('tab', 'all');
        if ($tab === 'active') {
            $query->whereNotNull('active_until')->where('active_until', '>', now());
        } elseif ($tab === 'inactive') {
            $query->where(function ($builder) {
                $builder->whereNull('active_until')->orWhere('active_until', '<=', now());
            });
        }

        $bloggers = $query
            ->orderByRaw('CASE WHEN active_until IS NOT NULL AND active_until > NOW() THEN 0 ELSE 1 END')
            ->orderBy('active_until')
            ->orderBy('first_name')
            ->paginate(20)
            ->withQueryString();

        $stats = [
            'total' => Blogger::count(),
            'active' => Blogger::query()->whereNotNull('active_until')->where('active_until', '>', now())->count(),
            'inactive' => Blogger::query()->where(function ($builder) {
                $builder->whereNull('active_until')->orWhere('active_until', '<=', now());
            })->count(),
            'shipments' => BloggerShipment::count(),
        ];

        return view('a122.bloggers.index', compact('bloggers', 'stats', 'tab'));
    }

    public function create()
    {
        return view('a122.bloggers.edit');
    }

    public function store(Request $request)
    {
        $blogger = Blogger::create($this->validatedData($request));
        return redirect()->route('admin.bloggers.show', $blogger)->with('success', "Hamkor bloger qo'shildi.");
    }

    public function show(Blogger $blogger)
    {
        $blogger->load([
            'shipments' => fn ($query) => $query->latest('scheduled_for')->latest('id'),
            'shipments.items',
        ]);
        $stats = [
            'pending_shipments' => $blogger->shipments->where('status', BloggerShipment::STATUS_PENDING)->count(),
            'delivered_shipments' => $blogger->shipments->where('status', BloggerShipment::STATUS_DELIVERED)->count(),
            'items_total' => $blogger->shipments->sum(fn ($shipment) => $shipment->items->count()),
        ];

        return view('a122.bloggers.show', compact('blogger', 'stats'));
    }

    public function edit(Blogger $blogger)
    {
        return view('a122.bloggers.edit', compact('blogger'));
    }

    public function update(Request $request, Blogger $blogger)
    {
        $blogger->update($this->validatedData($request));
        return redirect()->route('admin.bloggers.show', $blogger)->with('success', 'Hamkor bloger yangilandi.');
    }

    public function destroy(Blogger $blogger)
    {
        $blogger->delete();
        return redirect()->route('admin.bloggers.index')->with('success', "Hamkor bloger o'chirildi.");
    }

    public function storeShipment(Request $request, Blogger $blogger)
    {
        $data = $request->validate([
            'scheduled_for' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:1000'],
            'items_text' => ['required', 'string'],
        ]);

        $items = $this->parseShipmentItems((string) $data['items_text']);
        if ($items === []) {
            return back()->withInput()->with('error', 'Kamida bitta item nomini kiriting.');
        }

        $shipment = $blogger->shipments()->create([
            'scheduled_for' => Carbon::parse($data['scheduled_for']),
            'status' => BloggerShipment::STATUS_PENDING,
            'note' => $data['note'] ?? null,
        ]);

        $this->syncShipmentItems($shipment, $items);

        return redirect()->route('admin.bloggers.show', $blogger)->with('success', "Jo'natma yaratildi.");
    }

    public function editShipment(Blogger $blogger, BloggerShipment $shipment)
    {
        abort_unless((int) $shipment->blogger_id === (int) $blogger->id, 404);
        $shipment->load('items');
        return view('a122.bloggers.shipment-edit', compact('blogger', 'shipment'));
    }

    public function updateShipment(Request $request, Blogger $blogger, BloggerShipment $shipment)
    {
        abort_unless((int) $shipment->blogger_id === (int) $blogger->id, 404);

        $data = $request->validate([
            'scheduled_for' => ['required', 'date'],
            'status' => ['required', Rule::in([BloggerShipment::STATUS_PENDING, BloggerShipment::STATUS_DELIVERED])],
            'note' => ['nullable', 'string', 'max:1000'],
            'items_text' => ['required', 'string'],
        ]);

        $items = $this->parseShipmentItems((string) $data['items_text']);
        if ($items === []) {
            return back()->withInput()->with('error', 'Kamida bitta item nomini kiriting.');
        }

        $shipment->update([
            'scheduled_for' => Carbon::parse($data['scheduled_for']),
            'status' => $data['status'],
            'delivered_at' => $data['status'] === BloggerShipment::STATUS_DELIVERED
                ? ($shipment->delivered_at ?: now())
                : null,
            'note' => $data['note'] ?? null,
        ]);

        $this->syncShipmentItems($shipment, $items);

        return redirect()->route('admin.bloggers.show', $blogger)->with('success', "Jo'natma yangilandi.");
    }

    public function destroyShipment(Blogger $blogger, BloggerShipment $shipment)
    {
        abort_unless((int) $shipment->blogger_id === (int) $blogger->id, 404);
        $shipment->delete();

        return redirect()->route('admin.bloggers.show', $blogger)->with('success', "Jo'natma o'chirildi.");
    }

    public function markShipmentDelivered(Blogger $blogger, BloggerShipment $shipment)
    {
        abort_unless((int) $shipment->blogger_id === (int) $blogger->id, 404);
        $shipment->markDelivered();

        return redirect()->route('admin.bloggers.show', $blogger)->with('success', 'Jo‘natma yetkazildi deb belgilandi.');
    }

    public function markShipmentPending(Blogger $blogger, BloggerShipment $shipment)
    {
        abort_unless((int) $shipment->blogger_id === (int) $blogger->id, 404);
        $shipment->markPending();

        return redirect()->route('admin.bloggers.show', $blogger)->with('success', 'Jo‘natma yetkazilmagan holatga qaytarildi.');
    }

    public function printShipment(Blogger $blogger, BloggerShipment $shipment, Request $request)
    {
        abort_unless((int) $shipment->blogger_id === (int) $blogger->id, 404);
        $locale = (string) $request->input('locale', 'uz');

        return view('a122.bloggers.print.receipt', [
            'blogger' => $blogger,
            'shipment' => $shipment->loadMissing(['blogger', 'items']),
            'receipt' => $this->printService->receiptData($shipment, $locale),
        ]);
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['nullable', 'string', 'max:120'],
            'phone_number' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:1000'],
            'instagram_url' => ['nullable', 'url', 'max:255'],
            'telegram_url' => ['nullable', 'url', 'max:255'],
            'youtube_url' => ['nullable', 'url', 'max:255'],
            'tiktok_url' => ['nullable', 'url', 'max:255'],
            'active_until' => ['required', 'date'],
        ]);
    }

    private function parseShipmentItems(string $text): array
    {
        return collect(preg_split('/\r\n|\r|\n/', $text) ?: [])
            ->map(fn ($line) => trim((string) $line))
            ->filter()
            ->values()
            ->all();
    }

    private function syncShipmentItems(BloggerShipment $shipment, array $items): void
    {
        $shipment->items()->delete();

        foreach ($items as $index => $name) {
            $shipment->items()->create([
                'name' => $name,
                'position' => $index + 1,
            ]);
        }
    }
}
