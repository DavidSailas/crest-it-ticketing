<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            // When this asset was actually handed to the user — separate
            // from created_at, since a device might be logged into the
            // system days after it was physically issued.
            $table->date('assigned_date')->nullable()->after('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropColumn('assigned_date');
        });
    }
};
