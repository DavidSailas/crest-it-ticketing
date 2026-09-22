<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'on_behalf_of_user_id',
        'on_behalf_of_name',
        'assigned_to',
        'department',
        'location',
        'title',
        'description',
        'attachment_path',
        'attachment_name',
        'category',
        'subcategory',
        'priority',
        'status',
        'resolved_at',
        'approved_at',
        'solution',
        'closed_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
        'approved_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    /**
     * The colleague this ticket was raised for, when somebody else had to
     * submit it on their behalf (e.g. their machine wouldn't boot).
     */
    public function onBehalfOf()
    {
        return $this->belongsTo(User::class, 'on_behalf_of_user_id');
    }

    /**
     * Who the ticket is actually about — the colleague if it was raised on
     * someone's behalf, otherwise the person who submitted it.
     */
    public function affectedPersonName(): string
    {
        if ($this->on_behalf_of_user_id) {
            return $this->onBehalfOf?->name ?? $this->on_behalf_of_name ?? $this->creator->name;
        }

        return $this->on_behalf_of_name ?? $this->creator->name;
    }

    public function isOnBehalf(): bool
    {
        return $this->on_behalf_of_user_id !== null || filled($this->on_behalf_of_name);
    }

    /**
     * Closed is a terminal state — status, assignment, and comments all
     * lock once a ticket gets here. Used throughout the ticket page and
     * controller instead of repeating the raw string comparison.
     */
    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    /**
     * The requester has confirmed that the resolved ticket is really fixed.
     */
    public function isApproved(): bool
    {
        return $this->approved_at !== null;
    }

    /**
     * IT Support marked it Resolved, but the requester hasn't approved yet.
     */
    public function isAwaitingApproval(): bool
    {
        return $this->status === 'resolved' && ! $this->isApproved();
    }

    /**
     * A ticket can only be closed once IT has marked it Resolved. The
     * requester's confirmation now happens off-platform (a call or message
     * from IT before closing), so it's a process step, not a system gate.
     */
    public function canBeClosed(): bool
    {
        return $this->status === 'resolved';
    }

    /**
     * Teammates who joined in to help without taking over ownership —
     * the ticket keeps its one owner (assigned_to), but anyone here can
     * comment and work it alongside them.
     */
    public function assistants()
    {
        return $this->belongsToMany(User::class, 'ticket_assistants')->withTimestamps();
    }

    public function isAssistedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $this->relationLoaded('assistants')
            ? $this->assistants->contains('id', $user->id)
            : $this->assistants()->where('user_id', $user->id)->exists();
    }

    /**
     * Whether $user could start assisting on this ticket right now: it has
     * to belong to someone else already, still be open work, and $user
     * can't already be helping.
     */
    public function canBeAssistedBy(User $user): bool
    {
        return $this->assigned_to
            && $this->assigned_to !== $user->id
            && ! $this->isClosed()
            && ! $this->isAssistedBy($user);
    }

    /**
     * Fingerprint of everything the ticket panel shows (status, owner,
     * approval, solution...). The live poll compares this with the one the
     * browser already has, and only sends new HTML when it differs.
     */
    public function liveHash(): string
    {
        $assistantIds = $this->relationLoaded('assistants')
            ? $this->assistants->pluck('id')->sort()->implode(',')
            : $this->assistants()->pluck('user_id')->sort()->implode(',');

        return md5(implode('|', [
            $this->status,
            $this->priority,
            $this->assigned_to,
            $this->resolved_at?->timestamp,
            $this->approved_at?->timestamp,
            $this->closed_at?->timestamp,
            $this->updated_at?->timestamp,
            $this->solution,
            $assistantIds,
        ]));
    }

    /**
     * Fingerprint of what decides the comment box: closed, assigned or not.
     */
    public function threadHash(): string
    {
        return $this->isClosed() ? 'closed' : ($this->assigned_to ? 'assigned' : 'unassigned');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function comments()
    {
        return $this->hasMany(TicketComment::class)->latest();
    }

    public function getAttachmentUrlAttribute(): ?string
    {
        return $this->attachment_path ? \Illuminate\Support\Facades\Storage::url($this->attachment_path) : null;
    }

    /**
     * A professional, branded ticket reference (e.g. "INC00001") used anywhere
     * the ticket ID is shown to a user, instead of the raw database "#1".
     */
    public function getTicketNumberAttribute(): string
    {
        return 'INC'.str_pad((string) $this->id, 5, '0', STR_PAD_LEFT);
    }
}
