<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketRead extends Model
{
    public $timestamps = false;

    protected $fillable = ['ticket_id', 'user_id', 'last_read_at'];

    protected $casts = [
        'last_read_at' => 'datetime',
    ];

    /**
     * Mark a ticket's chat as read by a given user right now. Used whenever
     * that user views the ticket page or actively polls its chat thread.
     */
    public static function markRead(int $ticketId, int $userId): void
    {
        static::updateOrCreate(
            ['ticket_id' => $ticketId, 'user_id' => $userId],
            ['last_read_at' => now()]
        );
    }
}
