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
        'solution',
        'closed_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
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
