<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketStatusUpdatedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Ticket $ticket,
        public string $oldStatus,
        public string $newStatus,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    private function label(string $status): string
    {
        return str_replace('_', ' ', ucfirst($status));
    }

    public function toMail(object $notifiable): MailMessage
    {
        $statusLabel = $this->label($this->newStatus);

        return (new MailMessage)
            ->subject("Ticket {$this->ticket->ticket_number} is now {$statusLabel}")
            ->greeting("Hi {$notifiable->name},")
            ->line("Your ticket status has changed to **{$statusLabel}**.")
            ->line("**{$this->ticket->ticket_number}: {$this->ticket->title}**")
            ->action('View Ticket', route('tickets.show', $this->ticket))
            ->line($this->newStatus === 'resolved'
                ? 'Please open the ticket and click "Approve" if the issue is fixed so IT can close it. If it is not fixed, reply on the ticket and IT will reopen it.'
                : 'Let us know if you need anything else.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => "Ticket status: {$this->label($this->newStatus)}",
            'message' => "Ticket {$this->ticket->ticket_number} changed from {$this->label($this->oldStatus)} to {$this->label($this->newStatus)}",
            'ticket_id' => $this->ticket->id,
            'url' => route('tickets.show', $this->ticket),
        ];
    }
}
