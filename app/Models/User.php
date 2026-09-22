<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'first_name',
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
            if ($user->isDirty(['first_name', 'last_name']) || blank($user->name)) {
                $user->name = trim("{$user->first_name} {$user->last_name}");
            }
        });
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class); // tickets this user created
    }

    public function assignedTickets()
    {
        return $this->hasMany(Ticket::class, 'assigned_to'); // tickets this IT staff is handling
    }

    public function assistingTickets()
    {
        return $this->belongsToMany(Ticket::class, 'ticket_assistants')->withTimestamps(); // tickets this IT staff is helping with, owned by someone else
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
