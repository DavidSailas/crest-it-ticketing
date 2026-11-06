<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;

/**
 * The page behind the "Confirm my email" button. The link is signed (can't be forged) and tied to
 * the address it was sent to, so it works even if the person isn't logged in on that device.
 */
class EmailConfirmationController extends Controller
{
    public function confirm(User $user, string $hash): RedirectResponse
    {
        $destination = fn (string $message) => auth()->check()
            ? redirect()->route('profile.edit')->with('status', 'email-confirmed')
            : redirect()->route('login')->with('status', $message);

        if (blank($user->email) || ! hash_equals(sha1((string) $user->email), $hash)) {
            return redirect()->route('login')->with('status', 'That confirmation link is no longer valid because the email address was changed. Please use the newest email we sent you.');
        }

        if (! $user->hasVerifiedEmail()) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        return $destination('Thank you — your email is confirmed. You will now get ticket updates by email.');
    }
}
