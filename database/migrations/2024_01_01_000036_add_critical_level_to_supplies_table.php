<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A second, more urgent warning tier below the reorder level:
        //   quantity <= reorder_level   -> Low
        //   quantity <= critical_level  -> Critical
        //   quantity = 0                -> Out
        // 0 means "no separate critical tier for this item".
        Schema::table('supplies', function (Blueprint $table) {
            $table->unsignedInteger('critical_level')->default(0)->after('reorder_level');
        });

        // Existing items: start at half the reorder level, which people can then tune per item.
        DB::table('supplies')->orderBy('id')->each(function ($row) {
            DB::table('supplies')->where('id', $row->id)->update([
                'critical_level' => intdiv((int) $row->reorder_level, 2),
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('supplies', function (Blueprint $table) {
            $table->dropColumn('critical_level');
        });
    }
};
