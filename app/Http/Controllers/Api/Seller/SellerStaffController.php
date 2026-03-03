<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Models\Seller;
use App\Models\SellerStaffLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;

class SellerStaffController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:seller');
    }

    /**
     * ✅ OWNER ACCESS: FAQAT OWNER
     */
    private function hasOwnerAccess($seller)
    {
        return !$seller->parent_id; // parent_id = NULL → OWNER
    }

    /**
     * 🧾 Hodimlar ro'yxatini olish
     */
    public function index(Request $request)
    {
        $seller = Auth::guard('seller')->user();
        if (!$seller) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // ✅ FAQAT OWNER
        if (!$this->hasOwnerAccess($seller)) {
            return response()->json([
                'success' => false, 
                'message' => 'Access denied. Only Owner can manage staff.'
            ], 403);
        }

        $staffList = Seller::where('parent_id', $seller->id)
            ->where('is_hidden', 0)
            ->select('id', 'firstname', 'lastname', 'role', 'staff_status', 'phone_number', 'created_at')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $staffList,
        ], 200);
    }

    /**
     * ➕ Yangi hodim qo'shish
     */
    public function store(Request $request)
    {
        $seller = Auth::guard('seller')->user();
        if (!$seller) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // ✅ FAQAT OWNER
        if (!$this->hasOwnerAccess($seller)) {
            return response()->json([
                'success' => false, 
                'message' => 'Access denied. Only Owner can add staff.'
            ], 403);
        }

        // Maksimal 4 hodim
        $staffCount = Seller::where('parent_id', $seller->id)
            ->where('is_hidden', 0)
            ->count();

        if ($staffCount >= 4) {
            return response()->json([
                'success' => false,
                'message' => 'Iltimos, avval mavjud xodimlardan birini o\'chiring (Maksimal 4 hodim)'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'firstname' => 'required|string|max:50',
            'lastname' => 'required|string|max:50',
            'phone_number' => 'required|string|max:20|unique:sellers,phone_number',
            'password' => 'required|string|min:6',
            'role' => 'required|integer|in:1,2,3,4',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validatsiya xatosi',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $staff = Seller::create([
                'parent_id' => $seller->id,
                'firstname' => $request->firstname,
                'lastname' => $request->lastname,
                'phone_number' => $request->phone_number,
                'password' => $request->password,
                'role' => $request->role,
                'staff_status' => 'active',
                'status' => 'approved',
                'is_hidden' => 0,
                'password_reset_limit' => 3,
            ]);

            SellerStaffLog::create([
                'seller_staff_id' => $staff->id,
                'text' => "Hodim yaratildi: {$staff->firstname} {$staff->lastname} (Role: {$staff->role})",
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Yangi hodim muvaffaqiyatli yaratildi',
                'data' => [
                    'id' => $staff->id,
                    'firstname' => $staff->firstname,
                    'lastname' => $staff->lastname,
                    'phone_number' => $staff->phone_number,
                    'role' => $staff->role,
                    'staff_status' => $staff->staff_status,
                ],
            ], 201);
        } catch (\Exception $e) {
            Log::error('Hodim qo\'shishda xato', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Serverda xatolik yuz berdi'
            ], 500);
        }
    }

    /**
     * 🧍‍♂️ Hodimni ko'rish (detallar + loglar)
     */
    public function show($id)
    {
        $seller = Auth::guard('seller')->user();
        if (!$seller) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // ✅ FAQAT OWNER
        if (!$this->hasOwnerAccess($seller)) {
            return response()->json([
                'success' => false, 
                'message' => 'Access denied. Only Owner can view staff details.'
            ], 403);
        }

        $staff = Seller::where('parent_id', $seller->id)
            ->where('id', $id)
            ->first();

        if (!$staff) {
            return response()->json(['success' => false, 'message' => 'Hodim topilmadi'], 404);
        }

        $logs = SellerStaffLog::where('seller_staff_id', $staff->id)
            ->orderBy('created_at', 'desc')
            ->take(20)
            ->get(['text', 'created_at']);

        return response()->json([
            'success' => true,
            'data' => $logs,
        ], 200);
    }

    /**
     * ✏️ Hodimni yangilash
     */
    public function update(Request $request, $id)
    {
        $seller = Auth::guard('seller')->user();
        if (!$seller) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // ✅ FAQAT OWNER
        if (!$this->hasOwnerAccess($seller)) {
            return response()->json([
                'success' => false, 
                'message' => 'Access denied. Only Owner can update staff.'
            ], 403);
        }

        $staff = Seller::where('parent_id', $seller->id)
            ->where('id', $id)
            ->first();

        if (!$staff) {
            return response()->json(['success' => false, 'message' => 'Hodim topilmadi'], 404);
        }

        $validator = Validator::make($request->all(), [
            'firstname' => 'sometimes|string|max:50',
            'lastname' => 'sometimes|string|max:50',
            'phone_number' => 'sometimes|string|max:20|unique:sellers,phone_number,' . $staff->id,
            'role' => 'sometimes|integer|in:1,2,3,4',
            'staff_status' => 'sometimes|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validatsiya xatosi',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $oldRole = $staff->role;
            $staff->update($request->only(['firstname', 'lastname', 'phone_number', 'role', 'staff_status']));

            $logText = "Hodim ma'lumotlari yangilandi";
            if ($request->has('role') && $oldRole != $request->role) {
                $logText .= " | Role: $oldRole → {$request->role}";
            }

            SellerStaffLog::create([
                'seller_staff_id' => $staff->id,
                'text' => $logText,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Hodim ma\'lumotlari yangilandi',
                'data' => $staff,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Hodimni yangilashda xato', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Server xatosi'], 500);
        }
    }

    /**
     * ❌ Hodimni o'chirish (deaktivatsiya)
     */
    public function destroy($id)
    {
        $seller = Auth::guard('seller')->user();
        if (!$seller) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // ✅ FAQAT OWNER
        if (!$this->hasOwnerAccess($seller)) {
            return response()->json([
                'success' => false, 
                'message' => 'Access denied. Only Owner can delete staff.'
            ], 403);
        }

        $staff = Seller::where('parent_id', $seller->id)
            ->where('id', $id)
            ->first();

        if (!$staff) {
            return response()->json(['success' => false, 'message' => 'Hodim topilmadi'], 404);
        }

        try {
            $staff->update(['staff_status' => 'inactive', 'is_hidden' => 1]);
            SellerStaffLog::create([
                'seller_staff_id' => $staff->id,
                'text' => "Hodim deaktivatsiya qilindi: {$staff->firstname} {$staff->lastname}",
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Hodim muvaffaqiyatli deaktivatsiya qilindi',
            ], 200);
        } catch (\Exception $e) {
            Log::error('Hodimni o\'chirishda xato', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Server xatosi'], 500);
        }
    }

    public function getPasswordSms($id)
    {
        $seller = Auth::guard('seller')->user();
        if (!$seller) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        if (!$this->hasOwnerAccess($seller)) {
            return response()->json([
                'success' => false, 
                'message' => 'Access denied. Only Owner can reset staff password.'
            ], 403);
        }

        $staff = Seller::where('parent_id', $seller->id)
            ->where('id', $id)
            ->first();

        if (!$staff) {
            return response()->json(['success' => false, 'message' => 'Hodim topilmadi'], 404);
        }

        if ($staff->password_reset_limit <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Parolni tiklash limiti tugagan. Yangi SMS yuborilmaydi.'
            ], 400);
        }

        try {
            $verifyCode = Str::random(8);
            $staff->update([
                'password' => $verifyCode,
                'password_reset_limit' => $staff->password_reset_limit - 1,
            ]);
            Http::post(route('api.sendSms'), [
                'phone' => $staff->phone_number,
                'msg' => "Kitobchi Business: sizning yangi parolingiz — $verifyCode",
            ]);
            SellerStaffLog::create([
                'seller_staff_id' => $staff->id,
                'text' => "Parol yangilandi va SMS yuborildi: $verifyCode",
            ]);
            return response()->json([
                'success' => true,
                'message' => "Yangi parol xodimning telefon raqamiga SMS orqali yuborildi",
            ], 200);
        } catch (\Exception $e) {
            Log::error('Parolni SMS orqali yuborishda xato', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Server xatosi'], 500);
        }
    }
}