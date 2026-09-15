<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTicketRequest;
use App\Models\ActivityLog;
use App\Models\Department;
use App\Models\Ticket;
use App\Models\TicketRead;
use App\Models\User;
use App\Notifications\TicketAcceptedNotification;
use App\Notifications\TicketAssignedNotification;
use App\Notifications\TicketStatusUpdatedNotification;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $search = trim((string) $request->query('search', ''));

        if ($user->isStaff()) {
            $query = Ticket::where('user_id', $user->id);
        } elseif ($user->isItSupport()) {
            // The Open Queue of unclaimed tickets lives on the dashboard — this
            // page is the agent's own ticket history: what they're actively
            // working on, plus what they've already resolved or closed.
            $query = Ticket::where('assigned_to', $user->id)
                ->whereIn('status', ['in_progress', 'pending', 'resolved', 'closed']);
        } else { // admin
            $query = Ticket::query();
        }

        if ($search !== '') {
            $ticketId = null;
            if (preg_match('/^(inc)?0*(\d+)$/i', $search, $matches)) {
                $ticketId = (int) $matches[2];
            }

            $query->where(function ($q) use ($search, $ticketId) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%")
                    ->orWhere('department', 'like', "%{$search}%")
                    ->orWhereHas('creator', fn ($c) => $c->where('name', 'like', "%{$search}%"));

                if ($ticketId !== null) {
                    $q->orWhere('id', $ticketId);
                }
            });
        }

        $tickets = $query->latest()->paginate(10)->withQueryString();

        return view('tickets.index', compact('tickets', 'search'));
    }

    public function create()
    {
        $departments = Department::orderBy('name')->pluck('name');
        $colleagues = User::where('role', 'staff')
            ->where('id', '!=', auth()->id())
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('tickets.create', compact('departments', 'colleagues'));
    }

    public function store(StoreTicketRequest $request)
    {
        $data = $request->validated();

        $data['user_id'] = $request->user()->id;

        // There's no separate title field anymore — the category the requester
        // picked ("Printer", "Password / Account", etc.) doubles as the ticket
        // title everywhere else in the app (lists, chat, notifications).
        $data['title'] = $data['category'];

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $data['attachment_path'] = $file->store('ticket-attachments', 'public');
            $data['attachment_name'] = $file->getClientOriginalName();
        }

        // VIP accounts (owner, CEO, president, etc.) always get bumped to critical,
        // regardless of what was selected in the form.
        $wasAutoEscalated = false;
        if ($request->user()->is_vip && $data['priority'] !== 'critical') {
            $data['priority'] = 'critical';
            $wasAutoEscalated = true;
        }

        $ticket = Ticket::create($data);

        ActivityLog::record(
            'ticket_created',
            $wasAutoEscalated
                ? "Submitted ticket {$ticket->ticket_number}: {$ticket->title} (auto-escalated to Critical — VIP account)"
                : "Submitted ticket {$ticket->ticket_number}: {$ticket->title}",
            ['ticket_id' => $ticket->id, 'auto_escalated' => $wasAutoEscalated]
        );

        return redirect()->route('tickets.confirmation', $ticket);
    }

    /**
     * A professional "receipt" shown right after submission — the ticket
     * number, what was submitted, and where to go next. Only the requester
     * (or an admin) can view it.
     */
    public function confirmation(Ticket $ticket)
    {
        abort_unless(
            $ticket->user_id === auth()->id() || auth()->user()->isAdmin(),
            403
        );

        return view('tickets.confirmation', compact('ticket'));
    }

    /**
     * The other side of a ticket's 1:1 chat: the creator if you're IT/admin,
     * or the assigned IT staffer if you're the creator. Used to compute
     * "Seen" read receipts.
     */
    private function otherPartyId(Ticket $ticket, int $currentUserId): ?int
    {
        return $ticket->user_id === $currentUserId
            ? $ticket->assigned_to
            : $ticket->user_id;
    }

    public function show(Ticket $ticket)
    {
        $ticket->load('comments.author', 'creator', 'assignee');

        $currentUserId = auth()->id();
        TicketRead::markRead($ticket->id, $currentUserId);

        $otherPartyId = $this->otherPartyId($ticket, $currentUserId);
        $otherPartyLastReadAt = $otherPartyId
            ? optional(TicketRead::where('ticket_id', $ticket->id)->where('user_id', $otherPartyId)->first())->last_read_at
            : null;

        return view('tickets.show', compact('ticket', 'otherPartyLastReadAt'));
    }

    public function accept(Request $request, Ticket $ticket)
    {
        abort_unless($request->user()->isItSupport() || $request->user()->isAdmin(), 403);

        $ticket->update([
            'assigned_to' => $request->user()->id,
            'status' => 'in_progress',
        ]);

        ActivityLog::record(
            'ticket_accepted',
            "Accepted ticket {$ticket->ticket_number}: {$ticket->title}",
            ['ticket_id' => $ticket->id]
        );

        $ticket->creator?->notify(new TicketAcceptedNotification($ticket, $request->user()));

        return back()->with('status', 'Ticket accepted.');
    }

    /**
     * Admin-only: assign a ticket directly to a specific IT support agent,
     * rather than waiting for someone to self-accept it from the queue.
     */
    public function assign(Request $request, Ticket $ticket)
    {
        abort_unless($request->user()->isAdmin(), 403);

        $validated = $request->validate([
            'assigned_to' => 'required|exists:users,id',
        ], [
            'assigned_to.required' => 'Please choose an IT Support agent to assign this to.',
        ]);

        $agent = User::where('id', $validated['assigned_to'])->where('role', 'it_support')->firstOrFail();

        $ticket->update([
            'assigned_to' => $agent->id,
            'status' => $ticket->status === 'open' ? 'in_progress' : $ticket->status,
        ]);

        ActivityLog::record(
            'ticket_assigned',
            "Assigned ticket {$ticket->ticket_number} to {$agent->name}",
            ['ticket_id' => $ticket->id, 'assigned_to' => $agent->id]
        );

        // Notification delivery (email) can fail independently of the actual
        // assignment — e.g. a mail provider's rate limit — and shouldn't turn
        // a successful save into a 500 for the admin. Only touching this
        // admin-assign path, nothing else.
        try {
            $agent->notify(new TicketAssignedNotification($ticket, $request->user()));
            $ticket->creator?->notify(new TicketAcceptedNotification($ticket, $agent));
        } catch (\Throwable $e) {
            report($e);
        }

        return back()->with('status', "Assigned to {$agent->name}.");
    }

    public function updateStatus(Request $request, Ticket $ticket)
    {
        abort_unless($request->user()->isItSupport() || $request->user()->isAdmin(), 403);

        $request->validate(['status' => 'required|in:open,in_progress,pending,resolved,closed']);

        $oldStatus = $ticket->status;

        $updates = ['status' => $request->status];

        // Stamp resolved_at the moment a ticket first becomes resolved; clear it
        // if it gets reopened later so the "solved" date always reflects reality.
        if ($request->status === 'resolved' && $oldStatus !== 'resolved') {
            $updates['resolved_at'] = now();
        } elseif (in_array($request->status, ['open', 'in_progress'])) {
            $updates['resolved_at'] = null;
        }

        $ticket->update($updates);

        ActivityLog::record(
            'ticket_status_updated',
            "Changed ticket {$ticket->ticket_number} status from {$oldStatus} to {$request->status}",
            ['ticket_id' => $ticket->id, 'from' => $oldStatus, 'to' => $request->status]
        );

        if ($oldStatus !== $request->status) {
            $ticket->creator?->notify(new TicketStatusUpdatedNotification($ticket, $oldStatus, $request->status));
        }

        return back()->with('status', 'Ticket status updated.');
    }

    /**
     * Lightweight polling endpoint used by the IT support dashboard / nav bell
     * to check for newly created, unassigned tickets in near real-time.
     *
     * GET /tickets/queue/poll?since_id=123
     */
    public function pollQueue(Request $request)
    {
        abort_unless($request->user()->isItSupport() || $request->user()->isAdmin(), 403);

        $sinceId = (int) $request->query('since_id', 0);

        $newTickets = Ticket::with('creator')
            ->where('status', 'open')
            ->whereNull('assigned_to')
            ->where('id', '>', $sinceId)
            ->orderBy('id')
            ->get()
            ->map(fn (Ticket $ticket) => [
                'id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number,
                'title' => $ticket->title,
                'department' => $ticket->department,
                'category' => $ticket->category,
                'subcategory' => $ticket->subcategory,
                'priority' => $ticket->priority,
                'requester' => $ticket->creator->name,
                'requester_is_vip' => (bool) $ticket->creator->is_vip,
                'url' => route('tickets.show', $ticket),
                'created_at' => $ticket->created_at->diffForHumans(),
            ]);

        $unassignedCount = Ticket::where('status', 'open')->whereNull('assigned_to')->count();
        $latestId = Ticket::max('id') ?? $sinceId;

        return response()->json([
            'tickets' => $newTickets,
            'unassigned_count' => $unassignedCount,
            'latest_id' => $latestId,
        ]);
    }

    /**
     * Chat inbox — one thread per ticket, scoped to the same tickets each role
     * already sees on /tickets, ordered by most recent activity so active
     * conversations float to the top.
     */
    /**
     * The other side of a ticket's chat, and the pieces needed to render one
     * row of the Chat inbox: who it's with, the latest message, and whether
     * the current user has already seen that latest message.
     */
    private function chatThreadSummary(Ticket $ticket, $user, ?\Illuminate\Support\Carbon $lastReadAt): array
    {
        $isStaff = $user->isStaff();
        $otherParty = $isStaff ? $ticket->assignee : $ticket->creator;
        $lastMessage = $ticket->comments->first();

        // Unread = the other party's latest message hasn't been seen yet.
        // A thread I sent the last message in is never "unread" for me.
        $unread = $lastMessage
            && $lastMessage->user_id !== $user->id
            && (! $lastReadAt || $lastMessage->created_at->gt($lastReadAt));

        $activityAt = $lastMessage->created_at ?? $ticket->created_at;

        return [
            'ticket_id' => $ticket->id,
            'ticket_number' => $ticket->ticket_number,
            'url' => route('tickets.show', $ticket).'#chat',
            'other_party_name' => $isStaff ? ($ticket->assignee->name ?? 'IT Support (unassigned)') : $ticket->creator->name,
            'other_party_initials' => $otherParty
                ? strtoupper(collect(explode(' ', $otherParty->name))->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode(''))
                : null,
            'show_vip_badge' => ! $isStaff && $ticket->creator->is_vip,
            'title' => $ticket->title,
            'status' => $ticket->status,
            'has_messages' => (bool) $lastMessage,
            'last_message_author' => $lastMessage?->author->name,
            'last_message_body' => $lastMessage ? \Illuminate\Support\Str::limit($lastMessage->body, 80) : null,
            'last_activity_at' => $activityAt->toIso8601String(),
            'last_activity_human' => $activityAt->diffForHumans(),
            'unread' => $unread,
        ];
    }

    /** Same ticket scoping chatIndex/chatPoll both need, kept in one place. */
    private function chatTicketsQuery($user)
    {
        if ($user->isStaff()) {
            return Ticket::where('user_id', $user->id)->with(['comments.author', 'assignee']);
        } elseif ($user->isItSupport()) {
            // Same rule as their Tickets page: only tickets *specifically*
            // assigned to this agent — not the whole company's open queue.
            // If David has accepted 3, only those 3 show here for him.
            return Ticket::where('assigned_to', $user->id)->with(['comments.author', 'creator']);
        }

        return Ticket::with(['comments.author', 'creator', 'assignee']); // admin
    }

    /** @return array<int, array> */
    private function buildChatThreads($tickets, $user): array
    {
        $lastReads = TicketRead::where('user_id', $user->id)
            ->whereIn('ticket_id', $tickets->pluck('id'))
            ->pluck('last_read_at', 'ticket_id');

        return $tickets
            ->sortByDesc(fn (Ticket $ticket) => optional($ticket->comments->first())->created_at ?? $ticket->created_at)
            ->values()
            ->map(fn (Ticket $ticket) => $this->chatThreadSummary($ticket, $user, $lastReads->get($ticket->id)))
            ->all();
    }

    public function chatIndex(Request $request)
    {
        $user = $request->user();
        $tickets = $this->chatTicketsQuery($user)->get();
        $threads = $this->buildChatThreads($tickets, $user);

        return view('chat.index', compact('threads'));
    }

    /**
     * Polled every few seconds by the Chat inbox so new messages and
     * read/unread state show up without a page refresh.
     */
    public function chatPoll(Request $request)
    {
        $user = $request->user();
        $tickets = $this->chatTicketsQuery($user)->get();

        return response()->json(['threads' => $this->buildChatThreads($tickets, $user)]);
    }

    public function comment(Request $request, Ticket $ticket)
    {
        abort_if($ticket->status === 'closed', 403, 'This ticket is closed — the conversation can no longer be replied to.');

        $request->validate(['body' => 'required|string|max:2000'], [
            'body.required' => 'Please type a message before sending.',
            'body.max' => 'Message is too long — please keep it under 2,000 characters.',
        ]);

        $comment = $ticket->comments()->create([
            'user_id' => $request->user()->id,
            'body' => $request->body,
        ]);

        $comment->load('author');

        ActivityLog::record(
            'ticket_comment_added',
            "Commented on ticket {$ticket->ticket_number}: {$ticket->title}",
            ['ticket_id' => $ticket->id]
        );

        if ($request->wantsJson()) {
            return response()->json([
                'comment' => [
                    'id' => $comment->id,
                    'body' => $comment->body,
                    'author_name' => $comment->author->name,
                    'author_id' => $comment->user_id,
                    'is_mine' => true,
                    'created_at_human' => $comment->created_at->diffForHumans(),
                    'created_at' => $comment->created_at->toIso8601String(),
                ],
            ]);
        }

        return back()->with('status', 'Comment added.');
    }

    /**
     * Lightweight polling endpoint for the ticket chat thread — the browser
     * asks every few seconds for anything newer than the last message it has,
     * giving a near real-time feel without needing WebSockets/queue workers.
     *
     * GET /tickets/{ticket}/chat/poll?since_id=123
     */
    public function pollChat(Request $request, Ticket $ticket)
    {
        $user = $request->user();

        abort_unless(
            $user->isAdmin()
                || $ticket->user_id === $user->id
                || $ticket->assigned_to === $user->id
                || ($user->isItSupport() && $ticket->status === 'open'),
            403
        );

        $sinceId = (int) $request->query('since_id', 0);

        // Polling means the user is actively looking at this thread right now.
        TicketRead::markRead($ticket->id, $user->id);

        $otherPartyId = $this->otherPartyId($ticket, $user->id);
        $otherPartyLastReadAt = $otherPartyId
            ? optional(TicketRead::where('ticket_id', $ticket->id)->where('user_id', $otherPartyId)->first())->last_read_at
            : null;

        $messages = $ticket->comments()
            ->with('author')
            ->where('id', '>', $sinceId)
            ->orderBy('id')
            ->get()
            ->map(fn ($comment) => [
                'id' => $comment->id,
                'body' => $comment->body,
                'author_name' => $comment->author->name,
                'author_id' => $comment->user_id,
                'is_mine' => $comment->user_id === $user->id,
                'is_it' => $comment->author->role !== 'staff',
                'created_at_human' => $comment->created_at->diffForHumans(),
                'created_at' => $comment->created_at->toIso8601String(),
            ]);

        return response()->json([
            'messages' => $messages,
            'latest_id' => $ticket->comments()->max('id') ?? $sinceId,
            'other_party_last_read_at' => $otherPartyLastReadAt?->toIso8601String(),
        ]);
    }
}
