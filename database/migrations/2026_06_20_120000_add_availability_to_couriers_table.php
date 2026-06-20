<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('couriers', function (Blueprint $table) {
            $table->boolean('is_online')->default(true)->after('status')->index();
            $table->timestamp('availability_updated_at')->nullable()->after('is_online');
        });
    }

    public function down(): void
    {
        Schema::table('couriers', function (Blueprint $table) {
            $table->dropIndex(['is_online']);
            $table->dropColumn(['is_online', 'availability_updated_at']);
        });
    }
};
