<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * The "we got it" receipt the requester gets right after submitting a ticket.
 */
class TicketReceivedNotification extends AppNotification
{
    public function __construct(public Ticket $ticket) {}

    public function emailType(): string
    {
        return 'ticket_updates';
    }

    // The requester just saw the confirmation page, so a bell entry would only be noise — email only.
    public function via(object $notifiable): array
    {
        return $notifiable->wantsEmail($this->emailType()) ? ['mail'] : [];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return $this->brandedMail($notifiable, "We received your request · {$this->ticket->ticket_number}", [
            'heading' => 'We received your request',
            'preheader' => "Your ticket {$this->ticket->ticket_number} was submitted. IT Support has been notified.",
            'paragraphs' => [
                'Thank you — your request was logged and **IT Support has been notified**.',
            ],
            'ticket' => $this->ticketCard($this->ticket),
            'ctaLabel' => 'Follow my ticket',
            'ctaUrl' => route('tickets.show', $this->ticket),
            'outro' => "You'll get an email when someone picks it up and when it is resolved. You can add details or reply from the ticket page at any time.",
        ]);
    }
}
