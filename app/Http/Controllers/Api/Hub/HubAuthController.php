<?php

namespace App\Http\Controllers\Api\Hub;

use App\Http\Controllers\Controller;
use App\Models\HubStaff;
use App\Services\HubRoleAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class HubAuthController extends Controller
{
    public function __construct(
        private readonly HubRoleAccessService $hubRoleAccessService,
    ) {}

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        /** @var HubStaff|null $staff */
        $staff = HubStaff::query()
            ->with('hub:id,name,code,city_name,country_code,is_active')
            ->where('username', trim((string) $request->input('username')))
            ->first();

        if (! $staff || ! Hash::check((string) $request->input('password'), (string) $staff->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Login yoki parol xato.',
            ], 401);
        }

        if (! $staff->is_active || ! $staff->hub?->is_active) {
            return response()->json([
                'status' => 'error',
                'message' => 'Hub akkaunti hozir faol emas.',
            ], 403);
        }

        $token = $staff->createToken('hub-token')->plainTextToken;
        $staff->forceFill(['last_seen_at' => now()])->save();

        return response()->json([
            'status' => 'success',
            'token' => $token,
            'staff' => [
                'id' => $staff->id,
                'username' => $staff->username,
                'full_name' => $staff->full_name,
                'role' => $staff->role,
                'permissions' => $this->hubRoleAccessService->effectivePermissions($staff),
                'hub' => $staff->hub ? [
                    'id' => $staff->hub->id,
                    'name' => $staff->hub->name,
                    'code' => $staff->hub->code,
                    'city_name' => $staff->hub->city_name,
                    'country_code' => $staff->hub->country_code,
                ] : null,
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $request->user('hub')?->currentAccessToken()?->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Hub akkauntdan chiqildi.',
        ]);
    }

    public function me(Request $request)
    {
        /** @var HubStaff $staff */
        $staff = $request->user('hub');
        $staff->loadMissing('hub:id,name,code,city_name,country_code,is_active');
        $staff->forceFill(['last_seen_at' => now()])->save();

        return response()->json([
            'status' => 'success',
            'staff' => [
                'id' => $staff->id,
                'username' => $staff->username,
                'full_name' => $staff->full_name,
                'phone_number' => $staff->phone_number,
                'role' => $staff->role,
                'permissions' => $this->hubRoleAccessService->effectivePermissions($staff),
                'hub' => $staff->hub ? [
                    'id' => $staff->hub->id,
                    'name' => $staff->hub->name,
                    'code' => $staff->hub->code,
                    'city_name' => $staff->hub->city_name,
                    'country_code' => $staff->hub->country_code,
                ] : null,
            ],
        ]);
    }
}
