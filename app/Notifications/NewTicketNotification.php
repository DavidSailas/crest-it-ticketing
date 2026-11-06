<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Str;

/**
 * Alerts IT Support (and admins who opted in) that a new ticket is waiting.
 */
class NewTicketNotification extends AppNotification
{
    public function __construct(public Ticket $ticket) {}

    public function emailType(): string
    {
        return 'new_tickets';
    }

    // Admins don't get these unless they switched them on, so the bell stays quiet for them too.
    public function via(object $notifiable): array
    {
        if (! $notifiable->wantsAlert('new_tickets')) {
            return [];
        }

        return parent::via($notifiable);
    }

    private function isUrgent(): bool
    {
        return in_array($this->ticket->priority, ['critical', 'high'], true);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->ticket->loadMissing('creator');
        $by = $this->ticket->creator?->name ?? 'A user';
        $urgent = $this->ticket->priority === 'critical' ? 'URGENT · ' : '';

        return $this->brandedMail($notifiable, "{$urgent}New ticket {$this->ticket->ticket_number}: {$this->ticket->title}", [
            'heading' => $this->ticket->priority === 'critical' ? 'New CRITICAL ticket' : 'New ticket waiting',
            'preheader' => "{$by} submitted {$this->ticket->ticket_number}: {$this->ticket->title}",
            'paragraphs' => ["**{$by}** submitted a new ticket".($this->ticket->location ? " from {$this->ticket->location}." : '.')],
            'ticket' => $this->ticketCard($this->ticket),
            'quote' => Str::limit((string) $this->ticket->description, 400),
            'quoteBy' => 'What they wrote',
            'ctaLabel' => 'Open the ticket',
            'ctaUrl' => route('tickets.show', $this->ticket),
            'outro' => 'Open it and press Accept to take it.',
            'accent' => $this->isUrgent() ? '#dc2626' : '#1a6b3c',
        ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->ticket->priority === 'critical' ? 'New CRITICAL ticket' : 'New ticket submitted',
            'message' => "{$this->ticket->ticket_number}: {$this->ticket->title}".($this->ticket->creator ? " — {$this->ticket->creator->name}" : ''),
            'ticket_id' => $this->ticket->id,
            'url' => route('tickets.show', $this->ticket),
        ];
    }
}
