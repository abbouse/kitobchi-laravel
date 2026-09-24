<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * KATALOG JOYI (buy box slot) — do'kon pul to'lab kitob kartasida birinchi va
 * tanlangan taklif bo'lib turadi.
 *
 * Bitta kartada BITTA joy: band bo'lsa boshqa do'kon o'sha kartaga sotib
 * ololmaydi. Joy do'kon balansidan to'lanadi (premium obuna kabi), admin
 * tasdiqlaydi. Pullik do'konning qoldig'i tugasa joy vaqtincha keyingi
 * do'konga o'tadi, lekin to'langan muddat davom etaveradi.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('catalog_slot_purchases')) {
            Schema::create('catalog_slot_purchases', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('edition_id');
                $table->unsignedBigInteger('seller_id');
                // Aynan qaysi taklif ustun bo'ladi (do'konning o'sha kartadagi kitobi)
                $table->unsignedBigInteger('book_id');

                // pending → active → expired; rejected/cancelled — yakuniy holatlar
                $table->string('status', 16)->default('pending');
                $table->string('reject_reason', 500)->nullable();
                $table->unsignedBigInteger('reviewer_id')->nullable();
                $table->timestamp('reviewed_at')->nullable();

                $table->unsignedInteger('days');
                $table->unsignedBigInteger('price_uzs');
                // Balansdan yechilgan payt (rad etilsa qaytariladi)
                $table->timestamp('charged_at')->nullable();
                $table->timestamp('refunded_at')->nullable();

                // Muddat admin tasdiqlaganda boshlanadi
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('ends_at')->nullable();

                $table->timestamps();

                $table->index(['edition_id', 'status']);
                $table->index(['seller_id', 'status']);
                $table->index('ends_at');
                $table->index('book_id');
            });
        }

        if (! Schema::hasTable('catalog_slot_settings')) {
            Schema::create('catalog_slot_settings', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('price_per_month')->default(0);
                $table->unsignedInteger('min_days')->default(7);
                $table->unsignedInteger('max_days')->default(90);
                $table->boolean('is_active')->default(false);
                $table->timestamps();
            });
        }

        /**
         * DO'KON XARAJATLARI DAFTARI.
         *
         * Ilgari premium obuna `sellers.balance` dan to'g'ridan-to'g'ri yechilardi
         * va hech qayerda iz qolmasdi — do'kon "pulim qayerga ketdi?" deganda
         * javob yo'q edi. Endi balansga har qanday tegish shu yerga yoziladi.
         */
        if (! Schema::hasTable('seller_balance_entries')) {
            Schema::create('seller_balance_entries', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('seller_id');
                // Manfiy — xarajat, musbat — tushum
                $table->bigInteger('amount');
                $table->bigInteger('balance_after');
                // catalog_slot | catalog_slot_refund | premium | premium_renewal | manual
                $table->string('type', 32);
                $table->string('reference_type', 64)->nullable();
                $table->unsignedBigInteger('reference_id')->nullable();
                $table->string('note', 255)->nullable();
                $table->timestamps();

                $table->index(['seller_id', 'created_at']);
                $table->index(['reference_type', 'reference_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('seller_balance_entries');
        Schema::dropIfExists('catalog_slot_settings');
        Schema::dropIfExists('catalog_slot_purchases');
    }
};
