<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Models\SellerLocation;
use App\Models\SellerLocationWorkday;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SellerLocationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:seller');
    }

    private function hasOwnerAccess($seller)
    {
        return ! $seller->parent_id; // parent_id == null bo‘lsa owner hisoblanadi
    }

    /**
     * Barcha locationlarni olish (faqat owner)
     */
    public function index(Request $request)
    {
        $seller = Auth::guard('seller')->user();

        if (! $seller) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $storeSellerId = $seller->parent_id ?: $seller->id;
        $locations = SellerLocation::with('workdays')
            ->withCount('staff')
            ->where('seller_id', $storeSellerId)
            ->where('is_deleted', false)
            ->when($seller->parent_id, function ($query) use ($seller) {
                $query->where('id', $seller->seller_location_id);
            })
            ->get();

        return response()->json(['success' => true, 'data' => $locations]);
    }

    /**
     * Yangi location yaratish (va avtomatik 7 kun workday yozish)
     */
    public function store(Request $request)
    {
        $seller = Auth::guard('seller')->user();

        if (! $seller) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        if (! $this->hasOwnerAccess($seller)) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Only Owner can manage locations.',
            ], 403);
        }

        $validated = $request->validate([
            'lat' => 'required|numeric',
            'lon' => 'required|numeric',
            'fullAddress' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // seller_id ni auth orqali avtomatik beramiz
            $validated['seller_id'] = $seller->id;

            $location = SellerLocation::create($validated);

            // Haftaning 7 kuni uchun avtomatik yozish
            $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

            foreach ($days as $day) {
                SellerLocationWorkday::create([
                    'location_id' => $location->id,
                    'day_of_week' => $day,
                    'open_time' => '09:00',
                    'close_time' => '18:00',
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Location va workdaylar muvaffaqiyatli yaratildi.',
                'data' => $location->load('workdays'),
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Xatolik: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Location va workdaylarni yangilash
     */
    public function update(Request $request, $id)
    {
        $seller = Auth::guard('seller')->user();
        $location = SellerLocation::where('id', $id)
            ->where('seller_id', $seller->id)
            ->firstOrFail();

        if (! $this->hasOwnerAccess($seller)) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Only Owner can update locations.',
            ], 403);
        }

        $validated = $request->validate([
            'lat' => 'sometimes|numeric',
            'lon' => 'sometimes|numeric',
            'fullAddress' => 'sometimes|nullable|string|max:255',
            'description' => 'nullable|string',
            'workdays' => 'nullable|array',
            'workdays.*.day_of_week' => 'required_with:workdays|string',
            'workdays.*.open_time' => 'required_with:workdays|string',
            'workdays.*.close_time' => 'required_with:workdays|string',
        ]);

        DB::beginTransaction();
        try {
            $location->update($validated);

            if (isset($validated['workdays'])) {
                foreach ($validated['workdays'] as $day) {
                    SellerLocationWorkday::updateOrCreate(
                        [
                            'location_id' => $location->id,
                            'day_of_week' => $day['day_of_week'],
                        ],
                        [
                            'open_time' => $day['open_time'],
                            'close_time' => $day['close_time'],
                        ]
                    );
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Location muvaffaqiyatli yangilandi.',
                'data' => $location->load('workdays'), // ← Javobda yangi ma'lumot
            ], 200);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Xatolik: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Location va workdaylarni o‘chirish
     */
    public function destroy(Request $request, $id)
    {
        $seller = Auth::guard('seller')->user();
        $location = SellerLocation::where('id', $id)
            ->where('seller_id', $seller->id)
            ->firstOrFail();

        if (! $this->hasOwnerAccess($seller)) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Only Owner can delete locations.',
            ], 403);
        }

        if ($location->staff()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Bu filialga xodimlar biriktirilgan. Avval ularni boshqa filialga o‘tkazing.',
            ], 422);
        }

        DB::beginTransaction();
        try {
            // $location->workdays()->delete();
            $location->is_deleted = true;
            $location->save();

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Location va workdaylar o‘chirildi.']);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json(['success' => false, 'message' => 'Xatolik: '.$e->getMessage()], 500);
        }
    }

    public function makeMainLocation(Request $request, $id)
    {
        $seller = Auth::guard('seller')->user();

        if (! $seller) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        if (! $this->hasOwnerAccess($seller)) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Only Owner can manage locations.',
            ], 403);
        }
        $location = SellerLocation::where('id', $id)
            ->where('seller_id', $seller->id)
            ->first();
        if (! $location) {
            return response()->json([
                'success' => false,
                'message' => 'Location not found.',
            ], 404);
        }
        SellerLocation::where('seller_id', $seller->id)->update(['is_main' => false]);
        $location->is_main = true;
        $location->save();

        return response()->json([
            'success' => true,
            'message' => 'Location set as main successfully.',
            'data' => $location,
        ], 200);
    }

    public function rotateQr(Request $request, $id)
    {
        $seller = Auth::guard('seller')->user();

        if (! $seller) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        if (! $this->hasOwnerAccess($seller)) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Only Owner can manage locations.',
            ], 403);
        }

        $location = SellerLocation::where('id', $id)
            ->where('seller_id', $seller->id)
            ->where('is_deleted', false)
            ->first();

        if (! $location) {
            return response()->json([
                'success' => false,
                'message' => 'Location not found.',
            ], 404);
        }

        $location->rotateQrToken();

        return response()->json([
            'success' => true,
            'message' => 'Location QR updated successfully.',
            'data' => $location->fresh()->load('workdays'),
        ]);
    }
}
