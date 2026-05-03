<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->json('perform_fiscal_data')->nullable()->after('perform_time_unix');
            $table->json('cancel_fiscal_data')->nullable()->after('perform_fiscal_data');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['perform_fiscal_data', 'cancel_fiscal_data']);
        });
    }
};
