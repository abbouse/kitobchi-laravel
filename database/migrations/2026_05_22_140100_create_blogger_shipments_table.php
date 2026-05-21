<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blogger_shipments', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('blogger_id')->index();
            $table->dateTime('scheduled_for')->index();
            $table->string('status', 32)->default('pending')->index();
            $table->dateTime('delivered_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->foreign('blogger_id')
                ->references('id')
                ->on('bloggers')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blogger_shipments');
    }
};
