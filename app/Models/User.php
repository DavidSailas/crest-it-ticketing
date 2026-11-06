<?php

namespace App\Models;

use App\Notifications\ConfirmEmailNotification;
use App\Support\Notifier;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'first_name',
        'middle_name',
        'last_name',
        'name',
        'username',
        'password',
        'role',
        'is_department_head',
        'approver_id',
        'email_preferences',
        'department_id',
        'position_id',
        'location',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_department_head' => 'boolean',
            'email_preferences' => 'array',
            'suspended_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }

    protected static function booted()
    {
        // 'name' (the combined full name) is what the rest of the app
        // already reads everywhere — greetings, ticket/asset listings,
        // exports. Rather than rewriting every one of those call sites to
        // use first_name/last_name separately, we keep 'name' in sync
        // automatically whenever either part changes.
        static::saving(function (User $user) {
            if ($user->isDirty(['first_name', 'middle_name', 'last_name']) || blank($user->name)) {
                $middlePart = $user->middle_initial ? $user->middle_initial.' ' : '';
                $user->name = trim("{$user->first_name} {$middlePart}{$user->last_name}");
            }
        });

        // A new or changed address is unconfirmed until the person clicks the link we email them.
        static::saving(function (User $user) {
            if ($user->exists && $user->isDirty('email') && ! $user->isDirty('email_verified_at')) {
                $user->email_verified_at = null;
            }
        });

        static::saved(function (User $user) {
            // Seeders, tinker and tests shouldn't send real emails.
            if (app()->runningInConsole() || blank($user->email) || $user->hasVerifiedEmail()) {
                return;
            }

            if ($user->wasRecentlyCreated || $user->wasChanged('email')) {
                Notifier::send($user, new ConfirmEmailNotification($user));
            }
        });
    }

    /** Has an address on the account AND the person confirmed it (clicked the link we emailed). */
    public function hasVerifiedEmail(): bool
    {
        return filled($this->email) && $this->email_verified_at !== null;
    }

    /** A guess for the Email addresses admin page: username@company-domain. Never used automatically. */
    public function suggestedEmail(): ?string
    {
        $local = strtolower(trim((string) $this->username));
        $domain = config('company.email_domain');

        return ($local !== '' && filled($domain) && preg_match('/^[a-z0-9._-]+$/', $local))
            ? "{$local}@{$domain}"
            : null;
    }

    /**
     * Middle name shortened to a single initial with a period, e.g. a stored
     * middle name of "Villondo" is shown as "V.". Null when there is none.
     */
    public function getMiddleInitialAttribute(): ?string
    {
        $middle = trim((string) $this->middle_name);

        return $middle !== '' ? mb_strtoupper(mb_substr($middle, 0, 1)).'.' : null;
    }

    /**
     * Public URL of the uploaded profile photo, or null when none was uploaded
     * (callers then fall back to the initials circle).
     */
    public function getAvatarUrlAttribute(): ?string
    {
        return $this->avatar ? Storage::url($this->avatar) : null;
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class); // tickets this user created
    }

    public function assignedTickets()
    {
        return $this->hasMany(Ticket::class, 'assigned_to'); // tickets this IT staff is handling
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function position()
    {
        return $this->belongsTo(Position::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isItSupport(): bool
    {
        return $this->role === 'it_support';
    }

    /**
     * Heads, managers and junior managers — whoever an admin gave the "can approve" right —
     * approve supply requests, and so can admins. The right is stored in is_department_head.
     */
    public function canApproveSupplyRequests(): bool
    {
        return $this->isAdmin() || ($this->is_department_head && ! $this->isSuspended());
    }

    /**
     * The emails a person can switch on or off (Profile → Email notifications). The bell inside the
     * system always works; these only control email. `for` limits a switch to the roles that get that kind of email.
     */
    public const EMAIL_TYPES = [
        'ticket_updates' => [
            'label' => 'Updates on my tickets',
            'hint' => 'We received it, IT picked it up, status changes (resolved, closed…), edits and cancellations.',
            'for' => ['staff', 'it_support', 'admin'],
        ],
        'comments' => [
            'label' => 'New messages on a ticket',
            'hint' => 'When someone replies on a ticket you are part of. Quick back-and-forth is combined into one email, and nothing is sent while you are online.',
            'for' => ['staff', 'it_support', 'admin'],
        ],
        'supply_requests' => [
            'label' => 'Supply requests',
            'hint' => 'Requests that need your approval, and whether yours was approved or declined.',
            'for' => ['staff', 'it_support', 'admin'],
        ],
        'work_alerts' => [
            'label' => 'Work assigned to me',
            'hint' => 'Tickets assigned to you, a teammate joining your ticket, and requesters confirming a fix.',
            'for' => ['it_support', 'admin'],
        ],
        'new_tickets' => [
            'label' => 'New tickets submitted',
            'hint' => 'An alert whenever a new ticket arrives, so nothing waits unnoticed.',
            'for' => ['it_support', 'admin'],
        ],
    ];

    /** The email switches that apply to this person's role. */
    public function emailTypes(): array
    {
        return array_filter(self::EMAIL_TYPES, fn ($type) => in_array($this->role, $type['for'], true));
    }

    /** Do they want this kind of notification at all? (Default on — except new-ticket alerts, which default on for IT Support only.) */
    public function wantsAlert(string $type): bool
    {
        $preferences = $this->email_preferences ?? [];

        if (array_key_exists($type, $preferences)) {
            return (bool) $preferences[$type];
        }

        return $type !== 'new_tickets' || $this->isItSupport();
    }

    /** Should an email go out? Needs a CONFIRMED address on the account and the switch on. */
    public function wantsEmail(string $type): bool
    {
        return $this->hasVerifiedEmail() && $this->wantsAlert($type);
    }

    /** IT Support and admins who are active — who hears about new tickets. */
    public static function itTeam()
    {
        return static::query()->whereIn('role', ['it_support', 'admin'])->whereNull('suspended_at')->get();
    }

    /** How many supply requests are waiting for this person's decision (0 unless they can approve). */
    public function supplyApprovalsWaiting(): int
    {
        // once(): the menu and the dashboard card both ask — count only one time per page load.
        return once(fn () => $this->canApproveSupplyRequests()
            ? SupplyRequest::awaitingFor($this)->count()
            : 0);
    }

    /** The person an admin picked to approve this employee's supply requests. */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    /** People who can be chosen as someone's approver (for the Users form). */
    public static function approverOptions()
    {
        return static::query()
            ->where('is_department_head', true)
            ->whereNull('suspended_at')
            ->with('department:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'location', 'department_id']);
    }

    /**
     * Who gets this person's supply requests:
     *   1. the approver an admin picked for them, if that person can still approve;
     *   2. otherwise approvers in the same department and branch;
     *   3. otherwise approvers in the same department at any branch;
     *   4. otherwise the admins — so a request is never left without anyone to see it.
     */
    public static function approversFor(User $requester)
    {
        $heads = fn () => static::query()
            ->where('is_department_head', true)
            ->whereNull('suspended_at')
            ->where('id', '!=', $requester->id);

        if ($requester->approver_id) {
            $assigned = $heads()->whereKey($requester->approver_id)->get();
            if ($assigned->isNotEmpty()) {
                return $assigned;
            }
        }

        $sameBranch = $heads()
            ->where('department_id', $requester->department_id)
            ->where('location', $requester->location)
            ->get();
        if ($sameBranch->isNotEmpty()) {
            return $sameBranch;
        }

        $sameDepartment = $heads()->where('department_id', $requester->department_id)->get();
        if ($sameDepartment->isNotEmpty()) {
            return $sameDepartment;
        }

        return static::query()->where('role', 'admin')->whereNull('suspended_at')->where('id', '!=', $requester->id)->get();
    }

    /** Is this person one of the people who may approve a request made by $requester? (Admins always may.) */
    public function isApproverFor(User $requester): bool
    {
        if ($this->id === $requester->id) {
            return false;
        }

        if ($this->isAdmin()) {
            return true;
        }

        return $this->canApproveSupplyRequests()
            && static::approversFor($requester)->contains('id', $this->id);
    }

    /**
     * Does this person's own supply request need someone's approval? Admins, and heads who
     * have no approver of their own, don't; everyone else does.
     */
    public function requiresSupplyApproval(): bool
    {
        if ($this->isAdmin()) {
            return false;
        }

        if ($this->approver_id && $this->approver_id !== $this->id && $this->approver?->canApproveSupplyRequests()) {
            return true;
        }

        return ! $this->canApproveSupplyRequests();
    }

    public function isStaff(): bool
    {
        return $this->role === 'staff';
    }

    /**
     * Suspended accounts are blocked from logging in and are signed out
     * immediately if already logged in (see EnsureAccountIsActive). Only an
     * admin can suspend/reactivate an account — see Admin\UserController.
     */
    public function isSuspended(): bool
    {
        return $this->suspended_at !== null;
    }

    /**
     * "Online" = made a request in the last 2 minutes (see TrackLastSeen).
     */
    public function isOnline(): bool
    {
        return $this->last_seen_at !== null && $this->last_seen_at->gt(now()->subMinutes(2));
    }

    /**
     * Full branch name for display (e.g. "Cebu") from the stored short code
     * (e.g. "CEB"). Reuses Branch::options() as the single source of truth
     * for branch names across users and assets.
     */
    public function getBranchNameAttribute(): ?string
    {
        return Branch::nameForCode($this->location);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'location', 'code');
    }
}
