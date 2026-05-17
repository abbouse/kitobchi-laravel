<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mystery_box_plans', function (Blueprint $table) {
            if (!Schema::hasColumn('mystery_box_plans', 'description_uz')) {
                $table->text('description_uz')->nullable()->after('books_per_month');
            }
            if (!Schema::hasColumn('mystery_box_plans', 'description_ru')) {
                $table->text('description_ru')->nullable()->after('description_uz');
            }
            if (!Schema::hasColumn('mystery_box_plans', 'description_en')) {
                $table->text('description_en')->nullable()->after('description_ru');
            }
            if (!Schema::hasColumn('mystery_box_plans', 'description_ja')) {
                $table->text('description_ja')->nullable()->after('description_en');
            }
        });
    }

    public function down(): void
    {
        Schema::table('mystery_box_plans', function (Blueprint $table) {
            foreach (['description_ja', 'description_en', 'description_ru', 'description_uz'] as $column) {
                if (Schema::hasColumn('mystery_box_plans', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
