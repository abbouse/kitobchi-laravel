<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 3 — courier_orders ga bonus + SLA ustunlari.
 *
 * pickup_bonus       — joriy surge bonusi (har minut yangilanadi pending paytda)
 * locked_bonus       — kuryer qabul qilgan paytdagi bonus (snapshot)
 * final_bonus        — yetkazib berilgandan keyingi yakuniy bonus (penalty bilan)
 * picked_up_at       — kuryer qabul qilgan vaqt (= confirm)
 * sla_deadline       — picked_up_at + sla_minutes (kechikishni shu nuqtaga nisbatan o'lchaymiz)
 * is_customer_delay  — kuryer "mijoz javob bermayapti" tugmasini bosgan flag
 * customer_delay_started_at — pause boshlangan vaqt (resume da total_delay_seconds ga qo'shamiz)
 * total_delay_seconds — barcha pause oraliqlarining yig'indisi (sla_deadline ni siljitish uchun)
 * bonus_threshold_notified — pre-acceptance push bir marta yuborilgani uchun flag
 * sla_warning_notified     — SLA-5min push bir marta yuborilgani uchun flag
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courier_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('courier_orders', 'pickup_bonus')) {
                $table->unsignedInteger('pickup_bonus')->default(0)->after('courierBonus');
            }
            if (!Schema::hasColumn('courier_orders', 'locked_bonus')) {
                $table->unsignedInteger('locked_bonus')->nullable()->after('pickup_bonus');
            }
            if (!Schema::hasColumn('courier_orders', 'final_bonus')) {
                $table->unsignedInteger('final_bonus')->nullable()->after('locked_bonus');
            }
            if (!Schema::hasColumn('courier_orders', 'picked_up_at')) {
                $table->timestamp('picked_up_at')->nullable()->after('final_bonus');
            }
            if (!Schema::hasColumn('courier_orders', 'sla_deadline')) {
                $table->timestamp('sla_deadline')->nullable()->after('picked_up_at');
            }
            if (!Schema::hasColumn('courier_orders', 'is_customer_delay')) {
                $table->boolean('is_customer_delay')->default(false)->after('sla_deadline');
            }
            if (!Schema::hasColumn('courier_orders', 'customer_delay_started_at')) {
                $table->timestamp('customer_delay_started_at')->nullable()->after('is_customer_delay');
            }
            if (!Schema::hasColumn('courier_orders', 'total_delay_seconds')) {
                $table->unsignedInteger('total_delay_seconds')->default(0)->after('customer_delay_started_at');
            }
            if (!Schema::hasColumn('courier_orders', 'bonus_threshold_notified')) {
                $table->boolean('bonus_threshold_notified')->default(false)->after('total_delay_seconds');
            }
            if (!Schema::hasColumn('courier_orders', 'sla_warning_notified')) {
                $table->boolean('sla_warning_notified')->default(false)->after('bonus_threshold_notified');
            }
        });

        // Status + scheduler optimizatsiyasi: pending va in_delivery rowlarni
        // har minutda tezroq topish uchun (millionlab qatorlar uchun ham yengil).
        Schema::table('courier_orders', function (Blueprint $table) {
            // Mavjud ustunlar uchun maxsus index bo'lmasa qo'shamiz.
            // (eski jadval `status` ga index qo'ymagan — biz qo'shamiz.)
            $table->index('status', 'idx_courier_orders_status');
            $table->index(['status', 'sla_deadline'], 'idx_courier_orders_status_sla');
        });
    }

    public function down(): void
    {
        Schema::table('courier_orders', function (Blueprint $table) {
            // Indexlarni o'chiramiz (mavjud bo'lsa).
            try { $table->dropIndex('idx_courier_orders_status'); } catch (\Throwable $e) {}
            try { $table->dropIndex('idx_courier_orders_status_sla'); } catch (\Throwable $e) {}

            foreach ([
                'pickup_bonus',
                'locked_bonus',
                'final_bonus',
                'picked_up_at',
                'sla_deadline',
                'is_customer_delay',
                'customer_delay_started_at',
                'total_delay_seconds',
                'bonus_threshold_notified',
                'sla_warning_notified',
            ] as $col) {
                if (Schema::hasColumn('courier_orders', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
