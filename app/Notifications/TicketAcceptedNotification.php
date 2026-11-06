<?php

namespace App\Notifications;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Tells the requester that someone from IT Support picked up their ticket.
 */
class TicketAcceptedNotification extends AppNotification
{
    public function __construct(
        public Ticket $ticket,
        public User $acceptedBy,
    ) {}

    public function emailType(): string
    {
        return 'ticket_updates';
    }

    public function toMail(object $notifiable): MailMessage
    {
        return $this->brandedMail($notifiable, "{$this->ticket->ticket_number} is being worked on", [
            'heading' => 'Your ticket is being worked on',
            'preheader' => "{$this->acceptedBy->name} picked up {$this->ticket->ticket_number}.",
            'paragraphs' => ["Good news — **{$this->acceptedBy->name}** from IT Support has picked up your ticket."],
            'ticket' => $this->ticketCard($this->ticket),
            'ctaLabel' => 'View my ticket',
            'ctaUrl' => route('tickets.show', $this->ticket),
            'outro' => 'Have more details? Message them directly from the ticket page.',
        ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Your ticket was accepted',
            'message' => "{$this->acceptedBy->name} is now handling ticket {$this->ticket->ticket_number}: {$this->ticket->title}",
            'ticket_id' => $this->ticket->id,
            'url' => route('tickets.show', $this->ticket),
        ];
    }
}
