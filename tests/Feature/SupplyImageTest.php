<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Supply;
use App\Models\SupplyRequest;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * IT Support / Admin add a photo to a supply item so staff can recognise it before requesting.
 */
class SupplyImageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    private function it(): User
    {
        return User::factory()->create(['role' => 'it_support']);
    }

    private function staff(): User
    {
        return User::factory()->create(['role' => 'staff', 'location' => 'CEB', 'department_id' => Department::first()->id]);
    }

    private function payload(array $overrides = []): array
    {
        return $overrides + [
            'name' => 'Logitech M185 Mouse', 'category' => 'mouse', 'brand' => 'Logitech', 'model_no' => 'M185',
            'unit' => 'pcs', 'quantity' => 5, 'reorder_level' => 3, 'critical_level' => 1, 'location' => 'CEB',
        ];
    }

    private function supply(array $overrides = []): Supply
    {
        return Supply::create($overrides + [
            'name' => 'Brother TN-2380 Black Toner', 'category' => 'ink_toner', 'brand' => 'Brother', 'unit' => 'cartridge',
            'quantity' => 10, 'reorder_level' => 4, 'critical_level' => 2, 'location' => 'CEB',
        ]);
    }

    private function dimensions(string $path): array
    {
        [$w, $h] = getimagesizefromstring(Storage::disk('public')->get($path));

        return [$w, $h];
    }

    private function thumb(string $path): string
    {
        return preg_replace('/\.jpg$/', '_thumb.jpg', $path);
    }

    /* ---------- Uploading ---------- */

    public function test_it_support_can_add_an_item_with_a_photo_and_a_thumbnail_is_made(): void
    {
        $this->actingAs($this->it())
            ->post(route('supplies.store'), $this->payload(['image' => UploadedFile::fake()->image('mouse.jpg', 800, 600)]))
            ->assertSessionHasNoErrors()->assertRedirect(route('supplies.index'));

        $supply = Supply::firstOrFail();
        $this->assertNotNull($supply->image_path);
        $this->assertStringStartsWith('supplies/', $supply->image_path);
        $this->assertStringEndsWith('.jpg', $supply->image_path);
        Storage::disk('public')->assertExists($supply->image_path);
        Storage::disk('public')->assertExists($this->thumb($supply->image_path));

        $this->assertStringContainsString($supply->image_path, $supply->image_url);
        $this->assertStringContainsString('_thumb.jpg', $supply->thumb_url);
    }

    public function test_big_photos_are_scaled_down_and_small_ones_are_not_blown_up(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('supplies.store'), $this->payload(['name' => 'Big', 'image' => UploadedFile::fake()->image('big.jpg', 3000, 2000)]));
        $this->actingAs($admin)->post(route('supplies.store'), $this->payload(['name' => 'Small', 'image' => UploadedFile::fake()->image('small.jpg', 300, 200)]));

        $big = Supply::firstWhere('name', 'Big');
        $this->assertSame([1000, 667], $this->dimensions($big->image_path));
        $this->assertSame([360, 240], $this->dimensions($this->thumb($big->image_path)));

        $small = Supply::firstWhere('name', 'Small');
        $this->assertSame([300, 200], $this->dimensions($small->image_path));
    }

    public function test_a_transparent_png_is_saved_as_a_jpeg_on_a_white_background(): void
    {
        $png = imagecreatetruecolor(200, 200);
        imagealphablending($png, false);
        imagesavealpha($png, true);
        imagefill($png, 0, 0, imagecolorallocatealpha($png, 255, 0, 0, 127));   // fully transparent
        ob_start();
        imagepng($png);
        $bytes = ob_get_clean();

        $this->actingAs($this->it())
            ->post(route('supplies.store'), $this->payload(['image' => UploadedFile::fake()->createWithContent('logo.png', $bytes)]))
            ->assertSessionHasNoErrors();

        $path = Supply::firstOrFail()->image_path;
        $this->assertStringEndsWith('.jpg', $path);

        $saved = imagecreatefromstring(Storage::disk('public')->get($path));
        $rgb = imagecolorat($saved, 10, 10);
        foreach ([($rgb >> 16) & 255, ($rgb >> 8) & 255, $rgb & 255] as $channel) {
            $this->assertGreaterThan(240, $channel, 'transparent area should become white');
        }
    }

    public function test_a_phone_photo_with_a_rotation_flag_is_turned_upright(): void
    {
        // A 400x200 landscape JPEG tagged EXIF Orientation=6 ("rotate 90° to display") — what a phone
        // held upright produces. It must come out portrait: 200 wide, 400 tall.
        $im = imagecreatetruecolor(400, 200);
        ob_start();
        imagejpeg($im, null, 90);
        $jpeg = ob_get_clean();

        $tiff = "MM\x00\x2A\x00\x00\x00\x08"                       // big-endian TIFF header, IFD at offset 8
              ."\x00\x01"                                          // one entry
              ."\x01\x12\x00\x03\x00\x00\x00\x01\x00\x06\x00\x00"  // Orientation (0x0112), SHORT, 1 value = 6
              ."\x00\x00\x00\x00";                                 // no next IFD
        $app1 = "\xFF\xE1".pack('n', 2 + 6 + strlen($tiff))."Exif\x00\x00".$tiff;
        $tagged = substr($jpeg, 0, 2).$app1.substr($jpeg, 2);

        $this->actingAs($this->it())
            ->post(route('supplies.store'), $this->payload(['image' => UploadedFile::fake()->createWithContent('IMG_0001.jpg', $tagged)]))
            ->assertSessionHasNoErrors();

        $this->assertSame([200, 400], $this->dimensions(Supply::firstOrFail()->image_path));
    }

    public function test_the_stored_photo_has_no_metadata_left(): void
    {
        $im = imagecreatetruecolor(100, 100);
        ob_start();
        imagejpeg($im, null, 90);
        $jpeg = ob_get_clean();
        $app1 = "\xFF\xE1".pack('n', 2 + 6 + 8)."Exif\x00\x00MM\x00\x2A\x00\x00\x00\x08";
        $tagged = substr($jpeg, 0, 2).$app1.substr($jpeg, 2);

        $this->actingAs($this->it())
            ->post(route('supplies.store'), $this->payload(['image' => UploadedFile::fake()->createWithContent('gps.jpg', $tagged)]));

        $this->assertStringNotContainsString('Exif', Storage::disk('public')->get(Supply::firstOrFail()->image_path));
    }

    /* ---------- Rejecting bad uploads ---------- */

    public function test_things_that_are_not_photos_are_rejected_and_nothing_is_saved(): void
    {
        $it = $this->it();

        $bad = [
            'a script renamed to .jpg' => UploadedFile::fake()->createWithContent('shell.jpg', '<?php system($_GET["c"]); ?>'),
            'a PDF' => UploadedFile::fake()->create('manual.pdf', 50, 'application/pdf'),
            'an SVG (can hold scripts)' => UploadedFile::fake()->createWithContent('logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'),
            'a GIF' => UploadedFile::fake()->image('anim.gif', 50, 50),
        ];

        foreach ($bad as $what => $file) {
            $response = $this->actingAs($it)->post(route('supplies.store'), $this->payload(['image' => $file]));

            try {
                $response->assertSessionHasErrors('image');
            } catch (\Throwable) {
                $this->fail("{$what} should have been refused");
            }
        }

        $this->assertDatabaseCount('supplies', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_a_corrupt_picture_gives_a_friendly_error_and_saves_nothing(): void
    {
        $corrupt = UploadedFile::fake()->createWithContent('broken.png', "\x89PNG\r\n\x1a\n".random_bytes(64));

        $this->actingAs($this->it())->post(route('supplies.store'), $this->payload(['image' => $corrupt]))
            ->assertSessionHasErrors('image');

        $this->assertDatabaseCount('supplies', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_the_photo_is_optional(): void
    {
        $this->actingAs($this->it())->post(route('supplies.store'), $this->payload())->assertSessionHasNoErrors();

        $supply = Supply::firstOrFail();
        $this->assertNull($supply->image_path);
        $this->assertNull($supply->image_url);
        $this->assertNull($supply->thumb_url);
    }

    public function test_staff_cannot_upload_photos(): void
    {
        $this->actingAs($this->staff())
            ->post(route('supplies.store'), $this->payload(['image' => UploadedFile::fake()->image('x.jpg')]))
            ->assertForbidden();

        $supply = $this->supply();
        $this->actingAs($this->staff())
            ->put(route('supplies.update', $supply), $this->payload(['image' => UploadedFile::fake()->image('x.jpg')]))
            ->assertForbidden();

        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    /* ---------- Replace / remove / delete ---------- */

    private function withPhoto(): Supply
    {
        $this->actingAs($this->it())->post(route('supplies.store'), $this->payload(['image' => UploadedFile::fake()->image('first.jpg', 600, 600)]));

        return Supply::firstOrFail();
    }

    public function test_replacing_the_photo_removes_the_old_files(): void
    {
        $supply = $this->withPhoto();
        $old = $supply->image_path;

        $this->actingAs($this->it())
            ->put(route('supplies.update', $supply), $this->payload(['image' => UploadedFile::fake()->image('second.jpg', 500, 500)]))
            ->assertSessionHasNoErrors();

        $supply->refresh();
        $this->assertNotSame($old, $supply->image_path);            // new name, so browsers never show a stale picture
        Storage::disk('public')->assertMissing($old);
        Storage::disk('public')->assertMissing($this->thumb($old));
        Storage::disk('public')->assertExists($supply->image_path);
        Storage::disk('public')->assertExists($this->thumb($supply->image_path));
        $this->assertCount(2, Storage::disk('public')->allFiles());
    }

    public function test_saving_without_choosing_a_file_keeps_the_current_photo(): void
    {
        $supply = $this->withPhoto();
        $path = $supply->image_path;

        $this->actingAs($this->it())->put(route('supplies.update', $supply), $this->payload(['name' => 'Renamed mouse', 'remove_image' => 0]))
            ->assertSessionHasNoErrors();

        $this->assertSame($path, $supply->fresh()->image_path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_a_photo_can_be_removed(): void
    {
        $supply = $this->withPhoto();
        $path = $supply->image_path;

        $this->actingAs($this->it())->put(route('supplies.update', $supply), $this->payload(['remove_image' => 1]))
            ->assertSessionHasNoErrors();

        $this->assertNull($supply->fresh()->image_path);
        Storage::disk('public')->assertMissing($path);
        Storage::disk('public')->assertMissing($this->thumb($path));
    }

    public function test_a_failed_replacement_keeps_the_old_photo(): void
    {
        $supply = $this->withPhoto();
        $path = $supply->image_path;

        $this->actingAs($this->it())
            ->put(route('supplies.update', $supply), $this->payload(['image' => UploadedFile::fake()->createWithContent('bad.jpg', '<?php ?>')]))
            ->assertSessionHasErrors('image');

        $this->assertSame($path, $supply->fresh()->image_path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_deleting_an_item_deletes_its_photos(): void
    {
        $supply = $this->withPhoto();
        $path = $supply->image_path;

        $this->actingAs(User::factory()->create(['role' => 'admin']))->delete(route('admin.supplies.destroy', $supply))->assertRedirect();

        Storage::disk('public')->assertMissing($path);
        Storage::disk('public')->assertMissing($this->thumb($path));
    }

    /* ---------- What people see ---------- */

    public function test_staff_see_the_photo_in_the_list_on_the_item_page_and_in_the_request_dialog(): void
    {
        $this->actingAs($this->it())->post(route('supplies.store'), $this->payload([
            'name' => 'Brother TN-2380 Black Toner', 'category' => 'ink_toner', 'brand' => 'Brother', 'unit' => 'cartridge',
            'image' => UploadedFile::fake()->image('toner.jpg', 700, 700),
        ]));
        $supply = Supply::firstOrFail();
        $staff = $this->staff();

        $this->actingAs($staff)->get(route('supplies.index'))
            ->assertOk()->assertSee($supply->thumb_url, false);

        $page = $this->actingAs($staff)->get(route('supplies.show', $supply));
        $page->assertOk()
            ->assertSee($supply->image_url, false)              // the big photo
            ->assertSee('Enlarge')
            ->assertSee('Check the photo — is this the one you need?');
    }

    public function test_the_photo_follows_the_request_to_the_requests_page_and_the_ticket(): void
    {
        $supply = $this->supply(['image_path' => 'supplies/demo.jpg']);
        $staff = $this->staff();

        $this->actingAs($staff)->post(route('supplies.request', $supply), ['quantity' => 1, 'purpose' => 'Accounting printer', 'urgency' => 'normal']);
        $ticket = Ticket::firstOrFail();

        $this->actingAs($staff)->get(route('supplies.requests'))->assertSee($supply->thumb_url, false);
        $this->actingAs($this->it())->get(route('tickets.show', $ticket))->assertSee($supply->thumb_url, false);
    }

    public function test_items_without_a_photo_get_an_icon_tile_and_it_support_a_nudge(): void
    {
        $supply = $this->supply();

        $this->actingAs($this->staff())->get(route('supplies.show', $supply))
            ->assertOk()->assertDontSee('Add a photo so staff can recognise this item')->assertDontSee('/storage/supplies/', false);

        $this->actingAs($this->it())->get(route('supplies.show', $supply))
            ->assertSee('Add a photo so staff can recognise this item');

        $this->actingAs($this->staff())->get(route('supplies.index'))->assertOk()->assertDontSee('/storage/supplies/', false);
    }

    public function test_the_forms_can_upload_and_the_edit_form_shows_the_current_photo(): void
    {
        $it = $this->it();

        $this->actingAs($it)->get(route('supplies.create'))
            ->assertOk()->assertSee('enctype="multipart/form-data"', false)->assertSee('Add photo')->assertSee('name="image"', false);

        $supply = $this->supply(['image_path' => 'supplies/demo.jpg']);
        $this->actingAs($it)->get(route('supplies.edit', $supply))
            ->assertOk()
            ->assertSee($supply->thumb_url, false)                                  // header thumbnail
            ->assertSee('\/storage\/supplies\/demo.jpg', false)                     // handed to the picker so it can show/remove it
            ->assertSee('name="remove_image"', false);
    }
}
