<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            if (!Schema::hasColumn('conversations', 'title')) {
                $table->string('title')->nullable()->after('type');
            }

            if (!Schema::hasColumn('conversations', 'avatar')) {
                $table->string('avatar')->nullable()->after('title');
            }

            if (!Schema::hasColumn('conversations', 'created_by_id')) {
                $table->unsignedBigInteger('created_by_id')->nullable()->after('avatar');
                $table->index('created_by_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            foreach (['created_by_id', 'avatar', 'title'] as $column) {
                if (Schema::hasColumn('conversations', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
