<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Ticket;
use App\Models\User;
use App\Support\Permissions;
use App\Support\RoleRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Roles & permissions: an admin can create a role (e.g. "Head"), tick what it may do,
 * and every tick is genuinely enforced. The three built-in roles keep their old behaviour.
 */
class RoleManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function headUser(array $permissions = []): User
    {
        $role = Role::where('key', 'head')->first() ?? Role::create(['key' => 'head', 'name' => 'Head', 'color' => 'sky', 'is_system' => false]);
        $role->syncPermissions($permissions);

        return User::factory()->create(['role' => 'head', 'location' => 'CEB', 'department_id' => \App\Models\Department::first()->id]);
    }

    private function ticket(User $owner): Ticket
    {
        static $n = 0;
        $n++;

        return Ticket::create([
            'user_id' => $owner->id, 'department' => 'Accounting', 'location' => 'Cebu Office', 'category' => 'Printer',
            'title' => "Printer jam {$n}", 'priority' => 'medium', 'status' => 'open', 'description' => 'Paper jam',
        ]);
    }

    private function asset(): \App\Models\Asset
    {
        static $n = 0;
        $n++;

        return \App\Models\Asset::create([
            'company' => 'CFI', 'location' => 'CEB', 'type' => 'LT', 'sequence' => 900 + $n,
            'asset_tag' => "CFI-CEB-ACC-LT-9{$n}", 'device_name' => "Device {$n}", 'status' => 'active',
            'department_id' => \App\Models\Department::where('code', 'ACC')->value('id'),
        ]);
    }

    private function supply(): \App\Models\Supply
    {
        return \App\Models\Supply::create([
            'name' => 'Wireless Mouse', 'category' => 'mouse', 'brand' => 'Logitech', 'model_no' => 'M185', 'unit' => 'pc',
            'quantity' => 5, 'reorder_level' => 2, 'critical_level' => 1, 'location' => 'CEB',
        ]);
    }

    private function createRole(array $permissions, string $name = 'Head')
    {
        return $this->actingAs($this->admin())->post(route('admin.roles.store'), [
            'name' => $name, 'description' => 'Department heads', 'color' => 'sky', 'permissions' => $permissions,
        ]);
    }

    /* ------------------------------------------------------------------ built-in roles are unchanged */

    public function test_the_three_built_in_roles_exist_with_their_old_access(): void
    {
        $registry = app(RoleRegistry::class);

        $this->assertEqualsCanonicalizing(['staff', 'it_support', 'admin'], $registry->all()->keys()->all());
        $this->assertTrue($registry->all()->every(fn (Role $r) => $r->is_system));

        $staff = User::factory()->create(['role' => 'staff']);
        $it = User::factory()->create(['role' => 'it_support']);
        $admin = $this->admin();

        $this->assertTrue($staff->isStaff());
        $this->assertTrue($it->isItSupport());
        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($admin->isItSupport());
        $this->assertFalse($admin->isStaff());

        $this->assertTrue($staff->hasPermission('supplies.request'));
        $this->assertFalse($staff->hasPermission('assets.manage'));
        $this->assertTrue($it->hasPermission('assets.manage'));
        $this->assertFalse($it->hasPermission('assets.delete'));
        $this->assertTrue($admin->hasPermission('assets.delete'));
        $this->assertFalse($admin->hasPermission('supplies.request'), 'administrators do not raise supply requests');
    }

    /* ------------------------------------------------------------------ creating roles */

    public function test_an_admin_can_create_a_role_and_pick_what_it_can_do(): void
    {
        $this->createRole(['reports.view', 'tickets.view_all'])->assertRedirect(route('admin.roles.index'))->assertSessionHasNoErrors();

        $role = Role::where('name', 'Head')->firstOrFail();
        $this->assertSame('head', $role->key);
        $this->assertFalse($role->is_system);
        $this->assertEqualsCanonicalizing(['reports.view', 'tickets.view_all'], $role->permissionKeys());
    }

    public function test_dependent_permissions_come_with_what_they_need(): void
    {
        $this->createRole(['assets.manage', 'assets.delete', 'supplies.request', 'policy.manage'])->assertSessionHasNoErrors();

        $keys = Role::where('key', 'head')->first()->permissionKeys();
        foreach (['assets.view', 'supplies.view', 'policy.view'] as $needed) {
            $this->assertContains($needed, $keys, "$needed should be added automatically");
        }
    }

    public function test_locked_and_unknown_permissions_can_never_be_granted(): void
    {
        $this->createRole(['users.manage', 'roles.manage', 'made.up', 'reports.view'])->assertSessionHasNoErrors();

        $head = User::factory()->create(['role' => 'head']);
        $this->assertSame(['reports.view'], Role::where('key', 'head')->first()->permissionKeys());
        $this->assertFalse($head->hasPermission('users.manage'));
        $this->assertFalse($head->hasPermission('roles.manage'));
    }

    public function test_role_names_must_be_unique_and_present(): void
    {
        $this->createRole([], 'Head')->assertSessionHasNoErrors();
        $this->createRole([], 'head')->assertSessionHasErrors('name');
        $this->createRole([], '')->assertSessionHasErrors('name');
        $this->createRole([], 'Staff')->assertSessionHasErrors('name');
    }

    public function test_keys_are_short_unique_and_safe(): void
    {
        $this->createRole([], 'Department Head of Information Technology Services');
        $this->createRole([], 'Dept Head of Information Technology Services Two');

        $keys = Role::where('is_system', false)->pluck('key');
        $this->assertCount(2, $keys->unique());
        $keys->each(fn ($k) => $this->assertLessThanOrEqual(20, strlen($k)));

        $this->createRole([], 'All');
        $this->assertNotContains('all', Role::pluck('key')->all(), '"all" means everyone in announcements');
    }

    /* ------------------------------------------------------------------ editing, resetting, deleting */

    public function test_permissions_of_a_custom_role_can_be_changed_and_take_effect_immediately(): void
    {
        $head = $this->headUser(['reports.view']);
        $this->assertTrue($head->hasPermission('reports.view'));
        $this->assertFalse($head->hasPermission('tickets.view_all'));

        $role = Role::where('key', 'head')->first();
        $this->actingAs($this->admin())->put(route('admin.roles.update', $role), [
            'name' => 'Head', 'color' => 'rose', 'permissions' => ['tickets.view_all'],
        ])->assertSessionHasNoErrors();

        $head = $head->fresh();
        $this->assertTrue($head->hasPermission('tickets.view_all'));
        $this->assertFalse($head->hasPermission('reports.view'));
        $this->assertSame('rose', $role->fresh()->color);
    }

    public function test_the_administrator_role_cannot_be_changed(): void
    {
        $admin = Role::where('key', 'admin')->first();

        $this->actingAs($this->admin())->put(route('admin.roles.update', $admin), ['name' => 'X', 'color' => 'rose', 'permissions' => []])
            ->assertRedirect(route('admin.roles.index'))->assertSessionHas('error');

        $this->assertSame('Administrator', $admin->fresh()->name);
        $this->actingAs($this->admin())->get(route('admin.roles.edit', $admin))->assertOk()->assertSee('full access');
    }

    public function test_built_in_staff_can_be_adjusted_and_reset_and_keeps_its_name(): void
    {
        $staff = Role::where('key', 'staff')->first();

        $this->actingAs($this->admin())->put(route('admin.roles.update', $staff), [
            'name' => 'Renamed', 'color' => 'indigo', 'permissions' => ['assets.view'],
        ])->assertSessionHasNoErrors();

        $this->assertSame('Staff', $staff->fresh()->name, 'built-in names are fixed');
        $this->assertSame(['assets.view'], $staff->fresh()->permissionKeys());

        $this->actingAs($this->admin())->post(route('admin.roles.reset', $staff))->assertSessionHasNoErrors();
        $this->assertEqualsCanonicalizing(Permissions::DEFAULTS['staff'], $staff->fresh()->permissionKeys());
    }

    public function test_a_role_in_use_cannot_be_deleted_but_an_empty_one_can(): void
    {
        $this->headUser(['reports.view']);
        $role = Role::where('key', 'head')->first();

        $this->actingAs($this->admin())->delete(route('admin.roles.destroy', $role))->assertSessionHas('error');
        $this->assertNotNull(Role::where('key', 'head')->first());

        User::where('role', 'head')->update(['role' => 'staff']);
        $this->actingAs($this->admin())->delete(route('admin.roles.destroy', $role))->assertSessionHas('status');
        $this->assertNull(Role::where('key', 'head')->first());
    }

    public function test_built_in_roles_cannot_be_deleted(): void
    {
        foreach (['staff', 'it_support', 'admin'] as $key) {
            $this->actingAs($this->admin())->delete(route('admin.roles.destroy', Role::where('key', $key)->first()))->assertSessionHas('error');
            $this->assertNotNull(Role::where('key', $key)->first());
        }
    }

    /* ------------------------------------------------------------------ who may manage roles */

    public function test_only_administrators_can_manage_roles_even_if_a_role_tries_to_claim_it(): void
    {
        $head = $this->headUser(['reports.view']);
        \DB::table('role_permissions')->insert(['role_id' => Role::where('key', 'head')->first()->id, 'permission' => 'roles.manage']);
        app(RoleRegistry::class)->flush();

        $this->actingAs($head)->get(route('admin.roles.index'))->assertForbidden();
        $this->actingAs($head)->post(route('admin.roles.store'), ['name' => 'Evil', 'color' => 'rose', 'permissions' => ['reports.view']])->assertForbidden();
        $this->actingAs($head)->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'staff']))->get(route('admin.roles.index'))->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'it_support']))->get(route('admin.roles.index'))->assertForbidden();
    }

    public function test_the_pages_render_for_admins(): void
    {
        $this->headUser(['reports.view']);
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.roles.index'))
            ->assertOk()->assertSee('Roles &amp; Permissions', false)->assertSee('Head')->assertSee('Compare roles')->assertSee('Built-in');
        $this->actingAs($admin)->get(route('admin.roles.create'))->assertOk()->assertSee('Create role')->assertSee('Start from');
        $this->actingAs($admin)->get(route('admin.roles.edit', Role::where('key', 'head')->first()))->assertOk()->assertSee('Save changes');
        $this->actingAs($admin)->get(route('dashboard'))->assertSee(route('admin.roles.index'), false);
    }

    /* ------------------------------------------------------------------ people and roles */

    public function test_a_person_can_be_given_a_custom_role_and_appears_on_its_own_tab(): void
    {
        $this->headUser(['reports.view']);
        $person = User::factory()->create(['role' => 'staff', 'name' => 'Pat Example']);

        $this->actingAs($this->admin())->patch(route('admin.users.role', $person), ['role' => 'head'])->assertSessionHas('status');
        $this->assertSame('head', $person->fresh()->role);
        $this->assertSame('Head', $person->fresh()->roleName());

        $this->actingAs($this->admin())->get(route('admin.users.index', ['tab' => 'head']))->assertOk()->assertSee('Pat Example');
        $this->actingAs($this->admin())->patch(route('admin.users.role', $person), ['role' => 'nonsense'])->assertSessionHasErrors('role');
    }

    public function test_the_last_active_admin_cannot_be_moved_out_of_the_admin_role(): void
    {
        $only = $this->admin();

        $this->actingAs($only)->patch(route('admin.users.role', $only), ['role' => 'staff'])->assertSessionHas('error');
        $this->assertSame('admin', $only->fresh()->role);
    }

    /* ------------------------------------------------------------------ each permission is really enforced */

    public function test_menu_and_pages_follow_the_permissions(): void
    {
        $head = $this->headUser(['reports.view']);

        $this->actingAs($head)->get(route('admin.reports.index'))->assertOk();
        $this->actingAs($head)->get(route('assets.index'))->assertForbidden();
        $this->actingAs($head)->get(route('supplies.index'))->assertForbidden();
        $this->actingAs($head)->get(route('it-policy.show'))->assertForbidden();
        $this->actingAs($head)->get(route('users.directory'))->assertForbidden();

        $page = $this->actingAs($head)->get(route('dashboard'))->assertOk();
        $page->assertSee(route('admin.reports.index'), false);
        $page->assertDontSee(route('assets.index'), false);
        $page->assertDontSee(route('supplies.index'), false);
        $page->assertDontSee(route('it-policy.show'), false);
    }

    public function test_granting_view_permissions_opens_those_pages(): void
    {
        $head = $this->headUser(['assets.view', 'supplies.view', 'policy.view', 'users.directory', 'reports.view']);

        foreach (['assets.index', 'supplies.index', 'it-policy.show', 'users.directory', 'admin.reports.index'] as $route) {
            $this->actingAs($head)->get(route($route))->assertOk();
        }
    }

    public function test_management_permissions_unlock_only_their_own_area(): void
    {
        $head = $this->headUser(['assets.manage']);

        $this->actingAs($head)->get(route('assets.create'))->assertOk();
        $this->actingAs($head)->get(route('assets.index'))->assertOk()->assertSee(route('assets.create'), false);   // can also see the list (implied)
        $this->actingAs($head)->get(route('supplies.create'))->assertForbidden();
        $this->actingAs($head)->get(route('admin.announcements.index'))->assertForbidden();
        $this->actingAs($head)->get(route('admin.branches.index'))->assertForbidden();
        $this->actingAs($head)->get(route('admin.branding.edit'))->assertForbidden();
        $this->actingAs($head)->get(route('admin.it-policy.edit'))->assertForbidden();
    }

    public function test_each_admin_area_has_its_own_permission(): void
    {
        $cases = [
            'announcements.manage' => 'admin.announcements.index',
            'organization.manage' => 'admin.branches.index',
            'branding.manage' => 'admin.branding.edit',
            'policy.manage' => 'admin.it-policy.edit',
        ];
        foreach ($cases as $permission => $route) {
            $head = $this->headUser([$permission]);
            $this->actingAs($head)->get(route($route))->assertOk();
            foreach (array_diff($cases, [$route]) as $other) {
                $this->actingAs($head)->get(route($other))->assertForbidden();
            }
        }
    }

    public function test_see_all_tickets_is_read_only_and_works_page_by_page(): void
    {
        $head = $this->headUser(['tickets.view_all']);
        $owner = User::factory()->create(['role' => 'staff']);
        $ticket = $this->ticket($owner);

        $this->actingAs($head)->get(route('tickets.index'))->assertOk()->assertSee('All Tickets')->assertSee($ticket->ticket_number);
        $this->actingAs($head)->get(route('tickets.show', $ticket))->assertOk();

        // …but they cannot work it
        $this->actingAs($head)->post(route('tickets.accept', $ticket))->assertForbidden();
        $this->actingAs($head)->patch(route('tickets.status', $ticket), ['status' => 'closed'])->assertForbidden();
    }

    public function test_without_see_all_tickets_a_person_only_sees_their_own(): void
    {
        $head = $this->headUser(['reports.view']);
        $other = User::factory()->create(['role' => 'staff']);
        $theirs = $this->ticket($other);
        $mine = $this->ticket($head);

        $this->actingAs($head)->get(route('tickets.index'))->assertOk()->assertSee($mine->ticket_number)->assertDontSee($theirs->ticket_number);
        $this->actingAs($head)->get(route('tickets.show', $theirs))->assertForbidden();
        $this->actingAs($head)->get(route('tickets.show', $mine))->assertOk();
    }

    public function test_work_on_tickets_gives_the_it_support_workspace_and_makes_them_assignable(): void
    {
        $lead = $this->headUser(['tickets.work']);
        $owner = User::factory()->create(['role' => 'staff']);
        $ticket = $this->ticket($owner);

        $this->assertTrue($lead->isItSupport());
        $this->assertFalse($lead->isStaff());
        $this->assertTrue(User::agents()->whereKey($lead->id)->exists());
        $this->assertFalse(User::requesters()->whereKey($lead->id)->exists());

        $this->actingAs($lead)->post(route('tickets.accept', $ticket))->assertRedirect();
        $this->assertSame($lead->id, $ticket->fresh()->assigned_to);

        // an admin can now assign tickets to this role too
        $second = $this->ticket($owner);
        $this->actingAs($this->admin())->patch(route('tickets.assign', $second), ['assigned_to' => $lead->id])->assertSessionHasNoErrors();
        $this->assertSame($lead->id, $second->fresh()->assigned_to);
    }

    public function test_exports_need_their_own_permission(): void
    {
        $plain = $this->headUser(['assets.view']);
        $this->actingAs($plain)->get(route('admin.assets.export.excel'))->assertForbidden();
        $this->actingAs($plain)->get(route('admin.tickets.export.excel'))->assertForbidden();

        $exporter = $this->headUser(['assets.export']);
        $this->actingAs($exporter)->get(route('admin.assets.export.excel'))->assertOk();
        $this->actingAs($exporter)->get(route('admin.tickets.export.excel'))->assertForbidden();
    }

    public function test_deleting_assets_and_supplies_need_their_own_permission(): void
    {
        $asset = $this->asset();
        $manager = $this->headUser(['assets.manage']);
        $this->actingAs($manager)->delete(route('admin.assets.destroy', $asset))->assertForbidden();

        $deleter = $this->headUser(['assets.delete']);
        $this->actingAs($deleter)->delete(route('admin.assets.destroy', $asset))->assertRedirect();
        $this->assertNull(\App\Models\Asset::find($asset->id));
    }

    public function test_requesting_supplies_needs_the_request_permission(): void
    {
        $viewer = $this->headUser(['supplies.view']);
        $supply = $this->supply();

        $this->actingAs($viewer)->post(route('supplies.request', $supply), ['quantity' => 1, 'note' => 'please'])->assertForbidden();
    }

    /* ------------------------------------------------------------------ announcements, reports, comments */

    public function test_announcements_can_be_aimed_at_a_custom_role(): void
    {
        $head = $this->headUser(['reports.view']);
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.announcements.create'))->assertOk()->assertSee('Head only');
        $this->assertArrayHasKey('head', \App\Models\Announcement::audiences());
    }

    public function test_comments_from_a_head_are_not_marked_as_it_replies(): void
    {
        $head = $this->headUser(['tickets.view_all']);
        $this->assertTrue($head->isStaff());

        $lead = $this->headUser(['tickets.work']);
        $this->assertFalse($lead->isStaff());
    }

    public function test_changes_are_written_to_the_activity_log(): void
    {
        $this->createRole(['reports.view']);

        $this->assertDatabaseHas('activity_logs', ['action' => 'role_created']);
    }
}
