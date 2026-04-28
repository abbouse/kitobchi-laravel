<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * cashback_settings ga `type` enum qo'shamiz:
 *   - 'delivery' (default) — odatdagi yetkazib beriladigan buyurtmalar
 *   - 'pickup' — mijoz "Kitob OL!" QR'ni skanerlab do'kondan olib ketgani
 *
 * Pickup cashback'ni alohida hisoblash imkonini beradi (do'konga jalb
 * qilish uchun yuqoriroq foiz qo'yish mumkin).
 *
 * Eski qatorlar avtomatik 'delivery' bo'lib qoladi (orqaga moslik).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cashback_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('cashback_settings', 'type')) {
                $table->enum('type', ['delivery', 'pickup'])
                    ->default('delivery')
                    ->after('cashback');
                $table->index(['type', 'fromUzs', 'toUzs'], 'cashback_settings_type_range_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('cashback_settings', function (Blueprint $table) {
            try { $table->dropIndex('cashback_settings_type_range_idx'); } catch (\Throwable $e) {}
            if (Schema::hasColumn('cashback_settings', 'type')) {
                $table->dropColumn('type');
            }
        });
    }
};
