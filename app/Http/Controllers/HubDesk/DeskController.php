<?php

namespace App\Http\Controllers\HubDesk;

use App\Http\Controllers\Controller;
use App\Models\OrderFulfillment;
use App\Services\HubPrintViewService;
use App\Services\HubRoleAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DeskController extends Controller
{
    public function __construct(
        private readonly HubRoleAccessService $hubRoleAccessService,
        private readonly HubPrintViewService $hubPrintViewService,
    ) {}

    public function index(Request $request)
    {
        $staff = $this->staff();
        $this->authorizePermission('desk.view');

        $query = OrderFulfillment::query()
            ->with(['order.user:id,name,lastname,phone_number', 'hub:id,name,code'])
            ->where('hub_id', $staff->hub_id)
            ->latest('id');

        if ($search = trim((string) $request->input('q'))) {
            $query->where(function ($builder) use ($search) {
                $builder->where('label_code', 'like', "%{$search}%")
                    ->orWhere('postal_tracking_number', 'like', "%{$search}%")
                    ->orWhere('order_id', is_numeric($search) ? (int) $search : 0)
                    ->orWhereHas('order.user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('lastname', 'like', "%{$search}%")
                            ->orWhere('phone_number', 'like', "%{$search}%");
                    });
            });
        }

        $counts = [
            'ready_to_label' => (clone $query)->whereIn('status_code', ['packed', 'labeled'])->count(),
            'ready_to_dispatch' => (clone $query)->whereIn('status_code', ['labeled', 'assigned_last_mile'])->count(),
            'with_exceptions' => (clone $query)->whereNotNull('meta')->count(),
        ];

        $fulfillments = $query->paginate(20)->withQueryString();

        return view('hubdesk.index', [
            'staff' => $staff,
            'counts' => $counts,
            'fulfillments' => $fulfillments,
            'permissions' => $this->hubRoleAccessService->effectivePermissions($staff),
        ]);
    }

    public function show(OrderFulfillment $fulfillment)
    {
        $staff = $this->staff();
        $this->authorizeSameHub($fulfillment, $staff->hub_id);
        $this->authorizePermission('desk.view');

        $fulfillment->load(['order.user', 'hub']);

        return view('hubdesk.show', [
            'staff' => $staff,
            'fulfillment' => $fulfillment,
            'permissions' => $this->hubRoleAccessService->effectivePermissions($staff),
        ]);
    }

    public function printLabel(OrderFulfillment $fulfillment)
    {
        $staff = $this->staff();
        $this->authorizeSameHub($fulfillment, $staff->hub_id);
        $this->authorizePermission('print.label');
        $fulfillment->loadMissing(['order.user', 'hub']);

        return view('a122.hubs.print.label', [
            'order' => $fulfillment->order,
            'fulfillment' => $fulfillment,
            'label' => $this->hubPrintViewService->labelData($fulfillment),
        ]);
    }

    public function printReceipt(OrderFulfillment $fulfillment)
    {
        $staff = $this->staff();
        $this->authorizeSameHub($fulfillment, $staff->hub_id);
        $this->authorizePermission('print.receipt');
        $fulfillment->loadMissing(['order.user', 'hub']);

        return view('a122.hubs.print.receipt', [
            'order' => $fulfillment->order,
            'fulfillment' => $fulfillment,
            'receipt' => $this->hubPrintViewService->receiptData($fulfillment),
        ]);
    }

    private function staff()
    {
        /** @var \App\Models\HubStaff $staff */
        $staff = Auth::guard('hub_web')->user();

        return $staff;
    }

    private function authorizePermission(string $permission): void
    {
        abort_unless($this->hubRoleAccessService->can($this->staff(), $permission), 403);
    }

    private function authorizeSameHub(OrderFulfillment $fulfillment, ?int $hubId): void
    {
        abort_unless((int) $fulfillment->hub_id === (int) $hubId, 404);
    }
}
