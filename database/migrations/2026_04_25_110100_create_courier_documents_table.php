<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * courier_documents — kuryer hujjatlari (pasport sahifalari, haydovchi
 * guvohnomasi, transport guvohnomasi, sug'urta polisi, va h.k.).
 *
 * Bitta kuryer uchun bir nechta hujjat — masalan pasportning ikki tomoni,
 * haydovchi guvohnomasi old/orqa, transport texpasporti.
 *
 * Pattern seller_documents bilan bir xil: type enum + file_path + uploaded_by
 * (admin auditi uchun).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courier_documents', function (Blueprint $table) {
            $table->id();
            // couriers.id = int unsigned — shuning uchun unsignedInteger ishlatamiz
            $table->unsignedInteger('courier_id');
            $table->enum('type', [
                'passport',           // pasport skani
                'driver_license',     // haydovchi guvohnomasi
                'vehicle_reg',        // transport vositasi guvohnomasi (texpasport)
                'vehicle_insurance',  // OSAGO/sug'urta polisi
                'inn_certificate',    // STIR ma'lumotnomasi
                'medical_cert',       // tibbiy ma'lumotnoma
                'photo_with_passport',// pasport bilan selfi (kim ekanligini tasdiqlash)
                'other',
            ])->default('other')->index();
            $table->string('file_path', 500);
            $table->string('original_name', 255)->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->unsignedInteger('file_size_kb')->nullable();
            $table->unsignedBigInteger('uploaded_by')->nullable(); // admin id
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index('courier_id');
            $table->index(['courier_id', 'type']);

            $table->foreign('courier_id')
                  ->references('id')->on('couriers')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courier_documents');
    }
};
