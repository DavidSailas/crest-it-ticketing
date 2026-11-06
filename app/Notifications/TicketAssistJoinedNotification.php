<?php

namespace App\Notifications;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Tells the agent handling a ticket that a teammate joined in to help.
 */
class TicketAssistJoinedNotification extends AppNotification
{
    public function __construct(
        public Ticket $ticket,
        public User $assistant,
    ) {}

    public function emailType(): string
    {
        return 'work_alerts';
    }

    public function toMail(object $notifiable): MailMessage
    {
        return $this->brandedMail($notifiable, "{$this->assistant->name} is helping on {$this->ticket->ticket_number}", [
            'heading' => 'A teammate is helping on your ticket',
            'preheader' => "{$this->assistant->name} joined {$this->ticket->ticket_number}.",
            'paragraphs' => ["**{$this->assistant->name}** joined in to help with a ticket you are handling."],
            'ticket' => $this->ticketCard($this->ticket),
            'ctaLabel' => 'Open the ticket',
            'ctaUrl' => route('tickets.show', $this->ticket),
            'outro' => "You're still the owner — they're just lending a hand.",
        ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'A teammate is helping on your ticket',
            'message' => "{$this->assistant->name} is now assisting on ticket {$this->ticket->ticket_number}: {$this->ticket->title}",
            'ticket_id' => $this->ticket->id,
            'url' => route('tickets.show', $this->ticket),
        ];
    }
}
