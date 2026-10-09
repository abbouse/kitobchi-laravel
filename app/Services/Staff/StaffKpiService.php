<?php

namespace App\Services\Staff;

use App\Models\Admin;
use App\Models\AdminAuditLog;
use App\Services\Support\SupportInboxService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Xodimlar KPI: kim qancha ish qildi.
 *
 * Manbalar:
 *  - admin_audit_logs — paneldagi har bir o'zgartiruvchi amal (modul bo'yicha);
 *  - domen jadvallari — kim tasdiqlagani aniq yozilgan joylar
 *    (kitob arizalari, katalog joylari, pul qaytarish, chiqimlar, HR javoblari);
 *  - support inbox — javoblar, yopilganlar, birinchi javob vaqti, baholar.
 */
class StaffKpiService
{
    public function __construct(private SupportInboxService $support)
    {
    }

    /** Bitta xodimning shaxsiy ko'rsatkichlari (dashboard "Mening ishim" va jamoa sahifasidagi batafsil oyna). */
    public function personal(Admin $admin, Carbon $from, Carbon $to, bool $withRecent = true): array
    {
        $activity = $this->activity([$admin->id], $from, $to)[$admin->id] ?? $this->emptyActivity($from, $to);
        $domain = $this->domain([$admin->id], $from, $to)[$admin->id] ?? [];
        $support = $admin->hasPermission('support') ? $this->supportRow($admin->id, $from, $to) : null;

        return [
            'range' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'admin' => $this->adminCard($admin),
            'activity' => $activity,
            'domain' => $domain,
            'support' => $support,
            'highlights' => $this->highlights($admin, $activity, $domain, $support),
            'recent' => $withRecent ? $this->recent($admin->id, 12) : [],
        ];
    }

    /** Jamoa jadvali: barcha faol adminlar. */
    public function team(Carbon $from, Carbon $to): array
    {
        $admins = Admin::query()->orderByDesc('is_active')->orderBy('name')->get();
        $ids = $admins->pluck('id')->map(fn ($id) => (int) $id)->all();

        $activity = $this->activity($ids, $from, $to);
        $domain = $this->domain($ids, $from, $to);
        $supportAgents = collect($this->support->kpi($from, $to)['agents'] ?? [])->keyBy('id');

        $rows = $admins->map(function (Admin $admin) use ($activity, $domain, $supportAgents, $from, $to) {
            $act = $activity[$admin->id] ?? $this->emptyActivity($from, $to);
            $sup = $supportAgents->get($admin->id);

            return [
                ...$this->adminCard($admin),
                'actions' => $act['total'],
                'activeDays' => $act['activeDays'],
                'failed' => $act['failed'],
                'modules' => array_slice($act['modules'], 0, 4),
                'days' => array_column($act['days'], 'count'),
                'domain' => $domain[$admin->id] ?? [],
                'support' => $sup ? [
                    'replies' => $sup['replies'],
                    'closed' => $sup['closed'],
                    'good' => $sup['good'],
                    'bad' => $sup['bad'],
                    'satisfaction' => $sup['satisfaction'],
                    'avgFirstResponse' => $sup['avg_first_response_s'],
                ] : null,
            ];
        })->values();

        $total = $rows->sum('actions');

        return [
            'range' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'rows' => $rows->all(),
            'totals' => [
                'admins' => $rows->where('isActive', true)->count(),
                'activeAdmins' => $rows->filter(fn ($r) => $r['actions'] > 0)->count(),
                'actions' => $total,
                'failed' => $rows->sum('failed'),
                'catalogReviews' => $rows->sum(fn ($r) => (int) ($r['domain']['catalogReviews'] ?? 0)),
                'supportReplies' => $rows->sum(fn ($r) => (int) ($r['support']['replies'] ?? 0)),
            ],
            'modules' => $this->moduleTotals($activity),
            'days' => $this->teamDays($activity, $from, $to),
        ];
    }

    // ─── AUDIT LOG ───────────────────────────────────────────────────────────

    /**
     * @param  list<int>  $adminIds
     * @return array<int, array{total:int, failed:int, activeDays:int, modules:list<array>, days:list<array>}>
     */
    public function activity(array $adminIds, Carbon $from, Carbon $to): array
    {
        if ($adminIds === [] || ! Schema::hasTable('admin_audit_logs')) {
            return [];
        }

        $rows = DB::table('admin_audit_logs')
            ->whereIn('admin_id', $adminIds)
            ->whereBetween('created_at', [$from, $to])
            ->where('method', '!=', 'GET')
            ->where(fn ($q) => $q->whereNull('route_name')->orWhere('route_name', '!=', 'boshqaruv.logout'))
            ->selectRaw('admin_id, route_name, DATE(created_at) AS d, COUNT(*) AS n, SUM(CASE WHEN status_code >= 400 THEN 1 ELSE 0 END) AS failed')
            ->groupBy('admin_id', 'route_name', DB::raw('DATE(created_at)'))
            ->get();

        $out = [];
        foreach ($rows->groupBy('admin_id') as $adminId => $items) {
            $modules = [];
            $days = [];
            foreach ($items as $row) {
                $module = StaffModules::moduleOf($row->route_name);
                $modules[$module] = ($modules[$module] ?? 0) + (int) $row->n;
                $days[(string) $row->d] = ($days[(string) $row->d] ?? 0) + (int) $row->n;
            }
            arsort($modules);

            $out[(int) $adminId] = [
                'total' => (int) $items->sum('n'),
                'failed' => (int) $items->sum('failed'),
                'activeDays' => count($days),
                'modules' => collect($modules)->map(fn ($count, $module) => [
                    'module' => $module,
                    'label' => StaffModules::label($module),
                    'count' => $count,
                ])->values()->all(),
                'days' => $this->fillDays($days, $from, $to),
            ];
        }

        return $out;
    }

    private function emptyActivity(Carbon $from, Carbon $to): array
    {
        return ['total' => 0, 'failed' => 0, 'activeDays' => 0, 'modules' => [], 'days' => $this->fillDays([], $from, $to)];
    }

    private function fillDays(array $days, Carbon $from, Carbon $to): array
    {
        $out = [];
        $cursor = $from->copy()->startOfDay();
        $end = $to->copy()->startOfDay();
        $guard = 0;
        while ($cursor->lte($end) && $guard++ < 93) {
            $key = $cursor->toDateString();
            $out[] = ['date' => $key, 'count' => (int) ($days[$key] ?? 0)];
            $cursor->addDay();
        }

        return $out;
    }

    private function moduleTotals(array $activity): array
    {
        $totals = [];
        foreach ($activity as $row) {
            foreach ($row['modules'] as $m) {
                $totals[$m['module']] = ($totals[$m['module']] ?? 0) + $m['count'];
            }
        }
        arsort($totals);

        return collect($totals)->map(fn ($count, $module) => ['module' => $module, 'label' => StaffModules::label($module), 'count' => $count])->values()->all();
    }

    private function teamDays(array $activity, Carbon $from, Carbon $to): array
    {
        $days = [];
        foreach ($activity as $row) {
            foreach ($row['days'] as $d) {
                $days[$d['date']] = ($days[$d['date']] ?? 0) + $d['count'];
            }
        }

        return $this->fillDays($days, $from, $to);
    }

    /** So'nggi amallar (odam o'qiydigan ko'rinishda). */
    public function recent(int $adminId, int $limit = 12): array
    {
        if (! Schema::hasTable('admin_audit_logs')) {
            return [];
        }

        return AdminAuditLog::query()
            ->where('admin_id', $adminId)
            ->where('method', '!=', 'GET')
            ->where(fn ($q) => $q->whereNull('route_name')->orWhere('route_name', '!=', 'boshqaruv.logout'))
            ->latest('id')
            ->limit($limit)
            ->get(['id', 'route_name', 'method', 'target_id', 'status_code', 'created_at'])
            ->map(fn ($log) => [
                'id' => (int) $log->id,
                'text' => StaffModules::describe($log->route_name, $log->method, $log->target_id ? (string) $log->target_id : null),
                'module' => StaffModules::moduleOf($log->route_name),
                'moduleLabel' => StaffModules::label(StaffModules::moduleOf($log->route_name)),
                'ok' => (int) $log->status_code < 400,
                'at' => optional($log->created_at)->toIso8601String(),
            ])->all();
    }

    // ─── DOMEN KO'RSATKICHLARI ───────────────────────────────────────────────

    /**
     * @param  list<int>  $ids
     * @return array<int, array<string, int>>
     */
    public function domain(array $ids, Carbon $from, Carbon $to): array
    {
        $out = [];
        $add = function (string $metric, $rows) use (&$out) {
            foreach ($rows as $row) {
                $out[(int) $row->a][$metric] = (int) $row->n;
            }
        };

        $count = fn (string $table, string $actor, string $time, ?callable $extra = null) => DB::table($table)
            ->whereIn($actor, $ids)
            ->whereBetween($time, [$from, $to])
            ->when($extra, $extra)
            ->selectRaw("$actor AS a, COUNT(*) AS n")
            ->groupBy($actor)
            ->get();

        if ($ids === []) {
            return [];
        }

        if ($this->has('book_edition_submissions', ['reviewer_id', 'reviewed_at'])) {
            $add('catalogReviews', $count('book_edition_submissions', 'reviewer_id', 'reviewed_at'));
        }
        if ($this->has('catalog_slot_purchases', ['reviewer_id', 'reviewed_at'])) {
            $add('slotReviews', $count('catalog_slot_purchases', 'reviewer_id', 'reviewed_at'));
        }
        if ($this->has('order_refunds', ['processed_by_admin_id', 'created_at'])) {
            $add('refunds', $count('order_refunds', 'processed_by_admin_id', 'created_at'));
        }
        if ($this->has('platform_expenses', ['created_by', 'created_at'])) {
            $add('expenses', $count('platform_expenses', 'created_by', 'created_at'));
        }
        if ($this->has('career_application_messages', ['admin_id', 'created_at'])) {
            $add('hrReplies', $count('career_application_messages', 'admin_id', 'created_at'));
        }
        if ($this->has('seller_contract_history', ['performed_by', 'created_at'])) {
            $add('contracts', $count('seller_contract_history', 'performed_by', 'created_at'));
        }

        // Audit log orqali: moderatsiya va tasdiqlash amallari
        if (Schema::hasTable('admin_audit_logs')) {
            $routes = [
                'productModeration' => ['boshqaruv.books.moderate', 'boshqaruv.stationery.moderate'],
                'sellerApprovals' => ['boshqaruv.sellers.approve', 'boshqaruv.sellers.reject'],
                'courierApprovals' => ['boshqaruv.couriers.approve', 'boshqaruv.couriers.reject'],
                'payouts' => ['boshqaruv.transactions.approve', 'boshqaruv.transactions.reject', 'boshqaruv.courier-transactions.approve', 'boshqaruv.courier-transactions.reject'],
                'orderUpdates' => ['boshqaruv.orders.status', 'boshqaruv.orders.cancel', 'boshqaruv.orders.refund-cancel', 'boshqaruv.seller-orders.status', 'boshqaruv.courier-orders.status', 'boshqaruv.courier-orders.assign'],
                'bookClubModeration' => ['boshqaruv.book-club.moderation', 'boshqaruv.book-club.comment.moderation', 'boshqaruv.book-club.destroy', 'boshqaruv.book-club.comment.delete', 'boshqaruv.book-club.warn'],
                'complaints' => ['boshqaruv.complaints.status'],
                'pushSent' => ['boshqaruv.push.store', 'boshqaruv.push.resend'],
            ];
            foreach ($routes as $metric => $names) {
                $add($metric, DB::table('admin_audit_logs')
                    ->whereIn('admin_id', $ids)
                    ->whereBetween('created_at', [$from, $to])
                    ->whereIn('route_name', $names)
                    ->where('status_code', '<', 400)
                    ->selectRaw('admin_id AS a, COUNT(*) AS n')
                    ->groupBy('admin_id')
                    ->get());
            }
        }

        return $out;
    }

    private function supportRow(int $adminId, Carbon $from, Carbon $to): ?array
    {
        $row = collect($this->support->kpi($from, $to)['agents'] ?? [])->firstWhere('id', $adminId);
        if (! $row) {
            return null;
        }

        return [
            'replies' => $row['replies'],
            'closed' => $row['closed'],
            'handled' => $row['handled'],
            'good' => $row['good'],
            'bad' => $row['bad'],
            'satisfaction' => $row['satisfaction'],
            'avgFirstResponse' => $row['avg_first_response_s'],
            'openNow' => $row['open_now'],
        ];
    }

    /**
     * Rolga mos asosiy ko'rsatkichlar (eng ko'pi bilan 4 ta karta).
     *
     * @return list<array{label:string, value:int|string, hint:string, tone:string, icon:string}>
     */
    private function highlights(Admin $admin, array $activity, array $domain, ?array $support): array
    {
        $cards = [];
        $can = fn (string $m) => $admin->isSuperAdmin() || $admin->hasPermission($m);

        if ($support) {
            $cards[] = ['label' => 'Javoblar', 'value' => $support['replies'], 'hint' => 'Mijoz va do‘konlarga', 'tone' => 'primary', 'icon' => 'ti-message-check'];
            $cards[] = ['label' => 'Yopilgan suhbatlar', 'value' => $support['closed'], 'hint' => 'Hal qilingan murojaatlar', 'tone' => 'success', 'icon' => 'ti-circle-check'];
            $cards[] = [
                'label' => 'Mamnunlik',
                'value' => $support['satisfaction'] === null ? '—' : $support['satisfaction'] . '%',
                'hint' => "{$support['good']} yaxshi · {$support['bad']} yomon",
                'tone' => $support['satisfaction'] === null ? 'secondary' : ($support['satisfaction'] >= 85 ? 'success' : ($support['satisfaction'] >= 60 ? 'warning' : 'danger')),
                'icon' => 'ti-mood-happy',
            ];
        }
        if ($can('catalog')) {
            $cards[] = ['label' => 'Katalog qarorlari', 'value' => ($domain['catalogReviews'] ?? 0) + ($domain['productModeration'] ?? 0) + ($domain['slotReviews'] ?? 0), 'hint' => 'Arizalar, moderatsiya, katalog joylari', 'tone' => 'info', 'icon' => 'ti-books'];
        }
        if ($can('orders')) {
            $cards[] = ['label' => 'Buyurtma amallari', 'value' => $domain['orderUpdates'] ?? 0, 'hint' => 'Holat, bekor qilish, kuryer biriktirish', 'tone' => 'primary', 'icon' => 'ti-truck-delivery'];
        }
        if ($can('finance')) {
            $cards[] = ['label' => 'To‘lov qarorlari', 'value' => ($domain['payouts'] ?? 0) + ($domain['refunds'] ?? 0), 'hint' => 'Pul yechish va qaytarishlar', 'tone' => 'success', 'icon' => 'ti-cash'];
        }
        if ($can('sellers') || $can('couriers')) {
            $cards[] = ['label' => 'Hamkor arizalari', 'value' => ($domain['sellerApprovals'] ?? 0) + ($domain['courierApprovals'] ?? 0), 'hint' => 'Do‘kon va kuryerlarni tasdiqlash', 'tone' => 'warning', 'icon' => 'ti-user-check'];
        }
        if ($can('book-club')) {
            $cards[] = ['label' => 'Book Club moderatsiya', 'value' => $domain['bookClubModeration'] ?? 0, 'hint' => 'Post va izohlar bo‘yicha qarorlar', 'tone' => 'info', 'icon' => 'ti-bookmark'];
        }
        if ($can('hr')) {
            $cards[] = ['label' => 'Nomzodlarga javob', 'value' => $domain['hrReplies'] ?? 0, 'hint' => 'Karyera arizalari', 'tone' => 'primary', 'icon' => 'ti-id-badge'];
        }

        array_unshift($cards, ['label' => 'Jami amallar', 'value' => $activity['total'], 'hint' => "{$activity['activeDays']} kun faol", 'tone' => 'primary', 'icon' => 'ti-activity']);

        return array_slice($cards, 0, 4);
    }

    private function adminCard(Admin $admin): array
    {
        return [
            'id' => (int) $admin->id,
            'name' => (string) $admin->name,
            'email' => (string) $admin->email,
            'role' => (string) ($admin->role_label ?? $admin->role),
            'roleKey' => (string) $admin->role,
            'isActive' => (bool) ($admin->is_active ?? true),
            'isReadOnly' => (bool) ($admin->is_read_only ?? false),
            'lastLogin' => optional($admin->last_login_at)->toIso8601String(),
        ];
    }

    private function has(string $table, array $columns): bool
    {
        if (! Schema::hasTable($table)) {
            return false;
        }
        foreach ($columns as $column) {
            if (! Schema::hasColumn($table, $column)) {
                return false;
            }
        }

        return true;
    }
}
