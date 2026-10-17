<?php

namespace App\Observers;

use App\Models\SupplyRequest;
use App\Models\Ticket;

/**
 * Keeps IT Supplies stock in step with tickets, without touching the ticket screens:
 * when a supply-request ticket is marked Resolved (or Closed), the requested quantity is
 * taken out of stock automatically. Cancelling a ticket needs no action here — a request
 * on a cancelled ticket simply stops reserving stock (see SupplyRequest::scopeActive).
 */
class TicketSupplyObserver
{
    public function updated(Ticket $ticket): void
    {
        if (! $ticket->wasChanged('status') || ! in_array($ticket->status, ['resolved', 'closed'], true)) {
            return;
        }

        SupplyRequest::query()
            ->where('ticket_id', $ticket->id)
            ->where('status', 'pending')
            ->first()
            ?->fulfil(auth()->id());
    }
}
