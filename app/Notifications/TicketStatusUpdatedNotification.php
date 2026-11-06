<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Tells the requester that the status of their ticket changed (resolved, closed, pending…).
 */
class TicketStatusUpdatedNotification extends AppNotification
{
    public function __construct(
        public Ticket $ticket,
        public string $oldStatus,
        public string $newStatus,
    ) {}

    public function emailType(): string
    {
        return 'ticket_updates';
    }

    private function label(string $status): string
    {
        return Ticket::statusLabel($status);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $label = $this->label($this->newStatus);

        [$heading, $intro, $cta, $outro, $accent] = match ($this->newStatus) {
            'resolved' => [
                'Your ticket was resolved',
                'IT Support marked your ticket as **Resolved**. Please check that everything works for you.',
                'Check and approve',
                'If it is fixed, press **Approve** on the ticket so IT can close it. If it is not fixed, reply on the ticket and IT will reopen it.',
                '#15803d',
            ],
            'closed' => [
                'Your ticket was closed',
                'Your ticket is now **Closed**. Thank you for your patience.',
                'View the ticket',
                'Need more help later? Just submit a new ticket.',
                '#6b7280',
            ],
            'pending' => [
                'We need something from you',
                'Your ticket is now **Pending User** — IT Support is waiting for your reply or action.',
                'Reply on the ticket',
                'The sooner you reply, the sooner IT can continue.',
                '#6d28d9',
            ],
            'pending_backend' => [
                'Your ticket is with the backend team',
                'Your ticket is now **Pending Backend** — it needs a change on the server side (for example spam filtering, mail or hosting settings). You do not need to do anything.',
                'View the ticket',
                "We'll email you again as soon as it moves forward.",
                '#0f766e',
            ],
            'in_progress' => $this->oldStatus === 'pending_vendor' ? [
                'The vendor responded — we are back on your ticket',
                'The supplier or service provider got back to IT Support, so your ticket is **In Progress** again.',
                'View the ticket',
                "We'll keep you posted as soon as it is resolved.",
                '#4338ca',
            ] : [
                "Status changed to {$label}",
                "Your ticket status has changed to **{$label}**.",
                'View the ticket',
                'Let us know if you need anything else.',
                '#1a6b3c',
            ],
            'pending_vendor' => [
                'Your ticket is with the vendor',
                $this->ticket->vendor_name
                    ? "IT Support sent your ticket to **{$this->ticket->vendor_name}** (the supplier or service provider) for repair or service. You do not need to do anything."
                    : 'Your ticket is now **Awaiting Vendor** — IT Support is waiting for the supplier or service provider. You do not need to do anything.',
                'View the ticket',
                "We'll email you again as soon as the vendor responds and your ticket moves forward.",
                '#c2410c',
            ],
            default => [
                "Status changed to {$label}",
                "Your ticket status has changed to **{$label}**.",
                'View the ticket',
                'Let us know if you need anything else.',
                '#1a6b3c',
            ],
        };

        return $this->brandedMail($notifiable, "{$this->ticket->ticket_number} · {$label}", [
            'heading' => $heading,
            'preheader' => "{$this->ticket->ticket_number} is now {$label}.",
            'paragraphs' => [$intro],
            'ticket' => $this->ticketCard($this->ticket),
            'details' => $this->newStatus === 'pending_vendor' ? array_filter([
                'Vendor' => $this->ticket->vendor_name,
                'Case / RMA no.' => $this->ticket->vendor_reference,
                'Expected back' => $this->ticket->vendor_expected_at?->format('M j, Y'),
            ]) : [],
            'ctaLabel' => $cta,
            'ctaUrl' => route('tickets.show', $this->ticket),
            'outro' => $outro,
            'accent' => $accent,
        ]);
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
