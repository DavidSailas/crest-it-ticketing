<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Each employee can have a named approver (their head, manager or junior manager) —
     * the person who approves that employee's supply requests. Left empty, the request
     * goes to approvers in the same department and branch.
     */
    public function up(): void
    {
        if (Schema::hasColumn('users', 'approver_id')) {
            return; // already done (safe to run again)
        }

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('approver_id')->nullable()->after('is_department_head')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'approver_id')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['approver_id']);
            $table->dropColumn('approver_id');
        });
    }
};
