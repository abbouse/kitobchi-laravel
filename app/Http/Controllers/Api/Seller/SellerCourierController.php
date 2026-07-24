<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Models\Couriers;
use App\Models\ProjectSetting;
use App\Models\Seller;
use App\Models\SellerLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Do'kon kuryerlari (store couriers) — business app'da do'kon o'zi boshqaradi.
 * Kuryer shu do'konga bog'lanadi (couriers.seller_id), tanlangan filial(lar)ga
 * xizmat qiladi, xaritadagi zonasi (service_area) bo'yicha checkoutda ko'rinadi.
 * Darhol faol (status=approved) — platforma tasdig'i shart emas.
 */
class SellerCourierController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:seller');
    }

    /** Kuryer boshqaruvi — faqat do'kon egasi (owner). Aks holda null. */
    private function owner(): ?Seller
    {
        $seller = Auth::guard('seller')->user();
        if (! $seller) {
            return null;
        }

        return $seller->parent_id ? null : $seller;
    }

    /** Autentifikatsiya qilingan seller (staff bo'lsa ham) egasining do'kon id'si. */
    private function ownerId(): ?int
    {
        $seller = Auth::guard('seller')->user();
        if (! $seller) {
            return null;
        }

        return (int) ($seller->parent_id ?: $seller->id);
    }

    public function index(Request $request)
    {
        $ownerId = $this->ownerId();
        if (! $ownerId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $couriers = Couriers::query()
            ->where('seller_id', $ownerId)
            ->where('status', '!=', 'blocked')
            ->whereNull('store_courier_hidden_at')
            ->with('branches:id,seller_id,description,is_main,store_courier_delivery_price')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Couriers $courier) => $this->present($courier));

        $owner = Seller::find($ownerId);

        return response()->json([
            'success' => true,
            'delivery_price' => $this->mainBranchPrice($owner),
            'minimum_delivery_price' => $this->minimumDeliveryPrice(),
            'branch_prices' => $this->branchPrices($ownerId),
            'data' => $couriers,
        ]);
    }

    public function store(Request $request)
    {
        $seller = $this->owner();
        if (! $seller) {
            return response()->json(['success' => false, 'message' => "Faqat do'kon egasi kuryer qo'sha oladi"], 403);
        }

        $validator = Validator::make($request->all(), $this->rules());
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validatsiya xatosi', 'errors' => $validator->errors()], 422);
        }

        $branchIds = $this->ownedBranchIds($seller->id, (array) $request->input('branch_ids', []));
        if ($branchIds->isEmpty()) {
            return response()->json(['success' => false, 'message' => "Tanlangan filial(lar) do'konga tegishli emas"], 422);
        }

        try {
            $courier = DB::transaction(function () use ($request, $seller, $branchIds) {
                $this->ensureBranchPrices($seller, $branchIds->all(), $request->has('delivery_price') ? (int) $request->input('delivery_price') : null);

                $courier = Couriers::create([
                    'seller_id' => $seller->id,
                    'first_name' => $request->input('first_name'),
                    'last_name' => $request->input('last_name'),
                    'phone_number' => $request->input('phone_number'),
                    'password' => $request->input('password'),
                    'region' => $seller->region,
                    'service_area' => $this->normalizeArea($request->input('service_area')),
                    'status' => 'approved',            // darhol faol
                    'verification_status' => 'verified',
                    'password_reset_limit' => 3,
                ]);
                $courier->branches()->sync($branchIds->all());

                return $courier;
            });

            return response()->json([
                'success' => true,
                'message' => "Do'kon kuryeri qo'shildi",
                'data' => $this->present($courier->load('branches:id,seller_id,description,is_main,store_courier_delivery_price')),
            ], 201);
        } catch (HttpResponseException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error("Do'kon kuryeri yaratishda xato", ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Server xatosi'], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $seller = $this->owner();
        if (! $seller) {
            return response()->json(['success' => false, 'message' => "Faqat do'kon egasi kuryerni tahrirlaydi"], 403);
        }

        $courier = Couriers::where('seller_id', $seller->id)->where('id', $id)->first();
        if (! $courier) {
            return response()->json(['success' => false, 'message' => 'Kuryer topilmadi'], 404);
        }

        $validator = Validator::make($request->all(), $this->rules($courier->id, false));
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validatsiya xatosi', 'errors' => $validator->errors()], 422);
        }

        try {
            DB::transaction(function () use ($request, $seller, $courier) {
                $payload = [];
                foreach (['first_name', 'last_name', 'phone_number'] as $field) {
                    if ($request->filled($field)) {
                        $payload[$field] = $request->input($field);
                    }
                }
                if ($request->filled('password')) {
                    $payload['password'] = $request->input('password');
                }
                if ($request->has('service_area')) {
                    $payload['service_area'] = $this->normalizeArea($request->input('service_area'));
                }
                if ($request->has('status')) {
                    $payload['status'] = $request->input('status') === 'inactive' ? 'blocked' : 'approved';
                }
                if (! empty($payload)) {
                    $courier->update($payload);
                }

                if ($request->has('branch_ids')) {
                    $branchIds = $this->ownedBranchIds($seller->id, (array) $request->input('branch_ids', []));
                    if ($branchIds->isNotEmpty()) {
                        $this->ensureBranchPrices($seller, $branchIds->all(), $request->has('delivery_price') ? (int) $request->input('delivery_price') : null);
                        $courier->branches()->sync($branchIds->all());
                    }
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'Kuryer yangilandi',
                'data' => $this->present($courier->fresh()->load('branches:id,seller_id,description,is_main,store_courier_delivery_price')),
            ]);
        } catch (HttpResponseException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error("Do'kon kuryerini yangilashda xato", ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Server xatosi'], 500);
        }
    }

    public function destroy($id)
    {
        $seller = $this->owner();
        if (! $seller) {
            return response()->json(['success' => false, 'message' => "Faqat do'kon egasi kuryerni o'chiradi"], 403);
        }

        $courier = Couriers::where('seller_id', $seller->id)->where('id', $id)->first();
        if (! $courier) {
            return response()->json(['success' => false, 'message' => 'Kuryer topilmadi'], 404);
        }

        try {
            DB::transaction(function () use ($courier) {
                // O'chirish o'rniga yashiramiz: tarix va filial bog'lanishi saqlanadi.
                $courier->update([
                    'status' => 'blocked',
                    'is_online' => false,
                    'store_courier_hidden_at' => now(),
                ]);
            });

            return response()->json(['success' => true, 'message' => "Kuryer o'chirildi"]);
        } catch (\Throwable $e) {
            Log::error("Do'kon kuryerini o'chirishda xato", ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Server xatosi'], 500);
        }
    }

    /** Do'kon kuryeri yetkazish narxi (flat, filial darajasida). */
    public function settings(Request $request)
    {
        $seller = $this->owner();
        if (! $seller) {
            return response()->json(['success' => false, 'message' => "Faqat do'kon egasi sozlaydi"], 403);
        }

        $data = $request->validate([
            'branch_id' => 'nullable|integer',
            'delivery_price' => 'required|integer|min:0|max:2000000000',
        ]);

        $price = (int) $data['delivery_price'];
        $minPrice = $this->minimumDeliveryPrice();
        if ($price < $minPrice) {
            return response()->json([
                'success' => false,
                'message' => "Yetkazish narxi kamida ".number_format($minPrice, 0, '.', ' ')." so'm bo'lishi kerak",
                'minimum_delivery_price' => $minPrice,
            ], 422);
        }

        if (! empty($data['branch_id'])) {
            $branch = SellerLocation::query()
                ->where('seller_id', $seller->id)
                ->where('is_deleted', false)
                ->where('id', (int) $data['branch_id'])
                ->first();

            if (! $branch) {
                return response()->json(['success' => false, 'message' => 'Filial topilmadi'], 404);
            }

            $branch->update(['store_courier_delivery_price' => $price]);
        } else {
            SellerLocation::query()
                ->where('seller_id', $seller->id)
                ->where('is_deleted', false)
                ->update(['store_courier_delivery_price' => $price]);

            // Eski app versiyalari fallback sifatida shu ustunni ham o'qishi mumkin.
            $seller->update(['own_courier_delivery_price' => $price]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Yetkazish narxi saqlandi',
            'delivery_price' => $this->mainBranchPrice($seller->fresh()),
            'minimum_delivery_price' => $minPrice,
            'branch_prices' => $this->branchPrices($seller->id),
        ]);
    }

    // ── Yordamchilar ───────────────────────────────────────────────

    private function rules(?int $ignoreId = null, bool $creating = true): array
    {
        $phoneUnique = 'unique:couriers,phone_number'.($ignoreId ? ','.$ignoreId : '');

        return [
            'first_name' => ($creating ? 'required' : 'sometimes').'|string|max:50',
            'last_name' => 'nullable|string|max:50',
            'phone_number' => ($creating ? 'required' : 'sometimes').'|string|max:20|'.$phoneUnique,
            'password' => ($creating ? 'required' : 'nullable').'|string|min:6',
            'branch_ids' => ($creating ? 'required' : 'sometimes').'|array|min:1',
            'branch_ids.*' => 'integer',
            'service_area' => ($creating ? 'required' : 'sometimes').'|array|min:3',
            'service_area.*' => 'array|size:2',
            'service_area.*.*' => 'numeric',
            'delivery_price' => 'sometimes|integer|min:0|max:2000000000',
            'status' => 'sometimes|in:active,inactive',
        ];
    }

    /** Berilgan filial id'lardan shu do'konga tegishlilarini qaytaradi. */
    private function ownedBranchIds(int $sellerId, array $ids)
    {
        return SellerLocation::query()
            ->where('seller_id', $sellerId)
            ->where('is_deleted', false)
            ->whereIn('id', array_map('intval', $ids))
            ->pluck('id');
    }

    /** Poligonni [[lat, lon], ...] float juftliklariga keltiradi. */
    private function normalizeArea($area): array
    {
        if (! is_array($area)) {
            return [];
        }

        $points = [];
        foreach ($area as $point) {
            if (is_array($point) && count($point) >= 2 && is_numeric($point[0]) && is_numeric($point[1])) {
                $points[] = [round((float) $point[0], 6), round((float) $point[1], 6)];
            }
        }

        return $points;
    }

    private function present(Couriers $courier): array
    {
        return [
            'id' => $courier->id,
            'first_name' => $courier->first_name,
            'last_name' => $courier->last_name,
            'phone_number' => $courier->phone_number,
            'status' => $courier->status === 'blocked' ? 'inactive' : 'active',
            'is_online' => (bool) $courier->is_online,
            'service_area' => $courier->service_area ?? [],
            'branches' => $courier->relationLoaded('branches')
                ? $courier->branches->map(fn ($b) => [
                    'id' => $b->id,
                    'description' => $b->description,
                    'is_main' => (bool) $b->is_main,
                    'delivery_price' => $this->branchPriceValue($b),
                ])->values()
                : [],
            'created_at' => optional($courier->created_at)->toDateTimeString(),
        ];
    }

    private function ensureBranchPrices(Seller $seller, array $branchIds, ?int $inputPrice): void
    {
        $minPrice = $this->minimumDeliveryPrice();
        if ($inputPrice !== null && $inputPrice < $minPrice) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => "Yetkazish narxi kamida ".number_format($minPrice, 0, '.', ' ')." so'm bo'lishi kerak",
                'minimum_delivery_price' => $minPrice,
            ], 422));
        }

        $fallback = $inputPrice ?? $seller->own_courier_delivery_price ?? $minPrice;
        $price = max($minPrice, max(0, (int) $fallback));

        SellerLocation::query()
            ->where('seller_id', $seller->id)
            ->where('is_deleted', false)
            ->whereIn('id', $branchIds)
            ->whereNull('store_courier_delivery_price')
            ->update(['store_courier_delivery_price' => $price]);
    }

    private function branchPrices(int $sellerId)
    {
        return SellerLocation::query()
            ->where('seller_id', $sellerId)
            ->where('is_deleted', false)
            ->orderByDesc('is_main')
            ->orderBy('id')
            ->get(['id', 'seller_id', 'description', 'is_main', 'store_courier_delivery_price'])
            ->map(fn (SellerLocation $branch) => [
                'id' => $branch->id,
                'description' => $branch->description,
                'is_main' => (bool) $branch->is_main,
                'delivery_price' => $this->branchPriceValue($branch),
            ])
            ->values();
    }

    private function mainBranchPrice(?Seller $seller): int
    {
        if (! $seller) {
            return 0;
        }

        $branch = SellerLocation::query()
            ->where('seller_id', $seller->id)
            ->where('is_deleted', false)
            ->orderByDesc('is_main')
            ->orderBy('id')
            ->first();

        if ($branch) {
            return $this->branchPriceValue($branch);
        }

        return max($this->minimumDeliveryPrice(), max(0, (int) ($seller->own_courier_delivery_price ?? 0)));
    }

    private function branchPriceValue(SellerLocation $branch): int
    {
        $price = $branch->store_courier_delivery_price;
        if ($price === null) {
            $price = $branch->seller?->own_courier_delivery_price ?? 0;
        }

        return max($this->minimumDeliveryPrice(), max(0, (int) $price));
    }

    private function minimumDeliveryPrice(): int
    {
        return max(0, (int) (ProjectSetting::query()->value('seller_courier_min_delivery_price') ?? 0));
    }
}
