<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Sellers ga unique QR token qo'shamiz.
 *
 * qr_token — har sotuvchining do'koniga osib qo'yiladigan QR uchun
 *            unique 40 belgili random string. App QR'ni skaner qilganida
 *            shu token orqali sellerni topadi va "Do'kon ichida" rejimini
 *            yoqadi. Token shubhali bo'lsa admin paneldan rotate qilish
 *            mumkin (rotate qilingach eski QR ishlamay qoladi).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sellers', function (Blueprint $table) {
            if (!Schema::hasColumn('sellers', 'qr_token')) {
                $table->string('qr_token', 64)->nullable()->after('photo');
            }
            if (!Schema::hasColumn('sellers', 'qr_rotated_at')) {
                $table->timestamp('qr_rotated_at')->nullable()->after('qr_token');
            }
        });

        // Mavjud sellerlarga token backfill — birma-bir, unique tekshiruv bilan.
        DB::table('sellers')->whereNull('qr_token')->orderBy('id')->each(function ($seller) {
            $token = $this->uniqueToken();
            DB::table('sellers')->where('id', $seller->id)->update([
                'qr_token'      => $token,
                'qr_rotated_at' => now(),
            ]);
        });

        // Backfill tugagach — UNIQUE constraint qo'shamiz.
        Schema::table('sellers', function (Blueprint $table) {
            $table->unique('qr_token', 'sellers_qr_token_unique');
        });
    }

    public function down(): void
    {
        Schema::table('sellers', function (Blueprint $table) {
            try { $table->dropUnique('sellers_qr_token_unique'); } catch (\Throwable $e) {}
            foreach (['qr_token', 'qr_rotated_at'] as $col) {
                if (Schema::hasColumn('sellers', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }

    private function uniqueToken(): string
    {
        do {
            $token = Str::random(40);
            $exists = DB::table('sellers')->where('qr_token', $token)->exists();
        } while ($exists);

        return $token;
    }
};
