<?php

namespace App\Http\Controllers\A122;

use App\Enums\HubStaffRole;
use App\Http\Controllers\Controller;
use App\Models\Hub;
use App\Models\HubStaff;
use App\Services\HubRoleAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class HubController extends Controller
{
    public function __construct(
        private readonly HubRoleAccessService $hubRoleAccessService,
    ) {}

    public function index()
    {
        $hubs = Hub::query()
            ->withCount(['staff', 'fulfillments', 'courierTasks'])
            ->orderByDesc('is_primary')
            ->orderByDesc('is_active')
            ->orderBy('priority')
            ->orderBy('name')
            ->get();

        $staff = HubStaff::query()
            ->with('hub:id,name,code')
            ->latest('id')
            ->get();

        $roleBlueprints = $this->hubRoleAccessService->roleBlueprints();
        $permissionCatalog = $this->hubRoleAccessService->permissionCatalog();

        return view('a122.hubs.index', [
            'hubs' => $hubs,
            'staff' => $staff,
            'roles' => HubStaffRole::cases(),
            'roleBlueprints' => $roleBlueprints,
            'permissionCatalog' => $permissionCatalog,
            'activeHubsCount' => $hubs->where('is_active', true)->count(),
            'postalHubsCount' => $hubs->where('supports_postal_dispatch', true)->count(),
            'firstMileHubsCount' => $hubs->where('supports_first_mile', true)->count(),
        ]);
    }

    public function store(Request $request)
    {
        Hub::create($this->validatedPayload($request));

        return back()->with('success', "Hub qo'shildi.");
    }

    public function update(Request $request, Hub $hub)
    {
        $hub->update($this->validatedPayload($request));

        return back()->with('success', 'Hub yangilandi.');
    }

    public function destroy(Hub $hub)
    {
        $hub->delete();

        return back()->with('success', "Hub o'chirildi.");
    }

    public function storeStaff(Request $request)
    {
        $data = $request->validate([
            'hub_id' => 'required|exists:hubs,id',
            'full_name' => 'required|string|max:150',
            'username' => 'required|string|max:80|unique:hub_staff,username',
            'phone_number' => 'nullable|string|max:40',
            'password' => 'required|string|min:6|max:120',
            'role' => 'required|string|in:'.implode(',', array_map(
                static fn (HubStaffRole $role) => $role->value,
                HubStaffRole::cases(),
            )),
        ]);

        HubStaff::create([
            'hub_id' => (int) $data['hub_id'],
            'full_name' => trim((string) $data['full_name']),
            'username' => trim((string) $data['username']),
            'phone_number' => trim((string) ($data['phone_number'] ?? '')),
            'password' => Hash::make((string) $data['password']),
            'role' => (string) $data['role'],
            'is_active' => true,
            'permissions' => [],
        ]);

        return back()->with('success', 'Hub staff yaratildi.');
    }

    public function updateStaff(Request $request, HubStaff $staff)
    {
        $data = $request->validate([
            'hub_id' => 'required|exists:hubs,id',
            'full_name' => 'required|string|max:150',
            'username' => 'required|string|max:80|unique:hub_staff,username,'.$staff->id,
            'phone_number' => 'nullable|string|max:40',
            'role' => 'required|string|in:'.implode(',', array_map(
                static fn (HubStaffRole $role) => $role->value,
                HubStaffRole::cases(),
            )),
            'permissions' => 'sometimes|array',
            'permissions.*' => 'string',
        ]);

        $allowedPermissions = array_keys($this->hubRoleAccessService->permissionCatalog());
        $customPermissions = collect($data['permissions'] ?? [])
            ->map(static fn ($permission) => trim((string) $permission))
            ->filter(static fn ($permission) => in_array($permission, $allowedPermissions, true))
            ->unique()
            ->values()
            ->all();

        $staff->update([
            'hub_id' => (int) $data['hub_id'],
            'full_name' => trim((string) $data['full_name']),
            'username' => trim((string) $data['username']),
            'phone_number' => trim((string) ($data['phone_number'] ?? '')),
            'role' => (string) $data['role'],
            'permissions' => $customPermissions,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'Hub staff yangilandi.');
    }

    public function resetStaffPassword(Request $request, HubStaff $staff)
    {
        $data = $request->validate([
            'password' => 'required|string|min:6|max:120',
        ]);

        $staff->update([
            'password' => Hash::make((string) $data['password']),
        ]);

        return back()->with('success', 'Hub staff paroli yangilandi.');
    }

    public function toggleStaff(HubStaff $staff)
    {
        $staff->update(['is_active' => ! $staff->is_active]);

        return back()->with('success', $staff->is_active ? 'Hub staff faollashtirildi.' : 'Hub staff o‘chirildi.');
    }

    private function validatedPayload(Request $request): array
    {
        $request->merge([
            'lat' => $this->normalizeCoordinate($request->input('lat')),
            'lon' => $this->normalizeCoordinate($request->input('lon')),
        ]);

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'code' => 'required|string|max:50',
            'country_code' => 'required|string|max:8',
            'region_name' => 'nullable|string|max:150',
            'city_name' => 'nullable|string|max:150',
            'address' => 'nullable|string|max:500',
            'lat' => 'nullable|numeric',
            'lon' => 'nullable|numeric',
            'priority' => 'nullable|integer|min:1|max:10000',
            'meta' => 'nullable|string|max:5000',
        ]);

        return [
            'name' => $validated['name'],
            'code' => strtoupper(trim($validated['code'])),
            'country_code' => strtoupper(trim($validated['country_code'])),
            'region_name' => $validated['region_name'] ?? null,
            'city_name' => $validated['city_name'] ?? null,
            'address' => $validated['address'] ?? null,
            'lat' => array_key_exists('lat', $validated) && $validated['lat'] !== null ? (float) $validated['lat'] : null,
            'lon' => array_key_exists('lon', $validated) && $validated['lon'] !== null ? (float) $validated['lon'] : null,
            'priority' => (int) ($validated['priority'] ?? 100),
            'is_active' => $request->boolean('is_active', true),
            'is_primary' => $request->boolean('is_primary'),
            'supports_first_mile' => $request->boolean('supports_first_mile', true),
            'supports_last_mile' => $request->boolean('supports_last_mile', true),
            'supports_postal_dispatch' => $request->boolean('supports_postal_dispatch', true),
            'meta' => filled($validated['meta'] ?? null)
                ? ['notes' => trim((string) $validated['meta'])]
                : null,
        ];
    }

    private function normalizeCoordinate(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim(str_replace(',', '.', (string) $value));

        return $value === '' ? null : $value;
    }
}
