<?php

namespace App\Http\Controllers\A122;

use App\Http\Controllers\Controller;
use App\Models\CashbackSetting;
use App\Models\CommissionSetting;
use App\Models\DeliveryService;
use App\Models\ProjectSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SettingsController extends Controller
{
    public function index(Request $request)
    {
        if ($request->input('tab') === 'delivery') {
            return redirect()->route('admin.logistics.index');
        }

        $project    = $this->projectSettings();
        $commission = CommissionSetting::orderBy('priceFrom')->get();
        $cashback   = CashbackSetting::orderBy('type')->orderBy('fromUzs')->get();
        $delivery   = DeliveryService::orderBy('name')->get();

        // Admin sahifasida ikkita guruhga bo'lib ko'rsatish uchun.
        $cashbackDelivery = $cashback->where('type', CashbackSetting::TYPE_DELIVERY)->values();
        $cashbackPickup   = $cashback->where('type', CashbackSetting::TYPE_PICKUP)->values();

        return view('a122.settings.index', compact(
            'project', 'commission', 'cashback',
            'cashbackDelivery', 'cashbackPickup',
            'delivery'
        ));
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

        $this->projectSettings()->update($request->only([
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
            'type'     => 'nullable|in:delivery,pickup',
        ]);

        CashbackSetting::create([
            'fromUzs'  => $request->integer('fromUzs'),
            'toUzs'    => $request->integer('toUzs'),
            'cashback' => $request->integer('cashback'),
            'type'     => $request->input('type', CashbackSetting::TYPE_DELIVERY),
        ]);

        return back()->with('success', "Cashback qoidasi qo'shildi.");
    }

    public function updateCashback(Request $request, CashbackSetting $cashbackSetting)
    {
        $request->validate([
            'fromUzs'  => 'required|integer|min:0',
            'toUzs'    => 'required|integer|gt:fromUzs',
            'cashback' => 'required|integer|min:0|max:100',
            'type'     => 'nullable|in:delivery,pickup',
        ]);

        $cashbackSetting->update([
            'fromUzs'  => $request->integer('fromUzs'),
            'toUzs'    => $request->integer('toUzs'),
            'cashback' => $request->integer('cashback'),
            'type'     => $request->input('type', $cashbackSetting->type ?? CashbackSetting::TYPE_DELIVERY),
        ]);

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

        $this->projectSettings()->update($request->only([
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
            'review_cashback_amount' => 'nullable|integer|min:0|max:100000',
            'ai_bot_extra_notes' => 'nullable|string|max:2000',
        ]);

        $settings = $this->projectSettings();

        $settings->update([
            'on_premium'            => $request->boolean('on_premium'),
            'on_reels'              => $request->boolean('on_reels'),
            'ramadan'               => $request->boolean('ramadan'),
            'stop_sales'            => $request->boolean('stop_sales'),
            'show_home_special_sections' => $request->has('show_home_special_sections')
                ? $request->boolean('show_home_special_sections')
                : (bool) ($settings->show_home_special_sections ?? true),
            'packaging_price_small' => $request->packaging_price_small,
            'packaging_price_large' => $request->packaging_price_large,
            'packaging_threshold'   => $request->packaging_threshold,
            // Izoh uchun keshbek
            'review_cashback_enabled' => $request->boolean('review_cashback_enabled'),
            'review_cashback_amount'  => $request->filled('review_cashback_amount')
                ? (int) $request->input('review_cashback_amount')
                : (int) ($settings->review_cashback_amount ?? 100),
            // AI bot qo'llanmasi
            'ai_bot_extra_notes' => trim((string) $request->input('ai_bot_extra_notes', '')) ?: null,
        ]);

        Cache::forget('project_settings');
        \App\Services\ReviewCashbackService::forgetSettingsCache();
        \App\Services\ChatBotKnowledgeService::forgetCache();

        return back()->with('success', 'App sozlamalari yangilandi.');
    }

    public function updateCourierBonus(Request $request)
    {
        $validated = $request->validate([
            'courier_base_fee' => 'required|integer|min:0|max:1000000',
            'courier_price_per_km' => 'required|integer|min:0|max:1000000',
            'courier_min_fee' => 'required|integer|min:0|max:1000000',
            'seller_courier_min_delivery_price' => 'required|integer|min:0|max:1000000',
            'courier_bonus_rules' => 'nullable|array',
            'courier_bonus_rules.*.from_km' => 'nullable|numeric|min:0|max:10000',
            'courier_bonus_rules.*.to_km' => 'nullable|numeric|min:0|max:10000',
            'courier_bonus_rules.*.bonus_amount' => 'nullable|integer|min:0|max:1000000',
        ]);

        $rules = collect($validated['courier_bonus_rules'] ?? [])
            ->map(function (array $rule) {
                $from = max(0, (float) ($rule['from_km'] ?? 0));
                $to = isset($rule['to_km']) && $rule['to_km'] !== '' ? max(0, (float) $rule['to_km']) : null;
                if ($to !== null && $to < $from) {
                    $to = $from;
                }

                return [
                    'from_km' => $from,
                    'to_km' => $to,
                    'bonus_amount' => max(0, (int) ($rule['bonus_amount'] ?? 0)),
                ];
            })
            ->filter(fn (array $rule) => $rule['bonus_amount'] > 0)
            ->values()
            ->all();

        $this->projectSettings()->update([
            'courier_base_fee' => $validated['courier_base_fee'],
            'courier_price_per_km' => $validated['courier_price_per_km'],
            'courier_min_fee' => $validated['courier_min_fee'],
            'seller_courier_min_delivery_price' => $validated['seller_courier_min_delivery_price'],
            'courier_bonus_rules' => $rules,
        ]);

        return back()->with('success', 'Kuryer bonus sozlamalari yangilandi.');
    }

    public function updateFinance(Request $request)
    {
        $validated = $request->validate([
            'tax_mode' => 'required|in:fixed,profit_percent',
            'tax_fixed_uzs' => 'required|integer|min:0|max:100000000000',
            'tax_profit_percent' => 'required|numeric|min:0|max:100',
            'payment_provider_percent' => 'required|numeric|min:0|max:100',
        ]);

        $this->projectSettings()->update($validated);

        return back()->with('success', 'Moliyaviy sozlamalar yangilandi.');
    }

    public function updateTelegram(Request $request)
    {
        $defaultIosRedirect = 'https://app3206985527-login.tg.dev';
        $defaultAndroidRedirect = 'https://app2854400165-login.tg.dev/tglogin';

        $request->validate([
            'telegram_client_id'            => 'nullable|string|max:100',
            'telegram_redirect_uri_ios'     => 'nullable|string|max:255',
            'telegram_redirect_uri_android' => 'nullable|string|max:255',
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

        $this->projectSettings()->update([
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

        $isSupportedCustomScheme = $scheme === 'kitobchi'
            && (
                (!$requireAndroidPath && $host === 'tglogin')
                || ($requireAndroidPath && $host === 'telegram-login')
            );

        $isTelegramUniversalLink = $requireAndroidPath
            && $scheme === 'https'
            && str_ends_with($host, '.tg.dev');

        if (!$isTelegramUniversalLink && !$isSupportedCustomScheme) {
            return $fallback;
        }

        return $value;
    }

    private function projectSettings(): ProjectSetting
    {
        return ProjectSetting::query()->firstOrCreate([]);
    }
}
