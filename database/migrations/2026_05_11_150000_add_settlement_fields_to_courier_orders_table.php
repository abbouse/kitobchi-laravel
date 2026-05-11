<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courier_orders', function (Blueprint $table) {
            $table->integer('settled_amount')->nullable()->after('final_bonus');
            $table->timestamp('settled_at')->nullable()->after('settled_amount');
        });
    }

    public function down(): void
    {
        Schema::table('courier_orders', function (Blueprint $table) {
            $table->dropColumn(['settled_amount', 'settled_at']);
        });
    }
};
