<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * QO'SHIMCHA PERFORMANCE INDEKSLARI — to'liq backend auditi (2026-08-08).
 *
 * Bu bazaning asosiy sxemasi (`database/schema/kitobchi_structure.sql`)
 * eski, boshqa serverdagi ishlab turgan DB'dan eksport qilingan — bu
 * yerda (localhost) faqat migratsiya fayllari va kod bor, jonli DB yo'q,
 * shuning uchun bu migratsiya STATIK tahlilga asoslangan: har bir jadval
 * uchun (1) asosiy sxemada qanday indeks borligi va (2) `app/` ichida
 * o'sha ustun haqiqatan HOT WHERE/JOIN sifatida ishlatilayotgani (grep
 * orqali, taxmin emas) tekshirilgan.
 *
 * Oldingi audit to'lqinlari (marketplace/transactional/bookclub/hub/
 * personalization/reading-intelligence — barchasi shu papkada) eng katta
 * va eng "issiq" jadvallarni allaqachon qamragan. Bu migratsiya o'sha
 * to'lqinlar TASHQARISIDA qolgan, lekin kodda haqiqatan tez-tez
 * so'raladigan ustunlarni qo'shadi:
 *
 *   - users.phone_number / telegram_id — HAR LOGIN (AuthController) shu
 *     ustunlar bo'yicha to'liq jadval skani qilardi.
 *   - connected_devices — umuman HECH QANDAY indeks yo'q edi (faqat PK),
 *     push-yuborish xizmatlarining DEYARLI barchasi (FcmRecipientService,
 *     SplitPushService, ProductStockAlertService va h.k.) user_id+user_type
 *     bo'yicha so'raydi.
 *   - courier_orders.order_id — buyurtma hayot sikli davomida (Sold
 *     observer, OrderService, CourierOrderController, Admin/Hub/Seller
 *     controllerlar) O'NLAB joyda so'raladi, indekssiz edi.
 *   - courier_notifications / seller_notifications / courier_transactions /
 *     seller_transactions / seller_ads — mos courier_id/seller_id bo'yicha
 *     ro'yxat/hisob so'rovlari ko'p, lekin indeks umuman yo'q edi.
 *   - search_histories — user/session tarixi (SearchController) va
 *   - promocode_histories.user_id — HAR BIR buyurtmada (SoldObserver,
 *     OrderService) tekshiriladi.
 *   - transactions.order_id — to'lov holatini tekshirish
 *     (PaylovOrderPaymentService) har safar shu ustun bo'yicha so'raydi.
 *
 * Idempotent (oldingi audit migratsiyalari bilan bir xil naqsh): mavjud
 * jadval/ustun/indeks bo'lmasa yoki allaqachon bo'lsa — o'tkazib
 * yuboriladi, xavfsiz qayta-qayta ishga tushirish mumkin.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── users — login (telefon/telegram) ──────────────────────
        $this->addIndex('users', 'users_phone_idx', ['phone_number']);
        $this->addIndex('users', 'users_telegram_idx', ['telegram_id']);

        // ── connected_devices — push-yuborish fan-out ──────────────
        $this->addIndex('connected_devices', 'cd_user_type_idx', ['user_id', 'user_type']);
        $this->addIndex('connected_devices', 'cd_token_idx', ['token']);

        // ── courier_orders — buyurtma<->kuryer bog'lanishi ─────────
        $this->addIndex('courier_orders', 'co_order_idx', ['order_id']);
        $this->addIndex('courier_orders', 'co_courier_idx', ['courier_id']);

        // ── courier_notifications / seller_notifications ───────────
        $this->addIndex('courier_notifications', 'cn_courier_read_idx', ['courier_id', 'isRead']);
        $this->addIndex('seller_notifications', 'sn_seller_read_idx', ['seller_id', 'isRead']);

        // ── courier_transactions / seller_transactions ──────────────
        $this->addIndex('courier_transactions', 'ct_courier_idx', ['courier_id']);
        $this->addIndex('courier_transactions', 'ct_status_idx', ['status']);
        $this->addIndex('seller_transactions', 'st_seller_idx', ['seller_id']);
        $this->addIndex('seller_transactions', 'st_status_idx', ['status']);

        // ── seller_ads ───────────────────────────────────────────────
        $this->addIndex('seller_ads', 'sad_seller_idx', ['seller_id']);

        // ── search_histories — SearchController::history()/clearHistory() ──
        $this->addIndex('search_histories', 'sh_user_draft_updated_idx', ['user_id', 'is_draft', 'updated_at']);
        $this->addIndex('search_histories', 'sh_session_draft_updated_idx', ['session_id', 'is_draft', 'updated_at']);

        // ── promocode_histories — har buyurtmada tekshiriladi ───────
        $this->addIndex('promocode_histories', 'ph_user_idx', ['user_id']);
        $this->addIndex('promocode_histories', 'ph_promocode_idx', ['promocode_id']);

        // ── transactions — to'lov holatini tekshirish (order_id) ────
        $this->addIndex('transactions', 'tx_order_idx', ['order_id']);
    }

    public function down(): void
    {
        foreach ([
            'users' => ['users_phone_idx', 'users_telegram_idx'],
            'connected_devices' => ['cd_user_type_idx', 'cd_token_idx'],
            'courier_orders' => ['co_order_idx', 'co_courier_idx'],
            'courier_notifications' => ['cn_courier_read_idx'],
            'seller_notifications' => ['sn_seller_read_idx'],
            'courier_transactions' => ['ct_courier_idx', 'ct_status_idx'],
            'seller_transactions' => ['st_seller_idx', 'st_status_idx'],
            'seller_ads' => ['sad_seller_idx'],
            'search_histories' => ['sh_user_draft_updated_idx', 'sh_session_draft_updated_idx'],
            'promocode_histories' => ['ph_user_idx', 'ph_promocode_idx'],
            'transactions' => ['tx_order_idx'],
        ] as $table => $indexes) {
            foreach ($indexes as $index) {
                $this->dropIndex($table, $index);
            }
        }
    }

    private function addIndex(string $table, string $name, array $columns): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }
        foreach ($columns as $col) {
            if (! Schema::hasColumn($table, $col)) {
                return; // ustun yo'q bo'lsa indeks yaratmaymiz
            }
        }
        if ($this->indexExists($table, $name)) {
            return;
        }
        Schema::table($table, function ($t) use ($name, $columns) {
            $t->index($columns, $name);
        });
    }

    private function dropIndex(string $table, string $name): void
    {
        if (Schema::hasTable($table) && $this->indexExists($table, $name)) {
            Schema::table($table, fn ($t) => $t->dropIndex($name));
        }
    }

    private function indexExists(string $table, string $name): bool
    {
        $result = DB::select('SHOW INDEX FROM `'.$table.'` WHERE Key_name = ?', [$name]);

        return ! empty($result);
    }
};
