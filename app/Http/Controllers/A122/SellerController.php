<?php

namespace App\Http\Controllers\A122;

use App\Http\Controllers\Controller;
use App\Models\Seller;
use App\Models\SellerBanLog;
use App\Models\SellerContractHistory;
use App\Models\SellerDocument;
use App\Models\SellerOrder;
use App\Models\SellerStaffLog;
use App\Models\SellerTransaction;
use App\Services\PasswordResetService;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use RuntimeException;

class SellerController extends Controller
{
    public function __construct(
        private readonly SmsService $smsService,
        private readonly PasswordResetService $passwordResetService
    ) {
    }

    private function mainScope($query)
    {
        return $query->where(fn($q) => $q->whereNull('parent_id')->orWhere('parent_id', 0));
    }

    private function resolveStoreSeller(Seller $seller): Seller
    {
        if (!$seller->parent_id) {
            return $seller;
        }

        return Seller::query()->findOrFail($seller->parent_id);
    }

    public function index(Request $request)
    {
        $query = Seller::withCount(['books', 'orders'])
            ->withCount([
                'premiumSubscriptions',
            ]);
        $this->mainScope($query);

        $tab = $request->input('tab', 'pending');
        if ($tab !== 'all') $query->where('status', $tab);

        // Shartnoma filteri (dashboard alert / widget'lardan keladi)
        $contractFilter = $request->input('contract');
        if ($contractFilter === 'expired') {
            $query->whereNotNull('contract_expires_at')
                  ->whereDate('contract_expires_at', '<', now()->toDateString())
                  ->where('contract_status', '!=', 'terminated');
        } elseif ($contractFilter === 'expiring') {
            $query->whereNotNull('contract_expires_at')
                  ->whereDate('contract_expires_at', '>=', now()->toDateString())
                  ->whereDate('contract_expires_at', '<=', now()->addDays(30)->toDateString())
                  ->where('contract_status', '!=', 'terminated');
        } elseif ($contractFilter === 'unsigned') {
            // Shartnomasi imzolanmagan sellerlar — admin tomonidan kuzatilishi kerak
            $query->where('contract_signed', false);
        } elseif ($contractFilter === 'signed') {
            $query->where('contract_signed', true);
        }

        if ($s = $request->input('search')) {
            $query->where(fn($q) => $q
                ->where('shop_name', 'like', "%$s%")
                ->orWhere('firstname', 'like', "%$s%")
                ->orWhere('lastname', 'like', "%$s%")
                ->orWhere('phone_number', 'like', "%$s%")
                ->orWhere('region', 'like', "%$s%")
                ->orWhere('id', $s)
            );
        }

        $sellers = $query->latest()->paginate(20)->withQueryString();
        $sellers->getCollection()->transform(function (Seller $seller) {
            $seller->active_warning_count = SellerBanLog::getWarningCount($seller->id);
            return $seller;
        });

        $base = fn() => Seller::where(fn($q) => $q->whereNull('parent_id')->orWhere('parent_id', 0));
        $counts = [
            'pending'  => $base()->where('status', 'pending')->count(),
            'approved' => $base()->where('status', 'approved')->count(),
            'rejected' => $base()->where('status', 'rejected')->count(),
            'blocked'  => $base()->where('status', 'blocked')->count(),
            'all'      => $base()->count(),
        ];

        return view('a122.sellers.index', compact('sellers', 'counts', 'tab'));
    }

    public function show(Seller $seller)
    {
        $storeSeller = $this->resolveStoreSeller($seller);
        $seller->loadCount('books')->load([
            'books',
            'location',
            'documents.uploader',
            'contractHistory.performer',
        ]);
        $storeSellerId = $storeSeller->id;
        $sellerIds = Seller::where('id', $storeSellerId)
            ->orWhere('parent_id', $storeSellerId)
            ->pluck('id');
        $orderCount   = SellerOrder::where('seller_id', $seller->id)->count();
        $totalRevenue = SellerTransaction::where('seller_id', $seller->id)->where('status', 'approved')->sum('amount');
        $recentOrders = SellerOrder::where('seller_id', $seller->id)->latest()->take(8)->get();
        $transactions = SellerTransaction::where('seller_id', $seller->id)->latest()->take(8)->get();
        $staffLogs = SellerStaffLog::whereIn('seller_staff_id', $sellerIds)
            ->latest()
            ->take(50)
            ->get();
        $banLogs = SellerBanLog::where('seller_id', $storeSellerId)
            ->latest()
            ->take(50)
            ->get();
        $warningCount = SellerBanLog::getWarningCount($storeSellerId);
        $isBlocked = $storeSeller->status === 'blocked';

        return view('a122.sellers.show', compact('seller', 'storeSeller', 'orderCount', 'totalRevenue', 'recentOrders', 'transactions', 'staffLogs', 'banLogs', 'warningCount', 'isBlocked'));
    }

    public function edit(Seller $seller)
    {
        return view('a122.sellers.edit', compact('seller'));
    }

    public function update(Request $request, Seller $seller)
    {
        $data = $request->validate([
            // ── Asosiy ma'lumotlar ────────────────────────────────
            'shop_name'          => 'required|string|max:255',
            'firstname'          => 'nullable|string|max:100',
            'lastname'           => 'nullable|string|max:100',
            'phone_number'       => ['required', 'string', Rule::unique('sellers', 'phone_number')->ignore($seller->id)],
            'region'             => 'required|string|max:100',
            'status'             => 'required|in:pending,approved,rejected,blocked',
            'balance'            => 'nullable|numeric|min:0',
            'commission_percent' => 'nullable|integer|min:0|max:100',
            'photo'              => 'nullable|image|mimes:jpg,jpeg,png|max:2048',

            // ── Premium ───────────────────────────────────────────
            // Premium berish — toggle + sana. Toggle off bo'lsa sana e'tiborsiz.
            'isPremiumShop'      => 'nullable|boolean',
            'isPremiumExpiresAt' => 'nullable|date|after:now',

            // ── Shartnoma ─────────────────────────────────────────
            'contract_number'     => 'nullable|string|max:50',
            'contract_signed'     => 'nullable|boolean',
            'contract_signed_at'  => 'nullable|date',
            'contract_expires_at' => 'nullable|date',
            'contract_status'     => 'nullable|in:none,active,expiring,expired,terminated',
            'contract_notes'      => 'nullable|string|max:2000',

            // ── Huquqiy ma'lumot + identifikatsiya ────────────────
            'legal_type'          => 'nullable|in:individual,entrepreneur,llc,jsc',
            'inn'                 => 'nullable|string|max:20',
            'passport_series'     => 'nullable|string|max:10',
            'passport_number'     => 'nullable|string|max:20',
            'passport_issued_by'  => 'nullable|string|max:150',
            'passport_issued_at'  => 'nullable|date',

            // ── Bank rekvizitlari ─────────────────────────────────
            'bank_name'           => 'nullable|string|max:100',
            'bank_account'        => 'nullable|string|max:30',
            'bank_mfo'            => 'nullable|string|max:10',
            'bank_swift'          => 'nullable|string|max:20',
            'payment_card'        => 'nullable|string|max:50',
            'card_holder'         => 'nullable|string|max:100',

            // ── Manzil ────────────────────────────────────────────
            'legal_address'       => 'nullable|string|max:255',
        ]);

        // Checkbox'lar form'da yuborilmasa request'da yo'q — shu sababli
        // ularni aniq bool ko'rinishiga keltiramiz va premium toggle
        // bo'yicha expires_at'ni muvofiqlashtiramiz.
        $isPremium = $request->boolean('isPremiumShop');
        $data['isPremiumShop'] = $isPremium;

        // Shartnoma imzolangan toggle — checkbox: yuborilmasa false.
        $data['contract_signed'] = $request->boolean('contract_signed');
        if ($isPremium) {
            // Toggle yoqilgan bo'lsa, sana majburiy.
            $request->validate([
                'isPremiumExpiresAt' => 'required|date|after:now',
            ]);
            $data['isPremiumExpiresAt'] = $request->input('isPremiumExpiresAt');
        } else {
            // Toggle o'chirilsa, sanani ham tozalaymiz.
            $data['isPremiumExpiresAt'] = null;
        }

        if ($request->filled('password')) {
            $data['password'] = $request->input('password');
        }

        if ($request->hasFile('photo')) {
            if ($seller->photo) Storage::disk('public')->delete($seller->photo);
            $data['photo'] = $request->file('photo')->store('seller_photos', 'public');
        }

        // ── Shartnoma tarixi log ─────────────────────────────────
        // Agar shartnoma raqami / tugash sanasi o'zgargan bo'lsa — audit log
        // yoziladi. `action` ni oldin/hozirgi holatga qarab avtomatik aniqlaymiz.
        $oldExpiry   = $seller->contract_expires_at
            ? Carbon::parse($seller->contract_expires_at)->toDateString()
            : null;
        $newExpiry   = !empty($data['contract_expires_at'])
            ? Carbon::parse($data['contract_expires_at'])->toDateString()
            : null;
        $oldNumber   = $seller->contract_number;
        $newNumber   = $data['contract_number'] ?? null;
        $oldStatus   = $seller->contract_status;
        $newStatus   = $data['contract_status'] ?? $oldStatus;
        $oldSigned   = (bool) $seller->contract_signed;
        $newSigned   = (bool) ($data['contract_signed'] ?? false);

        $contractChanged = $oldExpiry !== $newExpiry
                        || $oldNumber !== $newNumber
                        || $oldStatus !== $newStatus
                        || $oldSigned !== $newSigned;

        DB::transaction(function () use ($seller, $data, $contractChanged, $oldExpiry, $newExpiry, $oldNumber, $newNumber, $oldStatus, $newStatus, $oldSigned, $newSigned) {
            $seller->update($data);

            if ($contractChanged) {
                $action = $this->determineContractAction($oldExpiry, $newExpiry, $oldNumber, $newNumber, $oldStatus, $newStatus);

                // Imzolanish holati o'zgargan bo'lsa, alohida yozuv ham qo'yamiz —
                // signed o'zgarishi audit uchun muhim.
                $signedNote = ($oldSigned !== $newSigned)
                    ? ($newSigned ? " | Shartnoma imzolangan deb belgilandi" : " | Imzolangan belgisi olib tashlandi")
                    : '';

                SellerContractHistory::create([
                    'seller_id'       => $seller->id,
                    'action'          => $action,
                    'contract_number' => $newNumber,
                    'old_expires_at'  => $oldExpiry,
                    'new_expires_at'  => $newExpiry,
                    'notes'           => "Admin edit form orqali yangilandi" . $signedNote,
                    'performed_by'    => Auth::id(),
                    'created_at'      => now(),
                ]);
            }
        });

        return redirect()->route('admin.sellers.show', $seller)->with('success', "Ma'lumotlar yangilandi.");
    }

    /**
     * Shartnoma tarixi uchun `action` ni old/new holatdan avtomatik aniqlash.
     */
    private function determineContractAction(?string $oldExpiry, ?string $newExpiry, ?string $oldNumber, ?string $newNumber, ?string $oldStatus, ?string $newStatus): string
    {
        if ($newStatus === 'terminated' && $oldStatus !== 'terminated') return 'terminated';
        if (empty($oldNumber) && !empty($newNumber))   return 'created';
        if ($oldStatus === 'expired' && in_array($newStatus, ['active', 'expiring'], true)) return 'renewed';
        if ($oldExpiry && $newExpiry && $newExpiry > $oldExpiry) return 'extended';
        return 'updated';
    }

    /**
     * Tez uzaytirish — dashboard/show sahifadan bitta bosishda shartnoma muddatini
     * +N oyga uzaytiradi (default 12 oy). Alohida endpoint bo'lishi view dan ham,
     * index filterdan ham chaqirish uchun qulay.
     */
    public function extendContract(Request $request, Seller $seller)
    {
        $request->validate([
            'months' => 'required|integer|min:1|max:60',
            'notes'  => 'nullable|string|max:1000',
        ]);

        $months = (int) $request->input('months');
        $oldExpiry = $seller->contract_expires_at
            ? Carbon::parse($seller->contract_expires_at)
            : now();
        // Agar shartnoma allaqachon tugagan bo'lsa — bugundan boshlab yangidan sanaymiz
        if ($oldExpiry->isPast()) $oldExpiry = now();

        $newExpiry = $oldExpiry->copy()->addMonths($months);

        DB::transaction(function () use ($seller, $oldExpiry, $newExpiry, $request) {
            // Tez uzaytirish — shartnoma faol va imzolangan deb hisoblanadi.
            $seller->update([
                'contract_expires_at' => $newExpiry->toDateString(),
                'contract_status'     => 'active',
                'contract_signed'     => true,
            ]);

            SellerContractHistory::create([
                'seller_id'       => $seller->id,
                'action'          => 'extended',
                'contract_number' => $seller->contract_number,
                'old_expires_at'  => $oldExpiry->toDateString(),
                'new_expires_at'  => $newExpiry->toDateString(),
                'notes'           => $request->input('notes') ?: "Tez uzaytirish tugmasi",
                'performed_by'    => Auth::id(),
                'created_at'      => now(),
            ]);
        });

        return back()->with('success', "Shartnoma {$months} oyga uzaytirildi. Yangi tugash: {$newExpiry->format('Y-m-d')}");
    }

    /**
     * Hujjat yuklash — seller_documents jadvaliga yozib, faylni storage/public
     * ichiga saqlaydi.
     */
    public function uploadDocument(Request $request, Seller $seller)
    {
        $request->validate([
            'type'        => 'required|in:passport,contract,inn_certificate,license,bank_details,addendum,other',
            'file'        => 'required|file|mimes:pdf,jpg,jpeg,png,webp|max:10240', // 10 MB
            'description' => 'nullable|string|max:500',
        ]);

        $file = $request->file('file');
        $path = $file->store("seller_documents/{$seller->id}", 'public');

        SellerDocument::create([
            'seller_id'     => $seller->id,
            'type'          => $request->input('type'),
            'file_path'     => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type'     => $file->getMimeType(),
            'file_size_kb'  => (int) round($file->getSize() / 1024),
            'uploaded_by'   => Auth::id(),
            'description'   => $request->input('description'),
        ]);

        return back()->with('success', 'Hujjat yuklandi.');
    }

    /**
     * Hujjatni o'chirish — DB dan ham, storage'dan ham.
     */
    public function deleteDocument(Seller $seller, SellerDocument $document)
    {
        if ($document->seller_id !== $seller->id) {
            abort(404);
        }

        if ($document->file_path && !str_starts_with($document->file_path, 'http')) {
            Storage::disk('public')->delete($document->file_path);
        }
        $document->delete();

        return back()->with('success', "Hujjat o'chirildi.");
    }

    public function approve(Seller $seller)
    {
        $seller->update(['status' => 'approved']);
        return back()->with('success', 'Sotuvchi tasdiqlandi.');
    }

    public function reject(Seller $seller)
    {
        $seller->update(['status' => 'rejected']);
        return back()->with('success', 'Sotuvchi rad etildi.');
    }

    /**
     * Do'kon QR tokenini yangilaydi (eski QR ishlamay qoladi).
     * Faqat asosiy seller (parent_id = 0) uchun ishlaydi —
     * staff sellerlar do'kon QR'siga ega emas.
     */
    public function rotateQr(Seller $seller)
    {
        $store = $this->resolveStoreSeller($seller);
        $store->rotateQrToken();

        return back()->with('success', 'Do\'kon QR tokeni yangilandi. Eski QR ishlamay qoladi — yangi QR\'ni do\'konga yopishtiring.');
    }

    public function warn(Request $request, Seller $seller)
    {
        $request->validate([
            'title' => 'required|string|max:120',
            'message' => 'required|string|max:2000',
        ]);

        $storeSeller = $this->resolveStoreSeller($seller);

        DB::transaction(function () use ($request, $storeSeller) {
            SellerBanLog::create([
                'seller_id' => $storeSeller->id,
                'title' => $request->string('title')->toString(),
                'message' => $request->string('message')->toString(),
                'type' => SellerBanLog::TYPE_WARNING,
                'is_read' => false,
            ]);

            if (SellerBanLog::hasReachedBlockThreshold($storeSeller->id)) {
                $storeSeller->update(['status' => 'blocked']);
            }
        });

        $warningCount = SellerBanLog::getWarningCount($storeSeller->id);
        $message = $warningCount >= 3
            ? 'Ogohlantirish yuborildi va sotuvchi avtomatik bloklandi.'
            : "Ogohlantirish yuborildi. Faol ogohlantirishlar soni: {$warningCount}";

        return back()->with('success', $message);
    }

    public function unblock(Request $request, Seller $seller)
    {
        $request->validate([
            'message' => 'nullable|string|max:2000',
        ]);

        $storeSeller = $this->resolveStoreSeller($seller);

        DB::transaction(function () use ($request, $storeSeller) {
            $storeSeller->update(['status' => 'approved']);

            SellerBanLog::create([
                'seller_id' => $storeSeller->id,
                'title' => 'Blokdan chiqarildi',
                'message' => $request->filled('message')
                    ? $request->string('message')->toString()
                    : 'Admin tomonidan blokdan chiqarildi. Ogohlantirish hisobi qayta boshlandi.',
                'type' => SellerBanLog::TYPE_UNBAN,
                'is_read' => false,
            ]);
        });

        return back()->with('success', 'Sotuvchi blokdan chiqarildi.');
    }

    public function resetPassword(Seller $seller)
    {
        if ($seller->status !== 'approved') {
            return back()->with('error', 'Faqat tasdiqlangan sotuvchi uchun parolni yangilash mumkin.');
        }

        try {
            $this->passwordResetService->ensureHasAttempts($seller);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        try {
            $newPassword = $this->passwordResetService->generatePassword();
            $this->smsService->send(
                $seller->phone_number,
                "Kitobchi Business: sizning yangi parolingiz — {$newPassword}"
            );
            $remaining = $this->passwordResetService->applyNewPassword($seller, $newPassword);

            return back()->with('success', "Yangi parol SMS orqali yuborildi. Qolgan urinishlar: {$remaining}");
        } catch (\Throwable $e) {
            return back()->with('error', 'Parolni SMS orqali yuborishda xatolik yuz berdi.');
        }
    }
}
