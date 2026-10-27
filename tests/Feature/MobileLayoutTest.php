<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Department;
use App\Models\Supply;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phones get cards instead of tables, collapsible filters, and full-size buttons;
 * tablets and desktops keep the tables. (The look itself is checked by eye on real
 * screenshots; these tests keep the structure from quietly regressing.)
 */
class MobileLayoutTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        return User::factory()->create(['role' => $role, 'location' => 'CEB', 'department_id' => Department::first()->id]);
    }

    private function seedData(): array
    {
        $staff = $this->user('staff');
        $ticket = Ticket::create([
            'user_id' => $staff->id, 'department' => 'Accounting', 'location' => 'Cebu Office', 'category' => 'Printer',
            'title' => 'Printer', 'priority' => 'high', 'status' => 'open', 'description' => 'Jam',
        ]);
        $asset = Asset::create([
            'company' => 'CFI', 'location' => 'CEB', 'type' => 'LT', 'sequence' => 1, 'asset_tag' => 'CFI-CEB-ACC-LT-001',
            'device_name' => 'Dell Latitude 5420', 'status' => 'active', 'department_id' => Department::first()->id,
        ]);
        $supply = Supply::create([
            'name' => 'Brother TN-2380 Black Toner', 'category' => 'ink_toner', 'brand' => 'Brother', 'unit' => 'cartridge',
            'quantity' => 10, 'reorder_level' => 4, 'critical_level' => 2, 'location' => 'CEB',
        ]);

        return compact('staff', 'ticket', 'asset', 'supply');
    }

    public function test_list_pages_have_a_card_list_for_phones_and_a_table_for_tablets_and_up(): void
    {
        $this->seedData();
        $admin = $this->user('admin');

        foreach ([route('supplies.index'), route('assets.index'), route('tickets.index'), route('admin.users.index'), route('users.directory')] as $url) {
            $html = $this->actingAs($admin)->get($url)->assertOk()->getContent();

            $this->assertStringContainsString('<article', $html, "{$url} should render phone cards");
            $this->assertStringContainsString('md:hidden', $html, "{$url} should hide the cards from tablets up");
            // The table is hidden on phones and comes back from tablet width (on the table itself or its wrapper).
            $this->assertMatchesRegularExpression('/class="[^"]*\bhidden\b[^"]*\bmd:(table|block)\b/', $html, "{$url} should keep a table from tablet width");
            $this->assertStringContainsString('<table', $html, "{$url} should still have its table");
        }
    }

    public function test_every_phone_card_shows_the_important_details_and_a_tap_target(): void
    {
        ['supply' => $supply, 'asset' => $asset, 'ticket' => $ticket] = $this->seedData();
        $admin = $this->user('admin');

        $this->actingAs($admin)->get(route('supplies.index'))
            ->assertSee('Brother TN-2380 Black Toner')->assertSee('aria-label="View Brother TN-2380 Black Toner"', false);
        $this->actingAs($admin)->get(route('assets.index'))
            ->assertSee('Dell Latitude 5420')->assertSee('CFI-CEB-ACC-LT-001');
        $this->actingAs($admin)->get(route('tickets.index'))
            ->assertSee('aria-label="Open ticket '.$ticket->ticket_number.'"', false);
    }

    public function test_phone_buttons_use_the_shared_button_styles(): void
    {
        $this->seedData();

        $html = $this->actingAs($this->user('admin'))->get(route('supplies.index'))->getContent();

        foreach (['btn btn-secondary btn-sm', 'btn btn-soft btn-sm', 'btn btn-danger btn-sm'] as $classes) {
            $this->assertStringContainsString($classes, $html);
        }
    }

    public function test_the_delete_buttons_on_phone_cards_do_not_clash_with_the_table_ones(): void
    {
        ['supply' => $supply, 'asset' => $asset] = $this->seedData();
        $admin = $this->user('admin');

        $html = $this->actingAs($admin)->get(route('supplies.index'))->getContent();
        $this->assertStringContainsString("delete-supply-{$supply->id}", $html);
        $this->assertStringContainsString("delete-supply-m-{$supply->id}", $html);

        $html = $this->actingAs($admin)->get(route('assets.index'))->getContent();
        $this->assertStringContainsString("delete-asset-{$asset->id}", $html);
        $this->assertStringContainsString("delete-asset-m-{$asset->id}", $html);
    }

    public function test_the_asset_details_dialogs_live_outside_the_table_so_phones_can_open_them(): void
    {
        ['asset' => $asset] = $this->seedData();

        $html = $this->actingAs($this->user('admin'))->get(route('assets.index'))->getContent();

        $dialog = strpos($html, "viewingId === {$asset->id}");
        $tableEnd = strpos($html, '</table>');

        $this->assertNotFalse($dialog);
        $this->assertNotFalse($tableEnd);
        $this->assertGreaterThan($tableEnd, $dialog, 'the dialog must not be inside the (phone-hidden) table');
        $this->assertStringContainsString('@click="viewingId = '.$asset->id.'"', $html);   // the phone card's View button
    }

    public function test_filters_fold_behind_a_button_on_phones(): void
    {
        $this->seedData();
        $admin = $this->user('admin');

        foreach ([
            route('supplies.index') => 'supply-filter-fields',
            route('assets.index') => 'asset-filter-fields',
            route('tickets.index') => 'ticket-filter-fields',
            route('admin.users.index') => 'user-filter-fields',
            route('users.directory') => 'directory-filter-fields',
        ] as $url => $id) {
            $html = $this->actingAs($admin)->get($url)->assertOk()->getContent();
            $this->assertStringContainsString("id=\"{$id}\"", $html, "{$url} filters");
            $this->assertStringContainsString("aria-controls=\"{$id}\"", $html, "{$url} filter button");
        }
    }

    public function test_filters_start_open_when_one_is_already_applied(): void
    {
        $this->seedData();

        $this->actingAs($this->user('admin'))->get(route('supplies.index', ['category' => 'ink_toner']))
            ->assertSee('filters: true', false);
        $this->actingAs($this->user('admin'))->get(route('supplies.index'))
            ->assertSee('filters: false', false);
    }

    public function test_the_page_header_has_the_hook_the_phone_styles_use(): void
    {
        $this->actingAs($this->user('admin'))->get(route('supplies.index'))
            ->assertSee('<header class="app-header', false);
    }

    public function test_department_branch_and_position_lists_have_phone_cards_with_real_buttons_and_an_edit_form(): void
    {
        $admin = $this->user('admin');

        foreach ([route('admin.departments.index'), route('admin.branches.index')] as $url) {
            $html = $this->actingAs($admin)->get($url)->assertOk()->getContent();

            $this->assertStringContainsString('sm:hidden', $html);               // the phone list
            $this->assertStringContainsString('btn btn-soft btn-sm', $html);     // Edit
            $this->assertStringContainsString('btn btn-danger btn-sm', $html);   // Delete
            $this->assertStringContainsString('name="code"', $html);             // the code can be edited on a phone now
            $this->assertStringContainsString('flex flex-col gap-3 sm:flex-row', $html);   // the add form stacks on phones
        }
    }

    public function test_dashboards_use_cards_on_phones(): void
    {
        $this->seedData();

        foreach (['admin', 'it_support', 'staff'] as $role) {
            $html = $this->actingAs($this->user($role))->get(route('dashboard'))->assertOk()->getContent();

            if ($role !== 'staff') {
                $this->assertStringContainsString('<article', $html, "{$role} dashboard should list tickets as cards on phones");
            }
        }
    }

    public function test_tables_on_user_pages_become_cards_on_phones(): void
    {
        ['staff' => $staff] = $this->seedData();

        $html = $this->actingAs($this->user('admin'))->get(route('admin.users.show', $staff))->assertOk()->getContent();
        $this->assertStringContainsString('<article', $html);

        $html = $this->actingAs($this->user('it_support'))->get(route('users.directory.show', $staff))->assertOk()->getContent();
        $this->assertStringContainsString('<article', $html);
    }

    public function test_nothing_in_the_card_components_breaks_when_a_title_is_very_long(): void
    {
        Supply::create([
            'name' => str_repeat('Extremely long supply item name ', 6), 'category' => 'other', 'unit' => 'pcs',
            'quantity' => 1, 'reorder_level' => 1, 'critical_level' => 0, 'location' => 'CEB',
        ]);

        // Long text must be allowed to wrap rather than push the card wider than the screen.
        $this->actingAs($this->user('staff'))->get(route('supplies.index'))
            ->assertOk()->assertSee('break-words', false);
    }
}
