<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * TRANSACTIONAL PERFORMANCE — savat, sevimlilar, buyurtma jadvallari.
 *
 * Bu jadvallar juda ko'p o'qiladi (har savat/sevimli/buyurtma ochilganda),
 * lekin filter ustunlariga (user_id, seller_id, order_id, product_id...)
 * indeks yo'q edi → to'liq jadval skani. Indekslar javobni tezlashtiradi,
 * natija (qaytadigan ma'lumot) umuman o'zgarmaydi.
 *
 * Idempotent: mavjud indeks o'tkazib yuboriladi.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Savat — har ochilganda user_id bo'yicha; updateOrCreate (user+type+product)
        $this->addIndex('my_carts', 'my_carts_user_idx', ['user_id', 'product_type', 'product_id']);

        // Sevimlilar — batch (user+type), toggle/exists (user+product+type), restock (product+type)
        $this->addIndex('favourite_products', 'fav_user_type_idx', ['user_id', 'product_type', 'product_id']);
        $this->addIndex('favourite_products', 'fav_product_idx', ['product_id', 'product_type']);

        // Seller buyurtmalari — do'kon ro'yxati (seller+status), order bo'yicha join, client bo'yicha
        $this->addIndex('seller_orders', 'so_seller_status_idx', ['seller_id', 'status_code']);
        $this->addIndex('seller_orders', 'so_order_idx', ['order_id']);
        $this->addIndex('seller_orders', 'so_client_idx', ['client_id']);

        // Seller buyurtma itemlari — order bo'yicha, seller bo'yicha, product bo'yicha
        $this->addIndex('seller_order_items', 'soi_order_seller_idx', ['order_id', 'seller_id']);
        $this->addIndex('seller_order_items', 'soi_product_idx', ['product_id', 'type']);

        // Kuryer buyurtma itemlari
        $this->addIndex('courier_order_items', 'coi_order_idx', ['order_id']);
        $this->addIndex('courier_order_items', 'coi_seller_idx', ['seller_id']);
        $this->addIndex('courier_order_items', 'coi_location_idx', ['seller_location_id']);

        // Chat tarixi — user bo'yicha, vaqt tartibida
        $this->addIndex('chat_messages', 'chat_user_created_idx', ['user_id', 'created_at']);

        // Push bildirishnomalar — who bo'yicha filtr + updated_at tartibi
        $this->addIndex('fcm_notifications', 'fcm_who_updated_idx', ['who', 'updated_at']);

        // Solds — mijoz buyurtmalari ro'yxati (user + faol/tugagan), vaqt tartibi
        $this->addIndex('solds', 'solds_created_idx', ['created_at']);
        $this->addIndex('solds', 'solds_user_completed_idx', ['user_id', 'completed_at']);

        // Book club feed — is_deleted filtri + tartib, theme, user, repost hisoblari
        $this->addIndex('book_club', 'bc_deleted_updated_idx', ['is_deleted', 'updated_at']);
        $this->addIndex('book_club', 'bc_theme_idx', ['theme_id', 'is_deleted']);
        $this->addIndex('book_club', 'bc_user_idx', ['user_id', 'is_deleted']);
        $this->addIndex('book_club', 'bc_repost_idx', ['reposted_user_id', 'repost', 'is_deleted']);
    }

    public function down(): void
    {
        foreach ([
            'my_carts' => ['my_carts_user_idx'],
            'favourite_products' => ['fav_user_type_idx', 'fav_product_idx'],
            'seller_orders' => ['so_seller_status_idx', 'so_order_idx', 'so_client_idx'],
            'seller_order_items' => ['soi_order_seller_idx', 'soi_product_idx'],
            'courier_order_items' => ['coi_order_idx', 'coi_seller_idx', 'coi_location_idx'],
            'chat_messages' => ['chat_user_created_idx'],
            'fcm_notifications' => ['fcm_who_updated_idx'],
            'solds' => ['solds_created_idx', 'solds_user_completed_idx'],
            'book_club' => ['bc_deleted_updated_idx', 'bc_theme_idx', 'bc_user_idx', 'bc_repost_idx'],
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
                return;
            }
        }
        if ($this->indexExists($table, $name)) {
            return;
        }
        Schema::table($table, fn ($t) => $t->index($columns, $name));
    }

    private function dropIndex(string $table, string $name): void
    {
        if (Schema::hasTable($table) && $this->indexExists($table, $name)) {
            Schema::table($table, fn ($t) => $t->dropIndex($name));
        }
    }

    private function indexExists(string $table, string $name): bool
    {
        return ! empty(DB::select('SHOW INDEX FROM `'.$table.'` WHERE Key_name = ?', [$name]));
    }
};
