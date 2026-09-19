<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Split name fields — 'name' (the combined full name) stays as
            // the single source used everywhere else in the app (greetings,
            // ticket/asset listings, exports) and is kept in sync
            // automatically whenever first/last name change (see User model).
            $table->string('first_name')->nullable()->after('id');
            $table->string('last_name')->nullable()->after('first_name');

            // A short handle distinct from the login email — useful for
            // display in chat/notifications without exposing the address.
            $table->string('username')->nullable()->unique()->after('last_name');

            // Which department this person belongs to (Accounting, IT, HR,
            // etc.) — separate from 'location' (branch/city). Nullable and
            // nullOnDelete so removing a department doesn't take users with it.
            $table->foreignId('department_id')->nullable()->after('role')
                ->constrained('departments')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('department_id');
            $table->dropColumn(['first_name', 'last_name', 'username']);
        });
    }
};
