<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * From now on an address only receives notifications after the person confirms it.
     * Addresses that were already on accounts before this change were entered by an admin
     * and have been working, so they are treated as confirmed instead of being cut off.
     */
    public function up(): void
    {
        DB::table('users')
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->whereNull('email_verified_at')
            ->update(['email_verified_at' => now()]);
    }

    public function down(): void
    {
        // Nothing to undo: whether an address was confirmed can't be reconstructed.
    }
};
