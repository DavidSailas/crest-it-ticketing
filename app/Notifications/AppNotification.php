<?php

namespace App\Notifications;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Shared behaviour for every notification the system sends:
 *  - the bell (database) always gets it;
 *  - the email only goes to people who have an address AND have not switched that kind of email off
 *    (Profile → Email notifications);
 *  - every email uses the same branded layout (resources/views/emails).
 */
abstract class AppNotification extends Notification
{
    use Queueable;

    /** Which switch in User::EMAIL_TYPES controls this email. */
    abstract public function emailType(): string;

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($notifiable instanceof User && $notifiable->wantsEmail($this->emailType())) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * Build the branded email.
     *
     * $content keys (all optional except heading):
     *   heading, paragraphs[], ticket (see ticketCard), details[label => value], quote, quoteBy,
     *   ctaLabel, ctaUrl, outro, preheader, accent (hex colour of the top bar)
     */
    protected function brandedMail(object $notifiable, string $subject, array $content): MailMessage
    {
        $appName = config('app.name', 'IT Support');

        $data = array_merge([
            'appName' => $appName,
            'firstName' => trim(strtok((string) ($notifiable->name ?? ''), ' ') ?: ''),
            'prefsUrl' => route('profile.edit').'#email-notifications',
            'accent' => '#1a6b3c',
        ], $content);

        return (new MailMessage)
            ->subject("[{$appName}] {$subject}")
            ->view(['emails.notification', 'emails.notification-text'], $data);
    }

    /** The "ticket card" shown in emails: number, title, status and a few key facts. */
    protected function ticketCard(Ticket $ticket): array
    {
        $ticket->loadMissing(['creator', 'assignee']);

        return [
            'number' => $ticket->ticket_number,
            'title' => $ticket->title,
            'statusKey' => $ticket->status,
            'status' => Ticket::statusLabel($ticket->status),
            'rows' => array_filter([
                'Priority' => ucfirst((string) $ticket->priority),
                'Department' => $ticket->department,
                'Requested by' => $ticket->creator?->name,
                'Handled by' => $ticket->assignee?->name,
            ], fn ($value) => filled($value)),
        ];
    }
}
