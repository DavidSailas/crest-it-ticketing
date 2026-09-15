@php
    $hour = now()->hour;
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
    $firstName = explode(' ', Auth::user()->name)[0];
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Admin Dashboard</h2>
    </x-slot>

    <div class="py-8 max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-8">

        @if(session('status'))
            <div class="p-3 bg-green-50 text-green-800 text-sm rounded-lg border border-green-100">{{ session('status') }}</div>
        @endif

        {{-- Masthead --}}
        <div class="relative overflow-hidden rounded-xl" style="background-color:#123f24;">
            <svg class="absolute -right-6 -top-10 w-56 h-56 opacity-[0.07] pointer-events-none" viewBox="0 0 100 100" fill="none" stroke="white" stroke-width="1.5">
                <rect x="20" y="20" width="24" height="24" rx="3"/>
                <rect x="56" y="20" width="24" height="24" rx="3"/>
                <rect x="20" y="56" width="24" height="24" rx="3"/>
                <rect x="56" y="56" width="24" height="24" rx="3"/>
            </svg>
            <div class="relative px-6 py-7 sm:px-9 sm:py-9 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-5">
                <div>
                    <p class="text-white text-xl font-semibold">{{ $greeting }}, {{ $firstName }}.</p>
                    <p class="text-green-100/80 text-sm mt-1.5 max-w-md">Here's how the IT Service Desk is doing across the company.</p>
                </div>
                <a href="{{ route('admin.users.index') }}"
                   class="shrink-0 inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-lg bg-white text-sm font-semibold hover:bg-green-50 transition"
                   style="color:#123f24;">
                    Manage Users
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                </a>
            </div>
        </div>

        {{-- Tickets Overview --}}
        <div>
            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">Tickets Overview</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                @foreach([
                    ['label' => 'Unassigned', 'hint' => 'Waiting for pickup', 'value' => $stats['unassigned'], 'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z', 'bg' => 'bg-amber-50', 'fg' => 'text-amber-600'],
                    ['label' => 'Critical', 'hint' => 'Active, high urgency', 'value' => $stats['critical'], 'icon' => 'M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z', 'bg' => 'bg-red-50', 'fg' => 'text-red-600'],
                    ['label' => 'Active', 'hint' => 'Not yet resolved', 'value' => $stats['active'], 'icon' => 'M13 10V3L4 14h7v7l9-11h-7z', 'bg' => 'bg-blue-50', 'fg' => 'text-blue-600'],
                    ['label' => 'Resolved', 'hint' => 'This week', 'value' => $stats['resolved_this_week'], 'icon' => 'M5 13l4 4L19 7', 'bg' => 'bg-green-50', 'fg' => 'text-green-700'],
                ] as $stat)
                    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                        <div class="w-9 h-9 rounded-lg {{ $stat['bg'] }} flex items-center justify-center mb-3">
                            <svg class="w-4.5 h-4.5 {{ $stat['fg'] }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $stat['icon'] }}" /></svg>
                        </div>
                        <p class="text-2xl font-semibold text-gray-800 leading-none">{{ $stat['value'] }}</p>
                        <p class="text-xs font-medium text-gray-600 mt-1.5">{{ $stat['label'] }}</p>
                        <p class="text-xs text-gray-400 mt-0.5">{{ $stat['hint'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Team --}}
        <div>
            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">Team</h3>
            <div class="grid grid-cols-3 gap-4">
                @foreach([
                    ['label' => 'Total Users', 'value' => $stats['users'], 'icon' => 'M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4zm6 0a4 4 0 10-4-4'],
                    ['label' => 'IT Support', 'value' => $stats['it_support'], 'icon' => 'M9.5 14.5L4 9l5.5-5.5M14.5 3.5L20 9l-5.5 5.5'],
                    ['label' => 'Staff', 'value' => $stats['staff'], 'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
                ] as $stat)
                    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 flex items-center gap-3.5">
                        <div class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0" style="background-color:#e7f3ec;">
                            <svg class="w-5 h-5" style="color:#1a6b3c;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $stat['icon'] }}" /></svg>
                        </div>
                        <div>
                            <p class="text-2xl font-semibold text-gray-800 leading-none">{{ $stat['value'] }}</p>
                            <p class="text-xs text-gray-500 mt-1">{{ $stat['label'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Recent Tickets --}}
        <div>
            <div class="flex justify-between items-baseline mb-3">
                <h3 class="text-base font-semibold text-gray-800">Recent Tickets</h3>
                <a href="{{ route('tickets.index') }}" class="inline-flex items-center gap-1 text-sm font-medium text-green-700 hover:text-green-800">
                    View all
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                </a>
            </div>

            <div class="bg-white shadow-sm rounded-xl overflow-hidden border border-gray-100">
                @if($recentTickets->isEmpty())
                    <div class="flex flex-col items-center justify-center text-center px-6 py-14">
                        <div class="w-12 h-12 rounded-full bg-gray-50 flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h.01M15 12h.01M9 16h.01M15 16h.01M4 7h16a1 1 0 011 1v2a2 2 0 000 4v2a1 1 0 01-1 1H4a1 1 0 01-1-1v-2a2 2 0 000-4V8a1 1 0 011-1z" /></svg>
                        </div>
                        <p class="text-gray-700 font-medium">No tickets yet</p>
                        <p class="text-gray-400 text-sm mt-1">Once employees start submitting requests, they'll show up here.</p>
                    </div>
                @else
                    <table class="min-w-full text-sm border-collapse">
                        <thead>
                            <tr class="bg-gray-50 border-b-2 border-gray-200">
                                <th class="px-5 py-3 text-left font-semibold text-gray-600 border-r border-gray-200">Title</th>
                                <th class="px-5 py-3 text-left font-semibold text-gray-600 border-r border-gray-200">Status</th>
                                <th class="px-5 py-3 text-left font-semibold text-gray-600 border-r border-gray-200">Requested by</th>
                                <th class="px-5 py-3 text-left font-semibold text-gray-600 border-r border-gray-200">Assigned to</th>
                                <th class="px-5 py-3"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentTickets as $ticket)
                                <tr class="border-b border-gray-100 last:border-b-0 hover:bg-gray-50/70 transition">
                                    <td class="px-5 py-3.5 font-medium text-gray-800 border-r border-gray-100">{{ $ticket->title }}</td>
                                    <td class="px-5 py-3.5 border-r border-gray-100"><x-status-badge :status="$ticket->status" /></td>
                                    <td class="px-5 py-3.5 text-gray-600 border-r border-gray-100">
                                        <span class="inline-flex items-center gap-1.5">
                                            {{ $ticket->creator->name }}
                                            @if($ticket->creator->is_vip)
                                                <x-vip-badge size="compact" />
                                            @endif
                                        </span>
                                    </td>
                                    <td class="px-5 py-3.5 text-gray-600 border-r border-gray-100">{{ $ticket->assignee->name ?? '—' }}</td>
                                    <td class="px-5 py-3.5 text-right"><a href="{{ route('tickets.show', $ticket) }}" class="text-green-700 hover:underline font-medium">View</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
