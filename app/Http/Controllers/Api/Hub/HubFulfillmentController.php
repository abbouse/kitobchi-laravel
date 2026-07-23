<?php

namespace App\Http\Controllers\Api\Hub;

use App\Enums\CourierTaskStatusCode;
use App\Enums\FulfillmentStatusCode;
use App\Http\Controllers\Controller;
use App\Models\CourierTask;
use App\Models\HubStaff;
use App\Models\OrderFulfillment;
use App\Services\AdminOrderStatusSyncService;
use App\Services\CourierTaskOrchestratorService;
use App\Services\HubRoleAccessService;
use App\Services\QrTokenService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

class HubFulfillmentController extends Controller
{
    public function __construct(
        private readonly HubRoleAccessService $hubRoleAccessService,
        private readonly CourierTaskOrchestratorService $courierTaskOrchestratorService,
        private readonly AdminOrderStatusSyncService $statusSync,
        private readonly QrTokenService $qrTokenService,
    ) {
        $this->middleware('auth:hub');
    }

    public function dashboard(Request $request)
    {
        $staff = $this->staff($request);
        if (! $this->hubRoleAccessService->can($staff, 'dashboard.view')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Dashboard sizga ruxsat etilmagan.',
            ], 403);
        }
        $hubId = $staff->hub_id;

        // Dashboard is hub-scoped (not staff-scoped) and polled every ~20s by
        // every operator. Compute the heavy counts + analytics once per hub and
        // share it for a short window instead of running ~13 queries per poll.
        $data = Cache::remember(
            "hub:{$hubId}:dashboard",
            now()->addSeconds(15),
            fn () => [
                'counts' => [
                    'inbound' => $this->baseQuery($hubId)->whereIn('status_code', [
                        FulfillmentStatusCode::PICKED_FROM_SELLER->value,
                        FulfillmentStatusCode::ARRIVED_AT_HUB->value,
                    ])->count(),
                    'qc' => $this->baseQuery($hubId)->whereIn('status_code', [
                        FulfillmentStatusCode::ARRIVED_AT_HUB->value,
                        FulfillmentStatusCode::QC_CHECKED->value,
                    ])->count(),
                    'packing' => $this->baseQuery($hubId)->whereIn('status_code', [
                        FulfillmentStatusCode::QC_CHECKED->value,
                        FulfillmentStatusCode::PACKED->value,
                    ])->count(),
                    'dispatch' => $this->baseQuery($hubId)->whereIn('status_code', [
                        FulfillmentStatusCode::LABELED->value,
                        FulfillmentStatusCode::DISPATCHED_TO_POST->value,
                        FulfillmentStatusCode::ASSIGNED_LAST_MILE->value,
                    ])->count(),
                    'exceptions' => $this->baseQuery($hubId)
                        ->whereNotNull('meta->exception->code')
                        ->count(),
                    'active_couriers' => CourierTask::query()
                        ->where('hub_id', $hubId)
                        ->whereNotNull('courier_id')
                        ->whereIn('status_code', $this->activeCourierTaskStatuses())
                        ->distinct('courier_id')
                        ->count('courier_id'),
                ],
                'analytics' => $this->dashboardAnalytics($hubId),
            ],
        );

        return response()->json([
            'status' => 'success',
            'hub' => $staff->hub?->only(['id', 'name', 'code', 'city_name', 'country_code']),
            'counts' => $data['counts'],
            'analytics' => $data['analytics'],
        ]);
    }

    public function queue(Request $request, string $queue)
    {
        $staff = $this->staff($request);
        $permission = match ($queue) {
            'inbound' => 'queue.inbound.view',
            'qc' => 'queue.qc.view',
            'packing' => 'queue.packing.view',
            'dispatch' => 'queue.dispatch.view',
            default => null,
        };
        if (! $permission || ! $this->hubRoleAccessService->can($staff, $permission)) {
            return response()->json(['status' => 'error', 'message' => 'Bu queue sizga ruxsat etilmagan.'], 403);
        }

        $statuses = match ($queue) {
            'inbound' => [FulfillmentStatusCode::PICKED_FROM_SELLER->value, FulfillmentStatusCode::ARRIVED_AT_HUB->value],
            'qc' => [FulfillmentStatusCode::ARRIVED_AT_HUB->value, FulfillmentStatusCode::QC_CHECKED->value],
            'packing' => [FulfillmentStatusCode::QC_CHECKED->value, FulfillmentStatusCode::PACKED->value, FulfillmentStatusCode::LABELED->value],
            'dispatch' => [FulfillmentStatusCode::LABELED->value, FulfillmentStatusCode::DISPATCHED_TO_POST->value, FulfillmentStatusCode::ASSIGNED_LAST_MILE->value],
        };

        $rows = $this->baseQuery($staff->hub_id)
            ->whereIn('status_code', $statuses)
            ->when($request->filled('q'), function ($query) use ($request) {
                $search = trim((string) $request->string('q'));
                $query->where(function ($scoped) use ($search) {
                    $scoped->where('order_id', 'like', '%'.$search.'%')
                        ->orWhere('label_code', 'like', '%'.$search.'%')
                        ->orWhere('postal_tracking_number', 'like', '%'.$search.'%')
                        ->orWhereHas('order.user', function ($userQuery) use ($search) {
                            $userQuery->where('name', 'like', '%'.$search.'%')
                                ->orWhere('lastname', 'like', '%'.$search.'%')
                                ->orWhere('phone_number', 'like', '%'.$search.'%');
                        });
                });
            })
            ->when($request->boolean('cod_only'), fn ($query) => $query->where('is_cod', true))
            ->when($request->boolean('exceptions_only'), fn ($query) => $query->whereNotNull('meta->exception->code'))
            ->when($request->filled('mode'), fn ($query) => $query->where('fulfillment_mode', (string) $request->string('mode')))
            ->latest('updated_at')
            ->paginate((int) min(50, max(10, (int) $request->input('limit', 20))))
            ->through(fn (OrderFulfillment $fulfillment) => $this->serializeListItem($fulfillment));

        return response()->json([
            'status' => 'success',
            'queue' => $queue,
            'data' => $rows,
            'filters' => [
                'q' => (string) $request->string('q'),
                'cod_only' => $request->boolean('cod_only'),
                'exceptions_only' => $request->boolean('exceptions_only'),
                'mode' => (string) $request->string('mode'),
            ],
        ]);
    }

    public function exceptions(Request $request)
    {
        $staff = $this->staff($request);
        if (
            ! $this->hubRoleAccessService->can($staff, 'queue.exception.report')
            && ! $this->hubRoleAccessService->can($staff, 'queue.exception.resolve')
        ) {
            return response()->json(['status' => 'error', 'message' => 'Exception markazi sizga ruxsat etilmagan.'], 403);
        }

        $state = (string) $request->string('state', 'open');
        $rows = $this->baseQuery($staff->hub_id)
            ->whereNotNull('meta->exception->code')
            ->when($state === 'open', function ($query) {
                $query->whereNull('meta->exception->resolved_at');
            })
            ->when($state === 'resolved', function ($query) {
                $query->whereNotNull('meta->exception->resolved_at');
            })
            ->when($request->filled('q'), function ($query) use ($request) {
                $search = trim((string) $request->string('q'));
                $query->where(function ($scoped) use ($search) {
                    $scoped->where('order_id', 'like', '%'.$search.'%')
                        ->orWhere('label_code', 'like', '%'.$search.'%')
                        ->orWhere('postal_tracking_number', 'like', '%'.$search.'%')
                        ->orWhereHas('order.user', function ($userQuery) use ($search) {
                            $userQuery->where('name', 'like', '%'.$search.'%')
                                ->orWhere('lastname', 'like', '%'.$search.'%')
                                ->orWhere('phone_number', 'like', '%'.$search.'%');
                        });
                });
            })
            ->latest('updated_at')
            ->paginate((int) min(50, max(10, (int) $request->input('limit', 20))))
            ->through(fn (OrderFulfillment $fulfillment) => $this->serializeListItem($fulfillment));

        return response()->json([
            'status' => 'success',
            'data' => $rows,
            'filters' => [
                'q' => (string) $request->string('q'),
                'state' => $state,
            ],
        ]);
    }

    public function activity(Request $request)
    {
        $staff = $this->staff($request);
        if (! $this->hubRoleAccessService->can($staff, 'dashboard.view')) {
            return response()->json(['status' => 'error', 'message' => 'Activity markazi sizga ruxsat etilmagan.'], 403);
        }

        $windowDays = (int) min(30, max(1, (int) $request->input('days', 7)));
        $fulfillments = $this->baseQuery($staff->hub_id)
            ->where('updated_at', '>=', now()->subDays($windowDays))
            ->latest('updated_at')
            ->limit(300)
            ->get();

        $summary = [];
        $events = [];

        foreach ($fulfillments as $fulfillment) {
            $timeline = collect(Arr::get($fulfillment->meta ?? [], 'timeline', []))
                ->filter(fn ($row) => is_array($row) && ! empty($row['at']) && is_array($row['actor'] ?? null));

            foreach ($timeline as $row) {
                $actor = $row['actor'];
                $actorId = (int) ($actor['id'] ?? 0);
                $code = (string) ($row['code'] ?? '');
                if ($actorId <= 0 || $code === '') {
                    continue;
                }

                $summary[$actorId] ??= [
                    'staff_id' => $actorId,
                    'name' => (string) ($actor['name'] ?? ''),
                    'role' => (string) ($actor['role'] ?? ''),
                    'actions_count' => 0,
                    'exception_reports' => 0,
                    'exception_resolves' => 0,
                    'label_prints' => 0,
                    'receipt_prints' => 0,
                    'last_action_at' => null,
                ];

                $summary[$actorId]['actions_count']++;
                $summary[$actorId]['last_action_at'] = $row['at'];

                if ($code === 'exception_reported') {
                    $summary[$actorId]['exception_reports']++;
                }
                if ($code === 'exception_resolved') {
                    $summary[$actorId]['exception_resolves']++;
                }
                if ($code === 'label_printed') {
                    $summary[$actorId]['label_prints']++;
                }
                if ($code === 'receipt_printed') {
                    $summary[$actorId]['receipt_prints']++;
                }

                $events[] = [
                    'code' => $code,
                    'title' => (string) ($row['title'] ?? ''),
                    'at' => (string) ($row['at'] ?? ''),
                    'note' => (string) ($row['note'] ?? ''),
                    'order_id' => $fulfillment->order_id,
                    'actor' => [
                        'id' => $actorId,
                        'name' => (string) ($actor['name'] ?? ''),
                        'role' => (string) ($actor['role'] ?? ''),
                    ],
                ];
            }
        }

        $operators = collect($summary)
            ->sortByDesc(fn ($row) => [$row['actions_count'], $row['label_prints'] + $row['receipt_prints']])
            ->values()
            ->take(12)
            ->all();

        $recentEvents = collect($events)
            ->sortByDesc('at')
            ->values()
            ->take(40)
            ->all();

        return response()->json([
            'status' => 'success',
            'data' => [
                'window_days' => $windowDays,
                'operators' => $operators,
                'events' => $recentEvents,
            ],
        ]);
    }

    public function scan(Request $request)
    {
        $staff = $this->staff($request);
        if (! $this->hubRoleAccessService->can($staff, 'queue.scan.use')) {
            return response()->json(['status' => 'error', 'message' => 'Scanner sizga ruxsat etilmagan.'], 403);
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:120'],
        ]);

        $code = trim($validated['code']);
        $numericOrderId = preg_replace('/\D+/', '', $code);

        $fulfillment = $this->baseQuery($staff->hub_id)
            ->where(function ($query) use ($code, $numericOrderId) {
                $query->where('label_code', $code)
                    ->orWhere('postal_tracking_number', $code);

                if ($numericOrderId !== '') {
                    $query->orWhere('order_id', (int) $numericOrderId);
                }
            })
            ->latest('updated_at')
            ->first();

        if (! $fulfillment) {
            return response()->json([
                'status' => 'error',
                'message' => 'Bu kod bo‘yicha fulfillment topilmadi yoki boshqa hubga tegishli.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'fulfillment' => $this->serializeFulfillment($fulfillment),
        ]);
    }

    public function show(Request $request, OrderFulfillment $fulfillment)
    {
        $staff = $this->staff($request);
        if ((int) $fulfillment->hub_id !== (int) $staff->hub_id) {
            return response()->json(['status' => 'error', 'message' => 'Bu fulfillment boshqa hubga tegishli.'], 403);
        }

        $fulfillment->loadMissing(['order.user:id,name,lastname,phone_number', 'hub:id,name,code']);

        return response()->json([
            'status' => 'success',
            'fulfillment' => $this->serializeFulfillment($fulfillment),
        ]);
    }

    public function handoffQr(Request $request, OrderFulfillment $fulfillment)
    {
        $staff = $this->staff($request);
        if (! $this->hubRoleAccessService->can($staff, 'queue.inbound.arrive')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kuryerdan qabul qilish QR kodi sizga ruxsat etilmagan.',
            ], 403);
        }
        if ($response = $this->ensureSameHub($staff, $fulfillment)) {
            return $response;
        }
        if ($fulfillment->status_code !== FulfillmentStatusCode::PICKED_FROM_SELLER->value) {
            return response()->json([
                'status' => 'error',
                'message' => 'Bu fulfillment kuryerdan qabul qilish bosqichida emas.',
            ], 422);
        }

        $task = $fulfillment->courierTasks()
            ->where('leg', 'first_mile')
            ->whereNotNull('courier_id')
            ->whereNotIn('status_code', ['completed', 'failed', 'cancelled'])
            ->latest('id')
            ->first();

        if (! $task) {
            return response()->json([
                'status' => 'error',
                'message' => 'Bu buyurtmaga faol first-mile kuryer topilmadi.',
            ], 422);
        }

        $expiresAt = now()->addMinutes(10);
        $claims = [
            'fulfillment_id' => (int) $fulfillment->id,
            'hub_id' => (int) $fulfillment->hub_id,
            'order_id' => (int) $fulfillment->order_id,
            'courier_id' => (int) $task->courier_id,
        ];
        $token = $this->qrTokenService->makeHubHandoffToken(
            $fulfillment->id,
            (int) $fulfillment->hub_id,
            (int) $fulfillment->order_id,
            (int) $task->courier_id,
            60 * 10,
        );
        $code = $this->qrTokenService->makeNumericHubHandoffCode($claims, 60 * 10);

        return response()->json([
            'status' => 'success',
            'data' => [
                'token' => $token,
                'code' => $code,
                'order_id' => (int) $fulfillment->order_id,
                'fulfillment_id' => (int) $fulfillment->id,
                'expires_at' => $expiresAt->toIso8601String(),
            ],
        ]);
    }

    public function printPayload(Request $request, OrderFulfillment $fulfillment)
    {
        $staff = $this->staff($request);
        if ($response = $this->ensureSameHub($staff, $fulfillment)) {
            return $response;
        }

        $fulfillment->loadMissing([
            'order.user:id,name,lastname,phone_number',
            'hub:id,name,code,address,city_name',
        ]);

        $order = $fulfillment->order;
        $address = collect($order?->address ?? [])->first() ?? [];

        return response()->json([
            'status' => 'success',
            'payload' => [
                'label' => [
                    'paper_width_mm' => 80,
                    'paper_height_mm' => 48,
                    'hub_name' => $fulfillment->hub?->name,
                    'hub_code' => $fulfillment->hub?->code,
                    'order_id' => $fulfillment->order_id,
                    'label_code' => $fulfillment->label_code ?: ('ORD-'.$fulfillment->order_id),
                    'tracking_number' => $fulfillment->postal_tracking_number,
                    'customer_name' => trim((string) (($order?->user?->name ?? '').' '.($order?->user?->lastname ?? ''))),
                    'customer_phone' => $order?->user?->phone_number,
                    'address' => $address['fullAddress'] ?? $address['branch_address'] ?? null,
                    'fulfillment_mode' => $fulfillment->fulfillment_mode,
                    'last_mile_mode' => $fulfillment->last_mile_mode,
                    'is_cod' => (bool) $fulfillment->is_cod,
                    'cash_collect_amount' => (int) ($fulfillment->cash_collect_amount ?? 0),
                    'printed_at' => now()->toIso8601String(),
                ],
                'receipt' => [
                    'hub_name' => $fulfillment->hub?->name,
                    'order_id' => $fulfillment->order_id,
                    'customer_name' => trim((string) (($order?->user?->name ?? '').' '.($order?->user?->lastname ?? ''))),
                    'customer_phone' => $order?->user?->phone_number,
                    'address' => $address['fullAddress'] ?? $address['branch_address'] ?? null,
                    'order_amount' => (int) ($order?->amount ?? 0),
                    'delivery_price' => (int) ($order?->deliveryPrice ?? 0),
                    'is_cod' => (bool) $fulfillment->is_cod,
                    'cash_collect_amount' => (int) ($fulfillment->cash_collect_amount ?? 0),
                    'generated_at' => now()->toIso8601String(),
                ],
            ],
        ]);
    }

    public function markPrint(Request $request, OrderFulfillment $fulfillment)
    {
        $staff = $this->staff($request);
        if ($response = $this->ensureSameHub($staff, $fulfillment)) {
            return $response;
        }

        $validated = $request->validate([
            'type' => ['required', Rule::in(['label', 'receipt'])],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $permission = $validated['type'] === 'label' ? 'print.label' : 'print.receipt';
        if (! $this->hubRoleAccessService->can($staff, $permission)) {
            return response()->json(['status' => 'error', 'message' => 'Print sizga ruxsat etilmagan.'], 403);
        }

        $meta = $fulfillment->meta ?? [];
        $prints = collect(Arr::get($meta, 'prints', []))
            ->filter(fn ($row) => is_array($row))
            ->values()
            ->all();

        $prints[] = [
            'type' => $validated['type'],
            'reason' => trim((string) ($validated['reason'] ?? '')),
            'printed_at' => now()->toIso8601String(),
            'actor' => [
                'id' => $staff->id,
                'name' => $staff->full_name,
                'role' => $staff->role,
            ],
        ];

        Arr::set($meta, 'prints', $prints);
        $fulfillment->meta = $meta;
        $this->appendTimeline(
            $fulfillment,
            $staff,
            $validated['type'] === 'label' ? 'label_printed' : 'receipt_printed',
            $validated['type'] === 'label' ? 'Etiketka chop etildi' : 'Packing slip chop etildi',
            ['note' => trim((string) ($validated['reason'] ?? ''))]
        );
        $fulfillment->save();

        return response()->json([
            'status' => 'success',
            'fulfillment' => $this->serializeFulfillment($fulfillment->fresh()),
        ]);
    }

    public function arrive(Request $request, OrderFulfillment $fulfillment)
    {
        $staff = $this->staff($request);
        if (! $this->hubRoleAccessService->can($staff, 'queue.inbound.arrive')) {
            return response()->json(['status' => 'error', 'message' => 'Bu action sizga ruxsat etilmagan.'], 403);
        }
        if ($response = $this->ensureSameHub($staff, $fulfillment)) {
            return $response;
        }
        if ($fulfillment->status_code !== FulfillmentStatusCode::PICKED_FROM_SELLER->value) {
            return response()->json(['status' => 'error', 'message' => 'Bu fulfillment hali hubga qabul qilish bosqichida emas.'], 422);
        }

        $fulfillment = $this->statusSync->updateFulfillmentStatus($fulfillment, FulfillmentStatusCode::ARRIVED_AT_HUB);
        $this->appendTimeline($fulfillment, $staff, 'arrived_at_hub', 'Hubga qabul qilindi');
        $this->clearException($fulfillment);
        $fulfillment->save();
        $this->courierTaskOrchestratorService->markArrivedAtHub($fulfillment->fresh());

        return response()->json(['status' => 'success', 'fulfillment' => $this->serializeFulfillment($fulfillment->fresh())]);
    }

    public function qc(Request $request, OrderFulfillment $fulfillment)
    {
        $staff = $this->staff($request);
        if (! $this->hubRoleAccessService->can($staff, 'queue.qc.complete')) {
            return response()->json(['status' => 'error', 'message' => 'Bu action sizga ruxsat etilmagan.'], 403);
        }
        if ($response = $this->ensureSameHub($staff, $fulfillment)) {
            return $response;
        }
        if ($fulfillment->status_code !== FulfillmentStatusCode::ARRIVED_AT_HUB->value) {
            return response()->json(['status' => 'error', 'message' => 'QC faqat hubga qabul qilingan fulfillmentga ishlaydi.'], 422);
        }

        $fulfillment = $this->statusSync->updateFulfillmentStatus($fulfillment, FulfillmentStatusCode::QC_CHECKED);
        $this->appendTimeline($fulfillment, $staff, 'qc_checked', 'QC tekshiruvi yakunlandi');
        $this->clearException($fulfillment);
        $fulfillment->save();

        return response()->json(['status' => 'success', 'fulfillment' => $this->serializeFulfillment($fulfillment->fresh())]);
    }

    public function pack(Request $request, OrderFulfillment $fulfillment)
    {
        $staff = $this->staff($request);
        if (! $this->hubRoleAccessService->can($staff, 'queue.packing.pack')) {
            return response()->json(['status' => 'error', 'message' => 'Bu action sizga ruxsat etilmagan.'], 403);
        }
        if ($response = $this->ensureSameHub($staff, $fulfillment)) {
            return $response;
        }
        if ($fulfillment->status_code !== FulfillmentStatusCode::QC_CHECKED->value) {
            return response()->json(['status' => 'error', 'message' => 'Packing faqat QC tugagan fulfillmentga ishlaydi.'], 422);
        }

        $fulfillment = $this->statusSync->updateFulfillmentStatus($fulfillment, FulfillmentStatusCode::PACKED);
        $this->appendTimeline($fulfillment, $staff, 'packed', 'Qadoqlash tasdiqlandi');
        $this->clearException($fulfillment);
        $fulfillment->save();

        return response()->json(['status' => 'success', 'fulfillment' => $this->serializeFulfillment($fulfillment->fresh())]);
    }

    public function label(Request $request, OrderFulfillment $fulfillment)
    {
        $staff = $this->staff($request);
        if (! $this->hubRoleAccessService->can($staff, 'queue.packing.label')) {
            return response()->json(['status' => 'error', 'message' => 'Bu action sizga ruxsat etilmagan.'], 403);
        }
        if ($response = $this->ensureSameHub($staff, $fulfillment)) {
            return $response;
        }
        if ($fulfillment->status_code !== FulfillmentStatusCode::PACKED->value) {
            return response()->json(['status' => 'error', 'message' => 'Label faqat packed fulfillmentga ishlaydi.'], 422);
        }

        $fulfillment = $this->statusSync->updateFulfillmentStatus($fulfillment, FulfillmentStatusCode::LABELED);
        $fulfillment->label_code = $fulfillment->label_code ?: ('LBL-'.$fulfillment->order_id.'-'.now()->format('His'));
        $this->appendTimeline($fulfillment, $staff, 'labeled', 'Etiketka tayyorlandi', [
            'label_code' => $fulfillment->label_code,
        ]);
        $this->clearException($fulfillment);
        $fulfillment->save();

        return response()->json(['status' => 'success', 'fulfillment' => $this->serializeFulfillment($fulfillment->fresh())]);
    }

    public function dispatch(Request $request, OrderFulfillment $fulfillment)
    {
        $staff = $this->staff($request);
        $validated = $request->validate([
            'dispatch_method' => ['nullable', Rule::in(['postal', 'courier'])],
        ]);
        if (! $this->hubRoleAccessService->can($staff, 'queue.dispatch.send')) {
            return response()->json(['status' => 'error', 'message' => 'Bu action sizga ruxsat etilmagan.'], 403);
        }
        if ($response = $this->ensureSameHub($staff, $fulfillment)) {
            return $response;
        }
        if ($fulfillment->status_code !== FulfillmentStatusCode::LABELED->value) {
            return response()->json(['status' => 'error', 'message' => 'Dispatch faqat labeled fulfillmentga ishlaydi.'], 422);
        }

        $dispatchMethod = $validated['dispatch_method'] ?? null;
        $useCourier = $dispatchMethod === 'courier' || ($dispatchMethod === null && $fulfillment->last_mile_mode !== 'postal_dispatch');

        if (! $useCourier) {
            $fulfillment->last_mile_mode = 'postal_dispatch';
            $fulfillment->save();
            $fulfillment = $this->statusSync->updateFulfillmentStatus($fulfillment, FulfillmentStatusCode::DISPATCHED_TO_POST);
            $this->appendTimeline($fulfillment, $staff, 'dispatched_to_post', 'Pochtaga topshirildi');
        } else {
            $fulfillment->last_mile_mode = 'courier_delivery';
            $fulfillment->save();
            $this->courierTaskOrchestratorService->ensureLastMileTask($fulfillment->fresh());
            $fulfillment = $this->statusSync->updateFulfillmentStatus($fulfillment, FulfillmentStatusCode::ASSIGNED_LAST_MILE);
            $this->appendTimeline($fulfillment, $staff, 'assigned_last_mile', 'Last-mile kuryerga uzatildi');
        }
        $this->clearException($fulfillment);
        $fulfillment->save();

        return response()->json(['status' => 'success', 'fulfillment' => $this->serializeFulfillment($fulfillment->fresh())]);
    }

    public function reportException(Request $request, OrderFulfillment $fulfillment)
    {
        $staff = $this->staff($request);
        if (! $this->hubRoleAccessService->can($staff, 'queue.exception.report')) {
            return response()->json(['status' => 'error', 'message' => 'Exception yozish sizga ruxsat etilmagan.'], 403);
        }
        if ($response = $this->ensureSameHub($staff, $fulfillment)) {
            return $response;
        }

        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                Rule::in([
                    'missing_item',
                    'damaged_item',
                    'wrong_item',
                    'wrong_hub',
                    'damaged_package',
                    'label_problem',
                    'other',
                ]),
            ],
            'note' => ['required', 'string', 'max:1000'],
        ]);

        $meta = $fulfillment->meta ?? [];
        Arr::set($meta, 'exception', [
            'code' => $validated['code'],
            'note' => trim($validated['note']),
            'reported_at' => now()->toIso8601String(),
            'reported_by' => [
                'id' => $staff->id,
                'name' => $staff->full_name,
                'role' => $staff->role,
            ],
            'resolved_at' => null,
            'resolved_by' => null,
        ]);
        $fulfillment->meta = $meta;
        $this->appendTimeline($fulfillment, $staff, 'exception_reported', 'Muammo qayd etildi', [
            'exception_code' => $validated['code'],
            'note' => trim($validated['note']),
        ]);
        $fulfillment->save();

        return response()->json(['status' => 'success', 'fulfillment' => $this->serializeFulfillment($fulfillment->fresh())]);
    }

    public function resolveException(Request $request, OrderFulfillment $fulfillment)
    {
        $staff = $this->staff($request);
        if (! $this->hubRoleAccessService->can($staff, 'queue.exception.resolve')) {
            return response()->json(['status' => 'error', 'message' => 'Exception yopish sizga ruxsat etilmagan.'], 403);
        }
        if ($response = $this->ensureSameHub($staff, $fulfillment)) {
            return $response;
        }

        $meta = $fulfillment->meta ?? [];
        $exception = Arr::get($meta, 'exception');
        if (! is_array($exception) || empty($exception['code'])) {
            return response()->json(['status' => 'error', 'message' => 'Faol muammo topilmadi.'], 422);
        }

        Arr::set($meta, 'exception.resolved_at', now()->toIso8601String());
        Arr::set($meta, 'exception.resolved_by', [
            'id' => $staff->id,
            'name' => $staff->full_name,
            'role' => $staff->role,
        ]);
        $fulfillment->meta = $meta;
        $this->appendTimeline($fulfillment, $staff, 'exception_resolved', 'Muammo yopildi', [
            'exception_code' => $exception['code'],
        ]);
        $fulfillment->save();

        return response()->json(['status' => 'success', 'fulfillment' => $this->serializeFulfillment($fulfillment->fresh())]);
    }

    private function staff(Request $request): HubStaff
    {
        /** @var HubStaff $staff */
        $staff = $request->user('hub');
        $staff->loadMissing('hub:id,name,code,city_name,country_code,is_active');

        return $staff;
    }

    private function ensureSameHub(HubStaff $staff, OrderFulfillment $fulfillment)
    {
        if ((int) $fulfillment->hub_id !== (int) $staff->hub_id) {
            return response()->json(['status' => 'error', 'message' => 'Bu fulfillment boshqa hubga tegishli.'], 403);
        }

        return null;
    }

    private function baseQuery(int $hubId)
    {
        return OrderFulfillment::query()
            ->with([
                'order.user:id,name,lastname,phone_number',
                'hub:id,name,code',
                'courierTasks.courier:id,first_name,last_name,phone_number,photo,is_online,current_lat,current_lon,location_updated_at',
            ])
            ->where('hub_id', $hubId);
    }

    private function activeCourierTaskStatuses(): array
    {
        return [
            CourierTaskStatusCode::ASSIGNED->value,
            CourierTaskStatusCode::ACCEPTED->value,
            CourierTaskStatusCode::ARRIVED_AT_PICKUP->value,
            CourierTaskStatusCode::PICKED_UP->value,
            CourierTaskStatusCode::DROPPED_OFF->value,
        ];
    }

    private function clearException(OrderFulfillment $fulfillment): void
    {
        $meta = $fulfillment->meta ?? [];
        $exception = Arr::get($meta, 'exception');

        if (is_array($exception) && ! empty($exception['code']) && empty($exception['resolved_at'])) {
            Arr::set($meta, 'exception.resolved_at', now()->toIso8601String());
            Arr::set($meta, 'exception.resolved_by', [
                'id' => null,
                'name' => 'system',
                'role' => 'system',
            ]);
            $fulfillment->meta = $meta;
        }
    }

    private function appendTimeline(OrderFulfillment $fulfillment, HubStaff $staff, string $code, string $title, array $extra = []): void
    {
        $meta = $fulfillment->meta ?? [];
        $timeline = collect(Arr::get($meta, 'timeline', []))
            ->filter(fn ($row) => is_array($row))
            ->values()
            ->all();

        $timeline[] = array_merge([
            'code' => $code,
            'title' => $title,
            'at' => now()->toIso8601String(),
            'actor' => [
                'id' => $staff->id,
                'name' => $staff->full_name,
                'role' => $staff->role,
            ],
        ], $extra);

        Arr::set($meta, 'timeline', $timeline);
        $fulfillment->meta = $meta;
    }

    /**
     * Lightweight row for list endpoints (queue / exceptions).
     *
     * Drops the heavy detail-only payload — full timeline, print history,
     * per-stage timestamps and inactive courier tasks — keeping only what the
     * hub app list card renders. The full payload is still served by show()
     * and scan() via serializeFulfillment(). The Flutter client tolerates the
     * omitted keys (they default to empty), so this is backward compatible.
     */
    private function serializeListItem(OrderFulfillment $fulfillment): array
    {
        $order = $fulfillment->order;
        $address = collect($order?->address ?? [])->first() ?? [];
        $exception = Arr::get($fulfillment->meta ?? [], 'exception');
        $activeStatuses = $this->activeCourierTaskStatuses();

        return [
            'id' => $fulfillment->id,
            'order_id' => $fulfillment->order_id,
            'status_code' => $fulfillment->status_code,
            'fulfillment_mode' => $fulfillment->fulfillment_mode,
            'first_mile_mode' => $fulfillment->first_mile_mode,
            'last_mile_mode' => $fulfillment->last_mile_mode,
            'is_cod' => (bool) $fulfillment->is_cod,
            'cash_collect_amount' => (int) ($fulfillment->cash_collect_amount ?? 0),
            'label_code' => $fulfillment->label_code,
            'postal_tracking_number' => $fulfillment->postal_tracking_number,
            'exception' => is_array($exception) ? $exception : null,
            'courier_tasks' => $fulfillment->courierTasks
                ->filter(fn (CourierTask $task) => $task->courier_id
                    && in_array($task->status_code, $activeStatuses, true))
                ->sortByDesc('id')
                ->map(fn (CourierTask $task) => [
                    'id' => (int) $task->id,
                    'leg' => (string) $task->leg,
                    'status_code' => (string) $task->status_code,
                    'is_active' => true,
                    'courier' => $task->courier ? [
                        'id' => (int) $task->courier->id,
                        'name' => trim((string) ($task->courier->first_name.' '.$task->courier->last_name)),
                    ] : null,
                ])
                ->values()
                ->all(),
            'order' => $order ? [
                'id' => $order->id,
                'amount' => (int) $order->amount,
                'delivery_price' => (int) ($order->deliveryPrice ?? 0),
                'customer' => [
                    'name' => trim((string) (($order->user?->name ?? '').' '.($order->user?->lastname ?? ''))),
                    'phone_number' => $order->user?->phone_number,
                ],
                'address' => [
                    'full_address' => $address['fullAddress'] ?? $address['branch_address'] ?? null,
                ],
            ] : null,
        ];
    }

    private function serializeFulfillment(OrderFulfillment $fulfillment): array
    {
        $fulfillment->loadMissing([
            'order.user:id,name,lastname,phone_number',
            'hub:id,name,code',
            'courierTasks.courier:id,first_name,last_name,phone_number,photo,is_online,current_lat,current_lon,location_updated_at',
        ]);
        $order = $fulfillment->order;
        $address = collect($order?->address ?? [])->first() ?? [];
        $exception = Arr::get($fulfillment->meta ?? [], 'exception');

        return [
            'id' => $fulfillment->id,
            'order_id' => $fulfillment->order_id,
            'status_code' => $fulfillment->status_code,
            'fulfillment_mode' => $fulfillment->fulfillment_mode,
            'first_mile_mode' => $fulfillment->first_mile_mode,
            'last_mile_mode' => $fulfillment->last_mile_mode,
            'is_cod' => (bool) $fulfillment->is_cod,
            'cash_collect_amount' => (int) ($fulfillment->cash_collect_amount ?? 0),
            'hub' => $fulfillment->hub?->only(['id', 'name', 'code']),
            'label_code' => $fulfillment->label_code,
            'postal_tracking_number' => $fulfillment->postal_tracking_number,
            'timestamps' => [
                'ready_for_pickup_at' => optional($fulfillment->ready_for_pickup_at)?->toIso8601String(),
                'picked_from_seller_at' => optional($fulfillment->picked_from_seller_at)?->toIso8601String(),
                'arrived_at_hub_at' => optional($fulfillment->arrived_at_hub_at)?->toIso8601String(),
                'qc_checked_at' => optional($fulfillment->qc_checked_at)?->toIso8601String(),
                'packed_at' => optional($fulfillment->packed_at)?->toIso8601String(),
                'labeled_at' => optional($fulfillment->labeled_at)?->toIso8601String(),
                'dispatched_to_post_at' => optional($fulfillment->dispatched_to_post_at)?->toIso8601String(),
                'assigned_last_mile_at' => optional($fulfillment->assigned_last_mile_at)?->toIso8601String(),
                'out_for_delivery_at' => optional($fulfillment->out_for_delivery_at)?->toIso8601String(),
                'delivered_at' => optional($fulfillment->delivered_at)?->toIso8601String(),
            ],
            'timeline' => $this->buildTimeline($fulfillment),
            'exception' => is_array($exception) ? $exception : null,
            'prints' => collect(Arr::get($fulfillment->meta ?? [], 'prints', []))
                ->filter(fn ($row) => is_array($row))
                ->values()
                ->all(),
            'courier_tasks' => $fulfillment->courierTasks
                ->sortByDesc('id')
                ->map(function (CourierTask $task): array {
                    $courier = $task->courier;
                    $photo = trim((string) ($courier?->photo ?? ''));

                    return [
                        'id' => (int) $task->id,
                        'leg' => (string) $task->leg,
                        'status_code' => (string) $task->status_code,
                        'is_active' => in_array($task->status_code, $this->activeCourierTaskStatuses(), true),
                        'distance_km' => (float) ($task->distance_km ?? 0),
                        'pickup' => $task->pickup_address,
                        'dropoff' => $task->dropoff_address,
                        'assigned_at' => optional($task->assigned_at)?->toIso8601String(),
                        'accepted_at' => optional($task->accepted_at)?->toIso8601String(),
                        'picked_up_at' => optional($task->picked_up_at)?->toIso8601String(),
                        'completed_at' => optional($task->completed_at)?->toIso8601String(),
                        'courier' => $courier ? [
                            'id' => (int) $courier->id,
                            'name' => trim((string) ($courier->first_name.' '.$courier->last_name)),
                            'phone_number' => (string) ($courier->phone_number ?? ''),
                            'photo_url' => $photo !== '' ? asset('storage/'.$photo) : null,
                            'is_online' => (bool) $courier->is_online,
                            'location' => [
                                'lat' => $courier->current_lat,
                                'lon' => $courier->current_lon,
                                'updated_at' => optional($courier->location_updated_at)?->toIso8601String(),
                            ],
                        ] : null,
                    ];
                })
                ->values()
                ->all(),
            'order' => $order ? [
                'id' => $order->id,
                'amount' => (int) $order->amount,
                'delivery_price' => (int) ($order->deliveryPrice ?? 0),
                'customer' => [
                    'name' => trim((string) (($order->user?->name ?? '').' '.($order->user?->lastname ?? ''))),
                    'phone_number' => $order->user?->phone_number,
                ],
                'address' => [
                    'full_address' => $address['fullAddress'] ?? $address['branch_address'] ?? null,
                    'lat' => $address['lat'] ?? null,
                    'lon' => $address['lon'] ?? null,
                ],
            ] : null,
        ];
    }

    private function buildTimeline(OrderFulfillment $fulfillment): array
    {
        $timeline = new Collection;

        $pushTimestamp = function (?string $at, string $code, string $title) use ($timeline): void {
            if (! $at) {
                return;
            }

            $timeline->push([
                'code' => $code,
                'title' => $title,
                'at' => $at,
                'actor' => null,
            ]);
        };

        $timestamps = [
            ['at' => optional($fulfillment->ready_for_pickup_at)?->toIso8601String(), 'code' => 'ready_for_pickup', 'title' => 'Seller pickupga tayyorladi'],
            ['at' => optional($fulfillment->picked_from_seller_at)?->toIso8601String(), 'code' => 'picked_from_seller', 'title' => 'Sellerdan olib ketildi'],
            ['at' => optional($fulfillment->arrived_at_hub_at)?->toIso8601String(), 'code' => 'arrived_at_hub', 'title' => 'Hubga qabul qilindi'],
            ['at' => optional($fulfillment->qc_checked_at)?->toIso8601String(), 'code' => 'qc_checked', 'title' => 'QC yakunlandi'],
            ['at' => optional($fulfillment->packed_at)?->toIso8601String(), 'code' => 'packed', 'title' => 'Qadoqlandi'],
            ['at' => optional($fulfillment->labeled_at)?->toIso8601String(), 'code' => 'labeled', 'title' => 'Etiketka tayyorlandi'],
            ['at' => optional($fulfillment->dispatched_to_post_at)?->toIso8601String(), 'code' => 'dispatched_to_post', 'title' => 'Pochtaga topshirildi'],
            ['at' => optional($fulfillment->assigned_last_mile_at)?->toIso8601String(), 'code' => 'assigned_last_mile', 'title' => 'Last-milega uzatildi'],
            ['at' => optional($fulfillment->out_for_delivery_at)?->toIso8601String(), 'code' => 'out_for_delivery', 'title' => 'Mijozga yo‘l oldi'],
            ['at' => optional($fulfillment->delivered_at)?->toIso8601String(), 'code' => 'delivered', 'title' => 'Buyurtma topshirildi'],
        ];

        foreach ($timestamps as $timestamp) {
            $pushTimestamp($timestamp['at'], $timestamp['code'], $timestamp['title']);
        }

        collect(Arr::get($fulfillment->meta ?? [], 'timeline', []))
            ->filter(fn ($row) => is_array($row) && ! empty($row['at']) && ! empty($row['title']))
            ->each(fn ($row) => $timeline->push($row));

        return $timeline
            ->sortBy('at')
            ->values()
            ->all();
    }

    private function dashboardAnalytics(int $hubId): array
    {
        $base = OrderFulfillment::query()
            ->where('hub_id', $hubId)
            ->where('created_at', '>=', now()->subDays(7));

        $avgInboundMinutes = (int) round((float) ((clone $base)
            ->whereNotNull('picked_from_seller_at')
            ->whereNotNull('arrived_at_hub_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, picked_from_seller_at, arrived_at_hub_at)) as avg_minutes')
            ->value('avg_minutes') ?? 0));

        $avgPackingMinutes = (int) round((float) ((clone $base)
            ->whereNotNull('qc_checked_at')
            ->whereNotNull('packed_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, qc_checked_at, packed_at)) as avg_minutes')
            ->value('avg_minutes') ?? 0));

        $avgDispatchMinutes = (int) round((float) ((clone $base)
            ->whereNotNull('labeled_at')
            ->where(function ($query) {
                $query->whereNotNull('dispatched_to_post_at')
                    ->orWhereNotNull('assigned_last_mile_at');
            })
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, labeled_at, COALESCE(dispatched_to_post_at, assigned_last_mile_at))) as avg_minutes')
            ->value('avg_minutes') ?? 0));

        $total = (int) (clone $base)->count();
        $codCount = (int) (clone $base)->where('is_cod', true)->count();
        $exceptionCount = (int) (clone $base)->whereNotNull('meta->exception->code')->count();
        $recent = (clone $base)->get();
        $labelPrints = 0;
        $receiptPrints = 0;

        foreach ($recent as $fulfillment) {
            $prints = collect(Arr::get($fulfillment->meta ?? [], 'prints', []))
                ->filter(fn ($row) => is_array($row));
            $labelPrints += $prints->where('type', 'label')->count();
            $receiptPrints += $prints->where('type', 'receipt')->count();
        }

        return [
            'window_days' => 7,
            'avg_inbound_minutes' => $avgInboundMinutes,
            'avg_packing_minutes' => $avgPackingMinutes,
            'avg_dispatch_minutes' => $avgDispatchMinutes,
            'cod_share_percent' => $total > 0 ? (int) round(($codCount / $total) * 100) : 0,
            'exception_rate_percent' => $total > 0 ? (int) round(($exceptionCount / $total) * 100) : 0,
            'label_prints' => $labelPrints,
            'receipt_prints' => $receiptPrints,
        ];
    }
}
