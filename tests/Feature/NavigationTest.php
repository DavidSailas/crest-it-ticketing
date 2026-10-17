<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The top bar: same order for every role, setup pages grouped for admins, a menu that
 * works on phones (with the notification bell), and nothing that can wrap or overlap.
 */
class NavigationTest extends TestCase
{
    use RefreshDatabase;

    private function page(string $role)
    {
        return $this->actingAs(User::factory()->create(['role' => $role]))->get(route('supplies.index'));
    }

    public function test_every_role_gets_the_same_core_links_in_the_same_order(): void
    {
        foreach (['staff', 'it_support', 'admin'] as $role) {
            $html = $this->page($role)->assertOk()->getContent();

            $positions = [];
            foreach (['Dashboard', 'Tickets', 'Assets', 'Supplies', 'Users', 'Reports', 'IT Policy'] as $label) {
                preg_match('/<a [^>]*class="[^"]*border-b-2[^"]*"[^>]*>\s*'.preg_quote($label, '/').'/s', $html, $m, PREG_OFFSET_CAPTURE);
                $this->assertNotEmpty($m, "{$role} is missing the {$label} link in the top bar");
                $positions[] = $m[0][1];
            }

            $sorted = $positions;
            sort($sorted);
            $this->assertSame($sorted, $positions, "{$role}: top-bar links are out of order");
        }
    }

    public function test_admin_setup_pages_are_grouped_under_organization(): void
    {
        $html = $this->page('admin')->assertOk()->getContent();

        $this->assertStringContainsString('Organization', $html);
        foreach (['Departments', 'Branches', 'Positions'] as $label) {
            $this->assertStringContainsString($label, $html);
        }
        // They are no longer top-level bar links (which is what made the bar overflow).
        $this->assertDoesNotMatchRegularExpression('/<a [^>]*class="[^"]*border-b-2[^"]*"[^>]*>\s*(Departments|Branches|Positions)\s*</s', $html);
        // "Manage Users" wrapped onto two lines; it is simply "Users" now.
        $this->assertStringNotContainsString('Manage Users', $html);
    }

    public function test_staff_and_it_support_do_not_get_the_admin_menu(): void
    {
        foreach (['staff', 'it_support'] as $role) {
            $this->page($role)->assertOk()
                ->assertDontSee('Organization')->assertDontSee(route('admin.departments.index'), false)->assertDontSee(route('admin.branches.index'), false);
        }
    }

    public function test_users_link_points_to_the_right_page_for_each_role(): void
    {
        $this->page('admin')->assertSee('href="'.route('admin.users.index').'"', false);
        $this->page('it_support')->assertSee('href="'.route('users.directory').'"', false);
        $this->page('staff')->assertSee('href="'.route('users.directory').'"', false);
    }

    public function test_the_desktop_bar_only_shows_from_laptop_width_and_the_menu_button_below_it(): void
    {
        $html = $this->page('admin')->assertOk()->getContent();

        $this->assertStringContainsString('hidden h-full items-stretch gap-0.5 lg:flex', $html);   // links: laptops and up
        $this->assertStringContainsString('lg:hidden', $html);                                      // menu button / panel: below laptops
        $this->assertStringContainsString('aria-controls="mobile-menu"', $html);
        $this->assertStringContainsString('id="mobile-menu"', $html);
        // The old breakpoint (640px) is what squeezed ten links into a tablet-sized bar.
        $this->assertStringNotContainsString('hidden space-x-8 sm:-my-px sm:ms-10 sm:flex', $html);
    }

    public function test_the_notification_bell_is_available_on_phones_too(): void
    {
        $html = $this->page('staff')->assertOk()->getContent();

        $this->assertStringContainsString('aria-label="Notifications"', $html);
        // It is no longer inside the "hidden on small screens" container.
        $this->assertDoesNotMatchRegularExpression('/hidden sm:flex[^>]*>\s*<!--\s*Notifications/', $html);
        $this->assertStringContainsString("notificationBell('".route('notifications.poll')."'", $html);
    }

    public function test_the_phone_menu_lists_every_link_the_laptop_bar_has_plus_the_account_actions(): void
    {
        $html = $this->page('admin')->assertOk()->getContent();
        $menu = substr($html, strpos($html, 'id="mobile-menu"'));

        foreach (['Dashboard', 'Tickets', 'Assets', 'Supplies', 'Users', 'Reports', 'IT Policy', 'Organization', 'Departments', 'Branches', 'Positions', 'Profile', 'Log Out'] as $label) {
            $this->assertStringContainsString($label, $menu, "phone menu is missing {$label}");
        }
    }

    public function test_the_current_page_is_marked_and_uses_the_brand_green(): void
    {
        $html = $this->page('staff')->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/<a [^>]*aria-current="page"[^>]*>\s*Supplies/s', $html);
        $this->assertStringContainsString('border-[#1a6b3c]', $html);
        $this->assertStringNotContainsString('border-indigo-400', $html);
    }

    public function test_long_names_cannot_wrap_or_push_the_bar(): void
    {
        $user = User::factory()->create(['role' => 'admin', 'name' => 'Maria Concepcion Villanueva-Dela Cruz Santos']);

        $html = $this->actingAs($user)->get(route('supplies.index'))->assertOk()->getContent();

        $this->assertStringContainsString('max-w-[9rem] truncate', $html);
    }

    public function test_the_restock_badge_still_shows_and_is_red_when_something_is_critical(): void
    {
        \App\Models\Supply::create([
            'name' => 'Almost gone', 'category' => 'mouse', 'unit' => 'pcs', 'quantity' => 1,
            'reorder_level' => 8, 'critical_level' => 3, 'location' => 'CEB',
        ]);

        $html = $this->page('it_support')->assertOk()->getContent();

        $this->assertStringContainsString('1 supply item(s) need restocking', $html);
        $this->assertStringContainsString('bg-rose-100 text-rose-700', $html);
    }
}
