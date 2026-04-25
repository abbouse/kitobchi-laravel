<?php

namespace App\Http\Controllers\A122;

use App\Http\Controllers\Controller;
use App\Models\CashbackSetting;
use App\Models\CommissionSetting;
use App\Models\DeliveryService;
use App\Models\ProjectSetting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index()
    {
        $project    = ProjectSetting::first();
        $commission = CommissionSetting::orderBy('priceFrom')->get();
        $cashback   = CashbackSetting::orderBy('fromUzs')->get();
        $delivery   = DeliveryService::orderBy('name')->get();

        return view('a122.settings.index', compact('project', 'commission', 'cashback', 'delivery'));
    }

    public function updateVersions(Request $request)
    {
        $request->validate([
            'business_version_ios'     => 'required|string|max:20',
            'business_version_android' => 'required|string|max:20',
            'courier_version_ios'      => 'required|string|max:20',
            'courier_version_android'  => 'required|string|max:20',
            'market_version_ios'       => 'required|string|max:20',
            'market_version_android'   => 'required|string|max:20',
        ]);

        ProjectSetting::first()->update($request->only([
            'business_version_ios', 'business_version_android',
            'courier_version_ios',  'courier_version_android',
            'market_version_ios',   'market_version_android',
        ]));

        return back()->with('success', 'App versiyalari yangilandi.');
    }

    public function storeCommission(Request $request)
    {
        $request->validate([
            'priceFrom' => 'required|integer|min:0',
            'priceTo'   => 'required|integer|gt:priceFrom',
            'percent'   => 'required|integer|min:0|max:100',
        ]);
        CommissionSetting::create($request->only(['priceFrom', 'priceTo', 'percent']));
        return back()->with('success', "Komissiya qoidasi qo'shildi.");
    }

    public function updateCommission(Request $request, CommissionSetting $commissionSetting)
    {
        $request->validate([
            'priceFrom' => 'required|integer|min:0',
            'priceTo'   => 'required|integer|gt:priceFrom',
            'percent'   => 'required|integer|min:0|max:100',
        ]);
        $commissionSetting->update($request->only(['priceFrom', 'priceTo', 'percent']));
        return back()->with('success', 'Komissiya yangilandi.');
    }

    public function destroyCommission(CommissionSetting $commissionSetting)
    {
        $commissionSetting->delete();
        return back()->with('success', "Komissiya qoidasi o'chirildi.");
    }

    public function storeCashback(Request $request)
    {
        $request->validate([
            'fromUzs'  => 'required|integer|min:0',
            'toUzs'    => 'required|integer|gt:fromUzs',
            'cashback' => 'required|integer|min:0|max:100',
        ]);

        CashbackSetting::create($request->only(['fromUzs', 'toUzs', 'cashback']));

        return back()->with('success', "Cashback qoidasi qo'shildi.");
    }

    public function updateCashback(Request $request, CashbackSetting $cashbackSetting)
    {
        $request->validate([
            'fromUzs'  => 'required|integer|min:0',
            'toUzs'    => 'required|integer|gt:fromUzs',
            'cashback' => 'required|integer|min:0|max:100',
        ]);

        $cashbackSetting->update($request->only(['fromUzs', 'toUzs', 'cashback']));

        return back()->with('success', 'Cashback yangilandi.');
    }

    public function destroyCashback(CashbackSetting $cashbackSetting)
    {
        $cashbackSetting->delete();
        return back()->with('success', "Cashback qoidasi o'chirildi.");
    }

    public function storeDelivery(Request $request)
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'type'          => 'required|in:courier_service,mail_service',
            'priceKg'       => 'required|integer|min:0',
            'muddat'        => 'required|integer|min:1',
            'forCountry'    => 'required|string|max:50',
            'capital'       => 'nullable|boolean',
            'freePriceFrom' => 'nullable|integer|min:0',
            'status'        => 'nullable|boolean',
        ]);

        DeliveryService::create([
            ...$validated,
            'capital' => $request->boolean('capital'),
            'status' => $request->boolean('status'),
            'freePriceFrom' => $validated['freePriceFrom'] ?? 0,
        ]);

        return back()->with('success', "Yetkazish xizmati qo'shildi.");
    }

    public function updateDelivery(Request $request, DeliveryService $deliveryService)
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'type'          => 'required|in:courier_service,mail_service',
            'priceKg'       => 'required|integer|min:0',
            'muddat'        => 'required|integer|min:1',
            'forCountry'    => 'required|string|max:50',
            'capital'       => 'nullable|boolean',
            'freePriceFrom' => 'nullable|integer|min:0',
            'status'        => 'nullable|boolean',
        ]);

        $deliveryService->update([
            ...$validated,
            'capital' => $request->boolean('capital'),
            'status' => $request->boolean('status'),
            'freePriceFrom' => $validated['freePriceFrom'] ?? 0,
        ]);

        return back()->with('success', 'Yetkazish xizmati yangilandi.');
    }

    public function destroyDelivery(DeliveryService $deliveryService)
    {
        $deliveryService->delete();

        return back()->with('success', "Yetkazish xizmati o'chirildi.");
    }

    public function updateContacts(Request $request)
    {
        $request->validate([
            'kitobchi_phone' => 'nullable|string|max:50',
            'kitobchi_email' => 'nullable|email|max:100',
            'business_phone' => 'nullable|string|max:50',
            'business_email' => 'nullable|email|max:100',
            'courier_phone'  => 'nullable|string|max:50',
            'courier_email'  => 'nullable|email|max:100',
        ]);

        ProjectSetting::first()->update($request->only([
            'kitobchi_phone', 'kitobchi_email',
            'business_phone', 'business_email',
            'courier_phone',  'courier_email',
        ]));

        return back()->with('success', 'Kontakt ma\'lumotlari yangilandi.');
    }

    public function updateAppFlags(Request $request)
    {
        $request->validate([
            'packaging_price_small' => 'required|integer|min:0',
            'packaging_price_large' => 'required|integer|min:0',
            'packaging_threshold'   => 'required|integer|min:1',
        ]);

        ProjectSetting::first()->update([
            'on_premium'            => $request->boolean('on_premium'),
            'on_reels'              => $request->boolean('on_reels'),
            'ramadan'               => $request->boolean('ramadan'),
            'stop_sales'            => $request->boolean('stop_sales'),
            'packaging_price_small' => $request->packaging_price_small,
            'packaging_price_large' => $request->packaging_price_large,
            'packaging_threshold'   => $request->packaging_threshold,
        ]);

        return back()->with('success', 'App sozlamalari yangilandi.');
    }

    public function updateTelegram(Request $request)
    {
        $defaultIosRedirect = 'https://app3206985527-login.tg.dev';
        $defaultAndroidRedirect = 'https://app2854400165-login.tg.dev/tglogin';

        $request->validate([
            'telegram_client_id'            => 'nullable|string|max:100',
            'telegram_redirect_uri_ios'     => 'nullable|url|max:255',
            'telegram_redirect_uri_android' => 'nullable|url|max:255',
            'telegram_scopes'               => 'nullable|string|max:255',
        ]);

        $iosRedirect = $this->sanitizeTelegramRedirect(
            $request->telegram_redirect_uri_ios,
            $defaultIosRedirect,
            false,
        );
        $androidRedirect = $this->sanitizeTelegramRedirect(
            $request->telegram_redirect_uri_android,
            $defaultAndroidRedirect,
            true,
        );

        ProjectSetting::first()->update([
            'telegram_login_enabled'        => $request->boolean('telegram_login_enabled'),
            'telegram_client_id'            => $request->telegram_client_id,
            'telegram_redirect_uri_ios'     => $iosRedirect,
            'telegram_redirect_uri_android' => $androidRedirect,
            'telegram_scopes'               => $request->telegram_scopes ?: 'openid profile phone',
        ]);

        return back()->with('success', 'Telegram sozlamalari yangilandi.');
    }

    private function sanitizeTelegramRedirect(?string $candidate, string $fallback, bool $requireAndroidPath): string
    {
        $value = trim((string) ($candidate ?: $fallback));
        $parts = parse_url($value);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));
        $path = (string) ($parts['path'] ?? '');

        $isTelegramUniversalLink = $scheme === 'https'
            && str_ends_with($host, '.tg.dev');

        if (!$isTelegramUniversalLink) {
            return $fallback;
        }

        if ($requireAndroidPath && $path !== '/tglogin') {
            return $fallback;
        }

        return $value;
    }
}
