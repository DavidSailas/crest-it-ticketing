<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        $user = $request->user();

        $activityLogs = ActivityLog::forUser($user->id)
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('profile.edit', [
            'user' => $user,
            'activityLogs' => $activityLogs,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email,' . $request->user()->id],
        ], [
            'first_name.required' => 'Please enter your first name.',
            'first_name.max' => 'First name is too long — please keep it under 255 characters.',
            'middle_name.max' => 'Middle name is too long — please keep it under 255 characters.',
            'last_name.required' => 'Please enter your last name.',
            'last_name.max' => 'Last name is too long — please keep it under 255 characters.',
            'email.required' => 'Please enter your email address.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'That email is already in use by another account.',
        ]);

        // The full name is stored as typed in first_name / middle_name /
        // last_name. The combined "name" column (what the rest of the app
        // displays) is rebuilt automatically by the User model, which turns
        // the middle name into an initial — e.g. "Villondo" shows as "V.".
        $validated['first_name'] = trim($validated['first_name']);
        $validated['middle_name'] = filled($validated['middle_name'] ?? null) ? trim($validated['middle_name']) : null;
        $validated['last_name'] = trim($validated['last_name']);

        $request->user()->fill($validated);

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ], [
            'password.required' => 'Please enter your password to confirm account deletion.',
            'password.current_password' => 'That password doesn\'t match your account — please try again.',
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
