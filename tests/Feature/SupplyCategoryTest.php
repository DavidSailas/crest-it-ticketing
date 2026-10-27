<?php

namespace Tests\Feature;

use App\Models\Supply;
use App\Models\User;
use Database\Seeders\SupplySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * A freight forwarder uses desk telephones, not call-centre headsets or webcams.
 */
class SupplyCategoryTest extends TestCase
{
    use RefreshDatabase;

    private function it(): User
    {
        return User::factory()->create(['role' => 'it_support']);
    }

    private function payload(array $overrides = []): array
    {
        return $overrides + [
            'name' => 'Telephone Handset', 'category' => 'telephone', 'brand' => 'Panasonic', 'model_no' => 'KX-TS500',
            'unit' => 'pcs', 'quantity' => 4, 'reorder_level' => 2, 'critical_level' => 1, 'location' => 'CEB',
        ];
    }

    private function supply(array $overrides = []): Supply
    {
        return Supply::create($overrides + [
            'name' => 'Telephone Handset', 'category' => 'telephone', 'unit' => 'pcs',
            'quantity' => 4, 'reorder_level' => 2, 'critical_level' => 1, 'location' => 'CEB',
        ]);
    }

    public function test_headsets_and_webcams_are_gone_and_telephones_are_in(): void
    {
        $this->assertArrayNotHasKey('headsets', Supply::CATEGORIES);
        $this->assertSame('Telephone & Handsets', Supply::CATEGORIES['telephone']);
        $this->assertCount(8, Supply::CATEGORIES);

        foreach (Supply::CATEGORIES as $label) {
            $this->assertStringNotContainsStringIgnoringCase('headset', $label);
            $this->assertStringNotContainsStringIgnoringCase('webcam', $label);
        }
    }

    public function test_it_support_can_add_a_telephone_item_and_filter_by_it(): void
    {
        $it = $this->it();

        $this->actingAs($it)->post(route('supplies.store'), $this->payload())->assertSessionHasNoErrors();
        $this->supply(['name' => 'Wireless Mouse', 'category' => 'mouse']);

        $this->assertDatabaseHas('supplies', ['name' => 'Telephone Handset', 'category' => 'telephone', 'brand' => 'Panasonic']);

        $this->actingAs($it)->get(route('supplies.index'))
            ->assertOk()->assertSee('Telephone &amp; Handsets', false);

        $this->actingAs($it)->get(route('supplies.index', ['category' => 'telephone']))
            ->assertSee('Telephone Handset')->assertDontSee('Wireless Mouse');
    }

    public function test_the_old_headsets_category_is_no_longer_accepted(): void
    {
        $this->actingAs($this->it())->post(route('supplies.store'), $this->payload(['category' => 'headsets']))
            ->assertSessionHasErrors('category');

        $this->assertDatabaseCount('supplies', 0);
    }

    public function test_the_forms_and_list_offer_telephones_not_headsets(): void
    {
        $it = $this->it();
        $this->supply();

        foreach ([route('supplies.create'), route('supplies.index')] as $url) {
            $this->actingAs($it)->get($url)->assertOk()
                ->assertSee('Telephone &amp; Handsets', false)
                ->assertDontSee('Headsets')->assertDontSee('Webcams');
        }

        // Staff see the same categories.
        $this->actingAs(User::factory()->create(['role' => 'staff']))->get(route('supplies.index'))
            ->assertSee('Telephone &amp; Handsets', false)->assertDontSee('Headsets');
    }

    public function test_the_form_suggests_telephone_brands_for_telephones(): void
    {
        $this->actingAs($this->it())->get(route('supplies.create'))
            ->assertOk()->assertSee('Panasonic')->assertSee('Yealink')->assertSee('Grandstream')->assertSee('KX-TS500, T31P');
    }

    public function test_telephones_take_any_brand_and_ink_takes_another_brand_with_a_printer_note(): void
    {
        $it = $this->it();

        $this->actingAs($it)->post(route('supplies.store'), $this->payload(['brand' => 'Yealink']))->assertSessionHasNoErrors();

        // Another toner brand is allowed, but has to say which printer it is for.
        $toner = $this->payload(['name' => 'Toner', 'category' => 'ink_toner', 'brand' => 'HP']);
        $this->actingAs($it)->post(route('supplies.store'), $toner)->assertSessionHasErrors('notes')->assertSessionDoesntHaveErrors('brand');
        $this->actingAs($it)->post(route('supplies.store'), array_merge($toner, ['notes' => 'For the Accounting HP LaserJet']))->assertSessionHasNoErrors();
    }

    public function test_items_already_saved_as_headsets_move_to_other_and_nothing_else_changes(): void
    {
        $insert = fn (string $name, string $category) => DB::table('supplies')->insertGetId([
            'name' => $name, 'category' => $category, 'unit' => 'pcs', 'quantity' => 3, 'reorder_level' => 2, 'critical_level' => 1,
            'location' => 'CEB', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $headset = $insert('USB Headset', 'headsets');
        $mouse = $insert('Mouse', 'mouse');
        $phone = $insert('Handset', 'telephone');

        $migration = require database_path('migrations/2024_01_01_000040_replace_headsets_supply_category.php');
        $migration->up();
        $migration->up();   // running it twice is harmless

        $this->assertSame('other', Supply::find($headset)->category);
        $this->assertSame('USB Headset', Supply::find($headset)->name);   // the item itself is kept
        $this->assertSame(3, Supply::find($headset)->quantity);
        $this->assertSame('mouse', Supply::find($mouse)->category);
        $this->assertSame('telephone', Supply::find($phone)->category);

        // …and it now shows up in the list under Other instead of vanishing.
        $this->actingAs($this->it())->get(route('supplies.index', ['category' => 'other']))->assertSee('USB Headset');
    }

    public function test_the_sample_data_has_telephone_items_and_no_headsets(): void
    {
        $this->seed(SupplySeeder::class);

        $this->assertSame(0, Supply::where('category', 'headsets')->count());
        $this->assertSame(0, Supply::where('name', 'like', '%Headset')->where('category', '!=', 'telephone')->count());
        $this->assertGreaterThanOrEqual(2, Supply::where('category', 'telephone')->count());
    }
}
