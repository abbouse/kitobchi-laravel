<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blogger_shipment_items', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('blogger_shipment_id')->index();
            $table->string('name');
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();

            $table->foreign('blogger_shipment_id')
                ->references('id')
                ->on('blogger_shipments')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blogger_shipment_items');
    }
};
