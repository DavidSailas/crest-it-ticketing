<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent when IT Support / an Admin corrects a ticket's details (category, priority
 * or status) — e.g. the requester picked the wrong category by mistake.
 *
 * $changes looks like: ['Category' => ['Printer', 'Other'], 'Priority' => ['P2 · High', 'P4 · Low']]
 */
class TicketUpdatedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Ticket $ticket,
        public array $changes,
        public string $editedBy,
        public ?string $reason = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    private function summary(): string
    {
        return collect($this->changes)
            ->map(fn ($pair, $field) => "{$field}: {$pair[0]} → {$pair[1]}")
            ->implode('; ');
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject("Ticket {$this->ticket->ticket_number} was updated")
            ->greeting("Hi {$notifiable->name},")
            ->line("{$this->editedBy} corrected the details of **{$this->ticket->ticket_number}: {$this->ticket->title}**.");

        foreach ($this->changes as $field => $pair) {
            $mail->line("**{$field}:** {$pair[0]} → {$pair[1]}");
        }

        if ($this->reason) {
            $mail->line("**Reason:** {$this->reason}");
        }

        return $mail
            ->action('View Ticket', route('tickets.show', $this->ticket))
            ->line('If something looks wrong, reply on the ticket and IT will take a look.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Ticket details updated',
            'message' => "{$this->ticket->ticket_number} — {$this->summary()}".($this->reason ? " (Reason: {$this->reason})" : ''),
            'ticket_id' => $this->ticket->id,
            'url' => route('tickets.show', $this->ticket),
        ];
    }
}
