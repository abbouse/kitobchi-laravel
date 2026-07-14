<?php

namespace App\Http\Controllers\A122;

use App\Http\Controllers\Controller;
use App\Models\DeliveryService;
use App\Models\DeliveryZoneRule;
use App\Services\DeliveryEtaSyncService;
use App\Services\DeliveryZoneResolverService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class LogisticsController extends Controller
{
    public function __construct(
        private readonly DeliveryZoneResolverService $deliveryZoneResolverService,
        private readonly DeliveryEtaSyncService $deliveryEtaSyncService,
    ) {}

    public function index(Request $request)
    {
        $services = DeliveryService::orderBy('name')->get();
        $rulesQuery = DeliveryZoneRule::with('deliveryService');
        $codFilter = (string) $request->query('cod_filter', '');

        if ($codFilter === 'on') {
            $rulesQuery->where('cod_allowed', true);
        } elseif ($codFilter === 'off') {
            $rulesQuery->where('cod_allowed', false);
        }

        $rules = $rulesQuery
            ->orderByDesc('priority')
            ->orderBy('zone_name')
            ->get();

        $codEnabledRulesCount = DeliveryZoneRule::query()
            ->active()
            ->where('cod_allowed', true)
            ->whereHas('deliveryService', fn ($query) => $query
                ->where('status', true)
                ->where('type', 'courier_service'))
            ->count();

        $preview = null;
        if ($request->filled('preview_lat') && $request->filled('preview_lon')) {
            $validated = $request->validate([
                'preview_lat' => 'required|numeric',
                'preview_lon' => 'required|numeric',
                'preview_address' => 'nullable|string|max:500',
                'preview_country_code' => 'nullable|string|max:8',
                'preview_seller_count' => 'nullable|integer|min:1|max:20',
                'preview_total_sum' => 'nullable|integer|min:0|max:100000000',
            ]);

            $location = (object) [
                'lat' => (float) $validated['preview_lat'],
                'lon' => (float) $validated['preview_lon'],
                'fullAddress' => $validated['preview_address'] ?? null,
                'country_code' => strtoupper((string) ($validated['preview_country_code'] ?? '')),
            ];

            $preview = [
                'location' => $location,
                'seller_count' => (int) ($validated['preview_seller_count'] ?? 1),
                'total_sum' => (int) ($validated['preview_total_sum'] ?? 0),
                'offers' => $this->deliveryZoneResolverService->resolveOffers(
                    $location,
                    (int) ($validated['preview_seller_count'] ?? 1),
                    (int) ($validated['preview_total_sum'] ?? 0),
                ),
            ];
        }

        return view('a122.logistics.index', compact(
            'services',
            'rules',
            'preview',
            'codFilter',
            'codEnabledRulesCount',
        ));
    }

    public function store(Request $request)
    {
        $this->normalizePolygonInput($request);
        $validated = $this->validateRule($request);

        DeliveryZoneRule::create($this->payload($validated, $request));

        return back()->with('success', "Logistika qoidasi qo'shildi.");
    }

    public function update(Request $request, DeliveryZoneRule $logistic)
    {
        $this->normalizePolygonInput($request);
        $validated = $this->validateRule($request);
        $previousEtaDays = $logistic->eta_days;

        $logistic->update($this->payload($validated, $request));
        $freshLogistic = $logistic->fresh();

        $affectedOrders = $this->deliveryEtaSyncService->syncZoneRuleEtaChange(
            $freshLogistic,
            $previousEtaDays,
            $freshLogistic?->eta_days,
        );

        if ($affectedOrders > 0) {
            Log::info('Delivery zone ETA synced to active orders', [
                'delivery_zone_rule_id' => $logistic->id,
                'previous_eta_days' => $previousEtaDays,
                'new_eta_days' => $logistic->eta_days,
                'affected_orders' => $affectedOrders,
            ]);
        }

        $message = 'Logistika qoidasi yangilandi.';
        if ($affectedOrders > 0) {
            $message .= " {$affectedOrders} ta faol buyurtmaning muddat prognozi ham yangilandi.";
        }

        return back()->with('success', $message);
    }

    public function destroy(DeliveryZoneRule $logistic)
    {
        $logistic->delete();

        return back()->with('success', "Logistika qoidasi o'chirildi.");
    }

    public function toggleActive(DeliveryZoneRule $logistic)
    {
        $logistic->update(['is_active' => ! $logistic->is_active]);

        return back()->with('success', $logistic->is_active ? 'Zona yoqildi.' : "Zona o'chirildi (nofaol).");
    }

    private function validateRule(Request $request): array
    {
        return $request->validate([
            'zone_name' => 'required|string|max:255',
            'country_code' => 'required|string|max:8',
            'scope' => 'required|in:country,radius,polygon',
            'region_name' => 'nullable|string|max:150',
            'district_name' => 'nullable|string|max:150',
            'city_name' => 'nullable|string|max:150',
            'center_lat' => 'nullable|numeric',
            'center_lon' => 'nullable|numeric',
            'radius_km' => 'nullable|numeric|min:0.1|max:5000',
            'polygon' => 'nullable|array|max:1000',
            'polygon.*' => 'array|size:2',
            'polygon.*.*' => 'numeric',
            'color' => 'nullable|string|max:16',
            'delivery_service_id' => 'required|integer|exists:delivery_services,id',
            'priority' => 'required|integer|min:0|max:10000',
            'base_price' => 'nullable|integer|min:0|max:100000000',
            'additional_seller_percent' => 'nullable|numeric|min:0|max:1000',
            'free_price_from' => 'nullable|integer|min:0|max:100000000',
            'eta_days' => 'nullable|integer|min:0|max:365',
            'cod_allowed' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'notes' => 'nullable|string|max:5000',
        ], [
            'center_lat.numeric' => 'Markaz latitude son bo‘lishi kerak.',
            'center_lon.numeric' => 'Markaz longitude son bo‘lishi kerak.',
        ]);
    }

    /**
     * Frontend polygonni JSON string ko'rinishida yuboradi — uni massivga aylantiramiz.
     */
    private function normalizePolygonInput(Request $request): void
    {
        $polygon = $request->input('polygon');

        if (is_string($polygon)) {
            $trimmed = trim($polygon);
            $decoded = $trimmed === '' ? null : json_decode($trimmed, true);
            $request->merge(['polygon' => is_array($decoded) ? $decoded : null]);
        }
    }

    private function payload(array $validated, Request $request): array
    {
        $scope = $validated['scope'] ?? 'radius';

        if ($scope === 'radius') {
            $request->validate([
                'center_lat' => 'required|numeric',
                'center_lon' => 'required|numeric',
                'radius_km' => 'required|numeric|min:0.1|max:5000',
            ]);
        }

        $polygon = null;
        $bbox = ['min_lat' => null, 'min_lon' => null, 'max_lat' => null, 'max_lon' => null];

        if ($scope === 'polygon') {
            $polygon = DeliveryZoneRule::sanitizePolygon($validated['polygon'] ?? $request->input('polygon'));

            if ($polygon === null) {
                throw ValidationException::withMessages([
                    'polygon' => "Polygon zonasi kamida 3 ta nuqtadan iborat bo'lishi kerak. Xaritada zonani chizing.",
                ]);
            }

            $box = DeliveryZoneRule::boundingBox($polygon);
            $bbox = [
                'min_lat' => $box['min_lat'],
                'min_lon' => $box['min_lon'],
                'max_lat' => $box['max_lat'],
                'max_lon' => $box['max_lon'],
            ];
        }

        return [
            'zone_name' => $validated['zone_name'],
            'country_code' => strtoupper($validated['country_code']),
            'scope' => $scope,
            'region_name' => $validated['region_name'] ?? null,
            'district_name' => $validated['district_name'] ?? null,
            'city_name' => $validated['city_name'] ?? null,
            'center_lat' => $scope === 'radius' ? (float) $validated['center_lat'] : null,
            'center_lon' => $scope === 'radius' ? (float) $validated['center_lon'] : null,
            'radius_km' => $scope === 'radius' ? (float) $validated['radius_km'] : null,
            'polygon' => $polygon,
            'color' => $validated['color'] ?? null,
            'bbox_min_lat' => $bbox['min_lat'],
            'bbox_min_lon' => $bbox['min_lon'],
            'bbox_max_lat' => $bbox['max_lat'],
            'bbox_max_lon' => $bbox['max_lon'],
            'delivery_service_id' => (int) $validated['delivery_service_id'],
            'priority' => (int) $validated['priority'],
            'base_price' => array_key_exists('base_price', $validated) && $validated['base_price'] !== null ? (int) $validated['base_price'] : null,
            'additional_seller_percent' => (float) ($validated['additional_seller_percent'] ?? 50),
            'free_price_from' => array_key_exists('free_price_from', $validated) && $validated['free_price_from'] !== null ? (int) $validated['free_price_from'] : null,
            'eta_days' => array_key_exists('eta_days', $validated) && $validated['eta_days'] !== null ? (int) $validated['eta_days'] : null,
            'cod_allowed' => $request->boolean('cod_allowed'),
            'is_active' => $request->boolean('is_active'),
            'notes' => $validated['notes'] ?? null,
        ];
    }
}
