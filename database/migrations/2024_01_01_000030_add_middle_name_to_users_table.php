<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Optional — most people don't need it, but this lets admins
            // record a middle name or initial for those who do (e.g. for
            // official documents/reports). Included in the combined
            // 'name' field automatically — see User model.
            $table->string('middle_name')->nullable()->after('first_name');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('middle_name');
        });
    }
};
