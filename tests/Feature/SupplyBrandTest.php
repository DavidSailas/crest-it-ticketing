<?php

namespace Tests\Feature;

use App\Models\Supply;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * How brands are handled on IT Supplies.
 *
 * Ink & toner always needs a brand. Brother and Epson are the company's printers; any other brand is
 * allowed but must say which printer it is for. Every category gets a tidy brand picker, and the
 * server keeps one brand from turning into several ("logitech" / "Logitech" / "A4 Tech" / "A4Tech").
 */
class SupplyBrandTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
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

    private function toner(array $overrides = []): array
    {
        return $this->payload($overrides + ['name' => 'Toner', 'category' => 'ink_toner', 'model_no' => 'X1', 'unit' => 'cartridge']);
    }

    private function store(array $payload)
    {
        return $this->actingAs($this->admin())->post(route('supplies.store'), $payload);
    }

    /* ---------- Ink & toner ---------- */

    public function test_brother_and_epson_toner_needs_no_extra_note(): void
    {
        $this->store($this->toner(['brand' => 'Brother']))->assertSessionHasNoErrors();
        $this->store($this->toner(['name' => 'Ink', 'brand' => 'Epson']))->assertSessionHasNoErrors();

        $this->assertDatabaseCount('supplies', 2);
    }

    public function test_toner_for_another_brand_is_allowed_when_it_says_which_printer(): void
    {
        $this->store($this->toner(['brand' => 'HP', 'notes' => 'For the Accounting HP LaserJet']))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('supplies', ['brand' => 'HP', 'category' => 'ink_toner']);
    }

    public function test_toner_for_another_brand_without_a_printer_note_is_asked_for_one(): void
    {
        $this->store($this->toner(['brand' => 'HP']))
            ->assertSessionHasErrors('notes')
            ->assertSessionDoesntHaveErrors('brand');

        $this->store($this->toner(['brand' => 'HP', 'notes' => '   ']))->assertSessionHasErrors('notes');

        $this->assertDatabaseCount('supplies', 0);
    }

    public function test_toner_always_needs_a_brand(): void
    {
        $this->store($this->toner(['brand' => null]))->assertSessionHasErrors('brand');
        $this->store($this->toner(['brand' => '   ']))->assertSessionHasErrors('brand');

        $this->assertDatabaseCount('supplies', 0);
    }

    public function test_the_printer_note_is_only_required_for_ink_and_toner(): void
    {
        $this->store($this->payload(['brand' => 'Logitech', 'notes' => null]))->assertSessionHasNoErrors();
    }

    public function test_the_printer_note_is_also_required_when_editing_toner_to_another_brand(): void
    {
        $this->store($this->toner(['brand' => 'Brother']));
        $supply = Supply::firstOrFail();

        $edit = $this->toner(['brand' => 'Canon']);
        unset($edit['quantity']);

        $this->actingAs($this->admin())->put(route('supplies.update', $supply), $edit)->assertSessionHasErrors('notes');
        $this->assertSame('Brother', $supply->fresh()->brand);

        $this->actingAs($this->admin())->put(route('supplies.update', $supply), array_merge($edit, ['notes' => 'For the Canon in Billing']))
            ->assertSessionHasNoErrors();
        $this->assertSame('Canon', $supply->fresh()->brand);
    }

    /* ---------- One brand, one spelling ---------- */

    public function test_a_brand_keeps_the_spelling_we_already_have(): void
    {
        $this->store($this->payload(['brand' => 'Logitech']));

        // Different capitalisation, a different category, extra spaces — still "Logitech".
        $this->store($this->payload(['name' => 'Keyboard', 'category' => 'keyboard', 'brand' => '  logitech ', 'model_no' => 'K120']));
        $this->store($this->payload(['name' => 'Mouse 2', 'brand' => 'LOGITECH', 'model_no' => 'M90']));

        $this->assertSame(['Logitech'], Supply::distinct()->pluck('brand')->all());
    }

    public function test_spacing_and_punctuation_do_not_create_a_second_brand(): void
    {
        $this->store($this->payload(['brand' => 'A4 Tech']));   // starter list says "A4Tech"

        $this->assertDatabaseHas('supplies', ['brand' => 'A4Tech']);
        $this->assertDatabaseMissing('supplies', ['brand' => 'A4 Tech']);
    }

    public function test_company_printer_brands_are_corrected_to_their_proper_spelling(): void
    {
        $this->store($this->toner(['brand' => 'brother']))->assertSessionHasNoErrors();
        $this->store($this->toner(['name' => 'Ink', 'brand' => 'EPSON']))->assertSessionHasNoErrors();   // no note needed

        $this->assertEqualsCanonicalizing(['Brother', 'Epson'], Supply::pluck('brand')->all());
    }

    public function test_a_new_brand_is_saved_as_typed_apart_from_tidying_spaces(): void
    {
        $this->store($this->payload(['brand' => "  Rapoo   Wireless  "]))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('supplies', ['brand' => 'Rapoo Wireless']);
    }

    public function test_the_same_item_with_a_differently_typed_brand_is_still_a_duplicate(): void
    {
        $this->store($this->payload(['brand' => 'Logitech']));

        $this->store($this->payload(['brand' => 'logitech']))->assertSessionHasErrors('name');

        $this->assertDatabaseCount('supplies', 1);
    }

    public function test_other_categories_can_have_no_brand(): void
    {
        $this->store($this->payload(['name' => 'HDMI Cable', 'category' => 'cables', 'brand' => null, 'model_no' => null]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('supplies', ['name' => 'HDMI Cable', 'brand' => null]);
    }

    public function test_a_brand_that_is_not_text_is_rejected_cleanly(): void
    {
        $this->store($this->payload(['brand' => ['Logitech']]))->assertSessionHasErrors('brand');
        $this->store($this->toner(['brand' => ['HP']]))->assertSessionHasErrors('brand');

        $this->assertDatabaseCount('supplies', 0);
    }

    public function test_an_over_long_brand_is_rejected(): void
    {
        $this->store($this->payload(['brand' => str_repeat('a', 101)]))->assertSessionHasErrors('brand');
    }

    /* ---------- The brand picker's options ---------- */

    public function test_brand_options_start_with_the_starter_brands_then_add_what_is_in_stock(): void
    {
        $options = Supply::brandOptions();

        $this->assertSame(['Brother', 'Epson'], $options['ink_toner']);
        $this->assertSame(['Logitech', 'A4Tech'], $options['mouse']);
        $this->assertSame([], $options['other']);

        $this->store($this->toner(['brand' => 'HP', 'notes' => 'For Accounting']));
        $this->store($this->payload(['name' => 'Mouse 2', 'brand' => 'Rapoo', 'model_no' => 'M10']));

        $options = Supply::brandOptions();

        $this->assertSame(['Brother', 'Epson', 'HP'], $options['ink_toner']);
        $this->assertSame(['Logitech', 'A4Tech', 'Rapoo'], $options['mouse']);
        $this->assertNotContains('Rapoo', $options['ink_toner']);   // a mouse brand isn't offered for toner
    }

    public function test_brand_options_never_list_the_same_brand_twice(): void
    {
        Supply::create($this->payload(['name' => 'a', 'brand' => 'logitech']));
        Supply::create($this->payload(['name' => 'b', 'brand' => 'LOGITECH', 'model_no' => 'x']));

        $this->assertSame(['Logitech', 'A4Tech'], Supply::brandOptions()['mouse']);
    }

    public function test_company_printer_brand_check(): void
    {
        $this->assertTrue(Supply::isCompanyPrinterBrand('Brother'));
        $this->assertTrue(Supply::isCompanyPrinterBrand(' epson '));
        $this->assertFalse(Supply::isCompanyPrinterBrand('HP'));
        $this->assertFalse(Supply::isCompanyPrinterBrand(''));
        $this->assertFalse(Supply::isCompanyPrinterBrand(null));
    }

    /* ---------- The form ---------- */

    public function test_the_form_offers_the_picker_with_other_brand_and_the_printer_brands(): void
    {
        $this->actingAs($this->admin())->get(route('supplies.create'))
            ->assertOk()
            ->assertSee('Other brand…', false)
            ->assertSee('name="brand"', false)
            ->assertSee('Brother')->assertSee('Epson')
            ->assertDontSee('only stocked for');
    }

    public function test_the_form_offers_a_brand_added_earlier(): void
    {
        $this->store($this->toner(['brand' => 'HP', 'notes' => 'For Accounting']));

        $this->actingAs($this->admin())->get(route('supplies.create'))->assertOk()->assertSee('HP');
    }

    public function test_editing_an_item_with_another_brand_renders_with_that_brand(): void
    {
        $this->store($this->toner(['brand' => 'HP', 'notes' => 'For Accounting']));
        $supply = Supply::firstOrFail();

        $this->actingAs($this->admin())->get(route('supplies.edit', $supply))
            ->assertOk()->assertSee('value="HP"', false)->assertSee('For Accounting');
    }

    public function test_a_failed_save_keeps_what_was_typed_and_explains_what_is_missing(): void
    {
        $this->actingAs($this->admin())->from(route('supplies.create'))->followingRedirects()
            ->post(route('supplies.store'), $this->toner(['brand' => 'Canon']))   // no note → rejected
            ->assertOk()
            ->assertSee('value="Canon"', false)                    // the brand they typed is still there
            ->assertSee('Say which printer this is for', false);   // and the form says what is missing
    }
}
