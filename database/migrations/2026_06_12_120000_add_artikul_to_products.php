<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['books', 'stationeries', 'gifts'] as $table) {
            if (! Schema::hasColumn($table, 'artikul')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->string('artikul', 64)->nullable()->after('id');
                });
            }
        }

        $this->backfill('books', '10');
        $this->backfill('stationeries', '20');
        $this->backfill('gifts', '30');

        foreach (['books', 'stationeries', 'gifts'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->unique('artikul');
            });
        }
    }

    public function down(): void
    {
        foreach (['books', 'stationeries', 'gifts'] as $tableName) {
            if (! Schema::hasColumn($tableName, 'artikul')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->dropUnique(['artikul']);
                $table->dropColumn('artikul');
            });
        }
    }

    private function backfill(string $table, string $prefix): void
    {
        DB::table($table)
            ->whereNull('artikul')
            ->orderBy('id')
            ->select(['id'])
            ->chunkById(500, function ($rows) use ($table, $prefix) {
                foreach ($rows as $row) {
                    DB::table($table)
                        ->where('id', $row->id)
                        ->update(['artikul' => sprintf('%s%08d', $prefix, $row->id)]);
                }
            });
    }
};
