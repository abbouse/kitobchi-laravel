<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('career_applications', function (Blueprint $table) {
            $table->id();
            $table->string('type', 24); // vacancy | inquiry
            $table->foreignId('vacancy_id')->nullable()->constrained('vacancies')->nullOnDelete();
            $table->string('full_name');
            $table->string('email');
            $table->string('telegram_username', 128);
            $table->text('cover_message')->nullable();
            $table->string('cv_path')->nullable();
            $table->string('cv_original_name')->nullable();
            $table->string('status', 24)->default('new'); // new, reviewed, replied, closed
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['type', 'status']);
            $table->index('created_at');
        });

        Schema::create('career_application_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('career_application_id')->constrained('career_applications')->cascadeOnDelete();
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->string('sender', 16); // admin | system
            $table->text('body');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('career_application_messages');
        Schema::dropIfExists('career_applications');
    }
};
