<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Assets can belong to a person, to a whole department (router -> IT Department),
 * or sit unassigned in stock. Plus the new Mobile Phone / Telephone device types.
 */
class AssetAssignmentTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function it(): Department
    {
        return Department::where('code', 'IT')->firstOrFail();
    }

    private function payload(array $overrides = []): array
    {
        return $overrides + [
            'company' => 'CFI',
            'location' => 'CEB',
            'department_id' => Department::where('code', 'ACC')->value('id'),
            'type' => 'LT',
            'device_name' => 'Dell Latitude 5420',
            'assignment' => 'stock',
        ];
    }

    private function asset(array $overrides = []): Asset
    {
        static $n = 0;
        $n++;

        return Asset::create($overrides + [
            'company' => 'CFI', 'location' => 'CEB', 'type' => 'LT', 'sequence' => $n,
            'asset_tag' => "CFI-CEB-ACC-LT-{$n}", 'device_name' => "Device {$n}", 'status' => 'active',
            'department_id' => Department::where('code', 'ACC')->value('id'),
        ]);
    }

    /* ---------- New device types ---------- */

    public function test_mobile_phone_and_telephone_are_device_types(): void
    {
        $this->assertSame('Mobile Phone', Asset::TYPES['PH']);
        $this->assertSame('Telephone', Asset::TYPES['TP']);

        $admin = $this->admin();

        $this->actingAs($admin)->post(route('assets.store'), $this->payload(['type' => 'TP', 'device_name' => 'Yealink T31P']))->assertSessionHasNoErrors();
        $this->actingAs($admin)->post(route('assets.store'), $this->payload(['type' => 'PH', 'device_name' => 'Samsung A15']))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('assets', ['asset_tag' => 'CFI-CEB-ACC-TP-001', 'type' => 'TP']);
        $this->assertDatabaseHas('assets', ['asset_tag' => 'CFI-CEB-ACC-PH-001', 'type' => 'PH']);
    }

    public function test_the_list_has_a_tile_for_telephones(): void
    {
        $this->asset(['type' => 'TP', 'asset_tag' => 'CFI-CEB-ACC-TP-001', 'sequence' => 1, 'device_name' => 'Desk phone']);

        $this->actingAs($this->admin())->get(route('assets.index', ['type' => 'TP']))
            ->assertOk()->assertSee('Telephone')->assertSee('Mobile Phone')->assertSee('Desk phone');
    }

    /* ---------- IT Department exists ---------- */

    public function test_an_it_department_is_available_to_assign_to(): void
    {
        $it = $this->it();
        $this->assertSame('IT Department', $it->name);
        $this->assertSame($it->id, Asset::itDepartmentId());
    }

    public function test_an_existing_it_department_with_another_name_is_still_found(): void
    {
        Department::where('code', 'IT')->delete();
        $existing = Department::create(['name' => 'IT Support', 'code' => 'ITS']);

        $this->assertSame($existing->id, Asset::itDepartmentId());
    }

    /* ---------- Assigning ---------- */

    public function test_a_router_can_be_assigned_to_the_it_department(): void
    {
        $this->actingAs($this->admin())
            ->post(route('assets.store'), $this->payload([
                'type' => 'NW', 'device_name' => 'TP-Link Router', 'department_id' => $this->it()->id, 'assignment' => 'department',
            ]))
            ->assertRedirect(route('assets.index'))
            ->assertSessionHas('status', 'Asset CFI-CEB-IT-NW-001 assigned to IT Department.');

        $asset = Asset::firstWhere('asset_tag', 'CFI-CEB-IT-NW-001');
        $this->assertNull($asset->user_id);
        $this->assertTrue($asset->department_assigned);
        $this->assertSame($this->it()->id, $asset->department_id);
        $this->assertSame('department', $asset->assignment);
        $this->assertSame('IT Department (shared)', $asset->assignee_label);
    }

    public function test_assigning_to_a_department_ignores_any_person_that_was_picked(): void
    {
        $user = User::factory()->create(['role' => 'staff']);

        $this->actingAs($this->admin())
            ->post(route('assets.store'), $this->payload(['assignment' => 'department', 'user_id' => $user->id, 'department_id' => $this->it()->id]))
            ->assertSessionHasNoErrors();

        $asset = Asset::firstOrFail();
        $this->assertNull($asset->user_id);
        $this->assertTrue($asset->department_assigned);
    }

    public function test_assigning_to_a_person_needs_a_person(): void
    {
        $this->actingAs($this->admin())
            ->post(route('assets.store'), $this->payload(['assignment' => 'person', 'user_id' => null]))
            ->assertSessionHasErrors('user_id');

        $this->assertDatabaseCount('assets', 0);
    }

    public function test_a_person_assignment_stores_the_person_and_not_the_department_flag(): void
    {
        $user = User::factory()->create(['role' => 'staff']);

        $this->actingAs($this->admin())
            ->post(route('assets.store'), $this->payload(['assignment' => 'person', 'user_id' => $user->id]));

        $asset = Asset::firstOrFail();
        $this->assertSame($user->id, $asset->user_id);
        $this->assertFalse($asset->department_assigned);
        $this->assertSame('person', $asset->assignment);
        $this->assertSame($user->name, $asset->assignee_label);
    }

    public function test_not_assigned_goes_into_stock(): void
    {
        $this->actingAs($this->admin())->post(route('assets.store'), $this->payload(['assignment' => 'stock']));

        $asset = Asset::firstOrFail();
        $this->assertSame('stock', $asset->assignment);
        $this->assertSame('Unassigned', $asset->assignee_label);
    }

    public function test_older_forms_without_an_assignment_field_still_work(): void
    {
        $user = User::factory()->create(['role' => 'staff']);
        $admin = $this->admin();
        $base = $this->payload();
        unset($base['assignment']);

        $this->actingAs($admin)->post(route('assets.store'), $base + ['user_id' => $user->id]);
        $this->actingAs($admin)->post(route('assets.store'), $base);

        $this->assertSame(['person', 'stock'], Asset::orderBy('id')->get()->pluck('assignment')->all());
    }

    public function test_the_assign_asset_form_on_a_users_page_always_assigns_to_that_user(): void
    {
        $user = User::factory()->create(['role' => 'staff']);

        $this->actingAs($this->admin())
            ->post(route('admin.users.assets.store', $user), $this->payload(['assignment' => 'department']))
            ->assertSessionHasNoErrors();

        $this->assertSame($user->id, Asset::firstOrFail()->user_id);
    }

    /* ---------- Changing the assignment ---------- */

    public function test_an_asset_can_move_between_person_department_and_stock(): void
    {
        $user = User::factory()->create(['role' => 'staff']);
        $asset = $this->asset(['user_id' => $user->id, 'sequence' => 1, 'asset_tag' => 'CFI-CEB-ACC-LT-001']);
        $admin = $this->admin();

        $edit = fn (array $o) => $this->actingAs($admin)->put(route('assets.update', $asset), $o + [
            'device_name' => 'Dell', 'status' => 'active', 'sequence' => 1,
            'company' => 'CFI', 'location' => 'CEB', 'department_id' => $asset->department_id, 'type' => 'LT',
        ])->assertSessionHasNoErrors();

        $edit(['assignment' => 'department']);
        $asset->refresh();
        $this->assertSame([null, true], [$asset->user_id, $asset->department_assigned]);

        $edit(['assignment' => 'person', 'user_id' => $user->id]);
        $asset->refresh();
        $this->assertSame([$user->id, false], [$asset->user_id, $asset->department_assigned]);

        $edit(['assignment' => 'stock']);
        $asset->refresh();
        $this->assertSame([null, false], [$asset->user_id, $asset->department_assigned]);
    }

    public function test_the_quick_unassign_form_on_a_users_page_puts_it_back_in_stock(): void
    {
        $user = User::factory()->create(['role' => 'staff']);
        $asset = $this->asset(['user_id' => $user->id, 'sequence' => 1, 'asset_tag' => 'CFI-CEB-ACC-LT-001']);

        // That form sends no "assignment" field — just a blank user_id.
        $this->actingAs($this->admin())->put(route('assets.update', $asset), [
            'user_id' => '', 'device_name' => 'Dell', 'status' => 'active', 'sequence' => 1,
        ])->assertSessionHasNoErrors();

        $this->assertSame('stock', $asset->fresh()->assignment);
    }

    /* ---------- List, filters, counts ---------- */

    private function threeStates(): array
    {
        $user = User::factory()->create(['role' => 'staff']);

        return [
            'person' => $this->asset(['user_id' => $user->id, 'device_name' => 'Personal Laptop']),
            'department' => $this->asset(['department_assigned' => true, 'department_id' => $this->it()->id, 'type' => 'NW', 'device_name' => 'Core Router']),
            'stock' => $this->asset(['device_name' => 'Spare Laptop']),
        ];
    }

    public function test_the_assignment_filter_separates_people_departments_and_stock(): void
    {
        $this->threeStates();
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('assets.index', ['assignment' => 'assigned']))
            ->assertSee('Personal Laptop')->assertDontSee('Core Router')->assertDontSee('Spare Laptop');

        $this->actingAs($admin)->get(route('assets.index', ['assignment' => 'department']))
            ->assertSee('Core Router')->assertDontSee('Personal Laptop')->assertDontSee('Spare Laptop');

        $this->actingAs($admin)->get(route('assets.index', ['assignment' => 'unassigned']))
            ->assertSee('Spare Laptop')->assertDontSee('Personal Laptop')->assertDontSee('Core Router');
    }

    public function test_a_department_asset_is_not_counted_as_unassigned_and_is_labelled_as_shared(): void
    {
        $this->threeStates();

        $page = $this->actingAs($this->admin())->get(route('assets.index'));
        $page->assertOk()->assertSee('Shared equipment')->assertSee('IT Department');

        // Totals add up: 1 person + 1 department + 1 stock = 3
        $summary = $page->viewData('summary');
        $this->assertSame(3, $summary['total']);
        $this->assertSame(1, $summary['people']);
        $this->assertSame(1, $summary['department']);
        $this->assertSame(1, $summary['unassigned']);
        $this->assertSame(2, $summary['assigned']);
        $this->assertSame(1, $summary['byType']['NW']['department']);
    }

    public function test_it_support_sees_the_department_assignment_too(): void
    {
        $this->threeStates();

        $this->actingAs(User::factory()->create(['role' => 'it_support']))->get(route('assets.index'))
            ->assertOk()->assertSee('IT Department');
    }

    /* ---------- Forms, tag checker, exports ---------- */

    public function test_new_and_edit_forms_offer_the_three_assignment_choices(): void
    {
        $admin = $this->admin();
        $asset = $this->threeStates()['department'];

        $this->actingAs($admin)->get(route('assets.create'))
            ->assertOk()->assertSee('A department')->assertSee('A person')->assertSee('Not assigned')
            ->assertSee('Shared equipment')->assertSee('Mobile Phone')->assertSee('Telephone')
            ->assertSee('itDeptId: "'.$this->it()->id.'"', false);

        $this->actingAs($admin)->get(route('assets.edit', $asset))
            ->assertOk()->assertSee('assignTo: "department"', false);
    }

    public function test_the_tag_checker_names_the_department_that_holds_a_taken_tag(): void
    {
        $router = $this->asset([
            'department_assigned' => true, 'department_id' => $this->it()->id, 'type' => 'NW',
            'sequence' => 1, 'asset_tag' => 'CFI-CEB-IT-NW-001', 'device_name' => 'Core Router',
        ]);

        $this->actingAs($this->admin())
            ->getJson(route('assets.tag-check', ['company' => 'CFI', 'location' => 'CEB', 'department_id' => $this->it()->id, 'type' => 'NW', 'sequence' => 1]))
            ->assertOk()->assertJsonPath('taken', true)->assertJsonPath('existing.owner', 'IT Department (shared)');

        // A clash on save explains who holds it, too.
        $this->actingAs($this->admin())
            ->post(route('assets.store'), $this->payload(['type' => 'NW', 'department_id' => $this->it()->id, 'sequence' => 1, 'assignment' => 'department']))
            ->assertSessionHasErrors('sequence');
    }

    public function test_exports_handle_department_assets(): void
    {
        $this->threeStates();
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.assets.export.pdf'))->assertOk();
        $this->actingAs($admin)->get(route('admin.assets.export.excel'))->assertOk();
    }
}
