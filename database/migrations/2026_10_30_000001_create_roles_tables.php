<?php

use App\Support\Permissions;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Roles and their permissions (Admin → Roles & Permissions).
 *
 * The three roles the system has always had are created as "built-in" roles with
 * exactly the access they already had, so nothing changes until an admin edits it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('key', 20)->unique();      // what users.role stores; short because announcements.audience is 20 wide
            $table->string('name', 60);
            $table->string('description', 255)->nullable();
            $table->string('color', 20)->default('slate');
            $table->boolean('is_system')->default(false); // built-in: cannot be deleted
            $table->timestamps();
        });

        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->string('permission', 60);
            $table->unique(['role_id', 'permission']);
        });

        $now = now();
        $builtin = [
            ['key' => 'staff', 'name' => 'Staff', 'color' => 'indigo', 'description' => 'Raises tickets and requests supplies.'],
            ['key' => 'it_support', 'name' => 'IT Support', 'color' => 'green', 'description' => 'Works the ticket queue and looks after assets and supplies.'],
            ['key' => 'admin', 'name' => 'Administrator', 'color' => 'amber', 'description' => 'Full access to everything. Cannot be restricted.'],
        ];

        foreach ($builtin as $role) {
            $id = DB::table('roles')->insertGetId($role + ['is_system' => true, 'created_at' => $now, 'updated_at' => $now]);

            foreach (Permissions::defaultsFor($role['key']) as $permission) {
                DB::table('role_permissions')->insert(['role_id' => $id, 'permission' => $permission]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('roles');
    }
};
