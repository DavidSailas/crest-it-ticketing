<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The read-only Users page (Staff + IT Support) gets the same Branch and Department
 * filters Admin > Manage Users already has.
 */
class UserDirectoryFilterTest extends TestCase
{
    use RefreshDatabase;

    private function person(string $name, string $role, string $branch, string $department): User
    {
        return User::factory()->create([
            'name' => $name, 'role' => $role, 'location' => $branch,
            'department_id' => Department::where('name', $department)->value('id'),
        ]);
    }

    private function seedPeople(): void
    {
        $this->person('Ana Accounting Cebu', 'staff', 'CEB', 'Accounting');
        $this->person('Ben Accounting Manila', 'staff', 'MNL', 'Accounting');
        $this->person('Cara HR Cebu', 'staff', 'CEB', 'HR');
        $this->person('Dan IT Cebu', 'it_support', 'CEB', 'Operations');
    }

    public function test_staff_and_it_support_can_filter_by_branch(): void
    {
        $this->seedPeople();

        foreach (['staff', 'it_support'] as $role) {
            $viewer = User::factory()->create(['role' => $role]);

            $this->actingAs($viewer)->get(route('users.directory', ['branch' => 'MNL']))
                ->assertOk()->assertSee('Ben Accounting Manila')
                ->assertDontSee('Ana Accounting Cebu')->assertDontSee('Cara HR Cebu');
        }
    }

    public function test_filter_by_department(): void
    {
        $this->seedPeople();
        $hr = Department::where('name', 'HR')->value('id');

        $this->actingAs(User::factory()->create(['role' => 'staff']))
            ->get(route('users.directory', ['department_id' => $hr]))
            ->assertOk()->assertSee('Cara HR Cebu')->assertDontSee('Ana Accounting Cebu')->assertDontSee('Ben Accounting Manila');
    }

    public function test_branch_department_role_and_search_combine(): void
    {
        $this->seedPeople();
        $acc = Department::where('name', 'Accounting')->value('id');
        $viewer = User::factory()->create(['role' => 'it_support']);

        $this->actingAs($viewer)->get(route('users.directory', ['branch' => 'CEB', 'department_id' => $acc]))
            ->assertSee('Ana Accounting Cebu')->assertDontSee('Ben Accounting Manila')->assertDontSee('Cara HR Cebu');

        $this->actingAs($viewer)->get(route('users.directory', ['branch' => 'CEB', 'role' => 'it_support']))
            ->assertSee('Dan IT Cebu')->assertDontSee('Ana Accounting Cebu');

        $this->actingAs($viewer)->get(route('users.directory', ['branch' => 'CEB', 'q' => 'Cara']))
            ->assertSee('Cara HR Cebu')->assertDontSee('Ana Accounting Cebu');
    }

    public function test_unknown_branch_or_department_is_ignored_instead_of_emptying_the_list(): void
    {
        $this->seedPeople();

        $this->actingAs(User::factory()->create(['role' => 'staff']))
            ->get(route('users.directory', ['branch' => 'NOPE', 'department_id' => 99999]))
            ->assertOk()->assertSee('Ana Accounting Cebu')->assertSee('Ben Accounting Manila');
    }

    public function test_the_filter_bar_and_department_column_are_on_the_page(): void
    {
        $this->seedPeople();

        $this->actingAs(User::factory()->create(['role' => 'staff']))->get(route('users.directory'))
            ->assertOk()->assertSee('All branches')->assertSee('All departments')->assertSee('All roles')
            ->assertSee('Department');

        $this->actingAs(User::factory()->create(['role' => 'staff']))->get(route('users.directory', ['branch' => 'CEB']))
            ->assertSee('matching user');
    }

    public function test_admin_accounts_still_stay_out_of_the_directory(): void
    {
        $this->person('Hidden Admin', 'admin', 'CEB', 'Accounting');

        $this->actingAs(User::factory()->create(['role' => 'staff']))->get(route('users.directory', ['branch' => 'CEB']))
            ->assertDontSee('Hidden Admin');
    }
}
