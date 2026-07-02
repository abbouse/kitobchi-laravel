<?php

namespace App\Http\Controllers\Api\Hub;

use App\Enums\HubStaffRole;
use App\Http\Controllers\Controller;
use App\Models\HubStaff;
use App\Services\HubRoleAccessService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class HubStaffController extends Controller
{
    public function __construct(
        private readonly HubRoleAccessService $hubRoleAccessService,
    ) {}

    public function index(Request $request)
    {
        $manager = $this->manager($request);

        if (! $manager) {
            return response()->json([
                'status' => 'error',
                'message' => 'Hub hodimlarini faqat manager boshqara oladi.',
            ], 403);
        }

        $staff = HubStaff::query()
            ->where('hub_id', $manager->hub_id)
            ->orderByRaw("role = 'manager' desc")
            ->orderBy('full_name')
            ->get()
            ->map(fn (HubStaff $member) => $this->serializeStaff($member, $manager));

        $roles = collect($this->hubRoleAccessService->roleBlueprints())
            ->reject(fn ($role, $key) => $key === HubStaffRole::MANAGER->value)
            ->map(fn ($role, $key) => [
                'value' => $key,
                'label' => $role['label'] ?? str_replace('_', ' ', (string) $key),
                'description' => $role['description'] ?? '',
            ])
            ->values();

        return response()->json([
            'status' => 'success',
            'staff' => $staff,
            'roles' => $roles,
            'policy' => [
                'manager_role_locked' => true,
                'can_delete' => false,
                'can_assign_manager' => false,
            ],
        ]);
    }

    public function updateRole(Request $request, HubStaff $staff)
    {
        $manager = $this->manager($request);

        if (! $manager) {
            return response()->json([
                'status' => 'error',
                'message' => 'Hub hodimlarini faqat manager boshqara oladi.',
            ], 403);
        }

        if ((int) $staff->hub_id !== (int) $manager->hub_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Bu hodim boshqa hubga tegishli.',
            ], 403);
        }

        if ((int) $staff->id === (int) $manager->id) {
            return response()->json([
                'status' => 'error',
                'message' => "O'zingizning lavozimingizni ilovadan o'zgartirib bo'lmaydi.",
            ], 422);
        }

        if ($staff->role === HubStaffRole::MANAGER->value) {
            return response()->json([
                'status' => 'error',
                'message' => 'Manager lavozimi faqat admin boshqaruvidan o‘zgartiriladi.',
            ], 422);
        }

        $allowedRoles = collect(HubStaffRole::cases())
            ->map(fn (HubStaffRole $role) => $role->value)
            ->reject(fn (string $role) => $role === HubStaffRole::MANAGER->value)
            ->values()
            ->all();

        $validated = $request->validate([
            'role' => ['required', 'string', Rule::in($allowedRoles)],
        ]);

        $oldRole = (string) $staff->role;
        $staff->forceFill([
            'role' => $validated['role'],
            'permissions' => [],
        ])->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Hodim lavozimi yangilandi.',
            'staff' => $this->serializeStaff($staff->fresh(), $manager),
            'changed' => [
                'old_role' => $oldRole,
                'new_role' => $validated['role'],
            ],
        ]);
    }

    private function manager(Request $request): ?HubStaff
    {
        /** @var HubStaff|null $staff */
        $staff = $request->user('hub');

        if (! $staff || $staff->role !== HubStaffRole::MANAGER->value) {
            return null;
        }

        return $staff;
    }

    private function serializeStaff(HubStaff $member, HubStaff $manager): array
    {
        $isManager = $member->role === HubStaffRole::MANAGER->value;
        $isSelf = (int) $member->id === (int) $manager->id;

        return [
            'id' => $member->id,
            'username' => $member->username,
            'full_name' => $member->full_name,
            'phone_number' => $member->phone_number,
            'role' => $member->role,
            'is_active' => (bool) $member->is_active,
            'last_seen_at' => optional($member->last_seen_at)->toIso8601String(),
            'can_update_role' => ! $isManager && ! $isSelf,
            'lock_reason' => $isSelf
                ? "O'zingizning lavozimingiz admin orqali o'zgaradi."
                : ($isManager ? 'Manager lavozimi faqat admin boshqaruvidan o‘zgaradi.' : null),
        ];
    }
}
