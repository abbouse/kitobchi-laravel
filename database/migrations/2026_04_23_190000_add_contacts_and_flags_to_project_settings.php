<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('project_settings', function (Blueprint $table) {
            // ── Kontakt ma'lumotlari ──────────────────────────────────────
            $table->string('kitobchi_phone')->nullable()->after('market_version_android');
            $table->string('kitobchi_email')->nullable()->after('kitobchi_phone');
            $table->string('business_phone')->nullable()->after('kitobchi_email');
            $table->string('business_email')->nullable()->after('business_phone');
            $table->string('courier_phone')->nullable()->after('business_email');
            $table->string('courier_email')->nullable()->after('courier_phone');

            // ── App flaglar ───────────────────────────────────────────────
            $table->boolean('on_premium')->default(false)->after('courier_email');
            $table->boolean('on_reels')->default(false)->after('on_premium');
            $table->boolean('ramadan')->default(false)->after('on_reels');
            $table->boolean('stop_sales')->default(false)->after('ramadan');

            // ── Qadoqlash narxi ───────────────────────────────────────────
            $table->unsignedInteger('packaging_price_small')->default(25000)->after('stop_sales');
            $table->unsignedInteger('packaging_price_large')->default(40000)->after('packaging_price_small');
            $table->unsignedSmallInteger('packaging_threshold')->default(4)->after('packaging_price_large');
        });
    }

    public function down(): void
    {
        Schema::table('project_settings', function (Blueprint $table) {
            $table->dropColumn([
                'kitobchi_phone', 'kitobchi_email',
                'business_phone', 'business_email',
                'courier_phone',  'courier_email',
                'on_premium', 'on_reels', 'ramadan', 'stop_sales',
                'packaging_price_small', 'packaging_price_large', 'packaging_threshold',
            ]);
        });
    }
};
