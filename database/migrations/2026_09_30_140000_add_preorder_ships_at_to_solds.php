<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Predzakaz bo'lgan buyurtma: butun buyurtma shu kundan jo'natiladi
 * (eng kech chiqadigan kitob sanasi). Shu kungacha kuryerga chiqmaydi.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('solds') && ! Schema::hasColumn('solds', 'preorder_ships_at')) {
            Schema::table('solds', function (Blueprint $table) {
                $table->date('preorder_ships_at')->nullable()->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('solds') && Schema::hasColumn('solds', 'preorder_ships_at')) {
            Schema::table('solds', fn (Blueprint $table) => $table->dropColumn('preorder_ships_at'));
        }
    }
};
