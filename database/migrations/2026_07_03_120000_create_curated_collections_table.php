<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('curated_collections', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0)->index();

            $table->string('title_uz');
            $table->string('title_ru')->nullable();
            $table->string('title_en')->nullable();
            $table->string('title_ja')->nullable();

            $table->string('subtitle_uz')->nullable();
            $table->string('subtitle_ru')->nullable();
            $table->string('subtitle_en')->nullable();
            $table->string('subtitle_ja')->nullable();

            $table->text('description_uz')->nullable();
            $table->text('description_ru')->nullable();
            $table->text('description_en')->nullable();
            $table->text('description_ja')->nullable();

            $table->string('hero_image')->nullable();
            $table->string('gradient_from', 16)->default('#FF8A3D');
            $table->string('gradient_to', 16)->default('#FF5A3D');
            $table->string('button_bg_color', 16)->default('#121212');
            $table->string('button_text_color', 16)->default('#FFFFFF');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('curated_collections');
    }
};
