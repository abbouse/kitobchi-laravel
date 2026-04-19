<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vacancy_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vacancy_id')->constrained('vacancies')->cascadeOnDelete();
            $table->string('locale', 8);
            $table->string('title')->nullable();
            $table->string('contract_type')->nullable();
            $table->string('location')->nullable();
            $table->longText('description')->nullable();
            $table->timestamps();

            $table->unique(['vacancy_id', 'locale']);
        });

        Schema::create('policy_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('policy_id')->constrained('policies')->cascadeOnDelete();
            $table->string('locale', 8);
            $table->string('title')->nullable();
            $table->longText('content')->nullable();
            $table->timestamps();

            $table->unique(['policy_id', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('policy_translations');
        Schema::dropIfExists('vacancy_translations');
    }
};
