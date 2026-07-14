<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('order_fulfillments')
            || Schema::hasColumn('order_fulfillments', 'postal_office_address')) {
            return;
        }

        Schema::table('order_fulfillments', function (Blueprint $table) {
            // Pochta buyurtmasi boradigan pochta bo'limi manzili —
            // "yetib keldi" SMS'ida mijozga ko'rsatiladi
            $table->string('postal_office_address')->nullable()->after('postal_tracking_number');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('order_fulfillments')
            || ! Schema::hasColumn('order_fulfillments', 'postal_office_address')) {
            return;
        }

        Schema::table('order_fulfillments', function (Blueprint $table) {
            $table->dropColumn('postal_office_address');
        });
    }
};
