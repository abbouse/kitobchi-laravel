<?php

namespace App\Services;

use App\Enums\HubStaffRole;
use App\Models\HubStaff;

class HubRoleAccessService
{
    public function permissionCatalog(): array
    {
        return [
            'desk.view' => ['label' => 'Desk sahifasi', 'description' => 'Hub desk web sahifasiga kirish va navbatlarni ko‘rish.'],
            'dashboard.view' => ['label' => 'Dashboard', 'description' => 'Hub KPI va navbat metrikalarini ko‘rish.'],
            'queue.inbound.view' => ['label' => 'Inbound navbati', 'description' => 'Sellerdan kelgan orderlarni ko‘rish.'],
            'queue.inbound.arrive' => ['label' => 'Inbound qabul', 'description' => 'Sellerdan olingan orderni hubga kelgan deb belgilash.'],
            'queue.qc.view' => ['label' => 'QC navbati', 'description' => 'QC tekshiruv navbatini ko‘rish.'],
            'queue.qc.complete' => ['label' => 'QC yakunlash', 'description' => 'QC tekshiruvni tugatish.'],
            'queue.packing.view' => ['label' => 'Packing navbati', 'description' => 'Qadoqlash navbatini ko‘rish.'],
            'queue.packing.pack' => ['label' => 'Pack action', 'description' => 'Orderni qadoqlandi deb belgilash.'],
            'queue.packing.label' => ['label' => 'Label action', 'description' => 'Etiketka yopildi deb belgilash.'],
            'queue.dispatch.view' => ['label' => 'Dispatch navbati', 'description' => 'Pochtaga yoki last-mile’ga chiqadigan orderlarni ko‘rish.'],
            'queue.dispatch.send' => ['label' => 'Dispatch action', 'description' => 'Orderni dispatch qilish.'],
            'queue.search.use' => ['label' => 'Qidiruv', 'description' => 'Order/label/tracking bo‘yicha qidirish.'],
            'queue.timeline.view' => ['label' => 'Timeline', 'description' => 'Orderning hub ichidagi action tarixini ko‘rish.'],
            'queue.exception.report' => ['label' => 'Exception yozish', 'description' => 'Muammo qaydini ochish.'],
            'queue.exception.resolve' => ['label' => 'Exception yopish', 'description' => 'Muammo hal bo‘ldi deb belgilash.'],
            'queue.scan.use' => ['label' => 'Scanner', 'description' => 'QR/barcode orqali fulfillmentni topish.'],
            'print.label' => ['label' => 'Label print', 'description' => '48x80 mm etiketka print qilish.'],
            'print.receipt' => ['label' => 'Receipt print', 'description' => 'Chek/packing slip print qilish.'],
        ];
    }

    public function roleBlueprints(): array
    {
        return [
            HubStaffRole::MANAGER->value => [
                'label' => 'Manager',
                'description' => 'Hubdagi barcha oqimlarni ko‘radi, override va print bilan ishlay oladi.',
                'permissions' => array_keys($this->permissionCatalog()),
            ],
            HubStaffRole::SUPERVISOR->value => [
                'label' => 'Supervisor',
                'description' => 'Smenani boshqaradi, barcha navbat va exception’larni kuzatadi.',
                'permissions' => array_keys($this->permissionCatalog()),
            ],
            HubStaffRole::INBOUND_OPERATOR->value => [
                'label' => 'Inbound operator',
                'description' => 'Sellerdan kelgan orderni qabul qiladi va scan bilan hubga kiritadi.',
                'permissions' => [
                    'desk.view',
                    'dashboard.view',
                    'queue.inbound.view',
                    'queue.inbound.arrive',
                    'queue.search.use',
                    'queue.timeline.view',
                    'queue.exception.report',
                    'queue.scan.use',
                    'print.receipt',
                ],
            ],
            HubStaffRole::QC_OPERATOR->value => [
                'label' => 'QC operator',
                'description' => 'Orderni tekshiradi, nuqsonlarni qayd etadi.',
                'permissions' => [
                    'desk.view',
                    'dashboard.view',
                    'queue.qc.view',
                    'queue.qc.complete',
                    'queue.search.use',
                    'queue.timeline.view',
                    'queue.exception.report',
                    'queue.scan.use',
                    'print.receipt',
                ],
            ],
            HubStaffRole::PACKING_OPERATOR->value => [
                'label' => 'Packing operator',
                'description' => 'Qadoqlaydi va etiketka/packing slip bilan ishlaydi.',
                'permissions' => [
                    'desk.view',
                    'dashboard.view',
                    'queue.packing.view',
                    'queue.packing.pack',
                    'queue.packing.label',
                    'queue.search.use',
                    'queue.timeline.view',
                    'queue.exception.report',
                    'queue.scan.use',
                    'print.label',
                    'print.receipt',
                ],
            ],
            HubStaffRole::DISPATCH_OPERATOR->value => [
                'label' => 'Dispatch operator',
                'description' => 'Pochtaga yoki last-mile’ga topshiradi, final jo‘natishni boshqaradi.',
                'permissions' => [
                    'desk.view',
                    'dashboard.view',
                    'queue.dispatch.view',
                    'queue.dispatch.send',
                    'queue.search.use',
                    'queue.timeline.view',
                    'queue.exception.report',
                    'queue.scan.use',
                    'print.label',
                    'print.receipt',
                ],
            ],
            HubStaffRole::INVENTORY_OPERATOR->value => [
                'label' => 'Inventory operator',
                'description' => 'Ichki nazorat va exception yechimi bilan ishlaydi.',
                'permissions' => [
                    'desk.view',
                    'dashboard.view',
                    'queue.inbound.view',
                    'queue.qc.view',
                    'queue.packing.view',
                    'queue.search.use',
                    'queue.timeline.view',
                    'queue.exception.report',
                    'queue.exception.resolve',
                    'queue.scan.use',
                    'print.receipt',
                ],
            ],
            HubStaffRole::SUPPORT_OPERATOR->value => [
                'label' => 'Support operator',
                'description' => 'Qidiruv, tarix va muammoli holatlarni ko‘radi, lekin oqim actionlarini bajarmaydi.',
                'permissions' => [
                    'desk.view',
                    'dashboard.view',
                    'queue.search.use',
                    'queue.timeline.view',
                    'queue.exception.report',
                    'queue.exception.resolve',
                    'queue.scan.use',
                    'print.receipt',
                ],
            ],
        ];
    }

    public function permissionsForRole(string $role): array
    {
        return $this->roleBlueprints()[$role]['permissions'] ?? [];
    }

    public function effectivePermissions(HubStaff $staff): array
    {
        $defaults = $this->permissionsForRole((string) $staff->role);
        $custom = collect($staff->permissions ?? [])->filter()->values()->all();

        return collect([...$defaults, ...$custom])
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function can(HubStaff $staff, string $permission): bool
    {
        return in_array($permission, $this->effectivePermissions($staff), true);
    }
}
