<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_settings', function (Blueprint $table) {
            // Flash delivery — kunlik bepul yetkazish aksiyasi sozlamalari
            $table->boolean('flash_delivery_enabled')->default(false)->after('ai_bot_extra_notes');
            $table->string('flash_delivery_start_time', 5)->default('20:00')->after('flash_delivery_enabled')
                ->comment('HH:MM formatida — masalan "20:00"');
            $table->unsignedSmallInteger('flash_delivery_duration')->default(15)->after('flash_delivery_start_time')
                ->comment('Aksiya davomiyligi (daqiqada)');
            $table->string('flash_delivery_promo_code', 50)->nullable()->after('flash_delivery_duration')
                ->comment('Ishlatiluvchi promokod kodi — Promocode jadvalida bo\'lishi shart');
        });
    }

    public function down(): void
    {
        Schema::table('project_settings', function (Blueprint $table) {
            $table->dropColumn([
                'flash_delivery_enabled',
                'flash_delivery_start_time',
                'flash_delivery_duration',
                'flash_delivery_promo_code',
            ]);
        });
    }
};
