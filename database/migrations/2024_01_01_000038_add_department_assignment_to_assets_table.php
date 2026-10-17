<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // An asset can now belong to a PERSON, to a whole DEPARTMENT (shared equipment such as
        // routers, switches or the office telephone), or sit unassigned in stock.
        //   user_id set                              -> person
        //   user_id empty + department_assigned      -> department (the asset's department)
        //   user_id empty + not department_assigned  -> unassigned (in stock)
        Schema::table('assets', function (Blueprint $table) {
            $table->boolean('department_assigned')->default(false)->after('department_id');
        });

        // Shared equipment needs somewhere to live. Add an IT Department unless one already
        // exists under a name or code we recognise (admins can rename it on the Departments page).
        $exists = DB::table('departments')
            ->where('code', 'IT')
            ->orWhereIn('name', ['IT', 'IT Department', 'IT Support', 'Information Technology'])
            ->exists();

        if (! $exists) {
            DB::table('departments')->insert([
                'name' => 'IT Department',
                'code' => 'IT',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // The IT Department row is left alone: people and assets may reference it by now.
        Schema::table('assets', function (Blueprint $table) {
            $table->dropColumn('department_assigned');
        });
    }
};
