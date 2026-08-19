<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'boshqaruv.app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $admin = Auth::guard('panel')->user();

        return array_merge(parent::share($request), [
            'auth' => [
                'admin' => $admin ? [
                    'id' => $admin->id,
                    'name' => $admin->name,
                    'email' => $admin->email,
                    'role' => $admin->role_label ?? 'Administrator',
                    'roleKey' => $admin->role,
                    'isSuperAdmin' => $admin->isSuperAdmin(),
                    'isReadOnly' => (bool) ($admin->is_read_only ?? false),
                    // Frontend sidebar/sahifa ko'rinishini shu ro'yxatga qarab
                    // filtrlaydi (Layout.tsx). Superadmin uchun barcha modul
                    // kalitlari yuboriladi, chunki u har doim hammasiga kira oladi.
                    'permissions' => $admin->isSuperAdmin()
                        ? array_keys(\App\Models\Admin::MODULES)
                        : ($admin->permissions ?? []),
                ] : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ]);
    }
}
