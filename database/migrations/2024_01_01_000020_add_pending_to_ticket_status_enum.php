<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Laravel's enum change() requires doctrine/dbal and doesn't handle
        // MySQL enums well, so we alter the column directly.
        DB::statement("ALTER TABLE tickets MODIFY COLUMN status ENUM('open', 'in_progress', 'pending', 'resolved', 'closed') NOT NULL DEFAULT 'open'");
    }

    public function down(): void
    {
        // Reassign any 'pending' tickets before shrinking the enum back down,
        // otherwise MySQL will silently truncate them to '' and this will fail.
        DB::table('tickets')->where('status', 'pending')->update(['status' => 'open']);

        DB::statement("ALTER TABLE tickets MODIFY COLUMN status ENUM('open', 'in_progress', 'resolved', 'closed') NOT NULL DEFAULT 'open'");
    }
};
