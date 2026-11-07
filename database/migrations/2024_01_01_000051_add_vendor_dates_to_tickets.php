<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * When a ticket was handed to a vendor and when it came back, so the ticket can say
     * "with the vendor for 5 days" and, afterwards, "the vendor took 9 days".
     */
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            if (! Schema::hasColumn('tickets', 'vendor_sent_at')) {
                $table->timestamp('vendor_sent_at')->nullable();
            }
            if (! Schema::hasColumn('tickets', 'vendor_returned_at')) {
                $table->timestamp('vendor_returned_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $drop = array_values(array_filter(
                ['vendor_sent_at', 'vendor_returned_at'],
                fn ($column) => Schema::hasColumn('tickets', $column)
            ));

            if ($drop) {
                $table->dropColumn($drop);
            }
        });
    }
};
