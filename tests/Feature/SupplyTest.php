<?php

namespace Tests\Feature;

use App\Models\Supply;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplyTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRole(string $role): User
    {
        return User::factory()->create(['role' => $role]);
    }

    private function supply(array $overrides = []): Supply
    {
        return Supply::create($overrides + [
            'name' => 'HP 305A Black Toner',
            'category' => 'ink_toner',
            'brand' => 'Brother',
            'model_no' => 'TN-2380',
            'unit' => 'cartridge',
            'quantity' => 10,
            'reorder_level' => 4,
            'critical_level' => 2,
            'location' => 'CEB',
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return $overrides + [
            'name' => 'Wireless Mouse',
            'category' => 'mouse',
            'brand' => 'Logitech',
            'model_no' => 'M185',
            'unit' => 'pcs',
            'quantity' => 8,
            'reorder_level' => 3,
            'critical_level' => 1,
            'location' => 'CEB',
            'notes' => null,
        ];
    }

    /* ---------- Who can see what ---------- */

    public function test_guests_are_sent_to_login(): void
    {
        $this->get(route('supplies.index'))->assertRedirect(route('login'));
    }

    public function test_every_role_can_view_the_list_and_an_item(): void
    {
        $supply = $this->supply();

        foreach (['staff', 'it_support', 'admin'] as $role) {
            $user = $this->userWithRole($role);

            $this->actingAs($user)->get(route('supplies.index'))
                ->assertOk()->assertSee('HP 305A Black Toner');

            $this->actingAs($user)->get(route('supplies.show', $supply))
                ->assertOk()->assertSee($supply->code);
        }
    }

    public function test_staff_see_no_management_controls(): void
    {
        $supply = $this->supply();
        $staff = $this->userWithRole('staff');

        $this->actingAs($staff)->get(route('supplies.index'))
            ->assertDontSee(route('supplies.create'), false)
            ->assertDontSee(route('supplies.edit', $supply), false)
            ->assertDontSee(route('admin.supplies.destroy', $supply), false);

        $this->actingAs($staff)->get(route('supplies.show', $supply))
            ->assertDontSee('Stock In')
            ->assertDontSee(route('supplies.adjust', $supply), false);
    }

    public function test_it_support_sees_add_and_edit_but_not_delete(): void
    {
        $supply = $this->supply();
        $it = $this->userWithRole('it_support');

        $this->actingAs($it)->get(route('supplies.index'))
            ->assertSee(route('supplies.create'), false)
            ->assertSee(route('supplies.edit', $supply), false)
            ->assertDontSee(route('admin.supplies.destroy', $supply), false);
    }

    public function test_admin_sees_delete(): void
    {
        $supply = $this->supply();

        $this->actingAs($this->userWithRole('admin'))->get(route('supplies.index'))
            ->assertSee(route('admin.supplies.destroy', $supply), false);
    }

    /* ---------- Staff are read-only (enforced server-side) ---------- */

    public function test_staff_cannot_create_edit_adjust_or_delete(): void
    {
        $supply = $this->supply();
        $staff = $this->userWithRole('staff');

        $this->actingAs($staff)->get(route('supplies.create'))->assertForbidden();
        $this->actingAs($staff)->post(route('supplies.store'), $this->payload())->assertForbidden();
        $this->actingAs($staff)->get(route('supplies.edit', $supply))->assertForbidden();
        $this->actingAs($staff)->put(route('supplies.update', $supply), $this->payload())->assertForbidden();
        $this->actingAs($staff)->post(route('supplies.adjust', $supply), ['type' => 'in', 'quantity' => 5])->assertForbidden();
        $this->actingAs($staff)->delete(route('admin.supplies.destroy', $supply))->assertForbidden();

        $this->assertSame(10, $supply->fresh()->quantity);
        $this->assertDatabaseCount('supplies', 1);
    }

    public function test_it_support_cannot_delete(): void
    {
        $supply = $this->supply();

        $this->actingAs($this->userWithRole('it_support'))
            ->delete(route('admin.supplies.destroy', $supply))
            ->assertForbidden();

        $this->assertDatabaseHas('supplies', ['id' => $supply->id]);
    }

    /* ---------- IT Support + Admin: add / edit ---------- */

    public function test_it_support_can_add_an_item_and_opening_stock_is_logged(): void
    {
        $it = $this->userWithRole('it_support');

        $this->actingAs($it)->post(route('supplies.store'), $this->payload())
            ->assertRedirect(route('supplies.index'));

        $supply = Supply::firstWhere('name', 'Wireless Mouse');
        $this->assertSame(8, $supply->quantity);
        $this->assertDatabaseHas('supply_movements', [
            'supply_id' => $supply->id, 'user_id' => $it->id, 'type' => 'initial', 'change' => 8, 'balance_after' => 8,
        ]);
    }

    public function test_it_support_can_edit_details_but_not_the_quantity_through_the_edit_form(): void
    {
        $supply = $this->supply();

        $this->actingAs($this->userWithRole('it_support'))
            ->put(route('supplies.update', $supply), $this->payload([
                'name' => 'HP 305A Toner (Black)',
                'quantity' => 999, // must be ignored — stock changes go through adjust()
            ]))
            ->assertRedirect();

        $supply->refresh();
        $this->assertSame('HP 305A Toner (Black)', $supply->name);
        $this->assertSame(10, $supply->quantity);
    }

    public function test_validation_rejects_bad_input(): void
    {
        $this->actingAs($this->userWithRole('admin'))
            ->post(route('supplies.store'), $this->payload(['name' => '', 'category' => 'nope', 'quantity' => -1, 'location' => 'XXX']))
            ->assertSessionHasErrors(['name', 'category', 'quantity', 'location']);

        $this->assertDatabaseCount('supplies', 0);
    }

    public function test_the_same_item_cannot_be_added_twice_to_a_branch(): void
    {
        $this->supply();

        $this->actingAs($this->userWithRole('admin'))
            ->post(route('supplies.store'), $this->payload([
                'name' => 'hp 305a black toner', 'category' => 'ink_toner', 'brand' => 'Brother', 'model_no' => 'TN-2380', 'unit' => 'cartridge',
            ]))
            ->assertSessionHasErrors('name');

        $this->assertDatabaseCount('supplies', 1);
    }

    public function test_admin_can_delete_and_history_goes_with_it(): void
    {
        $supply = $this->supply();
        $supply->applyMovement('in', 5);

        $this->actingAs($this->userWithRole('admin'))
            ->delete(route('admin.supplies.destroy', $supply))
            ->assertRedirect();

        $this->assertDatabaseMissing('supplies', ['id' => $supply->id]);
        $this->assertDatabaseCount('supply_movements', 0);
    }

    /* ---------- Adjusting stock ---------- */

    public function test_stock_in_and_out_update_the_quantity_and_the_history(): void
    {
        $supply = $this->supply();
        $it = $this->userWithRole('it_support');

        $this->actingAs($it)->post(route('supplies.adjust', $supply), ['type' => 'in', 'quantity' => 5, 'note' => 'Delivery'])
            ->assertRedirect(route('supplies.show', $supply));
        $this->assertSame(15, $supply->fresh()->quantity);

        $this->actingAs($it)->post(route('supplies.adjust', $supply), ['type' => 'out', 'quantity' => 3])
            ->assertRedirect();
        $this->assertSame(12, $supply->fresh()->quantity);

        $this->assertDatabaseHas('supply_movements', ['supply_id' => $supply->id, 'type' => 'in', 'change' => 5, 'balance_after' => 15, 'note' => 'Delivery', 'user_id' => $it->id]);
        $this->assertDatabaseHas('supply_movements', ['supply_id' => $supply->id, 'type' => 'out', 'change' => -3, 'balance_after' => 12]);
    }

    public function test_you_cannot_take_out_more_than_is_in_stock(): void
    {
        $supply = $this->supply(['quantity' => 2]);

        $this->actingAs($this->userWithRole('it_support'))
            ->post(route('supplies.adjust', $supply), ['type' => 'out', 'quantity' => 3])
            ->assertSessionHasErrorsIn('adjust', 'quantity');

        $this->assertSame(2, $supply->fresh()->quantity);
        $this->assertDatabaseCount('supply_movements', 0);
    }

    public function test_a_count_correction_needs_a_reason_and_records_the_difference(): void
    {
        $supply = $this->supply();
        $it = $this->userWithRole('it_support');

        $this->actingAs($it)->post(route('supplies.adjust', $supply), ['type' => 'count', 'quantity' => 7])
            ->assertSessionHasErrorsIn('adjust', 'note');
        $this->assertSame(10, $supply->fresh()->quantity);

        $this->actingAs($it)->post(route('supplies.adjust', $supply), ['type' => 'count', 'quantity' => 7, 'note' => 'Monthly stock-take'])
            ->assertRedirect();

        $this->assertSame(7, $supply->fresh()->quantity);
        $this->assertDatabaseHas('supply_movements', ['supply_id' => $supply->id, 'type' => 'count', 'change' => -3, 'balance_after' => 7]);
    }

    public function test_a_count_can_be_set_to_zero(): void
    {
        $supply = $this->supply();

        $this->actingAs($this->userWithRole('admin'))
            ->post(route('supplies.adjust', $supply), ['type' => 'count', 'quantity' => 0, 'note' => 'All used'])
            ->assertRedirect();

        $this->assertSame(0, $supply->fresh()->quantity);
        $this->assertSame('out', $supply->fresh()->stock_status);
    }

    public function test_stock_in_and_out_need_a_positive_quantity(): void
    {
        $supply = $this->supply();

        $this->actingAs($this->userWithRole('admin'))
            ->post(route('supplies.adjust', $supply), ['type' => 'in', 'quantity' => 0])
            ->assertSessionHasErrorsIn('adjust', 'quantity');
    }

    /* ---------- Stock status + filters ---------- */

    public function test_stock_status_is_worked_out_from_quantity_and_reorder_level(): void
    {
        $this->assertSame('ok', $this->supply(['name' => 'A', 'quantity' => 10, 'reorder_level' => 4])->stock_status);
        $this->assertSame('low', $this->supply(['name' => 'B', 'quantity' => 4, 'reorder_level' => 4])->stock_status);
        $this->assertSame('out', $this->supply(['name' => 'C', 'quantity' => 0, 'reorder_level' => 4])->stock_status);
        $this->assertSame('ok', $this->supply(['name' => 'D', 'quantity' => 1, 'reorder_level' => 0, 'critical_level' => 0])->stock_status);
        $this->assertSame('out', $this->supply(['name' => 'E', 'quantity' => 0, 'reorder_level' => 0, 'critical_level' => 0])->stock_status);
    }

    public function test_critical_is_its_own_warning_level_between_low_and_out(): void
    {
        $ok = $this->supply(['name' => 'Ok', 'quantity' => 9, 'reorder_level' => 8, 'critical_level' => 3]);
        $low = $this->supply(['name' => 'Low', 'quantity' => 6, 'reorder_level' => 8, 'critical_level' => 3]);
        $edge = $this->supply(['name' => 'Edge', 'quantity' => 3, 'reorder_level' => 8, 'critical_level' => 3]);
        $out = $this->supply(['name' => 'Out', 'quantity' => 0, 'reorder_level' => 8, 'critical_level' => 3]);
        $noTier = $this->supply(['name' => 'NoTier', 'quantity' => 1, 'reorder_level' => 8, 'critical_level' => 0]);

        $this->assertSame(['ok', 'low', 'critical', 'out', 'low'], [$ok->stock_status, $low->stock_status, $edge->stock_status, $out->stock_status, $noTier->stock_status]);

        // The list filters agree with the badges, and each item lands in exactly one level.
        $names = fn (string $status) => Supply::stockStatus($status)->orderBy('name')->pluck('name')->all();
        $this->assertSame(['Ok'], $names('ok'));
        $this->assertSame(['Low', 'NoTier'], $names('low'));
        $this->assertSame(['Edge'], $names('critical'));
        $this->assertSame(['Out'], $names('out'));

        $this->assertSame(['total' => 4, 'severe' => 2], Supply::restockAlert()); // low x2 + critical + out = 4; severe = critical + out
    }

    public function test_the_page_shows_the_critical_card_and_a_reorder_now_banner_to_it_support(): void
    {
        $this->supply(['name' => 'Almost gone', 'quantity' => 1, 'reorder_level' => 8, 'critical_level' => 3]);

        $this->actingAs($this->userWithRole('it_support'))->get(route('supplies.index'))
            ->assertOk()->assertSee('Critically low')->assertSee('to reorder now')->assertSee('Critical');

        $this->actingAs($this->userWithRole('staff'))->get(route('supplies.index'))
            ->assertDontSee('to reorder now');

        $this->actingAs($this->userWithRole('admin'))->get(route('supplies.index', ['status' => 'critical']))
            ->assertSee('Almost gone');
    }

    /* ---------- Brands (see also SupplyBrandTest) ---------- */

    public function test_other_categories_can_use_any_brand_or_none(): void
    {
        $this->actingAs($this->userWithRole('admin'))
            ->post(route('supplies.store'), $this->payload(['brand' => 'Logitech']))->assertSessionHasNoErrors();
        $this->actingAs($this->userWithRole('admin'))
            ->post(route('supplies.store'), $this->payload(['name' => 'Cable', 'category' => 'cables', 'brand' => null, 'model_no' => null]))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('supplies', 2);
    }

    public function test_critical_level_cannot_be_above_the_reorder_level(): void
    {
        $this->actingAs($this->userWithRole('admin'))
            ->post(route('supplies.store'), $this->payload(['reorder_level' => 3, 'critical_level' => 5]))
            ->assertSessionHasErrors('critical_level');
    }

    public function test_list_filters_by_search_category_and_stock_status(): void
    {
        $this->supply(['name' => 'Black Toner', 'quantity' => 10, 'reorder_level' => 4]);
        $this->supply(['name' => 'AA Batteries', 'category' => 'batteries', 'brand' => null, 'model_no' => null, 'quantity' => 2, 'reorder_level' => 8, 'critical_level' => 0]);
        $this->supply(['name' => 'Wireless Mouse', 'category' => 'mouse', 'brand' => null, 'model_no' => null, 'quantity' => 0, 'reorder_level' => 3]);
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)->get(route('supplies.index', ['search' => 'Mouse']))
            ->assertSee('Wireless Mouse')->assertDontSee('AA Batteries');

        $this->actingAs($admin)->get(route('supplies.index', ['category' => 'batteries']))
            ->assertSee('AA Batteries')->assertDontSee('Black Toner');

        $this->actingAs($admin)->get(route('supplies.index', ['status' => 'low']))
            ->assertSee('AA Batteries')->assertDontSee('Black Toner')->assertDontSee('Wireless Mouse');

        $this->actingAs($admin)->get(route('supplies.index', ['status' => 'out']))
            ->assertSee('Wireless Mouse')->assertDontSee('AA Batteries');

        $this->actingAs($admin)->get(route('supplies.index', ['status' => 'ok']))
            ->assertSee('Black Toner')->assertDontSee('AA Batteries')->assertDontSee('Wireless Mouse');
    }

    public function test_restock_badge_counts_low_and_out_items_for_it_but_not_staff(): void
    {
        $this->supply(['name' => 'Fine', 'quantity' => 10, 'reorder_level' => 4]);
        $this->supply(['name' => 'Low', 'quantity' => 2, 'reorder_level' => 4]);
        $this->supply(['name' => 'Out', 'quantity' => 0, 'reorder_level' => 4]);

        $this->assertSame(2, Supply::needingRestockCount());

        $this->actingAs($this->userWithRole('it_support'))->get(route('supplies.index'))
            ->assertSee('2 supply item(s) need restocking');

        $this->actingAs($this->userWithRole('staff'))->get(route('supplies.index'))
            ->assertDontSee('supply item(s) need restocking');
    }

    /* ---------- Pages render ---------- */

    public function test_new_and_edit_forms_render_for_it_support(): void
    {
        $supply = $this->supply();
        $it = $this->userWithRole('it_support');

        $this->actingAs($it)->get(route('supplies.create'))
            ->assertOk()->assertSee('Add Item')->assertSee('Low stock at')->assertSee('Critical at')->assertSee('Brother')->assertSee('Epson')->assertSee('Ink &amp; Toner', false);

        $this->actingAs($it)->get(route('supplies.edit', $supply))
            ->assertOk()->assertSee('Save Changes')->assertSee('HP 305A Black Toner')
            ->assertDontSee('name="quantity"', false); // quantity is only set on create
    }

    public function test_adjust_dialog_is_on_the_page_for_it_support_and_not_for_staff(): void
    {
        $supply = $this->supply();

        $this->actingAs($this->userWithRole('it_support'))->get(route('supplies.show', ['supply' => $supply, 'adjust' => 'out']))
            ->assertOk()->assertSee(route('supplies.adjust', $supply), false)->assertSee('Adjust stock');

        $staff = User::factory()->create(['role' => 'staff', 'location' => 'CEB']);
        $this->actingAs($staff)->get(route('supplies.show', ['supply' => $supply, 'adjust' => 'out']))
            ->assertOk()->assertDontSee('Adjust stock')->assertDontSee(route('supplies.adjust', $supply), false)->assertSee('Request this item');
    }

    public function test_a_failed_adjustment_shows_the_error_in_the_dialog(): void
    {
        $supply = $this->supply(['quantity' => 1]);
        $it = $this->userWithRole('it_support');

        $this->actingAs($it)->from(route('supplies.show', $supply))
            ->post(route('supplies.adjust', $supply), ['type' => 'out', 'quantity' => 5])
            ->assertRedirect(route('supplies.show', $supply));

        $this->actingAs($it)->get(route('supplies.show', $supply))->assertSee("Only 1 cartridge in stock");
    }

    public function test_sample_seeder_is_safe_to_run_twice(): void
    {
        $this->seed(\Database\Seeders\SupplySeeder::class);
        $count = Supply::count();
        $this->assertGreaterThan(5, $count);

        $this->seed(\Database\Seeders\SupplySeeder::class);
        $this->assertSame($count, Supply::count());
    }
}
