<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\URL;

/**
 * "Is this really your address?" — sent whenever an email is added or changed on an account.
 * Ticket emails only start once the person clicks the button.
 */
class ConfirmEmailNotification extends AppNotification
{
    public function __construct(public User $user) {}

    public function emailType(): string
    {
        return 'ticket_updates';
    }

    // Goes to the address being confirmed, even though it isn't confirmed yet. No bell entry.
    public function via(object $notifiable): array
    {
        return filled($notifiable->email) ? ['mail'] : [];
    }

    /** A link that works for 7 days and only for the address as it is right now. */
    public static function urlFor(User $user): string
    {
        return URL::temporarySignedRoute('email.confirm', now()->addDays(7), [
            'user' => $user->id,
            'hash' => sha1((string) $user->email),
        ]);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $app = config('app.name', 'the IT Service Desk');

        return $this->brandedMail($notifiable, 'Please confirm your email address', [
            'heading' => 'Confirm your email address',
            'preheader' => "Confirm this address to get {$app} updates by email.",
            'paragraphs' => [
                "This email address was added to your **{$app}** account so you can get updates by email — when your ticket is received, picked up, replied to or resolved.",
                'Please confirm that it is yours.',
            ],
            'ctaLabel' => 'Confirm my email',
            'ctaUrl' => self::urlFor($notifiable),
            'outro' => "The link works for 7 days. If you weren't expecting this, you can ignore the email — nothing will be sent to this address until it is confirmed.",
        ]);
    }
}
