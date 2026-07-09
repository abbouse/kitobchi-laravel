<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('hub_applications')) {
            return;
        }

        Schema::create('hub_applications', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('phone', 32);
            $table->string('region', 100);
            $table->string('position')->nullable();        // xohlagan lavozim
            $table->string('tashkent_availability', 16);    // yes | no | unsure
            $table->string('status', 24)->default('new');   // new, reviewed, contacted, closed
            $table->text('note')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hub_applications');
    }
};
