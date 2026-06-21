<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fcm_notifications', function (Blueprint $table) {
            if (! Schema::hasColumn('fcm_notifications', 'source')) {
                $table->string('source', 50)->default('legacy')->after('who');
            }
            if (! Schema::hasColumn('fcm_notifications', 'delivery_status')) {
                $table->string('delivery_status', 30)->default('in_app')->after('source');
            }
            if (! Schema::hasColumn('fcm_notifications', 'sent_count')) {
                $table->unsignedInteger('sent_count')->default(0)->after('delivery_status');
            }
            if (! Schema::hasColumn('fcm_notifications', 'failed_count')) {
                $table->unsignedInteger('failed_count')->default(0)->after('sent_count');
            }
        });
    }

    public function down(): void
    {
        Schema::table('fcm_notifications', function (Blueprint $table) {
            $columns = array_values(array_filter([
                Schema::hasColumn('fcm_notifications', 'source') ? 'source' : null,
                Schema::hasColumn('fcm_notifications', 'delivery_status') ? 'delivery_status' : null,
                Schema::hasColumn('fcm_notifications', 'sent_count') ? 'sent_count' : null,
                Schema::hasColumn('fcm_notifications', 'failed_count') ? 'failed_count' : null,
            ]));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
