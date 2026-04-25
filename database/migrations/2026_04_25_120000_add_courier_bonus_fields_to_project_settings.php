<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 3 — Kuryer bonus tizimi sozlamalari.
 *
 * Pre-acceptance surge: buyurtma yaratilganda pickup_bonus = 0; har minutda
 *   `surge_step` qiymatiga ko'tariladi, `surge_max` da to'xtaydi.
 *
 * Post-acceptance penalty: kuryer qabul qilgan paytdan boshlab `sla_minutes`
 *   ichida yetkazib berishi kerak. Kechiksa har minut uchun `penalty_step`
 *   ayriladi, minimum 0.
 *
 * `surge_threshold` — bonus shu chegaradan o'tganda kuryerlarga "yuqori bonus"
 *   bildirishnomasi yuboriladi (bir buyurtma uchun bir marta).
 *
 * Default qiymatlar foydalanuvchi tomonidan tanlangan: 0 boshlang'ich,
 *   +500/min, max 10 000, threshold 5 000, SLA 45 min, jarima -300/min.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('project_settings', 'courier_surge_step')) {
                $table->unsignedInteger('courier_surge_step')->default(500);
            }
            if (!Schema::hasColumn('project_settings', 'courier_surge_max')) {
                $table->unsignedInteger('courier_surge_max')->default(10000);
            }
            if (!Schema::hasColumn('project_settings', 'courier_surge_threshold')) {
                $table->unsignedInteger('courier_surge_threshold')->default(5000);
            }
            if (!Schema::hasColumn('project_settings', 'courier_sla_minutes')) {
                $table->unsignedSmallInteger('courier_sla_minutes')->default(45);
            }
            if (!Schema::hasColumn('project_settings', 'courier_penalty_step')) {
                $table->unsignedInteger('courier_penalty_step')->default(300);
            }
        });
    }

    public function down(): void
    {
        Schema::table('project_settings', function (Blueprint $table) {
            foreach ([
                'courier_surge_step',
                'courier_surge_max',
                'courier_surge_threshold',
                'courier_sla_minutes',
                'courier_penalty_step',
            ] as $col) {
                if (Schema::hasColumn('project_settings', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
