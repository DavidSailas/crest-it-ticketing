<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            // Set when the requester confirms that a "Resolved" ticket really
            // is fixed. IT Support can only close a ticket once this is filled.
            $table->timestamp('approved_at')->nullable()->after('resolved_at');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn('approved_at');
        });
    }
};
