<?php

namespace App\Notifications;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Tells the IT agent that the requester confirmed the fix, so the ticket can be closed.
 */
class TicketApprovedNotification extends AppNotification
{
    public function __construct(
        public Ticket $ticket,
        public User $approvedBy,
    ) {}

    public function emailType(): string
    {
        return 'work_alerts';
    }

    public function toMail(object $notifiable): MailMessage
    {
        return $this->brandedMail($notifiable, "{$this->ticket->ticket_number} confirmed — ready to close", [
            'heading' => 'The requester confirmed the fix',
            'preheader' => "{$this->approvedBy->name} approved {$this->ticket->ticket_number}. You can close it now.",
            'paragraphs' => ["**{$this->approvedBy->name}** confirmed that the issue is fixed."],
            'ticket' => $this->ticketCard($this->ticket),
            'ctaLabel' => 'Close the ticket',
            'ctaUrl' => route('tickets.show', $this->ticket),
            'outro' => 'Add the final solution when you close it, so it is on record for next time.',
            'accent' => '#15803d',
        ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Ticket approved — ready to close',
            'message' => "{$this->approvedBy->name} approved the resolution of ticket {$this->ticket->ticket_number}: {$this->ticket->title}",
            'ticket_id' => $this->ticket->id,
            'url' => route('tickets.show', $this->ticket),
        ];
    }
}
