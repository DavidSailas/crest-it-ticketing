@php
    $hour = now()->hour;
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
    $firstName = explode(' ', Auth::user()->name)[0];
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dashboard</h2>
    </x-slot>

    <div class="py-8 max-w-[96rem] mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

        @if(session('status'))
            <div class="p-3 bg-green-50 text-green-800 text-sm rounded-lg border border-green-100">{{ session('status') }}</div>
        @endif

        {{-- Masthead --}}
        <div class="relative overflow-hidden rounded-xl" style="background-color:#123f24;">
            <svg class="absolute -right-6 -top-10 w-56 h-56 opacity-[0.07] pointer-events-none" viewBox="0 0 100 100" fill="none" stroke="white" stroke-width="1.5">
                <rect x="14" y="30" width="72" height="44" rx="4"/>
                <path d="M14 30 L50 12 L86 30"/>
                <circle cx="50" cy="52" r="7"/>
                <path d="M50 52 v10 M46 62 h8"/>
            </svg>
            <div class="relative px-6 py-7 sm:px-9 sm:py-9 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-5">
                <div>
                    <div class="flex items-center gap-2.5 flex-wrap">
                        <p class="text-white text-xl font-semibold">{{ $greeting }}, {{ $firstName }}.</p>
                        @if(auth()->user()->is_vip)
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-400/20 text-amber-200 ring-1 ring-inset ring-amber-300/40">
                                <svg class="w-3 h-3" viewBox="0 0 20 20" fill="currentColor"><path d="M10 1.5l2.29 4.64 5.12.74-3.7 3.61.87 5.1L10 13.9l-4.58 2.4.87-5.1-3.7-3.61 5.12-.74L10 1.5z" /></svg>
                                VIP
                            </span>
                        @endif
                    </div>
                    <p class="text-green-100/80 text-sm mt-1.5 max-w-md">
                        @if(auth()->user()->is_vip)
                            As a VIP account, every ticket you submit is automatically prioritized as Critical and routed for immediate attention.
                        @else
                            Track your requests below, or submit a new one and we'll route it to the right team.
                        @endif
                    </p>
                </div>
                <a href="{{ route('tickets.create') }}"
                   class="shrink-0 inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-lg bg-white text-sm font-semibold hover:bg-green-50 transition"
                   style="color:#123f24;">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    New ticket
                </a>
            </div>
        </div>

        {{-- Summary strip --}}
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm">
            <div class="grid grid-cols-2 sm:grid-cols-4 divide-x divide-y sm:divide-y-0 divide-gray-100">
                @foreach([
                    ['label' => 'Open', 'value' => $stats['open'], 'color' => 'text-yellow-600', 'dot' => 'bg-yellow-500'],
                    ['label' => 'In progress', 'value' => $stats['in_progress'], 'color' => 'text-blue-600', 'dot' => 'bg-blue-500'],
                    ['label' => 'Resolved', 'value' => $stats['resolved'], 'color' => 'text-green-700', 'dot' => 'bg-green-600'],
                    ['label' => 'Closed', 'value' => $stats['closed'], 'color' => 'text-gray-500', 'dot' => 'bg-gray-400'],
                ] as $stat)
                    <div class="px-6 py-5">
                        <p class="{{ $stat['color'] }} text-3xl font-semibold leading-none">{{ $stat['value'] }}</p>
                        <p class="flex items-center gap-1.5 text-sm text-gray-500 mt-2">
                            <span class="w-1.5 h-1.5 rounded-full {{ $stat['dot'] }}"></span>
                            {{ $stat['label'] }}
                        </p>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Recent tickets --}}
        <div>
            <div class="flex justify-between items-baseline mb-3">
                <h3 class="text-base font-semibold text-gray-800">Recent tickets</h3>
                <a href="{{ route('tickets.index') }}" class="inline-flex items-center gap-1 text-sm font-medium text-green-700 hover:text-green-800">
                    View all
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                    </svg>
                </a>
            </div>

            <div class="bg-white shadow-sm rounded-xl border border-gray-100 overflow-x-auto">
                @if($recentTickets->isEmpty())
                    <div class="flex flex-col items-center justify-center text-center px-6 py-14">
                        <div class="w-12 h-12 rounded-full bg-gray-50 flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h.01M15 12h.01M9 16h.01M15 16h.01M4 7h16a1 1 0 011 1v2a2 2 0 000 4v2a1 1 0 01-1 1H4a1 1 0 01-1-1v-2a2 2 0 000-4V8a1 1 0 011-1z" />
                            </svg>
                        </div>
                        <p class="text-gray-700 font-medium">No tickets yet</p>
                        <p class="text-gray-400 text-sm mt-1 max-w-sm">Submit a request and it'll show up here.</p>
                        <a href="{{ route('tickets.create') }}" class="mt-5 inline-flex items-center gap-2 px-4 py-2.5 rounded-lg text-white text-sm font-semibold" style="background-color:#1a6b3c;">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            New ticket
                        </a>
                    </div>
                @else
                    <table class="min-w-full text-sm border-collapse">
                        <thead>
                            <tr class="bg-gray-50 border-b-2 border-gray-200">
                                <th class="px-5 py-3 text-left font-semibold text-gray-600 border-r border-gray-200">Ticket ID</th>
                                <th class="px-5 py-3 text-left font-semibold text-gray-600 border-r border-gray-200">Title</th>
                                <th class="px-5 py-3 text-left font-semibold text-gray-600 border-r border-gray-200">Priority</th>
                                <th class="px-5 py-3 text-left font-semibold text-gray-600 border-r border-gray-200">Status</th>
                                <th class="px-5 py-3 text-left font-semibold text-gray-600 border-r border-gray-200">Created</th>
                                <th class="px-5 py-3 text-left font-semibold text-gray-600 border-r border-gray-200">Resolved</th>
                                <th class="px-5 py-3"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentTickets as $ticket)
                                <tr class="border-b border-gray-100 last:border-b-0 hover:bg-gray-50/70 transition">
                                    <td class="px-5 py-3.5 font-mono text-xs text-gray-500 border-r border-gray-100">#{{ $ticket->id }}</td>
                                    <td class="px-5 py-3.5 font-medium text-gray-800 border-r border-gray-100">{{ $ticket->title }}</td>
                                    <td class="px-5 py-3.5 border-r border-gray-100"><x-priority-badge :priority="$ticket->priority" /></td>
                                    <td class="px-5 py-3.5 border-r border-gray-100"><x-status-badge :status="$ticket->status" /></td>
                                    <td class="px-5 py-3.5 text-gray-500 border-r border-gray-100 whitespace-nowrap">{{ $ticket->created_at->format('M j, Y g:i A') }}</td>
                                    <td class="px-5 py-3.5 text-gray-500 border-r border-gray-100 whitespace-nowrap">{{ $ticket->resolved_at?->format('M j, Y g:i A') ?? '—' }}</td>
                                    <td class="px-5 py-3.5 text-right">
                                        <a href="{{ route('tickets.show', $ticket) }}" class="text-green-700 hover:underline font-medium">View</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
