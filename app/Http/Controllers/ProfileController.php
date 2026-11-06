<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
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
     * Where (and which) notification emails the person wants.
     */
    public function updateNotifications(Request $request): RedirectResponse
    {
        $user = $request->user();

        $request->validateWithBag('notifications', [
            'email' => ['nullable', 'email:rfc', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
        ], [
            'email.email' => 'Please enter a valid email address, for example name@company.com.',
            'email.max' => 'That email address is too long.',
            'email.unique' => 'That email address is already used by another account.',
        ]);

        // Only the switches this person's role can see are touched; unchecked boxes are simply absent from the form.
        $preferences = $user->email_preferences ?? [];
        foreach (array_keys($user->emailTypes()) as $type) {
            $preferences[$type] = $request->boolean("email_types.{$type}");
        }

        $user->forceFill([
            'email' => $request->filled('email') ? mb_strtolower(trim((string) $request->input('email'))) : null,
            'email_preferences' => $preferences,
        ])->save();

        ActivityLog::record('profile_updated', 'Updated email notification settings');

        // A new or changed address was just emailed a confirmation link (see User::booted).
        $needsConfirmation = $user->wasChanged('email') && filled($user->email) && ! $user->hasVerifiedEmail();

        return Redirect::to(route('profile.edit').'#email-notifications')
            ->with('status', $needsConfirmation ? 'email-confirmation-sent' : 'notifications-updated');
    }

    /**
     * Send the confirmation link again.
     */
    public function resendEmailConfirmation(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (blank($user->email) || $user->hasVerifiedEmail()) {
            return back();
        }

        \App\Support\Notifier::send($user, new \App\Notifications\ConfirmEmailNotification($user));

        return Redirect::to(route('profile.edit').'#email-notifications')->with('status', 'email-confirmation-sent');
    }

    /**
     * Update the user's profile information.
     */
    public function update(Request $request): RedirectResponse
    {
        $isAdmin = $request->user()->role === 'admin';

        $rules = [
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
        ];

        // Only admins can change a username (everyone else asks the IT desk).
        if ($isAdmin) {
            $rules['username'] = ['required', 'string', 'max:255', 'alpha_dash', 'unique:users,username,' . $request->user()->id];
        }

        $validated = $request->validate($rules, [
            'username.required' => 'Please enter a username.',
            'username.alpha_dash' => 'Username can only contain letters, numbers, dashes, and underscores.',
            'username.unique' => 'That username is already taken.',
            'first_name.required' => 'Please enter your first name.',
            'first_name.max' => 'First name is too long — please keep it under 255 characters.',
            'middle_name.max' => 'Middle name is too long — please keep it under 255 characters.',
            'last_name.required' => 'Please enter your last name.',
            'last_name.max' => 'Last name is too long — please keep it under 255 characters.',
        ]);

        // The full name is stored as typed in first_name / middle_name /
        // last_name. The combined "name" column (what the rest of the app
        // displays) is rebuilt automatically by the User model, which turns
        // the middle name into an initial — e.g. "Villondo" shows as "V.".
        $validated['first_name'] = trim($validated['first_name']);
        $validated['middle_name'] = filled($validated['middle_name'] ?? null) ? trim($validated['middle_name']) : null;
        $validated['last_name'] = trim($validated['last_name']);

        $request->user()->fill($validated);

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
