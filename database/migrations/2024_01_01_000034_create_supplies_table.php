<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // IT Supplies = consumables counted by quantity (ink, batteries, mice,
        // keyboards, USB drives…). Individual tagged devices stay in `assets`.
        Schema::create('supplies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category', 40);
            $table->string('brand', 100)->nullable();
            $table->string('model_no', 100)->nullable();   // model / part number
            $table->string('unit', 20)->default('pcs');
            $table->unsignedInteger('quantity')->default(0);
            $table->unsignedInteger('reorder_level')->default(0); // "low stock" at or below this
            $table->string('location', 20)->nullable();    // branch code, e.g. CEB (same as assets.location)
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('category');
            $table->index('location');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplies');
    }
};
