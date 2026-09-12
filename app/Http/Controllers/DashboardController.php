<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->isStaff()) {
            $stats = [
                'open' => Ticket::where('user_id', $user->id)->where('status', 'open')->count(),
                'in_progress' => Ticket::where('user_id', $user->id)->where('status', 'in_progress')->count(),
                'resolved' => Ticket::where('user_id', $user->id)->where('status', 'resolved')->count(),
                'closed' => Ticket::where('user_id', $user->id)->where('status', 'closed')->count(),
            ];
            $recentTickets = Ticket::where('user_id', $user->id)->latest()->take(5)->get();

            return view('dashboard.staff', compact('stats', 'recentTickets'));
        }

        if ($user->isItSupport()) {
            $stats = [
                'unassigned' => Ticket::where('status', 'open')->whereNull('assigned_to')->count(),
                'my_active' => Ticket::where('assigned_to', $user->id)->where('status', 'in_progress')->count(),
                'my_resolved' => Ticket::where('assigned_to', $user->id)->where('status', 'resolved')->count(),
                'my_total' => Ticket::where('assigned_to', $user->id)->count(),
            ];
            $queue = Ticket::where('status', 'open')->whereNull('assigned_to')->latest()->take(5)->get();
            $myTickets = Ticket::where('assigned_to', $user->id)->whereIn('status', ['in_progress'])->latest()->take(5)->get();

            return view('dashboard.it_support', compact('stats', 'queue', 'myTickets'));
        }

        // admin
        $stats = [
            'total' => Ticket::count(),
            'open' => Ticket::where('status', 'open')->count(),
            'in_progress' => Ticket::where('status', 'in_progress')->count(),
            'resolved' => Ticket::where('status', 'resolved')->count(),
            'closed' => Ticket::where('status', 'closed')->count(),
            'users' => User::count(),
            'it_support' => User::where('role', 'it_support')->count(),
            'staff' => User::where('role', 'staff')->count(),
        ];
        $recentTickets = Ticket::latest()->take(8)->get();

        return view('dashboard.admin', compact('stats', 'recentTickets'));
    }
}
