<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            // When a worker's machine won't start they can't log in to raise a
            // ticket themselves, so a colleague submits it for them. user_id
            // stays as the person who actually typed it in (so it appears in
            // their own ticket list and they get the chat notifications),
            // while these record who the ticket is really about.
            $table->foreignId('on_behalf_of_user_id')->nullable()->after('user_id')
                ->constrained('users')->nullOnDelete();
            $table->string('on_behalf_of_name')->nullable()->after('on_behalf_of_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('on_behalf_of_user_id');
            $table->dropColumn('on_behalf_of_name');
        });
    }
};
