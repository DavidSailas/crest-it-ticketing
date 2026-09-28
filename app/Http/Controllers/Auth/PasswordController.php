<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', 'different:current_password', Password::defaults()],
        ], [
            'current_password.required' => 'Enter your current password to confirm it\'s you.',
            'current_password.current_password' => 'That isn\'t your current password. Please try again.',
            'password.required' => 'Enter a new password.',
            'password.confirmed' => 'The two new passwords don\'t match.',
            'password.different' => 'Your new password must be different from your current one.',
            'password.min' => 'Your new password must be at least :min characters long.',
            'password.mixed' => 'Your new password needs both uppercase and lowercase letters.',
            'password.letters' => 'Your new password needs at least one letter.',
            'password.numbers' => 'Your new password needs at least one number.',
            'password.symbols' => 'Your new password needs at least one symbol (for example ! ? # $).',
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('status', 'password-updated');
    }
}
