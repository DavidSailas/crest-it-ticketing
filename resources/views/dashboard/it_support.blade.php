@php
    $hour = now()->hour;
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
    $firstName = explode(' ', Auth::user()->name)[0];
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Support Dashboard</h2>
    </x-slot>

    <div class="py-8 max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">

        @if(session('status'))
            <div class="p-3 bg-green-50 text-green-800 text-sm rounded-lg border border-green-100">{{ session('status') }}</div>
        @endif

        {{-- Masthead --}}
        <div class="relative overflow-hidden rounded-xl" style="background-color:#123f24;">
            <svg class="absolute -right-6 -top-10 w-56 h-56 opacity-[0.07] pointer-events-none" viewBox="0 0 100 100" fill="none" stroke="white" stroke-width="1.5">
                <circle cx="50" cy="50" r="34"/>
                <path d="M50 30v20l14 8"/>
            </svg>
            <div class="relative px-6 py-7 sm:px-9 sm:py-9 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-5">
                <div>
                    <p class="text-white text-xl font-semibold">{{ $greeting }}, {{ $firstName }}.</p>
                    <p class="text-green-100/80 text-sm mt-1.5 max-w-md">
                        @if($stats['critical'] > 0)
                            <strong class="text-white">{{ $stats['critical'] }}</strong> critical ticket{{ $stats['critical'] === 1 ? '' : 's' }} need{{ $stats['critical'] === 1 ? 's' : '' }} attention right now.
                        @elseif($stats['open'] > 0)
                            There {{ $stats['open'] === 1 ? 'is' : 'are' }} <strong class="text-white">{{ $stats['open'] }}</strong> open ticket{{ $stats['open'] === 1 ? '' : 's' }} waiting.
                        @else
                            The queue is clear — nice work. 🎉
                        @endif
                    </p>
                </div>
                <div class="flex gap-2 shrink-0">
                    <a href="{{ route('tickets.create') }}"
                       class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg bg-white/10 text-white text-sm font-semibold hover:bg-white/20 transition border border-white/20">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                        New Ticket
                    </a>
                    <a href="{{ route('tickets.index') }}"
                       class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg bg-white text-sm font-semibold hover:bg-green-50 transition"
                       style="color:#123f24;">
                        View all tickets
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                    </a>
                </div>
            </div>
        </div>

        {{-- Stat cards --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @foreach([
                ['label' => 'Unassigned', 'hint' => 'Waiting for pickup', 'value' => $stats['unassigned'], 'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z', 'bg' => 'bg-amber-50', 'fg' => 'text-amber-600'],
                ['label' => 'Critical', 'hint' => 'Needs urgent action', 'value' => $stats['critical'], 'icon' => 'M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z', 'bg' => 'bg-red-50', 'fg' => 'text-red-600'],
                ['label' => 'My Active', 'hint' => 'Assigned to me', 'value' => $stats['my_active'], 'icon' => 'M13 10V3L4 14h7v7l9-11h-7z', 'bg' => 'bg-blue-50', 'fg' => 'text-blue-600'],
                ['label' => 'Resolved', 'hint' => 'By me today', 'value' => $stats['resolved_today'], 'icon' => 'M5 13l4 4L19 7', 'bg' => 'bg-green-50', 'fg' => 'text-green-700'],
            ] as $stat)
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 flex items-center gap-3.5">
                    <div class="w-10 h-10 rounded-lg {{ $stat['bg'] }} flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 {{ $stat['fg'] }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $stat['icon'] }}" /></svg>
                    </div>
                    <div>
                        <p class="text-2xl font-semibold text-gray-800 leading-none">{{ $stat['value'] }}</p>
                        <p class="text-xs font-medium text-gray-600 mt-1">{{ $stat['label'] }}</p>
                        <p class="text-xs text-gray-400 mt-0.5">{{ $stat['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Open Queue — every unassigned open ticket, critical priority first --}}
        <div>
            <div class="flex justify-between items-baseline mb-3">
                <h3 class="text-base font-semibold text-gray-800">Open Queue</h3>
                <span class="text-xs text-gray-400">Waiting for pickup · critical first</span>
            </div>

            <div class="bg-white shadow-sm rounded-xl overflow-hidden border border-gray-100">
                @if($openQueue->isEmpty())
                    <div class="flex flex-col items-center justify-center text-center px-6 py-12">
                        <div class="w-12 h-12 rounded-full bg-green-50 flex items-center justify-center mb-3">
                            <svg class="w-6 h-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                        </div>
                        <p class="text-gray-700 font-medium">Queue is empty</p>
                        <p class="text-gray-400 text-sm mt-1">Nothing waiting right now — great job staying on top of it.</p>
                    </div>
                @else
                    <table class="w-full text-sm border-collapse">
                        <thead>
                            <tr class="bg-gray-50 border-b-2 border-gray-200">
                                <th class="px-4 py-3 text-left font-semibold text-gray-600 border-r border-gray-200">Ticket</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600 border-r border-gray-200">Department</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600 border-r border-gray-200">Priority</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600 border-r border-gray-200">Requested by</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($openQueue as $ticket)
                                <tr class="border-b border-gray-100 last:border-b-0 transition {{ $ticket->priority === 'critical' ? 'bg-red-50/40 hover:bg-red-50/70' : 'hover:bg-gray-50/70' }}">
                                    <td class="px-4 py-3.5 border-r border-gray-100">
                                        <span class="font-mono text-xs text-gray-400 mr-1">{{ $ticket->ticket_number }}</span>
                                        <span class="font-medium text-gray-800">{{ $ticket->title }}</span>
                                    </td>
                                    <td class="px-4 py-3.5 text-gray-600 border-r border-gray-100">{{ $ticket->department ?? '—' }}</td>
                                    <td class="px-4 py-3.5 border-r border-gray-100"><x-priority-badge :priority="$ticket->priority" /></td>
                                    <td class="px-4 py-3.5 text-gray-600 border-r border-gray-100">
                                        <span class="inline-flex items-center gap-1.5">
                                            {{ $ticket->creator->name }}
                                            @if($ticket->creator->is_vip)
                                                <x-vip-badge size="compact" />
                                            @endif
                                        </span>
                                    </td>
                                    <td class="px-4 py-3.5 text-right"><a href="{{ route('tickets.show', $ticket) }}" class="text-green-700 hover:underline font-medium">View</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>

        {{-- My Assigned Tickets — kept separate from the Open Queue above --}}
        <div>
            <div class="flex justify-between items-baseline mb-3">
                <h3 class="text-base font-semibold text-gray-800">My Assigned Tickets</h3>
            </div>

            <div class="bg-white shadow-sm rounded-xl overflow-hidden border border-gray-100">
                @if($myAssigned->isEmpty())
                    <div class="flex flex-col items-center justify-center text-center px-6 py-12">
                        <div class="w-12 h-12 rounded-full bg-gray-50 flex items-center justify-center mb-3">
                            <svg class="w-6 h-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                        </div>
                        <p class="text-gray-700 font-medium">Nothing assigned yet</p>
                        <p class="text-gray-400 text-sm mt-1">Accept a ticket from the queue above to get started.</p>
                    </div>
                @else
                    <table class="w-full text-sm border-collapse">
                        <thead>
                            <tr class="bg-gray-50 border-b-2 border-gray-200">
                                <th class="px-4 py-3 text-left font-semibold text-gray-600 border-r border-gray-200">Ticket</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600 border-r border-gray-200">Priority</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600 border-r border-gray-200">Status</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($myAssigned as $ticket)
                                <tr class="border-b border-gray-100 last:border-b-0 hover:bg-gray-50/70 transition">
                                    <td class="px-4 py-3.5 border-r border-gray-100">
                                        <span class="font-mono text-xs text-gray-400 mr-1">{{ $ticket->ticket_number }}</span>
                                        <span class="font-medium text-gray-800">{{ $ticket->title }}</span>
                                    </td>
                                    <td class="px-4 py-3.5 border-r border-gray-100"><x-priority-badge :priority="$ticket->priority" /></td>
                                    <td class="px-4 py-3.5 border-r border-gray-100"><x-status-badge :status="$ticket->status" /></td>
                                    <td class="px-4 py-3.5 text-right"><a href="{{ route('tickets.show', $ticket) }}" class="text-green-700 hover:underline font-medium">View</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
