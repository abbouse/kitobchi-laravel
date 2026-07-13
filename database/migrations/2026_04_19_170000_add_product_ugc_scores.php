<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['books', 'stationeries'] as $tableName) {
            if (! Schema::hasTable($tableName) || Schema::hasColumn($tableName, 'ugc_aggregate_score')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->decimal('ugc_aggregate_score', 2, 1)->default(0)->after('is_approved');
            });
        }
    }

    public function down(): void
    {
        // UGC reytingi faol tizimda ishlatiladi; rollback tarixiy ma'lumotni o'chirmaydi.
    }
};
