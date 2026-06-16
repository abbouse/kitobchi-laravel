<?php

use App\Enums\CourierOrderStatusCode;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('courier_orders')) {
            return;
        }

        foreach (['A', 'P', 'B', 'C', 'D', 'F', 'R'] as $legacyStatus) {
            $statusCode = CourierOrderStatusCode::fromLegacy($legacyStatus);

            DB::table('courier_orders')
                ->where(function ($query) use ($legacyStatus) {
                    $query->where('status', $legacyStatus)
                        ->orWhere('status_code', $legacyStatus);
                })
                ->update([
                    'status' => $statusCode->legacy(),
                    'status_code' => $statusCode->value,
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        //
    }
};
