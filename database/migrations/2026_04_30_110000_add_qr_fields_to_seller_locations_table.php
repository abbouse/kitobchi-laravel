<?php

use App\Models\SellerLocation;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seller_locations', function (Blueprint $table) {
            $table->string('qr_token', 64)->nullable()->after('description');
            $table->timestamp('qr_rotated_at')->nullable()->after('qr_token');
            $table->unique('qr_token');
        });

        SellerLocation::query()->whereNull('qr_token')->chunkById(100, function ($locations) {
            foreach ($locations as $location) {
                $location->forceFill([
                    'qr_token' => SellerLocation::generateUniqueQrToken(),
                    'qr_rotated_at' => now(),
                ])->save();
            }
        });
    }

    public function down(): void
    {
        Schema::table('seller_locations', function (Blueprint $table) {
            $table->dropUnique(['qr_token']);
            $table->dropColumn(['qr_token', 'qr_rotated_at']);
        });
    }
};
