<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * sellers jadvaliga shartnoma + bank rekvizitlari + jismoniy/huquqiy shaxs
 * ma'lumotlarini qo'shish.
 *
 * Barcha ustunlar nullable — eski sellerlarga ta'sir qilmaydi. Admin edit
 * formasidan asta-sekin to'ldirib boradi. `payment_card` va `total_withdrawal`
 * oldindan mavjud, shuning uchun qayta qo'shilmaydi.
 *
 * Index: contract_expires_at — "tugashga yaqin sellerlar" dashboard widget
 * uchun tez filter qilish maqsadida.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sellers', function (Blueprint $table) {
            // ── Shartnoma ──────────────────────────────────────────
            $table->string('contract_number', 50)->nullable()->after('total_withdrawal');
            $table->date('contract_signed_at')->nullable()->after('contract_number');
            $table->date('contract_expires_at')->nullable()->after('contract_signed_at');
            $table->enum('contract_status', ['none', 'active', 'expiring', 'expired', 'terminated'])
                  ->default('none')->after('contract_expires_at');
            $table->text('contract_notes')->nullable()->after('contract_status');

            // ── Huquqiy shakl ──────────────────────────────────────
            // individual     = jismoniy shaxs
            // entrepreneur   = yakka tartibdagi tadbirkor
            // llc            = MChJ
            // jsc            = AJ (ochiq/yopiq aksiyadorlik jamiyati)
            $table->enum('legal_type', ['individual', 'entrepreneur', 'llc', 'jsc'])
                  ->nullable()->after('contract_notes');

            // ── Shaxsiy hujjat ma'lumotlari (jismoniy shaxs uchun) ─
            $table->string('inn', 20)->nullable()->after('legal_type'); // STIR
            $table->string('passport_series', 10)->nullable()->after('inn');
            $table->string('passport_number', 20)->nullable()->after('passport_series');
            $table->string('passport_issued_by', 150)->nullable()->after('passport_number');
            $table->date('passport_issued_at')->nullable()->after('passport_issued_by');

            // ── Bank rekvizitlari ──────────────────────────────────
            $table->string('bank_name', 100)->nullable()->after('passport_issued_at');
            $table->string('bank_account', 30)->nullable()->after('bank_name'); // 20 xonali raqam
            $table->string('bank_mfo', 10)->nullable()->after('bank_account');
            $table->string('bank_swift', 20)->nullable()->after('bank_mfo');
            $table->string('card_holder', 100)->nullable()->after('bank_swift'); // payment_card egasi

            // ── Manzil ─────────────────────────────────────────────
            $table->string('legal_address', 255)->nullable()->after('card_holder');

            // ── Index'lar ──────────────────────────────────────────
            $table->index('contract_expires_at');
            $table->index('contract_status');
        });
    }

    public function down(): void
    {
        Schema::table('sellers', function (Blueprint $table) {
            $table->dropIndex(['contract_expires_at']);
            $table->dropIndex(['contract_status']);

            $table->dropColumn([
                'contract_number', 'contract_signed_at', 'contract_expires_at',
                'contract_status', 'contract_notes',
                'legal_type',
                'inn', 'passport_series', 'passport_number',
                'passport_issued_by', 'passport_issued_at',
                'bank_name', 'bank_account', 'bank_mfo', 'bank_swift',
                'card_holder', 'legal_address',
            ]);
        });
    }
};
