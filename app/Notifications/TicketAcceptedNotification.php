<?php

namespace App\Notifications;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketAcceptedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Ticket $ticket,
        public User $acceptedBy,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Your ticket {$this->ticket->ticket_number} is being worked on")
            ->greeting("Hi {$notifiable->name},")
            ->line("Good news — {$this->acceptedBy->name} from IT Support has picked up your ticket.")
            ->line("**{$this->ticket->ticket_number}: {$this->ticket->title}**")
            ->action('View Ticket', route('tickets.show', $this->ticket))
            ->line('You can message them directly from the ticket page if you have more details to share.');
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
