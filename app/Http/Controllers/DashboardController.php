<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->isStaff()) {
            // Staff no longer land on a stats dashboard — the first thing they see
            // after logging in is the "new request" form itself. Their ticket
            // history lives under the Tickets nav item, and conversation with IT
            // support lives under Chat.
            $departments = Department::orderBy('name')->pluck('name');

            // Mapped the same way TicketController::create() builds this list,
            // so both paths that render the ticket form stay in sync and the
            // colleague picker's email never silently goes missing again.
            $colleagues = User::where('role', 'staff')
                ->where('id', '!=', $user->id)
                ->orderBy('name')
                ->get()
                ->map(function ($colleague) {
                    return [
                        'id' => $colleague->id,
                        'name' => $colleague->name,
                        'email' => $colleague->email ?? $colleague->email_address ?? null,
                    ];
                });

            return view('tickets.create', compact('departments', 'colleagues'));
        }

        if ($user->isItSupport()) {
            return view('dashboard.it_support', $this->itSupportData($user));
        }

        // admin
        $stats = [
            // Headline health metrics — what an admin actually needs at a glance.
            'unassigned' => Ticket::where('status', 'open')->whereNull('assigned_to')->count(),
            'critical' => Ticket::whereNotIn('status', ['resolved', 'closed'])->where('priority', 'critical')->count(),
            'active' => Ticket::whereIn('status', ['open', 'in_progress', 'pending'])->count(),
            'resolved_this_week' => Ticket::whereIn('status', ['resolved', 'closed'])
                ->where('updated_at', '>=', now()->startOfWeek())
                ->count(),
            // Team breakdown, shown in its own smaller row.
            'users' => User::count(),
            'it_support' => User::where('role', 'it_support')->count(),
            'staff' => User::where('role', 'staff')->count(),
        ];
        $recentTickets = Ticket::latest()->take(8)->get();

        return view('dashboard.admin', compact('stats', 'recentTickets'));
    }

    /**
     * Everything the IT Support dashboard shows, in one place so the page
     * itself and the live poll always render exactly the same data.
     *
     * @return array<string, mixed>
     */
    private function itSupportData(User $user): array
    {
        $stats = [
            // Waiting for somebody to pick up — the number that should drive action.
            'unassigned' => Ticket::where('status', 'open')->whereNull('assigned_to')->count(),
            'critical' => Ticket::whereNotIn('status', ['resolved', 'closed'])->where('priority', 'critical')->count(),
            // This agent's own live workload.
            'my_active' => Ticket::where('assigned_to', $user->id)->whereIn('status', ['in_progress', 'pending'])->count(),
            'resolved_today' => Ticket::where('assigned_to', $user->id)
                ->whereIn('status', ['resolved', 'closed'])
                ->whereDate('updated_at', today())
                ->count(),
            // Drives the masthead message only.
            'open' => Ticket::where('status', 'open')->count(),
        ];

        // One merged queue of every unassigned open ticket — critical priority
        // (VIP-escalated or otherwise) always surfaces first, then newest first
        // within each priority tier.
        $priorityRank = ['critical' => 0, 'high' => 1, 'medium' => 2, 'low' => 3];
        $openQueue = Ticket::where('status', 'open')
            ->whereNull('assigned_to')
            ->with('creator')
            ->latest()
            ->get()
            ->sortBy(fn (Ticket $ticket) => $priorityRank[$ticket->priority] ?? 99)
            ->values()
            ->take(15);

        // Every ticket that has an owner and isn't closed yet — across the
        // whole IT team, not just this agent — so everyone can see who is
        // carrying how much and jump in to help. Critical first, then newest.
        $assignedStatuses = ['in_progress', 'pending', 'resolved'];

        $assignedTickets = Ticket::whereNotNull('assigned_to')
            ->whereIn('status', $assignedStatuses)
            ->with(['assignee', 'creator', 'assistants'])
            ->orderByRaw("CASE priority WHEN 'critical' THEN 0 WHEN 'high' THEN 1 WHEN 'medium' THEN 2 WHEN 'low' THEN 3 ELSE 99 END")
            ->latest()
            ->paginate(10, ['*'], 'assigned_page')
            ->withPath(route('dashboard'))
            ->fragment('assigned-tickets');

        // Workload per agent (including agents with nothing assigned) so it's
        // obvious who is swamped and who has capacity.
        $agentWorkload = User::where('role', 'it_support')
            ->withCount(['assignedTickets as assigned_count' => fn ($q) => $q->whereIn('status', $assignedStatuses)])
            ->orderByDesc('assigned_count')
            ->orderBy('name')
            ->get();

        return compact('stats', 'openQueue', 'assignedTickets', 'agentWorkload');
    }

    /**
     * Real-time refresh for the IT dashboard. Returns the freshly rendered
     * dashboard body only when it differs from what the browser already has.
     *
     * GET /dashboard/live?hash=...&assigned_page=2
     */
    public function live(Request $request)
    {
        $user = $request->user();

        abort_unless($user->isItSupport(), 403);

        $html = view('dashboard.partials.it-live', $this->itSupportData($user))->render();
        $hash = md5($html);

        $payload = $hash === $request->query('hash')
            ? ['changed' => false]
            : ['changed' => true, 'hash' => $hash, 'html' => $html];

        return response()->json($payload)->header('Cache-Control', 'no-store');
    }
}
