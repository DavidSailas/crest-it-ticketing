<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            // Short code used to build asset tags, e.g. Accounting -> ACC
            // gives CFI-ACC-DT-001.
            $table->string('code', 10)->nullable()->unique()->after('name');
        });

        // Give the departments seeded earlier a sensible default code so
        // asset tagging works right away. Admins can change these any
        // time from the Departments page.
        $defaults = [
            'Accounting' => 'ACC',
            'Sales & Marketing' => 'SMK',
            'CSR' => 'CSR',
            'HR' => 'HR',
            'Operations' => 'OPS',
            'Tracking' => 'TRK',
        ];

        foreach ($defaults as $name => $code) {
            DB::table('departments')->where('name', $name)->update(['code' => $code]);
        }
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropColumn('code');
        });
    }
};
