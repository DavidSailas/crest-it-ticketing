<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A staff member's request for a supply item. Each one owns a ticket, so IT Support
        // works it in the normal queue. The quantity is "reserved" while pending and is taken
        // out of stock automatically when that ticket is marked Resolved.
        Schema::create('supply_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supply_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();   // who asked
            $table->unsignedInteger('quantity');
            $table->string('purpose');                                        // what it's for
            $table->string('status', 12)->default('pending');                 // pending, issued, short
            $table->timestamp('issued_at')->nullable();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('note')->nullable();
            $table->timestamps();

            $table->unique('ticket_id');
            $table->index(['supply_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supply_requests');
    }
};
