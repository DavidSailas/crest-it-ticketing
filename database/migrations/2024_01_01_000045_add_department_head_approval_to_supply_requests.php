<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Supply requests now wait for a department head before they become a ticket:
     *   for_approval -> (approved) pending -> issued   |   (declined) declined   |   cancelled
     * A request that is still waiting has no ticket yet, so ticket_id becomes optional.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Department heads receive and approve every supply request.
            $table->boolean('is_department_head')->default(false)->after('role');
        });

        Schema::table('supply_requests', function (Blueprint $table) {
            $table->dropForeign(['ticket_id']);
        });

        Schema::table('supply_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('ticket_id')->nullable()->change();
        });

        Schema::table('supply_requests', function (Blueprint $table) {
            $table->foreign('ticket_id')->references('id')->on('tickets')->cascadeOnDelete();

            $table->string('urgency', 10)->default('normal')->after('purpose');
            $table->foreignId('approved_by')->nullable()->after('issued_by')->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->foreignId('declined_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
            $table->timestamp('declined_at')->nullable()->after('declined_by');
            $table->string('decline_reason')->nullable()->after('declined_at');
        });
    }

    public function down(): void
    {
        // Requests that never became a ticket can't exist without the optional ticket_id.
        DB::table('supply_requests')->whereNull('ticket_id')->delete();

        Schema::table('supply_requests', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropForeign(['declined_by']);
            $table->dropColumn(['urgency', 'approved_by', 'approved_at', 'declined_by', 'declined_at', 'decline_reason']);
            $table->dropForeign(['ticket_id']);
        });

        Schema::table('supply_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('ticket_id')->nullable(false)->change();
        });

        Schema::table('supply_requests', function (Blueprint $table) {
            $table->foreign('ticket_id')->references('id')->on('tickets')->cascadeOnDelete();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_department_head');
        });
    }
};
