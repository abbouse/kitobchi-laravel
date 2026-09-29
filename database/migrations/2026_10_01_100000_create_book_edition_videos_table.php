<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Global kitob (nashr) uchun bitta mahsulot videosi. Admin yuklaydi,
 * navbatdagi job 480p/720p (faststart) va poster tayyorlaydi.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('book_edition_videos')) {
            return;
        }
        Schema::create('book_edition_videos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('edition_id')->unique();
            $table->string('status', 20)->default('processing'); // processing | ready | failed
            $table->string('original_path')->nullable();
            $table->string('sd_path')->nullable();
            $table->string('hd_path')->nullable();
            $table->string('poster_path')->nullable();
            $table->unsignedInteger('duration')->nullable(); // soniya
            $table->unsignedSmallInteger('width')->nullable();
            $table->unsignedSmallInteger('height')->nullable();
            $table->unsignedBigInteger('sd_size')->nullable();
            $table->unsignedBigInteger('hd_size')->nullable();
            $table->text('error')->nullable();
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_edition_videos');
    }
};
