<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Every change to a supply's quantity is one row here, so the count on
        // the item can always be explained: who changed it, when, and why.
        Schema::create('supply_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supply_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 10);                       // initial, in, out, count
            $table->integer('change');                        // signed: +10, -2
            $table->unsignedInteger('balance_after');
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index(['supply_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supply_movements');
    }
};
