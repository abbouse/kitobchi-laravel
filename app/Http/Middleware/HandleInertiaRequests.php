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
            // Sidebar badge'lari va header'dagi "navbat" qo'ng'irog'i (30 soniya kesh)
            'workQueue' => fn () => $admin ? $this->workQueue($admin) : null,
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ]);
    }

    private function workQueue($admin): array
    {
        try {
            $service = app(\App\Services\Staff\WorkQueueService::class);
            $items = array_values(array_filter($service->forAdmin($admin), fn ($item) => $item['count'] > 0));

            return [
                'items' => array_map(fn ($item) => [
                    'key' => $item['key'],
                    'label' => $item['label'],
                    'count' => $item['count'],
                    'tone' => $item['tone'],
                    'icon' => $item['icon'],
                    'url' => $item['url'],
                ], $items),
                'nav' => $service->navCounts($admin),
                'total' => array_sum(array_map(fn ($item) => in_array($item['tone'], ['danger', 'warning'], true) ? $item['count'] : 0, $items)),
            ];
        } catch (\Throwable) {
            return ['items' => [], 'nav' => (object) [], 'total' => 0];
        }
    }
}
