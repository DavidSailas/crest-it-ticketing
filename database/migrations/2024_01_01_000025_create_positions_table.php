<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('positions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            // Job title (e.g. "Accountant", "IT Technician") — separate from
            // Department (which team they're on) and Role (their access
            // level in this system). Nullable + nullOnDelete so removing a
            // position doesn't take users with it.
            $table->foreignId('position_id')->nullable()->after('department_id')
                ->constrained('positions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('position_id');
        });

        Schema::dropIfExists('positions');
    }
};
