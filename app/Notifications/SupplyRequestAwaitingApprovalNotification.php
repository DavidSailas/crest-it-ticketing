<?php

namespace App\Notifications;

use App\Models\SupplyRequest;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Sent to the employee's approver (their head or manager) when they ask for a supply item.
 */
class SupplyRequestAwaitingApprovalNotification extends AppNotification
{
    public function __construct(public SupplyRequest $supplyRequest) {}

    public function emailType(): string
    {
        return 'supply_requests';
    }

    private function summary(): string
    {
        $r = $this->supplyRequest;

        return "{$r->requester?->name} ({$r->requester?->branch_name}) asked for {$r->quantity} {$r->supply->unit} × {$r->supply->name} — {$r->purpose}";
    }

    /** Opens the approvals tab with this very request highlighted. */
    private function url(): string
    {
        return route('supplies.requests', ['show' => 'approval', 'focus' => $this->supplyRequest->id]);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $r = $this->supplyRequest;

        return $this->brandedMail($notifiable, 'A supply request needs your approval', [
            'heading' => 'A supply request needs your approval',
            'preheader' => $this->summary(),
            'paragraphs' => ["**{$r->requester?->name}** is asking for an IT supply item and needs your approval first."],
            'details' => array_filter([
                'Item' => $r->supply->name,
                'Quantity' => "{$r->quantity} {$r->supply->unit}",
                'Needed for' => $r->purpose,
                'Requested by' => trim("{$r->requester?->name} · {$r->requester?->branch_name}", ' ·'),
                'Urgency' => $r->urgency === 'urgent' ? 'Urgent' : null,
            ]),
            'ctaLabel' => 'Review & approve',
            'ctaUrl' => $this->url(),
            'outro' => 'Approve it and a ticket is created for IT Support. Decline it and the requester is told why.',
            'accent' => '#d97706',
        ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Supply request needs approval',
            'message' => $this->summary(),
            'url' => $this->url(),
        ];
    }
}
