<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Admin tomonidan qo'lda beriladigan split limit.
 *
 * manual_limit > 0 bo'lsa: skoring talablari (akkaunt yoshi, karta yoshi,
 * reputatsiya, buyurtmalar soni...) chetlab o'tiladi. Tasdiqlangan telefon,
 * kamida bitta tasdiqlangan karta va qattiq bloklar (admin blok, muddati
 * o'tgan to'lov, default, bloklangan akkaunt) baribir majburiy qoladi.
 *
 * limit_granted_notified_at — limit berilgani haqidagi bir martalik push.
 * last_promo_push_at — haftalik "limitingizni ishlating" promo push.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('split_user_profiles', function (Blueprint $table) {
            if (! Schema::hasColumn('split_user_profiles', 'manual_limit')) {
                $table->unsignedBigInteger('manual_limit')->nullable()->after('computed_limit');
            }
            if (! Schema::hasColumn('split_user_profiles', 'manual_limit_set_by')) {
                $table->unsignedBigInteger('manual_limit_set_by')->nullable()->after('manual_limit');
            }
            if (! Schema::hasColumn('split_user_profiles', 'manual_limit_set_at')) {
                $table->timestamp('manual_limit_set_at')->nullable()->after('manual_limit_set_by');
            }
            if (! Schema::hasColumn('split_user_profiles', 'limit_granted_notified_at')) {
                $table->timestamp('limit_granted_notified_at')->nullable();
            }
            if (! Schema::hasColumn('split_user_profiles', 'last_promo_push_at')) {
                $table->timestamp('last_promo_push_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('split_user_profiles', function (Blueprint $table) {
            foreach (['manual_limit', 'manual_limit_set_by', 'manual_limit_set_at', 'limit_granted_notified_at', 'last_promo_push_at'] as $column) {
                if (Schema::hasColumn('split_user_profiles', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
