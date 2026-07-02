<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('solds', function (Blueprint $table) {
            if (! Schema::hasColumn('solds', 'source_collection_id')) {
                $table->unsignedBigInteger('source_collection_id')
                    ->nullable()
                    ->after('resend_available_at')
                    ->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('solds', function (Blueprint $table) {
            if (Schema::hasColumn('solds', 'source_collection_id')) {
                $table->dropColumn('source_collection_id');
            }
        });
    }
};
