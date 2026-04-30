<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stationeries', function (Blueprint $table) {
            $table->string('barcode', 32)->nullable()->after('name')->index();
        });
    }

    public function down(): void
    {
        Schema::table('stationeries', function (Blueprint $table) {
            $table->dropIndex(['barcode']);
            $table->dropColumn('barcode');
        });
    }
};
