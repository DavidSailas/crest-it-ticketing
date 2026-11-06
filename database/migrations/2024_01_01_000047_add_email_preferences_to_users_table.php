<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Which emails each person wants (see User::EMAIL_TYPES). Empty = the defaults.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('email_preferences')->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('email_preferences');
        });
    }
};
