<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * seller_documents — sellerga tegishli har qanday fayl (pasport skani, shartnoma PDF,
 * litsenziya, qo'shimcha kelishuv va h.k.).
 *
 * Bitta sellerda bir necha hujjat bo'lishi mumkin (eski shartnoma + yangi shartnoma
 * arxivi, pasportning ikki tomoni, STIR ma'lumotnomasi va h.k.).
 *
 * `type` — kategoriya uchun (filter / ko'rsatish uchun).
 * `file_path` — storage ichidagi yo'l (asset('storage/' . file_path) orqali ko'rsatiladi).
 * `original_name` — yuklangandagi haqiqiy nom (admin yuklaganda raqamlangan nomdan tashqari
 * asl nomini ham saqlab qo'yamiz).
 * `uploaded_by` — qaysi admin yukladi (admin auditi uchun).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seller_documents', function (Blueprint $table) {
            $table->id();
            // sellers.id = bigint SIGNED — shuning uchun bigInteger (signed) ishlatamiz
            $table->bigInteger('seller_id');
            $table->enum('type', [
                'passport',        // pasport skani
                'contract',        // shartnoma PDF
                'inn_certificate', // STIR ma'lumotnomasi
                'license',         // litsenziya
                'bank_details',    // bank rekvizit spravkasi
                'addendum',        // qo'shimcha kelishuv
                'other',           // boshqa
            ])->default('other')->index();
            $table->string('file_path', 500);
            $table->string('original_name', 255)->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->unsignedInteger('file_size_kb')->nullable(); // fayl kattaligi (KB)
            $table->unsignedBigInteger('uploaded_by')->nullable(); // admin user id (users table)
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index('seller_id');
            $table->index(['seller_id', 'type']);

            $table->foreign('seller_id')
                  ->references('id')->on('sellers')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seller_documents');
    }
};
