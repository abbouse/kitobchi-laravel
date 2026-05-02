<?php

namespace App\Http\Controllers\A122;

use App\Http\Controllers\Controller;
use App\Models\Couriers;
use App\Models\CourierBanLog;
use App\Models\CourierDocument;
use App\Models\CourierOrder;
use App\Models\CourierTransaction;
use App\Services\PasswordResetService;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use RuntimeException;

class CourierController extends Controller
{
    public function __construct(
        private readonly SmsService $smsService,
        private readonly PasswordResetService $passwordResetService
    ) {
    }

    public function index(Request $request)
    {
        $query = Couriers::query();

        $tab = $request->input('tab', 'all');
        match ($tab) {
            'approved' => $query->where('status', 'approved'),
            'pending'  => $query->where('status', 'pending'),
            'rejected' => $query->where('status', 'rejected'),
            'blocked'  => $query->where('status', 'blocked'),
            default    => null,
        };

        // Verifikatsiya filteri (dashboardlardan keladi yoki to'g'ridan-to'g'ri URL)
        $verification = $request->input('verification');
        if (in_array($verification, ['unverified', 'pending', 'verified', 'rejected'], true)) {
            $query->where('verification_status', $verification);
        }

        // Transport bo'yicha filter
        $transport = $request->input('transport');
        if (in_array($transport, ['foot', 'bicycle', 'motorcycle', 'car'], true)) {
            $query->where('transport_type', $transport);
        }

        if ($s = $request->input('search')) {
            $query->where(fn($q) => $q
                ->where('first_name',     'like', "%$s%")
                ->orWhere('last_name',    'like', "%$s%")
                ->orWhere('phone_number', 'like', "%$s%")
                ->orWhere('region',       'like', "%$s%")
                ->orWhere('id', $s)
            );
        }

        $couriers = $query->latest()->paginate(20)->withQueryString();

        $counts = [
            'all'      => Couriers::count(),
            'approved' => Couriers::where('status', 'approved')->count(),
            'pending'  => Couriers::where('status', 'pending')->count(),
            'rejected' => Couriers::where('status', 'rejected')->count(),
            'blocked'  => Couriers::where('status', 'blocked')->count(),
        ];

        return view('a122.couriers.index', compact('couriers', 'counts', 'tab'));
    }

    public function create()
    {
        return view('a122.couriers.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'first_name'   => 'required|string|max:25',
            'last_name'    => 'required|string|max:25',
            'phone_number' => 'required|string|unique:couriers,phone_number',
            'region'       => 'required|string|max:50',
            'password'     => 'required|string|min:6',
            'status'       => 'nullable|in:approved,pending,rejected',
            'photo'        => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'transport_type' => 'nullable|in:foot,bicycle,motorcycle,car',
        ]);

        $data['status'] = $data['status'] ?? 'pending';
        $data['transport_type'] = $data['transport_type'] ?? 'foot';

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('courier_photos', 'public');
        }

        $courier = Couriers::create($data);
        return redirect()->route('admin.couriers.show', $courier)->with('success', "Kuryer yaratildi.");
    }

    public function show(Couriers $courier)
    {
        $courier->load(['documents.uploader']);

        $orderCount  = CourierOrder::where('courier_id', $courier->id)->count();
        $totalEarned = CourierTransaction::where('courier_id', $courier->id)
            ->where('status', 'approved')->sum('netAmount');
        $recentOrders = CourierOrder::with([
                'user:id,name,lastname,phone_number',
                'order:id,status,paymentStatus,deliveryType,amount',
            ])
            ->where('courier_id', $courier->id)
            ->latest()
            ->take(8)
            ->get();
        $recentTransactions = CourierTransaction::where('courier_id', $courier->id)
            ->latest()
            ->take(8)
            ->get();

        $banLogs      = CourierBanLog::where('courier_id', $courier->id)
            ->latest()->take(50)->get();
        $warningCount = CourierBanLog::getWarningCount($courier->id);

        return view('a122.couriers.show', compact(
            'courier', 'orderCount', 'totalEarned', 'recentOrders',
            'recentTransactions', 'banLogs', 'warningCount'
        ));
    }

    public function edit(Couriers $courier)
    {
        $courier->load(['documents.uploader']);
        return view('a122.couriers.edit', compact('courier'));
    }

    public function update(Request $request, Couriers $courier)
    {
        $data = $request->validate([
            // ── Asosiy ──────────────────────────────────────────
            'first_name'   => 'required|string|max:25',
            'last_name'    => 'required|string|max:25',
            'phone_number' => ['required', 'string', Rule::unique('couriers', 'phone_number')->ignore($courier->id)],
            'region'       => 'required|string|max:50',
            'status'       => 'required|in:approved,pending,rejected',
            'balance'      => 'nullable|numeric|min:0',
            'photo'        => 'nullable|image|mimes:jpg,jpeg,png|max:2048',

            // ── Transport ───────────────────────────────────────
            'transport_type'        => 'nullable|in:foot,bicycle,motorcycle,car',
            'vehicle_brand'         => 'nullable|string|max:50',
            'vehicle_model'         => 'nullable|string|max:50',
            'vehicle_color'         => 'nullable|string|max:30',
            'vehicle_plate_number'  => 'nullable|string|max:20',

            // ── Identifikatsiya ─────────────────────────────────
            'inn'                  => 'nullable|string|max:20',
            'birthdate'            => 'nullable|date|before:today',
            'passport_series'      => 'nullable|string|max:10',
            'passport_number'      => 'nullable|string|max:20',
            'passport_issued_by'   => 'nullable|string|max:150',
            'passport_issued_at'   => 'nullable|date',

            // ── Haydovchi guvohnomasi ───────────────────────────
            'driver_license_number'      => 'nullable|string|max:20',
            'driver_license_issued_at'   => 'nullable|date',
            'driver_license_expires_at'  => 'nullable|date',

            // ── Bank/karta + manzil ─────────────────────────────
            'payment_card'  => 'nullable|string|max:50',
            'card_holder'   => 'nullable|string|max:100',
            'home_address'  => 'nullable|string|max:255',

            // ── Verifikatsiya ───────────────────────────────────
            'verification_status' => 'nullable|in:unverified,pending,verified,rejected',
            'verification_notes'  => 'nullable|string|max:2000',
        ]);

        if ($request->filled('password')) {
            $data['password'] = $request->input('password');
        }

        if ($request->hasFile('photo')) {
            if ($courier->photo) Storage::disk('public')->delete($courier->photo);
            $data['photo'] = $request->file('photo')->store('courier_photos', 'public');
        }

        // Verifikatsiya holati o'zgargan bo'lsa va yangi holat 'verified' bo'lsa —
        // verified_at sanasini avtomatik yozamiz.
        $oldVerification = $courier->verification_status;
        $newVerification = $data['verification_status'] ?? $oldVerification;
        if ($oldVerification !== $newVerification) {
            if ($newVerification === 'verified') {
                $data['verified_at'] = now();
            } elseif (in_array($newVerification, ['unverified', 'rejected'])) {
                $data['verified_at'] = null;
            }
        }

        $courier->update($data);
        return redirect()->route('admin.couriers.show', $courier)->with('success', "Kuryer yangilandi.");
    }

    public function approve(Couriers $courier)
    {
        $courier->update(['status' => 'approved']);
        return back()->with('success', 'Kuryer tasdiqlandi.');
    }

    public function reject(Couriers $courier)
    {
        $courier->update(['status' => 'rejected']);
        return back()->with('success', 'Kuryer rad etildi.');
    }

    // ─────────────────────────────────────────────────────────────
    // OGOHLANTIRISH / BLOK
    // ─────────────────────────────────────────────────────────────

    /**
     * Kuryerga ogohlantirish yuborish. 3-ogohlantirishdan keyin avtomatik bloklanadi.
     */
    public function warn(Request $request, Couriers $courier)
    {
        $request->validate([
            'title'   => 'required|string|max:120',
            'message' => 'required|string|max:2000',
        ]);

        DB::transaction(function () use ($request, $courier) {
            CourierBanLog::create([
                'courier_id' => $courier->id,
                'title'      => $request->string('title')->toString(),
                'message'    => $request->string('message')->toString(),
                'type'       => CourierBanLog::TYPE_WARNING,
                'is_read'    => false,
            ]);

            if (CourierBanLog::hasReachedBlockThreshold($courier->id)) {
                $courier->update(['status' => 'blocked']);
            }
        });

        $warningCount = CourierBanLog::getWarningCount($courier->id);
        $message = $warningCount >= 3
            ? "Ogohlantirish yuborildi va kuryer avtomatik bloklandi (3/3)."
            : "Ogohlantirish yuborildi. Faol ogohlantirishlar: {$warningCount}/3";

        return back()->with('success', $message);
    }

    /**
     * Kuryerni blokdan chiqarish. Ogohlantirish hisobi ham qayta boshlanadi
     * (chunki activeWarningsQuery() oxirgi unbandan keyingilarini sanaydi).
     */
    public function unblock(Request $request, Couriers $courier)
    {
        $request->validate([
            'message' => 'nullable|string|max:2000',
        ]);

        DB::transaction(function () use ($request, $courier) {
            $courier->update(['status' => 'approved']);

            CourierBanLog::create([
                'courier_id' => $courier->id,
                'title'      => 'Blokdan chiqarildi',
                'message'    => $request->filled('message')
                    ? $request->string('message')->toString()
                    : 'Admin tomonidan blokdan chiqarildi. Ogohlantirish hisobi qayta boshlandi.',
                'type'       => CourierBanLog::TYPE_UNBAN,
                'is_read'    => false,
            ]);
        });

        return back()->with('success', 'Kuryer blokdan chiqarildi.');
    }

    public function destroy(Couriers $courier)
    {
        if ($courier->photo) Storage::disk('public')->delete($courier->photo);
        $courier->delete();
        return redirect()->route('admin.couriers.index')->with('success', "Kuryer o'chirildi.");
    }

    public function resetPassword(Couriers $courier)
    {
        if ($courier->status !== 'approved') {
            return back()->with('error', 'Faqat tasdiqlangan kuryer uchun parolni yangilash mumkin.');
        }

        try {
            $this->passwordResetService->ensureHasAttempts($courier);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        try {
            $newPassword = $this->passwordResetService->generatePassword();
            $this->smsService->send(
                $courier->phone_number,
                "Kitobchi Express: sizning yangi parolingiz — {$newPassword}"
            );
            $remaining = $this->passwordResetService->applyNewPassword($courier, $newPassword);

            return back()->with('success', "Yangi parol SMS orqali yuborildi. Qolgan urinishlar: {$remaining}");
        } catch (\Throwable $e) {
            return back()->with('error', 'Parolni SMS orqali yuborishda xatolik yuz berdi.');
        }
    }

    // ─────────────────────────────────────────────────────────────
    // HUJJAT UPLOAD / DELETE
    // ─────────────────────────────────────────────────────────────

    public function uploadDocument(Request $request, Couriers $courier)
    {
        $validated = $request->validate([
            'type'        => 'required|in:passport,driver_license,vehicle_reg,vehicle_insurance,inn_certificate,medical_cert,photo_with_passport,other',
            'file'        => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240', // 10 MB
            'description' => 'nullable|string|max:500',
        ]);

        $file = $request->file('file');
        $stored = $file->store("courier_documents/{$courier->id}", 'public');

        CourierDocument::create([
            'courier_id'    => $courier->id,
            'type'          => $validated['type'],
            'file_path'     => $stored,
            'original_name' => $file->getClientOriginalName(),
            'mime_type'     => $file->getMimeType(),
            'file_size_kb'  => (int) round($file->getSize() / 1024),
            'uploaded_by'   => Auth::id(),
            'description'   => $validated['description'] ?? null,
        ]);

        return back()->with('success', 'Hujjat yuklandi.');
    }

    public function deleteDocument(Couriers $courier, CourierDocument $document)
    {
        if ($document->courier_id !== $courier->id) {
            abort(404);
        }

        if ($document->file_path && !str_starts_with($document->file_path, 'http')) {
            Storage::disk('public')->delete($document->file_path);
        }

        $document->delete();
        return back()->with('success', "Hujjat o'chirildi.");
    }
}
