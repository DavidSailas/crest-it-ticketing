<?php

namespace App\Support;

use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use App\Notifications\NewTicketNotification;
use App\Notifications\TicketCommentedNotification;
use App\Notifications\TicketReceivedNotification;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Cache;

/**
 * One safe way to send notifications:
 *  - they go out AFTER the page has been sent to the browser, so a slow mail server never slows
 *    anyone down (and no queue worker is needed on shared hosting);
 *  - a delivery problem is logged and never turns a successful action into an error page.
 */
class Notifier
{
    /** @param  User|iterable<User>|null  $recipients */
    public static function send($recipients, Notification $notification): void
    {
        $users = collect($recipients instanceof User ? [$recipients] : $recipients)
            ->filter()
            ->unique('id')
            ->values();

        if ($users->isEmpty()) {
            return;
        }

        app()->terminating(function () use ($users, $notification) {
            foreach ($users as $user) {
                try {
                    $user->notifyNow($notification);
                } catch (\Throwable $e) {
                    report($e);
                }
            }
        });
    }

    /** A new ticket: confirm to the requester (optional) and alert the IT team. */
    public static function ticketCreated(Ticket $ticket, bool $confirmRequester = true): void
    {
        $ticket->loadMissing('creator');

        if ($confirmRequester) {
            static::send($ticket->creator, new TicketReceivedNotification($ticket));
        }

        static::send(
            User::itTeam()->where('id', '!=', $ticket->user_id),
            new NewTicketNotification($ticket)
        );
    }

    /**
     * A message on a ticket. The other side gets an email — but without flooding them:
     *  - nothing if they are online right now (they can see it);
     *  - at most one email per ticket every 10 minutes; messages in between are counted and
     *    mentioned in the next email ("+3 earlier messages").
     */
    public static function commentPosted(Ticket $ticket, TicketComment $comment, User $author): void
    {
        $ticket->loadMissing(['creator', 'assignee']);

        // Requester wrote -> the person handling it (or the whole IT team if nobody has picked it up yet).
        // IT / admin wrote -> the requester.
        $recipients = $author->id === $ticket->user_id
            ? ($ticket->assignee ? collect([$ticket->assignee]) : User::itTeam())
            : collect([$ticket->creator]);

        foreach ($recipients->filter()->reject(fn (User $u) => $u->id === $author->id) as $recipient) {
            if (! $recipient->wantsEmail('comments')) {
                continue;
            }

            if ($recipient->last_seen_at && $recipient->last_seen_at->gt(now()->subMinutes(2))) {
                continue;
            }

            $sentKey = "comment-mail:{$ticket->id}:{$recipient->id}";
            $pendingKey = "comment-pending:{$ticket->id}:{$recipient->id}";

            if (! Cache::add($sentKey, true, now()->addMinutes(10))) {
                Cache::put($pendingKey, (int) Cache::get($pendingKey, 0) + 1, now()->addHours(2));

                continue;
            }

            $earlier = (int) Cache::pull($pendingKey, 0);

            static::send($recipient, new TicketCommentedNotification($ticket, $comment, $author, $earlier));
        }
    }
}
