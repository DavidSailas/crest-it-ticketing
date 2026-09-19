<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Request;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'description',
        'properties',
        'ip_address',
    ];

    protected $casts = [
        'properties' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Quick helper to write a log entry.
     *
     * Usage: ActivityLog::record('ticket_created', 'Submitted ticket #123', ['ticket_id' => $ticket->id]);
     * Defaults to the currently authenticated user, but a user id can be passed explicitly
     * (useful for login events where you have the user object from the auth event).
     */
    public static function record(string $action, string $description, array $properties = [], ?int $userId = null): self
    {
        return static::create([
            'user_id' => $userId ?? auth()->id(),
            'action' => $action,
            'description' => $description,
            'properties' => $properties,
            'ip_address' => Request::ip(),
        ]);
    }

    /**
     * Icon name (for the profile activity feed) based on the action type.
     */
    public function getIconAttribute(): string
    {
        return match (true) {
            str_starts_with($this->action, 'login') => 'login',
            str_starts_with($this->action, 'logout') => 'logout',
            str_starts_with($this->action, 'ticket_created') => 'plus',
            str_starts_with($this->action, 'ticket_accepted') => 'check',
            str_starts_with($this->action, 'ticket_approved') => 'check',
            str_starts_with($this->action, 'ticket_status') => 'refresh',
            str_starts_with($this->action, 'ticket_comment') => 'chat',
            default => 'dot',
        };
    }
}
