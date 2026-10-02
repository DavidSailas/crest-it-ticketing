<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to the requester when someone else (IT Support / Admin) cancels
 * their ticket. Requesters cancelling their own ticket aren't notified.
 */
class TicketCancelledNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Ticket $ticket,
        public string $cancelledBy,
        public ?string $reason = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject("Ticket {$this->ticket->ticket_number} was cancelled")
            ->greeting("Hi {$notifiable->name},")
            ->line("Your ticket was cancelled by {$this->cancelledBy}.")
            ->line("**{$this->ticket->ticket_number}: {$this->ticket->title}**");

        if ($this->reason) {
            $mail->line("Reason: {$this->reason}");
        }

        return $mail
            ->action('View Ticket', route('tickets.show', $this->ticket))
            ->line('If you still need help, please submit a new ticket.');
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
