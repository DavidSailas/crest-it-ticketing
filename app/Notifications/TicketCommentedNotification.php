<?php

namespace App\Notifications;

use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Str;

/**
 * A new message on a ticket. Email only — the chat already shows its own unread badge — and
 * App\Support\Notifier::commentPosted() makes sure quick back-and-forth never floods anyone.
 */
class TicketCommentedNotification extends AppNotification
{
    public function __construct(
        public Ticket $ticket,
        public TicketComment $comment,
        public User $author,
        public int $earlierCount = 0,
    ) {}

    public function emailType(): string
    {
        return 'comments';
    }

    public function via(object $notifiable): array
    {
        return $notifiable->wantsEmail($this->emailType()) ? ['mail'] : [];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $extra = $this->earlierCount > 0
            ? [$this->earlierCount === 1 ? 'There is **1 more message** before this one.' : "There are **{$this->earlierCount} more messages** before this one."]
            : [];

        return $this->brandedMail($notifiable, "New message on {$this->ticket->ticket_number}", [
            'heading' => "New message from {$this->author->name}",
            'preheader' => Str::limit((string) $this->comment->body, 90),
            'paragraphs' => array_merge(["**{$this->author->name}** replied on this ticket."], $extra),
            'ticket' => $this->ticketCard($this->ticket),
            'quote' => Str::limit((string) $this->comment->body, 600),
            'quoteBy' => $this->author->name,
            'ctaLabel' => 'Read and reply',
            'ctaUrl' => route('tickets.show', $this->ticket),
            'outro' => 'To avoid filling your inbox, further messages within the next few minutes are combined into the next email.',
            'accent' => '#2563eb',
        ]);
    }
}
