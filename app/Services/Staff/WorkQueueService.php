<?php

namespace App\Services\Staff;

use App\Models\Admin;
use App\Support\CourierLimits;
use App\Support\DeliveryRescheduler;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Har bir xodim uchun "diqqat talab qiladigan ishlar" navbati — faqat
 * uning ruxsati bor modullar bo'yicha. Dashboard, sidebar badge'lari va
 * header'dagi bildirishnoma qo'ng'irog'i shu yerdan oladi.
 *
 * Har bir element: key, module, label, hint, count, tone (danger|warning|info|primary), icon, url, nav (sidebar yo'li)
 */
class WorkQueueService
{
    private const TTL_SECONDS = 30;

    public function forAdmin(?Admin $admin): array
    {
        if (! $admin) {
            return [];
        }

        $modules = $this->modulesFor($admin);
        $all = Cache::remember('staff:work-queues:v1', self::TTL_SECONDS, fn () => $this->compute());
        $personal = $this->personal($admin, $modules);

        $items = array_values(array_filter(
            array_merge($personal, $all),
            fn (array $item) => in_array($item['module'], $modules, true)
        ));

        usort($items, function ($a, $b) {
            $rank = ['danger' => 0, 'warning' => 1, 'primary' => 2, 'info' => 3];

            return [($a['count'] > 0 ? 0 : 1), $rank[$a['tone']] ?? 9, -$a['count']]
                <=> [($b['count'] > 0 ? 0 : 1), $rank[$b['tone']] ?? 9, -$b['count']];
        });

        return $items;
    }

    /**
     * Sidebar badge'lari: nav yo'li → son (faqat > 0).
     *
     * @return array<string, int>
     */
    public function navCounts(?Admin $admin): array
    {
        $counts = [];
        foreach ($this->forAdmin($admin) as $item) {
            if (($item['nav'] ?? null) && $item['count'] > 0 && ($item['badge'] ?? true)) {
                $counts[$item['nav']] = ($counts[$item['nav']] ?? 0) + $item['count'];
            }
        }

        return $counts;
    }

    /** @return list<string> */
    public function modulesFor(Admin $admin): array
    {
        if ($admin->isSuperAdmin()) {
            return array_keys(Admin::MODULES);
        }

        return array_values(array_filter(array_keys(Admin::MODULES), fn ($key) => $admin->hasPermission($key)));
    }

    /** Xodimga shaxsan tegishli navbatlar (keshsiz). */
    private function personal(Admin $admin, array $modules): array
    {
        $items = [];
        if (in_array('support', $modules, true) && Schema::hasColumn('bot_tickets', 'admin_id')) {
            $mine = $this->safe(fn () => DB::table('bot_tickets')->where('admin_id', $admin->id)->whereIn('status', ['queue', 'active'])->count()
                + (Schema::hasTable('seller_support_tickets') ? DB::table('seller_support_tickets')->where('admin_id', $admin->id)->where('status', '!=', 'closed')->count() : 0));
            $items[] = $this->item('support.mine', 'support', 'Mening ochiq suhbatlarim', 'Sizga biriktirilgan va hali yopilmagan', $mine, 'primary', 'ti-user-check', '/boshqaruv/support/inbox?filter=mine', null);
        }

        return $items;
    }

    private function compute(): array
    {
        $q = [];

        // ── Buyurtmalar ──
        if (Schema::hasTable('solds')) {
            $q[] = $this->item('orders.new', 'orders', 'Yangi buyurtmalar', 'Kutilmoqda va qadoqlanmoqda', $this->safe(fn () => $this->statusCount(['pending', 'packing'], ['A', 'P'])), 'primary', 'ti-shopping-bag', '/boshqaruv/orders?orders_tab=pending', '/boshqaruv/orders');
            $q[] = $this->item('orders.stale', 'orders', '24 soatdan oshgan', 'Yangi yoki qadoqda turib qolgan buyurtmalar', $this->safe(fn () => $this->statusCount(['pending', 'packing'], ['A', 'P'], now()->subDay())), 'danger', 'ti-clock-exclamation', '/boshqaruv/orders?orders_tab=pending', null, false);
            if (Schema::hasColumn('solds', 'delivery_date')) {
                $q[] = $this->item('orders.today', 'orders', 'Bugun yetkaziladi', 'Bugunga rejalashtirilgan, hali yetkazilmagan', $this->safe(fn () => DeliveryRescheduler::scopeActive(\App\Models\Sold::query())->whereDate('delivery_date', today())->count()), 'info', 'ti-calendar-event', '/boshqaruv/orders?orders_day=' . today()->toDateString(), null, false);
                $q[] = $this->item('orders.overdue', 'orders', 'Kechikkan yetkazishlar', 'Kuni o‘tgan, hali yetkazilmagan', $this->safe(fn () => DeliveryRescheduler::overdueQuery()->count()), 'danger', 'ti-alert-triangle', '/boshqaruv/orders?orders_day=overdue', null, false);
            }
        }

        // ── Do'konlar ──
        if (Schema::hasTable('sellers')) {
            $q[] = $this->item('sellers.pending', 'sellers', 'Tasdiq kutayotgan do‘konlar', 'Yangi ro‘yxatdan o‘tgan sotuvchilar', $this->safe(fn () => DB::table('sellers')->where('status', 'pending')->where(fn ($w) => $w->whereNull('parent_id')->orWhere('parent_id', 0))->count()), 'warning', 'ti-building-store', '/boshqaruv/sellers', '/boshqaruv/sellers');
        }
        if (Schema::hasTable('seller_orders')) {
            $q[] = $this->item('seller-orders.new', 'sellers', 'Do‘kon qabul qilmagan', '2 soatdan oshib ketgan yangi do‘kon buyurtmalari', $this->safe(fn () => DB::table('seller_orders')->where('status_code', 'new')->where('created_at', '<', now()->subHours(2))->count()), 'warning', 'ti-hourglass', '/boshqaruv/seller-orders', '/boshqaruv/seller-orders');
        }

        // ── Kuryerlar va hub ──
        if (Schema::hasTable('couriers')) {
            $q[] = $this->item('couriers.pending', 'couriers', 'Kuryer arizalari', 'Tasdiq kutayotgan kuryerlar', $this->safe(fn () => DB::table('couriers')->where('status', 'pending')->count()), 'warning', 'ti-bike', '/boshqaruv/couriers', '/boshqaruv/couriers');
        }
        if (Schema::hasTable('courier_orders')) {
            $q[] = $this->item('courier-orders.stuck', 'couriers', 'Qotib qolgan yetkazishlar', 'Uzoq vaqt kuryerda yoki kuryersiz turgan', $this->safe(fn () => CourierLimits::applyStuckScope(\App\Models\CourierOrder::query())->count()), 'danger', 'ti-truck-off', '/boshqaruv/courier-orders', '/boshqaruv/courier-orders');
        }
        if (Schema::hasTable('hub_applications')) {
            $q[] = $this->item('hubs.applications', 'hubs', 'Yangi hub arizalari', 'Ko‘rib chiqilmagan', $this->safe(fn () => DB::table('hub_applications')->where('status', 'new')->count()), 'info', 'ti-home-plus', '/boshqaruv/hub-arizalari', '/boshqaruv/hub-arizalari');
        }

        // ── Katalog ──
        if (Schema::hasTable('book_edition_submissions')) {
            $q[] = $this->item('catalog.submissions', 'catalog', 'Kitob arizalari', 'Yangi kitob va tuzatish so‘rovlari', $this->safe(fn () => DB::table('book_edition_submissions')->where('status', 'pending')->count()), 'warning', 'ti-inbox', '/boshqaruv/catalog/submissions', '/boshqaruv/catalog/submissions');
        }
        if (Schema::hasTable('books') && Schema::hasColumn('books', 'is_approved')) {
            $q[] = $this->item('catalog.books', 'catalog', 'Moderatsiyadagi kitoblar', 'Do‘konlar qo‘shgan, tasdiqlanmagan', $this->safe(fn () => DB::table('books')->where('is_approved', 0)->when(Schema::hasColumn('books', 'archived_at'), fn ($w) => $w->whereNull('archived_at'))->when(Schema::hasColumn('books', 'deleted_at'), fn ($w) => $w->whereNull('deleted_at'))->count()), 'warning', 'ti-book', '/boshqaruv/books', '/boshqaruv/books');
        }
        if (Schema::hasTable('stationeries') && Schema::hasColumn('stationeries', 'is_approved')) {
            $q[] = $this->item('catalog.stationery', 'catalog', 'Moderatsiyadagi kanselyariya', 'Tasdiqlanmagan mahsulotlar', $this->safe(fn () => DB::table('stationeries')->where(fn ($w) => $w->whereNull('is_approved')->orWhere('is_approved', 0))->when(Schema::hasColumn('stationeries', 'deleted_at'), fn ($w) => $w->whereNull('deleted_at'))->count()), 'info', 'ti-edit', '/boshqaruv/stationeries', '/boshqaruv/stationeries');
        }
        if (Schema::hasTable('catalog_slot_purchases')) {
            $q[] = $this->item('catalog.slots', 'catalog', 'Katalog joyi so‘rovlari', 'To‘langan, tasdiq kutmoqda', $this->safe(fn () => DB::table('catalog_slot_purchases')->where('status', 'pending')->count()), 'info', 'ti-crown', '/boshqaruv/catalog-slots', '/boshqaruv/catalog-slots');
        }

        // ── Moliya ──
        if (Schema::hasTable('seller_transactions')) {
            $q[] = $this->item('finance.payouts', 'finance', 'Do‘kon pul yechish so‘rovlari', 'Tasdiq kutmoqda', $this->safe(fn () => DB::table('seller_transactions')->where('status', 'pending')->where(fn ($w) => $w->whereNull('category')->orWhere('category', 'withdrawal'))->count()), 'warning', 'ti-cash', '/boshqaruv/transactions', '/boshqaruv/transactions');
        }
        if (Schema::hasTable('courier_transactions')) {
            $q[] = $this->item('finance.courier-payouts', 'finance', 'Kuryer pul yechish so‘rovlari', 'Tasdiq kutmoqda', $this->safe(fn () => DB::table('courier_transactions')->where('category', 'withdrawal')->where('status', 'pending')->count()), 'warning', 'ti-wallet', '/boshqaruv/transactions?transaction_owner=courier', '/boshqaruv/transactions');
        }
        if (Schema::hasTable('transactions') && Schema::hasColumn('transactions', 'perform_fiscal_data')) {
            $q[] = $this->item('finance.fiscal', 'finance', 'Fiskal chek xatolari', 'Soliqqa yuborilmagan to‘lovlar', $this->safe(fn () => \App\Models\Transaction::query()
                ->where('payment_type', 'order')->where('provider', 'paylov')->where('state', 2)->whereNotNull('order_id')
                ->whereNull('perform_fiscal_data->qr_code_url')->where('perform_fiscal_data->status', 'failed')->count()), 'danger', 'ti-receipt-off', '/boshqaruv/fiscalization?fiscal_status=failed', '/boshqaruv/fiscalization');
        }
        if (Schema::hasTable('split_contracts')) {
            $q[] = $this->item('split.overdue', 'split', 'Muddati o‘tgan nasiyalar', 'To‘lov kechikkan shartnomalar', $this->safe(fn () => DB::table('split_contracts')->where('status', 'overdue')->count()), 'danger', 'ti-calendar-x', '/boshqaruv/split', '/boshqaruv/split');
        }

        // ── Support ──
        if (Schema::hasTable('bot_tickets')) {
            $q[] = $this->item('support.unassigned', 'support', 'Navbatdagi suhbatlar', 'Hali hech kim olmagan murojaatlar', $this->safe(function () {
                $customer = DB::table('bot_tickets')->whereIn('status', ['queue', 'active'])
                    ->when(Schema::hasColumn('bot_tickets', 'admin_id'), fn ($w) => $w->whereNull('admin_id'))
                    ->where(fn ($w) => $w->whereNull('operator_id')->orWhere('operator_id', 0))->count();
                $shop = Schema::hasTable('seller_support_tickets')
                    ? DB::table('seller_support_tickets')->where('status', '!=', 'closed')->whereNull('admin_id')->count() : 0;

                return $customer + $shop;
            }), 'danger', 'ti-messages', '/boshqaruv/support/inbox?filter=unassigned', '/boshqaruv/support/inbox');
        }
        if (Schema::hasTable('reports')) {
            $q[] = $this->item('support.complaints', 'support', 'Ochiq shikoyatlar', 'Ko‘rib chiqilmagan', $this->safe(fn () => DB::table('reports')->where('status', 'pending')->count()), 'warning', 'ti-alert-triangle', '/boshqaruv/shikoyatlar', '/boshqaruv/shikoyatlar');
        }

        // ── Book Club, HR, marketing, AI ──
        if (Schema::hasTable('book_club') && Schema::hasColumn('book_club', 'ai_moderation_status')) {
            $q[] = $this->item('book-club.flagged', 'book-club', 'Book Club: AI belgilagan', 'Moderator qarorini kutmoqda', $this->safe(function () {
                $posts = DB::table('book_club')->where('ai_moderation_status', 'ai_flagged')
                    ->when(Schema::hasColumn('book_club', 'is_hidden_by_ai'), fn ($w) => $w->where('is_hidden_by_ai', 0))
                    ->when(Schema::hasColumn('book_club', 'is_deleted'), fn ($w) => $w->where('is_deleted', 0))->count();
                $comments = Schema::hasTable('book_club_comments') && Schema::hasColumn('book_club_comments', 'ai_moderation_status')
                    ? DB::table('book_club_comments')->where('ai_moderation_status', 'ai_flagged')
                        ->when(Schema::hasColumn('book_club_comments', 'is_hidden_by_ai'), fn ($w) => $w->where('is_hidden_by_ai', 0))->count()
                    : 0;

                return $posts + $comments;
            }), 'warning', 'ti-flag', '/boshqaruv/book-club', '/boshqaruv/book-club');
        }
        if (Schema::hasTable('career_applications')) {
            $q[] = $this->item('hr.applications', 'hr', 'Yangi karyera arizalari', 'Javob kutmoqda', $this->safe(fn () => DB::table('career_applications')->where('status', 'new')->count()), 'warning', 'ti-file-certificate', '/boshqaruv/karyera-arizalari', '/boshqaruv/karyera-arizalari');
        }
        if (Schema::hasTable('seller_ads') && Schema::hasColumn('seller_ads', 'moderation')) {
            $q[] = $this->item('marketing.ads', 'marketing', 'Reklama moderatsiyasi', 'Do‘kon reklamalari tasdiq kutmoqda', $this->safe(fn () => DB::table('seller_ads')->where('moderation', 'pending')->count()), 'warning', 'ti-speakerphone', '/boshqaruv/reklamalar', '/boshqaruv/reklamalar');
        }
        if (Schema::hasTable('seller_ai_actions')) {
            $q[] = $this->item('seller-ai.failed', 'seller-ai', 'Seller AI xatolari', 'Bajarilmagan AI amallar', $this->safe(fn () => DB::table('seller_ai_actions')->where('status', 'failed')->where('created_at', '>=', now()->subDays(7))->count()), 'info', 'ti-robot', '/boshqaruv/seller-ai-actions', null, false);
        }

        return $q;
    }

    private function statusCount(array $codes, array $legacy, $olderThan = null): int
    {
        return (int) DB::table('solds')
            ->where(fn ($w) => $w->whereIn('status_code', $codes)->orWhere(fn ($l) => $l->whereNull('status_code')->whereIn('status', $legacy)))
            ->when($olderThan, fn ($w) => $w->where('created_at', '<', $olderThan))
            ->count();
    }

    private function item(string $key, string $module, string $label, string $hint, int $count, string $tone, string $icon, string $url, ?string $nav, bool $badge = true): array
    {
        return compact('key', 'module', 'label', 'hint', 'count', 'tone', 'icon', 'url', 'nav', 'badge');
    }

    private function safe(callable $fn): int
    {
        try {
            return (int) $fn();
        } catch (\Throwable $e) {
            Log::info('[WorkQueue] hisoblab bo‘lmadi: ' . $e->getMessage());

            return 0;
        }
    }
}
