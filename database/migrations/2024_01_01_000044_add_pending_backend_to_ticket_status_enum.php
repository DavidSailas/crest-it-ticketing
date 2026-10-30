<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * "Pending Backend": the ticket needs a server-side fix (spam filtering,
     * cPanel, mail or hosting settings) that IT handles behind the scenes, so
     * it is not waiting on the requester. Same approach as the earlier status
     * migrations: alter the MySQL enum directly.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE tickets MODIFY COLUMN status ENUM('open', 'in_progress', 'pending', 'pending_backend', 'resolved', 'closed', 'cancelled') NOT NULL DEFAULT 'open'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            // Shrinking the enum would truncate these rows, so move them back to In Progress first.
            DB::table('tickets')->where('status', 'pending_backend')->update(['status' => 'in_progress']);

            DB::statement("ALTER TABLE tickets MODIFY COLUMN status ENUM('open', 'in_progress', 'pending', 'resolved', 'closed', 'cancelled') NOT NULL DEFAULT 'open'");
        }
    }
};
