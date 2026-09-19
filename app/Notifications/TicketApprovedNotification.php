<?php

namespace App\Notifications;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketApprovedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Ticket $ticket,
        public User $approvedBy,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Ticket {$this->ticket->ticket_number} was approved — ready to close")
            ->greeting("Hi {$notifiable->name},")
            ->line("{$this->approvedBy->name} confirmed that the issue is fixed.")
            ->line("**{$this->ticket->ticket_number}: {$this->ticket->title}**")
            ->action('Close Ticket', route('tickets.show', $this->ticket))
            ->line('You can now close this ticket and add the final solution.');
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
