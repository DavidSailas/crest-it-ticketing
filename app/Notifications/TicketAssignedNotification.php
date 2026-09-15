<?php

namespace App\Notifications;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketAssignedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Ticket $ticket,
        public User $assignedBy,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Ticket {$this->ticket->ticket_number} assigned to you")
            ->greeting("Hi {$notifiable->name},")
            ->line("{$this->assignedBy->name} assigned you a support ticket.")
            ->line("**{$this->ticket->ticket_number}: {$this->ticket->title}**")
            ->line("Priority: ".ucfirst($this->ticket->priority))
            ->line("Department: {$this->ticket->department}")
            ->action('View Ticket', route('tickets.show', $this->ticket))
            ->line('Please take a look at your earliest convenience.');
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
