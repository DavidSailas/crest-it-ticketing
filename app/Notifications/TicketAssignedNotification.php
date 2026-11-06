<?php

namespace App\Notifications;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Tells an IT agent that a ticket was assigned to them.
 */
class TicketAssignedNotification extends AppNotification
{
    public function __construct(
        public Ticket $ticket,
        public User $assignedBy,
    ) {}

    public function emailType(): string
    {
        return 'work_alerts';
    }

    public function toMail(object $notifiable): MailMessage
    {
        return $this->brandedMail($notifiable, "{$this->ticket->ticket_number} was assigned to you", [
            'heading' => 'A ticket was assigned to you',
            'preheader' => "{$this->assignedBy->name} assigned you {$this->ticket->ticket_number}.",
            'paragraphs' => ["**{$this->assignedBy->name}** assigned you a support ticket."],
            'ticket' => $this->ticketCard($this->ticket),
            'ctaLabel' => 'Open the ticket',
            'ctaUrl' => route('tickets.show', $this->ticket),
            'outro' => 'Please take a look at your earliest convenience.',
        ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Ticket assigned to you',
            'message' => "{$this->assignedBy->name} assigned you ticket {$this->ticket->ticket_number}: {$this->ticket->title}",
            'ticket_id' => $this->ticket->id,
            'url' => route('tickets.show', $this->ticket),
        ];
    }
}
