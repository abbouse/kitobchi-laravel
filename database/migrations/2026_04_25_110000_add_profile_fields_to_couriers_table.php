<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * couriers jadvaliga profil ma'lumotlari (transport, hujjatlar, bank).
 *
 * Asosiy maqsadlar:
 *   1. Transport turi (foot/bicycle/motorcycle/car) — bonus va order
 *      taqsimlash logikasida ishlatiladi (kelgusi Phase 3 da).
 *   2. Pasport + STIR + haydovchi guvohnomasi — admin verifikatsiyasi uchun.
 *   3. Bank/karta egasi — payment_card oldindan mavjud, lekin egasini
 *      to'liq saqlashga vajli ehtiyoj bor.
 *   4. verification_status — kuryer hujjatlarini ko'rib chiqish workflow'i.
 *
 * Eski kuryerlarga ta'sir qilmaydi — barcha ustunlar nullable yoki default
 * qiymat bilan. transport_type default 'foot' — kuryerlarning ko'p qismi
 * piyoda ishlasa shu mantiqli.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('couriers', function (Blueprint $table) {
            // ── Transport ─────────────────────────────────────────
            $table->enum('transport_type', ['foot', 'bicycle', 'motorcycle', 'car'])
                  ->default('foot')->after('region');

            // ── Vehicle (faqat motorcycle/car uchun) ──────────────
            $table->string('vehicle_brand', 50)->nullable()->after('transport_type');
            $table->string('vehicle_model', 50)->nullable()->after('vehicle_brand');
            $table->string('vehicle_color', 30)->nullable()->after('vehicle_model');
            $table->string('vehicle_plate_number', 20)->nullable()->after('vehicle_color');

            // ── Identifikatsiya ───────────────────────────────────
            $table->string('inn', 20)->nullable()->after('vehicle_plate_number'); // STIR
            $table->date('birthdate')->nullable()->after('inn');
            $table->string('passport_series', 10)->nullable()->after('birthdate');
            $table->string('passport_number', 20)->nullable()->after('passport_series');
            $table->string('passport_issued_by', 150)->nullable()->after('passport_number');
            $table->date('passport_issued_at')->nullable()->after('passport_issued_by');

            // ── Haydovchi guvohnomasi (motorcycle/car uchun) ─────
            $table->string('driver_license_number', 20)->nullable()->after('passport_issued_at');
            $table->date('driver_license_issued_at')->nullable()->after('driver_license_number');
            $table->date('driver_license_expires_at')->nullable()->after('driver_license_issued_at');

            // ── Bank/karta egasi ──────────────────────────────────
            // payment_card oldindan mavjud, faqat egasi kerak.
            $table->string('card_holder', 100)->nullable()->after('driver_license_expires_at');

            // ── Manzil ────────────────────────────────────────────
            $table->string('home_address', 255)->nullable()->after('card_holder');

            // ── Verifikatsiya ─────────────────────────────────────
            // unverified — yangi kuryer, hujjat hali yuborilmagan
            // pending    — hujjat yuborilgan, admin ko'rib chiqishi kerak
            // verified   — admin tasdiqladi
            // rejected   — hujjatlarda muammo, qayta yuborish kerak
            $table->enum('verification_status', ['unverified', 'pending', 'verified', 'rejected'])
                  ->default('unverified')->after('home_address');
            $table->timestamp('verified_at')->nullable()->after('verification_status');
            $table->text('verification_notes')->nullable()->after('verified_at');

            // ── Index'lar ─────────────────────────────────────────
            $table->index('transport_type');
            $table->index('verification_status');
        });
    }

    public function down(): void
    {
        Schema::table('couriers', function (Blueprint $table) {
            $table->dropIndex(['transport_type']);
            $table->dropIndex(['verification_status']);

            $table->dropColumn([
                'transport_type',
                'vehicle_brand', 'vehicle_model', 'vehicle_color', 'vehicle_plate_number',
                'inn', 'birthdate',
                'passport_series', 'passport_number',
                'passport_issued_by', 'passport_issued_at',
                'driver_license_number',
                'driver_license_issued_at', 'driver_license_expires_at',
                'card_holder',
                'home_address',
                'verification_status', 'verified_at', 'verification_notes',
            ]);
        });
    }
};
