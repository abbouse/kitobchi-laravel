<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('project_settings', 'review_cashback_enabled')) {
                $table->boolean('review_cashback_enabled')->default(true);
            }
            if (! Schema::hasColumn('project_settings', 'review_cashback_amount')) {
                $table->unsignedInteger('review_cashback_amount')->default(100);
            }
        });
    }

    public function down(): void
    {
        Schema::table('project_settings', function (Blueprint $table) {
            foreach (['review_cashback_enabled', 'review_cashback_amount'] as $column) {
                if (Schema::hasColumn('project_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
