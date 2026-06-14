<?php

use App\Support\ProductArtikul;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->rewriteArtikuls('books', 'book');
        $this->rewriteArtikuls('stationeries', 'stationery');
        $this->rewriteArtikuls('gifts', 'gift');
    }

    public function down(): void
    {
        $this->rewriteLegacyArtikuls('books', '10');
        $this->rewriteLegacyArtikuls('stationeries', '20');
        $this->rewriteLegacyArtikuls('gifts', '30');
    }

    private function rewriteArtikuls(string $table, string $type): void
    {
        if (! Schema::hasColumn($table, 'artikul')) {
            return;
        }

        DB::table($table)
            ->orderBy('id')
            ->select(['id'])
            ->chunkById(500, function ($rows) use ($table, $type) {
                foreach ($rows as $row) {
                    DB::table($table)
                        ->where('id', $row->id)
                        ->update(['artikul' => ProductArtikul::generate($type, (int) $row->id)]);
                }
            });
    }

    private function rewriteLegacyArtikuls(string $table, string $prefix): void
    {
        if (! Schema::hasColumn($table, 'artikul')) {
            return;
        }

        DB::table($table)
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
