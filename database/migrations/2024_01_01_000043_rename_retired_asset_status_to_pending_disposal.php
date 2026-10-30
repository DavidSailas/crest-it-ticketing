<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * "Retired" was ambiguous (already gone, or just out of service?). The
     * status is now "Pending Disposal": out of service and waiting to be
     * written off, recycled, sold or handed over.
     */
    public function up(): void
    {
        DB::table('assets')->where('status', 'retired')->update(['status' => 'pending_disposal']);
    }

    public function down(): void
    {
        DB::table('assets')->where('status', 'pending_disposal')->update(['status' => 'retired']);
    }
};
