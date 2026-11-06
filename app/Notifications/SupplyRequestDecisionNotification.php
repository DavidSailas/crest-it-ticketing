<?php

namespace App\Notifications;

use App\Models\SupplyRequest;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Tells the requester what their approver decided.
 */
class SupplyRequestDecisionNotification extends AppNotification
{
    public function __construct(public SupplyRequest $supplyRequest, public User $decidedBy) {}

    public function emailType(): string
    {
        return 'supply_requests';
    }

    private function approved(): bool
    {
        return $this->supplyRequest->status !== 'declined';
    }

    private function item(): string
    {
        $r = $this->supplyRequest;

        return "{$r->quantity} {$r->supply->unit} × {$r->supply->name}";
    }

    private function url(): string
    {
        $r = $this->supplyRequest;

        return $this->approved() && $r->ticket
            ? route('tickets.show', $r->ticket)
            : route('supplies.requests', ['show' => 'all']);
    }

    public function toMail(object $notifiable): MailMessage
    {
        if ($this->approved()) {
            return $this->brandedMail($notifiable, 'Your supply request was approved', [
                'heading' => 'Your supply request was approved',
                'preheader' => "{$this->decidedBy->name} approved {$this->item()}.",
                'paragraphs' => [
                    "**{$this->decidedBy->name}** approved your request for {$this->item()}.",
                    "It is now ticket **{$this->supplyRequest->ticket?->ticket_number}** and IT Support will prepare it.",
                ],
                'ctaLabel' => 'Follow my request',
                'ctaUrl' => $this->url(),
                'accent' => '#15803d',
            ]);
        }

        return $this->brandedMail($notifiable, 'Your supply request was declined', [
            'heading' => 'Your supply request was declined',
            'preheader' => "{$this->decidedBy->name} declined {$this->item()}.",
            'paragraphs' => ["**{$this->decidedBy->name}** declined your request for {$this->item()}."],
            'details' => ['Reason' => (string) $this->supplyRequest->decline_reason],
            'ctaLabel' => 'View my requests',
            'ctaUrl' => $this->url(),
            'outro' => 'If you still need it, talk to your approver or send a new request.',
            'accent' => '#dc2626',
        ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->approved() ? 'Supply request approved' : 'Supply request declined',
            'message' => $this->approved()
                ? "{$this->decidedBy->name} approved {$this->item()}. Ticket {$this->supplyRequest->ticket?->ticket_number} was created for IT Support."
                : "{$this->decidedBy->name} declined {$this->item()}: {$this->supplyRequest->decline_reason}",
            'url' => $this->url(),
        ];
    }
}
