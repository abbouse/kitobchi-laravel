<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('promocodes') || Schema::hasColumn('promocodes', 'eligible_order_count')) {
            return;
        }

        $afterColumn = Schema::hasColumn('promocodes', 'per_user_limit')
            ? 'per_user_limit'
            : 'min_order_amount';

        Schema::table('promocodes', function (Blueprint $table) use ($afterColumn) {
            $table->unsignedSmallInteger('eligible_order_count')
                ->nullable()
                ->after($afterColumn);
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('promocodes') || ! Schema::hasColumn('promocodes', 'eligible_order_count')) {
            return;
        }

        Schema::table('promocodes', function (Blueprint $table) {
            $table->dropColumn('eligible_order_count');
        });
    }
};
