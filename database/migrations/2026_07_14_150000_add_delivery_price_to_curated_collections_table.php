<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('curated_collections') || Schema::hasColumn('curated_collections', 'delivery_price')) {
            return;
        }

        Schema::table('curated_collections', function (Blueprint $table) {
            // To'plam yetkazish narxi (flat): 0 = bepul. Joylashuvdan qat'i nazar bir xil.
            $table->unsignedInteger('delivery_price')->default(0)->after('custom_total_price');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('curated_collections') || ! Schema::hasColumn('curated_collections', 'delivery_price')) {
            return;
        }

        Schema::table('curated_collections', function (Blueprint $table) {
            $table->dropColumn('delivery_price');
        });
    }
};
