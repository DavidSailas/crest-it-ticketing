<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Tells the requester that somebody else cancelled their ticket.
 */
class TicketCancelledNotification extends AppNotification
{
    public function __construct(
        public Ticket $ticket,
        public string $cancelledBy,
        public ?string $reason = null,
    ) {}

    public function emailType(): string
    {
        return 'ticket_updates';
    }

    public function toMail(object $notifiable): MailMessage
    {
        return $this->brandedMail($notifiable, "{$this->ticket->ticket_number} was cancelled", [
            'heading' => 'Your ticket was cancelled',
            'preheader' => "{$this->ticket->ticket_number} was cancelled by {$this->cancelledBy}.",
            'paragraphs' => ["Your ticket was cancelled by **{$this->cancelledBy}**."],
            'ticket' => $this->ticketCard($this->ticket),
            'details' => $this->reason ? ['Reason' => $this->reason] : [],
            'ctaLabel' => 'View the ticket',
            'ctaUrl' => route('tickets.show', $this->ticket),
            'outro' => 'If you still need help, please submit a new ticket.',
            'accent' => '#6b7280',
        ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Ticket cancelled',
            'message' => "Ticket {$this->ticket->ticket_number} was cancelled by {$this->cancelledBy}".($this->reason ? " — {$this->reason}" : ''),
            'ticket_id' => $this->ticket->id,
            'url' => route('tickets.show', $this->ticket),
        ];
    }
}
