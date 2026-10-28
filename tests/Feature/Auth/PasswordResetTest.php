<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Self-service password reset was removed: users sign in with a username and
 * ask the IT desk to reset a forgotten password (Admin > Users > Edit).
 */
class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_screen_is_not_available(): void
    {
        $this->get('/forgot-password')->assertNotFound();
        $this->post('/forgot-password', ['email' => 'someone@example.com'])->assertStatus(404);
    }

    public function test_reset_password_screen_is_not_available(): void
    {
        $this->get('/reset-password/some-token')->assertNotFound();
    }
}
