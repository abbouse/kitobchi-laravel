<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Models\Gifts;
use App\Models\SellerStaffLog;
use App\Support\ProductImageVariantGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class GiftController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:seller');
    }

    private function getStoreSellerId($seller)
    {
        return $seller->parent_id ?: $seller->id;
    }

    private function hasGiftAccess($seller)
    {
        return !$seller->parent_id || in_array($seller->role, [1, 2]);
    }

    private function writeLog($staff, $action, $details = '')
    {
        $storeSellerId = $this->getStoreSellerId($staff);
        SellerStaffLog::create([
            'seller_staff_id' => $staff->id,
            'seller_id' => $storeSellerId,
            'text' => "Hodim: {$staff->firstname} {$staff->lastname} → {$action}" . ($details ? " | {$details}" : ''),
        ]);
    }

    public function list(Request $request)
    {
        $seller = Auth::guard('seller')->user();
        if (!$this->hasGiftAccess($seller)) {
            return response()->json(['success' => false, 'message' => 'Access denied.'], 403);
        }

        $storeSellerId = $this->getStoreSellerId($seller);

        $gifts = Gifts::where('seller_id', $storeSellerId)
            ->latest('updated_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $gifts, // images avtomatik URL bilan chiqadi
        ], 200);
    }

    public function create(Request $request)
    {
        $seller = Auth::guard('seller')->user();
        if (!$this->hasGiftAccess($seller)) {
            return response()->json(['success' => false, 'message' => 'Access denied.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name'      => 'required|string|max:255',
            'stock'     => 'nullable|integer|min:0',
            'min_price' => 'nullable|numeric|min:0',
            'max_price' => 'nullable|numeric|min:0',
            'images.*'  => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $paths = $this->uploadImages($request->file('images'));

        $gift = Gifts::create([
            'seller_id' => $this->getStoreSellerId($seller),
            'name'      => $request->name,
            'stock'     => $request->stock ?? 0,
            'priceFrom' => $request->min_price,
            'priceTo'   => $request->max_price,
            'images'     => $paths, // to'g'ridan-to'g'ri array beramiz, Laravel json_encode qiladi
        ]);

        $this->writeLog($seller, 'Created gift', "Gift ID: {$gift->id}");

        return response()->json([
            'success' => true,
            'message' => 'Gift yaratildi',
            'gift'    => $gift // images accessor ishlaydi
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $seller = Auth::guard('seller')->user();
        if (!$this->hasGiftAccess($seller)) {
            return response()->json(['success' => false, 'message' => 'Access denied.'], 403);
        }

        $gift = Gifts::where('id', $id)
            ->where('seller_id', $this->getStoreSellerId($seller))
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'name'             => 'sometimes|required|string|max:255',
            'stock'            => 'nullable|integer|min:0',
            'min_price'        => 'nullable|numeric|min:0',
            'max_price'        => 'nullable|numeric|min:0',
            'images.*'         => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:10240',
            'existing_images'  => 'nullable|array',
            'existing_images.*'=> 'string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        // Oddiy maydonlar
        $gift->fill($request->only(['name', 'stock']));
        if ($request->has('min_price')) $gift->priceFrom = $request->min_price;
        if ($request->has('max_price')) $gift->priceTo = $request->max_price;

        // Rasmlar
        $currentImages = $gift->images ?? []; // array
        $existingImages = $request->input('existing_images', []);

        // O'chirilganlarni serverdan o'chirish
        foreach ($currentImages as $path) {
            if (!in_array($path, $existingImages) && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
                ProductImageVariantGenerator::deleteForPath($path);
            }
        }

        // Yangi rasmlarni yuklash
        $newImages = $request->hasFile('images') ? $this->uploadImages($request->file('images')) : [];

        // Yakuniy array
        $gift->images = array_merge($existingImages, $newImages);

        $gift->save();

        $this->writeLog($seller, 'Updated gift', "Gift ID: {$gift->id}");

        return response()->json([
            'success' => true,
            'message' => 'Gift yangilandi',
            'gift'    => $gift
        ], 200);
    }

    private function uploadImages($files)
    {
        $paths = [];
        foreach ($files as $file) {
            $path = $file->store('gifts', 'public');
            $paths[] = $path;
            ProductImageVariantGenerator::generateForPath($path);
        }
        return $paths;
    }
    /**
     * Gift o'chirish (ixtiyoriy — agar kerak bo'lsa qo'shishingiz mumkin)
     */
    public function delete(Request $request, $id)
    {
        $seller = Auth::guard('seller')->user();

        if (!$this->hasGiftAccess($seller)) {
            return response()->json(['success' => false, 'message' => 'Access denied.'], 403);
        }

        $storeSellerId = $this->getStoreSellerId($seller);

        $gift = Gifts::where('id', $id)
            ->where('seller_id', $storeSellerId)
            ->first();

        if (!$gift) {
            return response()->json(['success' => false, 'message' => 'Gift not found'], 404);
        }

        // Barcha rasmlarni o'chirish
        $images = json_decode($gift->images, true) ?? [];
        foreach ($images as $path) {
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
                ProductImageVariantGenerator::deleteForPath($path);
            }
        }

        $gift->delete();

        $this->writeLog($seller, 'Deleted gift', "Gift ID: {$id}");

        return response()->json([
            'success' => true,
            'message' => 'Gift muvaffaqiyatli o\'chirildi'
        ], 200);
    }
}
