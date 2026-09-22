<?php

namespace App\Notifications;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketAssistJoinedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Ticket $ticket,
        public User $assistant,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("{$this->assistant->name} is helping on ticket {$this->ticket->ticket_number}")
            ->greeting("Hi {$notifiable->name},")
            ->line("{$this->assistant->name} joined in to help you with a ticket you're handling.")
            ->line("**{$this->ticket->ticket_number}: {$this->ticket->title}**")
            ->action('View Ticket', route('tickets.show', $this->ticket))
            ->line("You're still the owner — they're just lending a hand.");
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
