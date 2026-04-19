<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\CashbackSetting;
use App\Models\CommissionSetting;
use App\Models\DeliveryService;
use App\Models\ProjectSetting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    // ── App versiyalari ───────────────────────────────────────────
    public function index()
    {
        $project = ProjectSetting::first();
        $commission = CommissionSetting::orderBy('priceFrom')->get();
        $cashback = CashbackSetting::orderBy('fromUzs')->get();
        $delivery = DeliveryService::orderBy('name')->get();

        return view('panel.settings.index', compact('project', 'commission', 'cashback', 'delivery'));
    }

    // ── App versiyalari saqlash ───────────────────────────────────
    public function updateVersions(Request $request)
    {
        $request->validate([
            'business_version_ios' => 'required|string|max:20',
            'business_version_android' => 'required|string|max:20',
            'courier_version_ios' => 'required|string|max:20',
            'courier_version_android' => 'required|string|max:20',
            'market_version_ios' => 'required|string|max:20',
            'market_version_android' => 'required|string|max:20',
        ]);

        ProjectSetting::first()->update($request->only([
            'business_version_ios', 'business_version_android',
            'courier_version_ios',  'courier_version_android',
            'market_version_ios',   'market_version_android',
        ]));

        return back()->with('success', 'App versiyalari yangilandi.');
    }

    // ── Komissiya ─────────────────────────────────────────────────
    public function storeCommission(Request $request)
    {
        $request->validate([
            'priceFrom' => 'required|integer|min:0',
            'priceTo' => 'required|integer|gt:priceFrom',
            'percent' => 'required|integer|min:0|max:100',
        ]);

        CommissionSetting::create($request->only(['priceFrom', 'priceTo', 'percent']));

        return back()->with('success', 'Komissiya qoidi qo\'shildi.');
    }

    public function updateCommission(Request $request, CommissionSetting $commissionSetting)
    {
        $request->validate([
            'priceFrom' => 'required|integer|min:0',
            'priceTo' => 'required|integer|gt:priceFrom',
            'percent' => 'required|integer|min:0|max:100',
        ]);

        $commissionSetting->update($request->only(['priceFrom', 'priceTo', 'percent']));

        return back()->with('success', 'Komissiya yangilandi.');
    }

    public function destroyCommission(CommissionSetting $commissionSetting)
    {
        $commissionSetting->delete();

        return back()->with('success', "Komissiya qoidasi o'chirildi.");
    }

    // ── Cashback ──────────────────────────────────────────────────
    public function storeCashback(Request $request)
    {
        $request->validate([
            'fromUzs' => 'required|integer|min:0',
            'toUzs' => 'required|integer|gt:fromUzs',
            'cashback' => 'required|integer|min:0|max:100',
        ]);

        CashbackSetting::create($request->only(['fromUzs', 'toUzs', 'cashback']));

        return back()->with('success', 'Cashback qoidasi qo\'shildi.');
    }

    public function updateCashback(Request $request, CashbackSetting $cashbackSetting)
    {
        $request->validate([
            'fromUzs' => 'required|integer|min:0',
            'toUzs' => 'required|integer|gt:fromUzs',
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

    // ── Yetkazish xizmati ─────────────────────────────────────────
    public function storeDelivery(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:courier_service,mail_service',
            'priceKg' => 'required|integer|min:0',
            'muddat' => 'required|integer|min:1',
            'forCountry' => 'required|string|max:50',
            'capital' => 'nullable|boolean',
            'freePriceFrom' => 'nullable|integer|min:0',
            'status' => 'nullable|boolean',
        ]);

        DeliveryService::create([
            'name' => $request->name,
            'type' => $request->type,
            'priceKg' => $request->priceKg,
            'muddat' => $request->muddat,
            'forCountry' => $request->forCountry,
            'capital' => $request->boolean('capital'),
            'freePriceFrom' => $request->freePriceFrom ?? 0,
            'status' => $request->boolean('status'),
        ]);

        return back()->with('success', "Yetkazish xizmati qo'shildi.");
    }

    public function updateDelivery(Request $request, DeliveryService $deliveryService)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:courier_service,mail_service',
            'priceKg' => 'required|integer|min:0',
            'muddat' => 'required|integer|min:1',
            'forCountry' => 'required|string|max:50',
            'capital' => 'nullable|boolean',
            'freePriceFrom' => 'nullable|integer|min:0',
            'status' => 'nullable|boolean',
        ]);

        $deliveryService->update([
            'name' => $request->name,
            'type' => $request->type,
            'priceKg' => $request->priceKg,
            'muddat' => $request->muddat,
            'forCountry' => $request->forCountry,
            'capital' => $request->boolean('capital'),
            'freePriceFrom' => $request->freePriceFrom ?? 0,
            'status' => $request->boolean('status'),
        ]);

        return back()->with('success', 'Yetkazish xizmati yangilandi.');
    }

    public function destroyDelivery(DeliveryService $deliveryService)
    {
        $deliveryService->delete();

        return back()->with('success', "Yetkazish xizmati o'chirildi.");
    }
}
