<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Models\BranchStock;
use App\Models\Books;
use App\Models\Gifts;
use App\Models\SellerLocation;
use App\Models\SellerStaffLog;
use App\Models\Stationery;
use App\Models\StockMovement;
use App\Services\BranchStockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * FILIAL-DARAJALI STOCK BOSHQARUVI (business app).
 *
 * Ruxsat qoidasi ("kartochka global, stock lokal"):
 *  - Owner / Admin(1)          → barcha filiallar stockini boshqaradi
 *  - Product manager(2)        → faqat O'Z filialining stockini boshqaradi
 *                                 (filialga bog'lanmagan bo'lsa — barcha)
 *  - Boshqa rollar             → faqat ko'rish yo'q, 403
 */
class BranchStockController extends Controller
{
    public function __construct(private readonly BranchStockService $branchStock)
    {
        $this->middleware('auth:seller');
    }

    private function storeSellerId($seller): int
    {
        return (int) ($seller->parent_id ?: $seller->id);
    }

    private function canView($seller): bool
    {
        return ! $seller->parent_id || in_array((int) $seller->role, [1, 2], true);
    }

    /** Hodim shu filial stockini o'zgartira oladimi? */
    private function canEditLocation($seller, int $locationId): bool
    {
        // Owner yoki Admin — hamma filial
        if (! $seller->parent_id || (int) $seller->role === 1) {
            return true;
        }

        // Product manager — faqat o'z filiali (bog'lanmagan bo'lsa hammasi)
        if ((int) $seller->role === 2) {
            return ! $seller->seller_location_id
                || (int) $seller->seller_location_id === $locationId;
        }

        return false;
    }

    private function resolveProduct(int $storeSellerId, string $type, int $productId): ?object
    {
        return match ($type) {
            'book' => Books::where('seller_id', $storeSellerId)->find($productId),
            'stationery' => Stationery::where('seller_id', $storeSellerId)->find($productId),
            'gift' => Gifts::where('seller_id', $storeSellerId)->find($productId),
            default => null,
        };
    }

    /**
     * GET /branch-stocks?type=book&product_id=12&variant_id=0
     * Mahsulotning filiallar bo'yicha taqsimoti.
     */
    public function show(Request $request)
    {
        $seller = Auth::guard('seller')->user();
        if (! $seller || ! $this->canView($seller)) {
            return response()->json(['success' => false, 'message' => 'Access denied.'], 403);
        }

        $data = $request->validate([
            'type' => 'required|in:book,stationery,gift',
            'product_id' => 'required|integer',
            'variant_id' => 'nullable|integer',
        ]);

        $storeSellerId = $this->storeSellerId($seller);
        $product = $this->resolveProduct($storeSellerId, $data['type'], (int) $data['product_id']);
        if (! $product) {
            return response()->json(['success' => false, 'message' => 'Mahsulot topilmadi.'], 404);
        }

        $variantId = (int) ($data['variant_id'] ?? 0);

        // Barcha faol filiallar (stock qatori bo'lmaganlar 0 bilan)
        $locations = SellerLocation::query()
            ->where('seller_id', $storeSellerId)
            ->where('is_deleted', false)
            ->orderByDesc('is_main')
            ->get(['id', 'fullAddress', 'description', 'is_main']);

        $rows = BranchStock::query()
            ->where('product_type', $data['type'])
            ->where('product_id', (int) $data['product_id'])
            ->where('variant_id', $variantId)
            ->get()
            ->keyBy('seller_location_id');

        $payload = $locations->map(function ($loc) use ($rows, $seller) {
            $row = $rows->get($loc->id);

            return [
                'location_id' => (int) $loc->id,
                'address' => $loc->fullAddress,
                'description' => $loc->description,
                'is_main' => (bool) $loc->is_main,
                'quantity' => (int) ($row->quantity ?? 0),
                'reserved' => (int) ($row->reserved ?? 0),
                'available' => max(0, (int) ($row->quantity ?? 0) - (int) ($row->reserved ?? 0)),
                'low_stock_threshold' => $row->low_stock_threshold ?? null,
                'can_edit' => $this->canEditLocation($seller, (int) $loc->id),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => [
                'total_available' => (int) $payload->sum('available'),
                'branches' => $payload,
            ],
        ]);
    }

    /**
     * POST /branch-stocks/set
     * Bitta filialdagi stockni o'rnatish yoki delta qo'llash.
     * Body: type, product_id, variant_id?, location_id, quantity? | delta?, note?
     */
    public function set(Request $request)
    {
        $seller = Auth::guard('seller')->user();
        if (! $seller || ! $this->canView($seller)) {
            return response()->json(['success' => false, 'message' => 'Access denied.'], 403);
        }

        $data = $request->validate([
            'type' => 'required|in:book,stationery,gift',
            'product_id' => 'required|integer',
            'variant_id' => 'nullable|integer',
            'location_id' => 'required|integer|exists:seller_locations,id',
            'quantity' => 'nullable|integer|min:0',
            'delta' => 'nullable|integer',
            'note' => 'nullable|string|max:255',
        ]);

        if (! isset($data['quantity']) && ! isset($data['delta'])) {
            return response()->json(['success' => false, 'message' => 'quantity yoki delta yuboring.'], 422);
        }

        $storeSellerId = $this->storeSellerId($seller);
        $locationId = (int) $data['location_id'];

        // Filial shu do'konnikimi?
        $ownsLocation = SellerLocation::where('id', $locationId)
            ->where('seller_id', $storeSellerId)
            ->where('is_deleted', false)
            ->exists();
        if (! $ownsLocation) {
            return response()->json(['success' => false, 'message' => 'Filial topilmadi.'], 404);
        }

        // FILIAL-SCOPE RUXSAT
        if (! $this->canEditLocation($seller, $locationId)) {
            return response()->json([
                'success' => false,
                'message' => 'Siz faqat o\'zingizga biriktirilgan filial stockini o\'zgartira olasiz.',
            ], 403);
        }

        $product = $this->resolveProduct($storeSellerId, $data['type'], (int) $data['product_id']);
        if (! $product) {
            return response()->json(['success' => false, 'message' => 'Mahsulot topilmadi.'], 404);
        }

        $variantId = (int) ($data['variant_id'] ?? 0);
        $ctx = [
            'actor_type' => $seller->parent_id ? 'staff' : 'seller',
            'actor_id' => $seller->id,
            'note' => $data['note'] ?? null,
        ];

        if (isset($data['quantity'])) {
            $this->branchStock->setBranchQuantity(
                $data['type'], (int) $data['product_id'], $variantId,
                $storeSellerId, $locationId, (int) $data['quantity'], 'manual_adjust', $ctx
            );
        } else {
            $this->branchStock->adjustBranch(
                $data['type'], (int) $data['product_id'], $variantId,
                $storeSellerId, $locationId, (int) $data['delta'],
                ((int) $data['delta']) > 0 ? 'intake' : 'manual_adjust', $ctx
            );
        }

        SellerStaffLog::create([
            'seller_staff_id' => $seller->id,
            'seller_id' => $storeSellerId,
            'text' => "Hodim: {$seller->firstname} {$seller->lastname} → Filial #{$locationId} stock o'zgartirdi"
                . " | {$data['type']}#{$data['product_id']}"
                . (isset($data['quantity']) ? " = {$data['quantity']}" : " Δ {$data['delta']}"),
        ]);

        return $this->show($request->merge(['variant_id' => $variantId]));
    }

    /**
     * POST /branch-stocks/transfer
     * Filiallararo ko'chirish (bitta mahsulot).
     * Body: type, product_id, variant_id?, from_location_id, to_location_id, quantity, note?
     */
    public function transfer(Request $request)
    {
        $seller = Auth::guard('seller')->user();
        if (! $seller || ! $this->canView($seller)) {
            return response()->json(['success' => false, 'message' => 'Access denied.'], 403);
        }

        $data = $request->validate([
            'type' => 'required|in:book,stationery,gift',
            'product_id' => 'required|integer',
            'variant_id' => 'nullable|integer',
            'from_location_id' => 'required|integer|different:to_location_id',
            'to_location_id' => 'required|integer',
            'quantity' => 'required|integer|min:1',
            'note' => 'nullable|string|max:255',
        ]);

        $storeSellerId = $this->storeSellerId($seller);

        $locations = SellerLocation::query()
            ->where('seller_id', $storeSellerId)
            ->where('is_deleted', false)
            ->whereIn('id', [(int) $data['from_location_id'], (int) $data['to_location_id']])
            ->count();
        if ($locations !== 2) {
            return response()->json(['success' => false, 'message' => 'Filial topilmadi.'], 404);
        }

        // Transfer uchun kamida bitta tomonga edit ruxsati bo'lishi kerak,
        // chiqim tomoni esa majburiy tekshiriladi.
        if (! $this->canEditLocation($seller, (int) $data['from_location_id'])) {
            return response()->json([
                'success' => false,
                'message' => 'Chiqim filialiga ruxsatingiz yo\'q.',
            ], 403);
        }

        $product = $this->resolveProduct($storeSellerId, $data['type'], (int) $data['product_id']);
        if (! $product) {
            return response()->json(['success' => false, 'message' => 'Mahsulot topilmadi.'], 404);
        }

        $variantId = (int) ($data['variant_id'] ?? 0);
        $qty = (int) $data['quantity'];
        $ctx = [
            'actor_type' => $seller->parent_id ? 'staff' : 'seller',
            'actor_id' => $seller->id,
            'note' => $data['note'] ?? null,
        ];

        // Chiqim (clamp: mavjuddan ko'p chiqarib bo'lmaydi)
        $taken = $this->branchStock->adjustBranch(
            $data['type'], (int) $data['product_id'], $variantId,
            $storeSellerId, (int) $data['from_location_id'], -$qty,
            'transfer_out', $ctx
        );

        $taken = abs($taken);
        if ($taken === 0) {
            return response()->json(['success' => false, 'message' => 'Chiqim filialida yetarli stock yo\'q.'], 422);
        }

        // Kirim (chiqqan miqdor qancha bo'lsa shuncha)
        $this->branchStock->adjustBranch(
            $data['type'], (int) $data['product_id'], $variantId,
            $storeSellerId, (int) $data['to_location_id'], $taken,
            'transfer_in', $ctx
        );

        SellerStaffLog::create([
            'seller_staff_id' => $seller->id,
            'seller_id' => $storeSellerId,
            'text' => "Hodim: {$seller->firstname} {$seller->lastname} → Transfer"
                . " | {$data['type']}#{$data['product_id']}: filial #{$data['from_location_id']} → #{$data['to_location_id']} ({$taken} dona)",
        ]);

        return response()->json([
            'success' => true,
            'message' => "{$taken} dona ko'chirildi.",
            'transferred' => abs($taken),
        ]);
    }

    /**
     * GET /branch-stocks/movements?type=book&product_id=12
     * Mahsulot bo'yicha oxirgi harakatlar (ledger).
     */
    public function movements(Request $request)
    {
        $seller = Auth::guard('seller')->user();
        if (! $seller || ! $this->canView($seller)) {
            return response()->json(['success' => false, 'message' => 'Access denied.'], 403);
        }

        $data = $request->validate([
            'type' => 'required|in:book,stationery,gift',
            'product_id' => 'required|integer',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $storeSellerId = $this->storeSellerId($seller);
        $product = $this->resolveProduct($storeSellerId, $data['type'], (int) $data['product_id']);
        if (! $product) {
            return response()->json(['success' => false, 'message' => 'Mahsulot topilmadi.'], 404);
        }

        $movements = StockMovement::query()
            ->whereHas('branchStock', function ($q) use ($data, $storeSellerId) {
                $q->where('product_type', $data['type'])
                    ->where('product_id', (int) $data['product_id'])
                    ->where('seller_id', $storeSellerId);
            })
            ->with('branchStock.location:id,fullAddress,is_main')
            ->orderByDesc('id')
            ->paginate((int) ($data['per_page'] ?? 30));

        return response()->json(['success' => true, 'data' => $movements]);
    }
}
