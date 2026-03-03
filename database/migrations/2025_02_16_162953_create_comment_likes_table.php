<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('book_club_comment_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comment_id')->constrained('book_club_comments')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['comment_id', 'user_id']); // Foydalanuvchi 1 ta kommentga faqat 1 marta like bosa oladi
        });
    }

    public function down() {
        Schema::dropIfExists('comment_likes');
    }
};
