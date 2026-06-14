<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('gifts', 'archived_at')) {
            Schema::table('gifts', function (Blueprint $table) {
                $table->timestamp('archived_at')->nullable()->index()->after('status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('gifts', 'archived_at')) {
            Schema::table('gifts', function (Blueprint $table) {
                $table->dropColumn('archived_at');
            });
        }
    }
};
