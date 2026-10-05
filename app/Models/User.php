<?php

namespace App\Models;

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
        'email',
        'password',
        'role',
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
