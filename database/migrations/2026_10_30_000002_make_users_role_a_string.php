<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * users.role was ENUM('staff','it_support','admin'). New roles need any key, so it becomes a
 * plain string that holds roles.key. Existing values are kept as they are.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('staff')->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['staff', 'it_support', 'admin'])->default('staff')->change();
        });
    }
};
