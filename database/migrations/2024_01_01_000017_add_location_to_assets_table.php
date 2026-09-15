<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            // Branch the asset is tagged to, e.g. CEB for Cebu. Sits
            // between company and department in the tag: CFI-CEB-IT-LT-001.
            $table->string('location', 10)->after('company');
            $table->index(['company', 'location', 'department_id', 'type'], 'assets_tag_scope_index');
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropIndex('assets_tag_scope_index');
            $table->dropColumn('location');
        });
    }
};
