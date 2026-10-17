<?php

namespace App\Http\Controllers\Boshqaruv;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Couriers;
use App\Models\Seller;
use App\Services\Trust\AccountRestrictionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Boshqaruv: do'kon va kuryerni cheklash (sabab + muddat), pul yechishni
 * muzlatish va balansni daftar orqali tuzatish.
 */
class AccountRestrictionController extends Controller
{
    public function __construct(private readonly AccountRestrictionService $service) {}

    private function admin(): Admin
    {
        /** @var Admin $admin */
        $admin = Auth::guard('panel')->user();

        return $admin;
    }

    /** Pulga tegadigan amallar — faqat moliya moduli yoki superadmin. */
    private function ensureFinance(): ?RedirectResponse
    {
        $admin = $this->admin();
        if ($admin->isSuperAdmin() || $admin->hasPermission('finance')) {
            return null;
        }

        return back()->with('error', 'Bu amal faqat moliya bo‘limi yoki superadmin uchun.');
    }

    private function blockData(Request $request): array
    {
        return $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:500'],
            'duration' => ['required', Rule::in(array_keys(AccountRestrictionService::DURATIONS))],
        ]);
    }

    public function blockSeller(Request $request, Seller $seller): RedirectResponse
    {
        $data = $this->blockData($request);
        $until = AccountRestrictionService::untilFor($data['duration']);
        $store = $this->service->blockSeller($seller, $this->admin(), trim($data['reason']), $until);

        return back()->with('success', '"'.$store->shop_name.'" '.($until ? $until->format('d.m.Y H:i').' gacha cheklandi' : 'muddatsiz bloklandi').'. Do‘kon va xodimlari ilovadan chiqarildi, mahsulotlari yashirildi.');
    }

    /** Do'konni xaridorlardan yashirish (blokdan yengil): do'kon ishlaydi, lekin vitrina yopiq. */
    public function sellerVisibility(Request $request, Seller $seller): RedirectResponse
    {
        $data = $request->validate(['hidden' => ['required', 'boolean']]);
        $store = $seller->storeAccount();
        $store->forceFill(['is_hidden' => (bool) $data['hidden']])->save();

        return back()->with('success', $data['hidden'] ? 'Do‘kon va mahsulotlari xaridorlardan yashirildi.' : 'Do‘kon yana vitrinada.');
    }

    public function freezeSeller(Request $request, Seller $seller): RedirectResponse
    {
        if ($deny = $this->ensureFinance()) {
            return $deny;
        }
        $data = $request->validate([
            'frozen' => ['required', 'boolean'],
            'reason' => ['nullable', 'required_if:frozen,1,true', 'string', 'max:500'],
        ]);
        $this->service->setSellerPayoutFreeze($seller, (bool) $data['frozen'], $data['reason'] ?? null);

        return back()->with('success', $data['frozen'] ? 'Do‘kon uchun pul yechish muzlatildi.' : 'Pul yechish qayta ochildi.');
    }

    public function adjustSeller(Request $request, Seller $seller): RedirectResponse
    {
        if ($deny = $this->ensureFinance()) {
            return $deny;
        }
        $data = $request->validate([
            'amount' => ['required', 'integer', 'not_in:0', 'between:-100000000,100000000'],
            'reason' => ['required', 'string', 'min:5', 'max:300'],
        ]);
        try {
            $entry = $this->service->adjustSellerBalance($seller, $this->admin(), (int) $data['amount'], trim($data['reason']));
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Balans tuzatildi. Yangi balans: '.number_format((int) $entry->balance_after, 0, '.', ' ').' so‘m.');
    }

    public function blockCourier(Request $request, Couriers $courier): RedirectResponse
    {
        $data = $this->blockData($request);
        $until = AccountRestrictionService::untilFor($data['duration']);
        $result = $this->service->blockCourier($courier, $this->admin(), trim($data['reason']), $until);

        $msg = 'Kuryer '.($until ? $until->format('d.m.Y H:i').' gacha cheklandi' : 'muddatsiz bloklandi').' va ilovadan chiqarildi.';
        if ($result['released'] > 0) {
            $msg .= " {$result['released']} ta olib ketilmagan buyurtma boshqa kuryerlarga qaytarildi.";
        }
        if ($result['kept'] !== []) {
            $msg .= ' Diqqat: kitob kuryer qo‘lida — #'.implode(', #', $result['kept']).' buyurtmalarni qo‘lda hal qiling.';
        }

        return back()->with($result['kept'] === [] ? 'success' : 'error', $msg);
    }

    public function freezeCourier(Request $request, Couriers $courier): RedirectResponse
    {
        if ($deny = $this->ensureFinance()) {
            return $deny;
        }
        $data = $request->validate([
            'frozen' => ['required', 'boolean'],
            'reason' => ['nullable', 'required_if:frozen,1,true', 'string', 'max:500'],
        ]);
        $this->service->setCourierPayoutFreeze($courier, (bool) $data['frozen'], $data['reason'] ?? null);

        return back()->with('success', $data['frozen'] ? 'Kuryer uchun pul yechish muzlatildi.' : 'Pul yechish qayta ochildi.');
    }

    public function adjustCourier(Request $request, Couriers $courier): RedirectResponse
    {
        if ($deny = $this->ensureFinance()) {
            return $deny;
        }
        $data = $request->validate([
            'amount' => ['required', 'integer', 'not_in:0', 'between:-100000000,100000000'],
            'reason' => ['required', 'string', 'min:5', 'max:300'],
        ]);
        try {
            $this->service->adjustCourierBalance($courier, $this->admin(), (int) $data['amount'], trim($data['reason']));
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Kuryer balansi tuzatildi: '.number_format((int) $courier->fresh()->balance, 0, '.', ' ').' so‘m.');
    }
}
