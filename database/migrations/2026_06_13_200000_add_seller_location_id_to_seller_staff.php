<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sellers', function (Blueprint $table) {
            $table->integer('seller_location_id')
                ->nullable()
                ->after('parent_id')
                ->index();

            $table->foreign('seller_location_id')
                ->references('id')
                ->on('seller_locations')
                ->nullOnDelete();
        });

        DB::table('sellers')
            ->where('parent_id', '>', 0)
            ->whereNull('seller_location_id')
            ->orderBy('id')
            ->each(function ($staff): void {
                $locationId = DB::table('seller_locations')
                    ->where('seller_id', $staff->parent_id)
                    ->where('is_deleted', false)
                    ->orderByDesc('is_main')
                    ->orderBy('id')
                    ->value('id');

                if ($locationId) {
                    DB::table('sellers')
                        ->where('id', $staff->id)
                        ->update(['seller_location_id' => $locationId]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('sellers', function (Blueprint $table) {
            $table->dropForeign(['seller_location_id']);
            $table->dropIndex(['seller_location_id']);
            $table->dropColumn('seller_location_id');
        });
    }
};
