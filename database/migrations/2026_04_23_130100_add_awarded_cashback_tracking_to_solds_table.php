<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('solds', function (Blueprint $table) {
            $table->integer('awarded_cashback_amount')->default(0)->after('cashbackAmount');
            $table->timestamp('cashback_awarded_at')->nullable()->after('awarded_cashback_amount');
        });
    }

    public function down(): void
    {
        Schema::table('solds', function (Blueprint $table) {
            $table->dropColumn(['awarded_cashback_amount', 'cashback_awarded_at']);
        });
    }
};
