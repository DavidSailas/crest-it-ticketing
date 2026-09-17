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
        'name',
        'email',
        'password',
        'role',
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

    public function tickets()
    {
        return $this->hasMany(Ticket::class); // tickets this user created
    }

    public function assignedTickets()
    {
        return $this->hasMany(Ticket::class, 'assigned_to'); // tickets this IT staff is handling
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
     * (e.g. "CEB"). Reuses Asset::LOCATIONS as the single source of truth
     * for branch names across users and assets.
     */
    public function getBranchNameAttribute(): ?string
    {
        return $this->location ? (Asset::LOCATIONS[$this->location] ?? $this->location) : null;
    }
}
