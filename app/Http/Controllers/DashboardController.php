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
            $colleagues = User::where('role', 'staff')
                ->where('id', '!=', $user->id)
                ->orderBy('name')
                ->get(['id', 'name']);

            return view('tickets.create', compact('departments', 'colleagues'));
        }

        if ($user->isItSupport()) {
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

            // Kept as its own list, separate from the Open Queue above.
            $myAssigned = Ticket::where('assigned_to', $user->id)
                ->whereIn('status', ['in_progress', 'pending', 'resolved'])
                ->latest()
                ->take(10)
                ->get();

            return view('dashboard.it_support', compact('stats', 'openQueue', 'myAssigned'));
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
}
