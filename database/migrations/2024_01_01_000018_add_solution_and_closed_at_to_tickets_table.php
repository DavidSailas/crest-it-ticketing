<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            // The writeup IT support gives when they close a ticket, shown
            // to the requester as the answer to "what fixed this".
            $table->text('solution')->nullable()->after('resolved_at');

            // Stamped the moment a ticket first becomes closed. Kept
            // separate from resolved_at, since a ticket can be closed
            // directly without ever passing through "resolved".
            $table->timestamp('closed_at')->nullable()->after('solution');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn(['solution', 'closed_at']);
        });
    }
};
