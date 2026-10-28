<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The profile photo. The browser crops it first (see components/avatar-cropper.blade.php) and uploads the
 * result as a small square JPEG; the server side is unchanged: one picture per person, old one removed.
 */
class AvatarTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_profile_page_has_the_crop_window_and_no_longer_uploads_straight_away(): void
    {
        $this->actingAs(User::factory()->create())->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Adjust your photo')
            ->assertSee('Save photo')
            ->assertSee('Rotate left')
            ->assertSee('avatarCropper()', false)
            ->assertDontSee('onchange="this.form.submit()"', false);   // choosing a file must open the cropper first
    }

    public function test_a_cropped_square_photo_is_saved_as_the_profile_picture(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)->patch(route('profile.avatar.update'), ['avatar' => UploadedFile::fake()->image('avatar.jpg', 512, 512)])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status', 'Profile picture updated.');

        $this->assertNotNull($user->fresh()->avatar);
        Storage::disk('public')->assertExists($user->fresh()->avatar);
    }

    public function test_a_new_photo_replaces_and_removes_the_old_one(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)->patch(route('profile.avatar.update'), ['avatar' => UploadedFile::fake()->image('a.jpg', 512, 512)]);
        $old = $user->fresh()->avatar;

        $this->actingAs($user)->patch(route('profile.avatar.update'), ['avatar' => UploadedFile::fake()->image('b.jpg', 512, 512)]);

        $this->assertNotSame($old, $user->fresh()->avatar);
        Storage::disk('public')->assertMissing($old);
        Storage::disk('public')->assertExists($user->fresh()->avatar);
    }

    public function test_something_that_is_not_a_picture_is_refused_and_the_message_is_shown(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)->from(route('profile.edit'))->followingRedirects()
            ->patch(route('profile.avatar.update'), ['avatar' => UploadedFile::fake()->create('notes.pdf', 50, 'application/pdf')])
            ->assertOk()
            ->assertSee('must be an image', false);   // the page now shows this under the photo

        $this->assertNull($user->fresh()->avatar);
    }

    public function test_the_profile_picture_can_be_removed(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $this->actingAs($user)->patch(route('profile.avatar.update'), ['avatar' => UploadedFile::fake()->image('a.jpg', 512, 512)]);
        $path = $user->fresh()->avatar;

        $this->actingAs($user)->delete(route('profile.avatar.destroy'))->assertSessionHas('status', 'Profile picture removed.');

        $this->assertNull($user->fresh()->avatar);
        Storage::disk('public')->assertMissing($path);
    }
}
