<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->string('title', 150);
            $table->text('body');
            // feature | update | maintenance | reminder | policy | important
            $table->string('category', 20)->default('update');
            // all | staff | it_support | admin
            $table->string('audience', 20)->default('all');
            $table->boolean('is_popup')->default(true);      // shown once as a pop-up after sign-in
            $table->boolean('is_pinned')->default(false);    // stays at the top of the feed / dashboard
            $table->boolean('is_published')->default(true);  // false = draft
            $table->timestamp('starts_at')->nullable();      // null = immediately
            $table->timestamp('ends_at')->nullable();        // null = until removed
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['is_published', 'starts_at', 'ends_at']);
        });

        Schema::create('announcement_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('announcement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('read_at');
            $table->unique(['announcement_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcement_reads');
        Schema::dropIfExists('announcements');
    }
};
