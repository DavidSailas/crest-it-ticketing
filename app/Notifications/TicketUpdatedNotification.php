<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Tells the requester / agent that someone corrected the details of a ticket.
 */
class TicketUpdatedNotification extends AppNotification
{
    /** @param  array<string, array{0: mixed, 1: mixed}>  $changes  field => [old, new] */
    public function __construct(
        public Ticket $ticket,
        public array $changes,
        public string $editedBy,
        public ?string $reason = null,
    ) {}

    public function emailType(): string
    {
        return 'ticket_updates';
    }

    private function summary(): string
    {
        return collect($this->changes)
            ->map(fn ($pair, $field) => "{$field}: {$pair[0]} → {$pair[1]}")
            ->implode('; ');
    }

    public function toMail(object $notifiable): MailMessage
    {
        $details = collect($this->changes)
            ->mapWithKeys(fn ($pair, $field) => [(string) $field => "{$pair[0]} → {$pair[1]}"])
            ->all();

        if ($this->reason) {
            $details['Reason'] = $this->reason;
        }

        return $this->brandedMail($notifiable, "{$this->ticket->ticket_number} was updated", [
            'heading' => 'Ticket details were updated',
            'preheader' => "{$this->editedBy} corrected {$this->ticket->ticket_number}.",
            'paragraphs' => ["**{$this->editedBy}** corrected the details of this ticket."],
            'ticket' => $this->ticketCard($this->ticket),
            'details' => $details,
            'ctaLabel' => 'View the ticket',
            'ctaUrl' => route('tickets.show', $this->ticket),
            'outro' => 'If something looks wrong, reply on the ticket and IT will take a look.',
        ]);
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
