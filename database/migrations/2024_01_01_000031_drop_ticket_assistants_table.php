<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The "assist on a teammate's ticket" feature has been removed, so the
     * pivot table backing it is no longer needed.
     */
    public function up(): void
    {
        Schema::dropIfExists('ticket_assistants');
    }

    public function down(): void
    {
        Schema::create('ticket_assistants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }
};
