<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('seller_order_items')) {
            $affectedSellerOrderIds = DB::table('seller_order_items')
                ->where('type', 'gift')
                ->where('seller_id', 1)
                ->pluck('order_id');

            DB::table('seller_order_items')
                ->where('type', 'gift')
                ->where('seller_id', 1)
                ->delete();

            if (
                $affectedSellerOrderIds->isNotEmpty()
                && Schema::hasTable('seller_orders')
            ) {
                DB::table('seller_orders')
                    ->whereIn('id', $affectedSellerOrderIds)
                    ->whereNotExists(function ($query) {
                        $query->selectRaw('1')
                            ->from('seller_order_items')
                            ->whereColumn('seller_order_items.order_id', 'seller_orders.id');
                    })
                    ->delete();
            }
        }

        if (Schema::hasTable('courier_order_items')) {
            DB::table('courier_order_items')
                ->where('type', 'gift')
                ->where('seller_id', 1)
                ->delete();
        }
    }

    public function down(): void
    {
        // Sold snapshot platforma sovg'asining yagona manbasi bo'lib qoladi.
    }
};
