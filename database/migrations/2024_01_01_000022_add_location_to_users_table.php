<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Which branch this person works out of (Cebu, Manila, Cagayan de
            // Oro, Davao). Reuses the same short codes as Asset::LOCATIONS
            // so a user's branch and their equipment's branch share one
            // vocabulary instead of two separate lists to keep in sync.
            // Nullable so existing accounts aren't broken by this migration —
            // they'll just show "—" until an admin sets it.
            $table->string('location', 3)->nullable()->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('location');
        });
    }
};
