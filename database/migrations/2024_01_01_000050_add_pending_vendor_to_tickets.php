<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * "Awaiting Vendor" (stored as pending_vendor): the ticket is waiting on a supplier or service provider (typically a device
     * still under warranty). We also keep who the vendor is, their case / RMA number and the date
     * it is expected back, so nobody has to dig through comments to find out.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE tickets MODIFY COLUMN status ENUM('open', 'in_progress', 'pending', 'pending_backend', 'pending_vendor', 'resolved', 'closed', 'cancelled') NOT NULL DEFAULT 'open'");
        }

        Schema::table('tickets', function (Blueprint $table) {
            $table->string('vendor_name', 120)->nullable();
            $table->string('vendor_reference', 80)->nullable();
            $table->date('vendor_expected_at')->nullable();
        });
    }

    public function down(): void
    {
        // The enum can't keep a value that no longer exists, so move those tickets back to In Progress first.
        DB::table('tickets')->where('status', 'pending_vendor')->update(['status' => 'in_progress']);

        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn(['vendor_name', 'vendor_reference', 'vendor_expected_at']);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE tickets MODIFY COLUMN status ENUM('open', 'in_progress', 'pending', 'pending_backend', 'resolved', 'closed', 'cancelled') NOT NULL DEFAULT 'open'");
        }
    }
};
