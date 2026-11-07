<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Supply requests now wait for an approver before they become a ticket:
     *   for_approval -> (approved) pending -> issued   |   (declined) declined   |   cancelled
     * A request that is still waiting has no ticket yet, so ticket_id becomes optional.
     *
     * Safe to run again after a partial failure: every step checks what already exists, and the
     * foreign key on ticket_id is looked up by what it really is (its name differs between servers).
     */
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'is_department_head')) {
            Schema::table('users', function (Blueprint $table) {
                // Heads, managers and junior managers an admin allows to approve supply requests.
                $table->boolean('is_department_head')->default(false)->after('role');
            });
        }

        $this->dropForeignKeys('supply_requests', 'ticket_id');

        Schema::table('supply_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('ticket_id')->nullable()->change();
        });

        // Put the link back (cascade on delete). If old data stops it, the app still works without it.
        try {
            Schema::table('supply_requests', function (Blueprint $table) {
                $table->foreign('ticket_id')->references('id')->on('tickets')->cascadeOnDelete();
            });
        } catch (QueryException $e) {
            report($e);
        }

        Schema::table('supply_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('supply_requests', 'urgency')) {
                $table->string('urgency', 10)->default('normal')->after('purpose');
            }
            if (! Schema::hasColumn('supply_requests', 'approved_by')) {
                $table->foreignId('approved_by')->nullable()->after('issued_by')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('supply_requests', 'approved_at')) {
                $table->timestamp('approved_at')->nullable();
            }
            if (! Schema::hasColumn('supply_requests', 'declined_by')) {
                $table->foreignId('declined_by')->nullable()->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('supply_requests', 'declined_at')) {
                $table->timestamp('declined_at')->nullable();
            }
            if (! Schema::hasColumn('supply_requests', 'decline_reason')) {
                $table->string('decline_reason')->nullable();
            }
        });
    }

    public function down(): void
    {
        // Requests that never became a ticket can't exist without the optional ticket_id.
        DB::table('supply_requests')->whereNull('ticket_id')->delete();

        foreach (['approved_by', 'declined_by'] as $column) {
            if (Schema::hasColumn('supply_requests', $column)) {
                $this->dropForeignKeys('supply_requests', $column);
            }
        }

        Schema::table('supply_requests', function (Blueprint $table) {
            $drop = array_values(array_filter(
                ['urgency', 'approved_by', 'approved_at', 'declined_by', 'declined_at', 'decline_reason'],
                fn ($column) => Schema::hasColumn('supply_requests', $column)
            ));

            if ($drop) {
                $table->dropColumn($drop);
            }
        });

        $this->dropForeignKeys('supply_requests', 'ticket_id');

        Schema::table('supply_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('ticket_id')->nullable(false)->change();
        });

        try {
            Schema::table('supply_requests', function (Blueprint $table) {
                $table->foreign('ticket_id')->references('id')->on('tickets')->cascadeOnDelete();
            });
        } catch (QueryException $e) {
            report($e);
        }

        if (Schema::hasColumn('users', 'is_department_head')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('is_department_head');
            });
        }
    }

    /** Drop every foreign key on the column, whatever the server named it (none is fine too). */
    private function dropForeignKeys(string $table, string $column): void
    {
        if (DB::getDriverName() === 'mysql') {
            $keys = DB::select(
                'SELECT CONSTRAINT_NAME AS name FROM information_schema.KEY_COLUMN_USAGE
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL',
                [$table, $column]
            );

            foreach ($keys as $key) {
                DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$key->name}`");
            }

            return;
        }

        try {
            Schema::table($table, fn (Blueprint $t) => $t->dropForeign([$column]));
        } catch (\Throwable $e) {
            // nothing to drop
        }
    }
};
