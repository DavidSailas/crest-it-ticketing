<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use App\Notifications\ConfirmEmailNotification;
use App\Support\Notifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Admin > Users > Email addresses: see who has no email (with a suggested one to review),
 * who still has to confirm theirs, and fill many in at once.
 *
 * An address an admin saves here is trusted straight away — no confirmation link — because the admin is
 * accountable for it. Only an address a person adds or changes themselves (Profile) must be confirmed.
 * Saving a row that is still "waiting" confirms it on the person's behalf.
 */
class UserEmailController extends Controller
{
    private function base()
    {
        return User::query()->whereNull('suspended_at');
    }

    public function index(Request $request)
    {
        $missing = fn ($q) => $q->whereNull('email')->orWhere('email', '');

        $counts = [
            'missing' => $this->base()->where($missing)->count(),
            'pending' => $this->base()->whereNotNull('email')->where('email', '!=', '')->whereNull('email_verified_at')->count(),
            'confirmed' => $this->base()->whereNotNull('email')->where('email', '!=', '')->whereNotNull('email_verified_at')->count(),
        ];
        $counts['all'] = array_sum($counts);

        // Land on the tab that needs work.
        $default = $counts['missing'] > 0 ? 'missing' : ($counts['pending'] > 0 ? 'pending' : 'all');
        $filter = (string) $request->query('show', $default);
        if (! array_key_exists($filter, $counts)) {
            $filter = $default;
        }

        $users = $this->base()
            ->with('department:id,name')
            ->when($filter === 'missing', fn ($q) => $q->where($missing))
            ->when($filter === 'pending', fn ($q) => $q->whereNotNull('email')->where('email', '!=', '')->whereNull('email_verified_at'))
            ->when($filter === 'confirmed', fn ($q) => $q->whereNotNull('email')->where('email', '!=', '')->whereNotNull('email_verified_at'))
            ->when(filled($request->query('search')), function ($q) use ($request) {
                $like = '%'.addcslashes(trim((string) $request->query('search')), '%_\\').'%';
                $q->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('username', 'like', $like)->orWhere('email', 'like', $like));
            })
            ->orderBy('name')
            ->paginate(30)
            ->withQueryString();

        return view('admin.users.emails', compact('users', 'counts', 'filter'));
    }

    public function update(Request $request)
    {
        $emails = (array) $request->input('emails', []);
        $selected = array_keys((array) $request->input('save', []));

        if ($selected === []) {
            return back()->with('error', 'Tick the people you want to save first.');
        }

        $saved = 0;
        $confirmed = 0;
        $cleared = 0;
        $problems = [];
        $seen = [];

        foreach (User::query()->whereIn('id', $selected)->get() as $user) {
            $email = mb_strtolower(trim((string) ($emails[$user->id] ?? '')));

            if ($email === '') {
                if (filled($user->email)) {
                    $user->forceFill(['email' => null, 'email_verified_at' => null])->save();
                    $cleared++;
                }

                continue;
            }

            if ($email === mb_strtolower((string) $user->email)) {
                // Same address, but still waiting for the person to click their link: the admin vouches for it.
                if (! $user->hasVerifiedEmail()) {
                    $user->forceFill(['email_verified_at' => now()])->save();
                    $confirmed++;
                }

                continue;
            }

            $validator = Validator::make(['email' => $email], [
                'email' => ['email:rfc', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            ], [
                'email.email' => 'is not a valid email address',
                'email.unique' => 'is already used by another account',
                'email.max' => 'is too long',
            ]);

            if ($validator->fails()) {
                $problems[] = "{$user->name}: “{$email}” ".$validator->errors()->first('email');

                continue;
            }

            if (isset($seen[$email])) {
                $problems[] = "{$user->name}: “{$email}” is also entered for {$seen[$email]} — one address can't belong to two people";

                continue;
            }
            $seen[$email] = $user->name;

            // Trusted: marked confirmed in the same save, so no confirmation email goes out (see User::booted).
            $user->forceFill(['email' => $email, 'email_verified_at' => now()])->save();
            $saved++;
        }

        if ($saved || $confirmed || $cleared) {
            ActivityLog::record('users_email_updated', "Updated email addresses: {$saved} saved, {$confirmed} confirmed, {$cleared} removed");
        }

        $message = collect([
            $saved ? "{$saved} email ".($saved === 1 ? 'address' : 'addresses').' saved — ready to receive ticket updates.' : null,
            $confirmed ? "{$confirmed} confirmed for the person." : null,
            $cleared ? "{$cleared} removed." : null,
        ])->filter()->implode(' ');

        return back()
            ->with('status', $message !== '' ? $message : ($problems ? null : 'Nothing to save — no address was changed.'))
            ->with('email_problems', $problems);
    }

    /** Send the confirmation link again (e.g. it went to spam or expired). */
    public function resend(User $user)
    {
        if (blank($user->email) || $user->hasVerifiedEmail()) {
            return back()->with('error', "{$user->name} has nothing waiting for confirmation.");
        }

        Notifier::send($user, new ConfirmEmailNotification($user));

        return back()->with('status', "Confirmation link sent again to {$user->email}.");
    }
}
