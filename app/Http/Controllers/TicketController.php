<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Ticket;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->isStaff()) {
            $tickets = Ticket::where('user_id', $user->id)->latest()->paginate(10);
        } elseif ($user->isItSupport()) {
            $tickets = Ticket::where('status', 'open')
                ->orWhere('assigned_to', $user->id)
                ->latest()->paginate(10);
        } else { // admin
            $tickets = Ticket::latest()->paginate(10);
        }

        return view('tickets.index', compact('tickets'));
    }

    public function create()
    {
        return view('tickets.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'department' => 'required|string|max:100',
            'description' => 'required|string',
            'category' => 'required|in:Hardware,Software',
            'subcategory' => 'required|string|max:100',
            'priority' => 'required|in:low,medium,high,critical',
        ]);

        $data['user_id'] = $request->user()->id;

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
                ? "Submitted ticket #{$ticket->id}: {$ticket->title} (auto-escalated to Critical — VIP account)"
                : "Submitted ticket #{$ticket->id}: {$ticket->title}",
            ['ticket_id' => $ticket->id, 'auto_escalated' => $wasAutoEscalated]
        );

        return redirect()->route('tickets.index')->with('status', 'Ticket submitted successfully.');
    }

    public function show(Ticket $ticket)
    {
        $ticket->load('comments.author', 'creator', 'assignee');
        return view('tickets.show', compact('ticket'));
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
            "Accepted ticket #{$ticket->id}: {$ticket->title}",
            ['ticket_id' => $ticket->id]
        );

        return back()->with('status', 'Ticket accepted.');
    }

    public function updateStatus(Request $request, Ticket $ticket)
    {
        abort_unless($request->user()->isItSupport() || $request->user()->isAdmin(), 403);

        $request->validate(['status' => 'required|in:open,in_progress,resolved,closed']);

        $oldStatus = $ticket->status;
        $ticket->update(['status' => $request->status]);

        ActivityLog::record(
            'ticket_status_updated',
            "Changed ticket #{$ticket->id} status from {$oldStatus} to {$request->status}",
            ['ticket_id' => $ticket->id, 'from' => $oldStatus, 'to' => $request->status]
        );

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

    public function comment(Request $request, Ticket $ticket)
    {
        $request->validate(['body' => 'required|string']);

        $ticket->comments()->create([
            'user_id' => $request->user()->id,
            'body' => $request->body,
        ]);

        ActivityLog::record(
            'ticket_comment_added',
            "Commented on ticket #{$ticket->id}: {$ticket->title}",
            ['ticket_id' => $ticket->id]
        );

        return back()->with('status', 'Comment added.');
    }
}
