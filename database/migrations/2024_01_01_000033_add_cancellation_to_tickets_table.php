<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Same approach as the "pending" migration: alter the MySQL enum directly.
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE tickets MODIFY COLUMN status ENUM('open', 'in_progress', 'pending', 'resolved', 'closed', 'cancelled') NOT NULL DEFAULT 'open'");
        }

        Schema::table('tickets', function (Blueprint $table) {
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('cancel_reason', 500)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cancelled_by');
            $table->dropColumn(['cancelled_at', 'cancel_reason']);
        });

        if (DB::getDriverName() === 'mysql') {
            // Shrinking the enum would truncate cancelled rows, so park them as closed first.
            DB::table('tickets')->where('status', 'cancelled')->update(['status' => 'closed']);

            DB::statement("ALTER TABLE tickets MODIFY COLUMN status ENUM('open', 'in_progress', 'pending', 'resolved', 'closed') NOT NULL DEFAULT 'open'");
        }
    }
};
