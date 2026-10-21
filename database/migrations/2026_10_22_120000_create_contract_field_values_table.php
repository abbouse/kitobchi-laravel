<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Boshqaruvda kiritiladigan shartnoma maydonlari: scope = 'company' yoki shablon slug'i. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_field_values', function (Blueprint $table) {
            $table->id();
            $table->string('scope', 64);
            $table->string('key', 64);
            $table->text('value')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->unique(['scope', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_field_values');
    }
};
