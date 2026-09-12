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
                        @if($stats['unassigned'] > 0)
                            There {{ $stats['unassigned'] === 1 ? 'is' : 'are' }} <strong class="text-white">{{ $stats['unassigned'] }}</strong> ticket{{ $stats['unassigned'] === 1 ? '' : 's' }} waiting for pickup.
                        @else
                            The queue is clear — nice work. 🎉
                        @endif
                    </p>
                </div>
                <a href="{{ route('tickets.index') }}"
                   class="shrink-0 inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-lg bg-white text-sm font-semibold hover:bg-green-50 transition"
                   style="color:#123f24;">
                    View all tickets
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                </a>
            </div>
        </div>

        {{-- Stat cards --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @foreach([
                ['label' => 'Unassigned', 'value' => $stats['unassigned'], 'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z', 'bg' => 'bg-amber-50', 'fg' => 'text-amber-600'],
                ['label' => 'My Active', 'value' => $stats['my_active'], 'icon' => 'M13 10V3L4 14h7v7l9-11h-7z', 'bg' => 'bg-blue-50', 'fg' => 'text-blue-600'],
                ['label' => 'Resolved by Me', 'value' => $stats['my_resolved'], 'icon' => 'M5 13l4 4L19 7', 'bg' => 'bg-green-50', 'fg' => 'text-green-700'],
                ['label' => 'Total Handled', 'value' => $stats['my_total'], 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2', 'bg' => 'bg-gray-100', 'fg' => 'text-gray-600'],
            ] as $stat)
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 flex items-center gap-3.5">
                    <div class="w-10 h-10 rounded-lg {{ $stat['bg'] }} flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 {{ $stat['fg'] }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $stat['icon'] }}" /></svg>
                    </div>
                    <div>
                        <p class="text-2xl font-semibold text-gray-800 leading-none" @if($stat['label'] === 'Unassigned') id="stat-unassigned" @endif>{{ $stat['value'] }}</p>
                        <p class="text-xs text-gray-500 mt-1">{{ $stat['label'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Open Queue --}}
        <div>
            <div class="flex justify-between items-baseline mb-3">
                <h3 class="text-base font-semibold text-gray-800">Open Queue</h3>
                <span class="text-xs text-gray-400">Waiting for pickup</span>
            </div>

            <div class="bg-white shadow-sm rounded-xl overflow-hidden border border-gray-100">
                <div id="open-queue-empty" @if(!$queue->isEmpty()) class="hidden" @endif>
                    <div class="flex flex-col items-center justify-center text-center px-6 py-12">
                        <div class="w-12 h-12 rounded-full bg-green-50 flex items-center justify-center mb-3">
                            <svg class="w-6 h-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                        </div>
                        <p class="text-gray-700 font-medium">Queue is empty</p>
                        <p class="text-gray-400 text-sm mt-1">Nothing waiting right now — great job staying on top of it.</p>
                    </div>
                </div>
                <table class="min-w-full text-sm border-collapse @if($queue->isEmpty()) hidden @endif" id="open-queue-table">
                    <thead>
                        <tr class="bg-gray-50 border-b-2 border-gray-200">
                            <th class="px-5 py-3 text-left font-semibold text-gray-600 border-r border-gray-200">Title</th>
                            <th class="px-5 py-3 text-left font-semibold text-gray-600 border-r border-gray-200">Department</th>
                            <th class="px-5 py-3 text-left font-semibold text-gray-600 border-r border-gray-200">Category / Type</th>
                            <th class="px-5 py-3 text-left font-semibold text-gray-600 border-r border-gray-200">Priority</th>
                            <th class="px-5 py-3 text-left font-semibold text-gray-600 border-r border-gray-200">Requested by</th>
                            <th class="px-5 py-3"></th>
                        </tr>
                    </thead>
                    <tbody id="open-queue-body">
                        @foreach($queue as $ticket)
                            <tr class="border-b border-gray-100 last:border-b-0 hover:bg-gray-50/70 transition" data-ticket-id="{{ $ticket->id }}">
                                <td class="px-5 py-3.5 font-medium text-gray-800 border-r border-gray-100">{{ $ticket->title }}</td>
                                <td class="px-5 py-3.5 text-gray-600 border-r border-gray-100">{{ $ticket->department ?? '—' }}</td>
                                <td class="px-5 py-3.5 text-gray-600 border-r border-gray-100">{{ $ticket->category }}@if($ticket->subcategory)<span class="text-gray-400"> · {{ $ticket->subcategory }}</span>@endif</td>
                                <td class="px-5 py-3.5 border-r border-gray-100"><x-priority-badge :priority="$ticket->priority" /></td>
                                <td class="px-5 py-3.5 text-gray-600 border-r border-gray-100">
                                    <span class="inline-flex items-center gap-1.5">
                                        {{ $ticket->creator->name }}
                                        @if($ticket->creator->is_vip)
                                            <x-vip-badge size="compact" />
                                        @endif
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-right"><a href="{{ route('tickets.show', $ticket) }}" class="text-green-700 hover:underline font-medium">View</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- My Active Tickets --}}
        <div>
            <div class="flex justify-between items-baseline mb-3">
                <h3 class="text-base font-semibold text-gray-800">My Active Tickets</h3>
            </div>

            <div class="bg-white shadow-sm rounded-xl overflow-hidden border border-gray-100">
                @if($myTickets->isEmpty())
                    <div class="flex flex-col items-center justify-center text-center px-6 py-12">
                        <div class="w-12 h-12 rounded-full bg-gray-50 flex items-center justify-center mb-3">
                            <svg class="w-6 h-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                        </div>
                        <p class="text-gray-700 font-medium">Nothing in progress</p>
                        <p class="text-gray-400 text-sm mt-1">Accept a ticket from the queue above to get started.</p>
                    </div>
                @else
                    <table class="min-w-full text-sm border-collapse">
                        <thead>
                            <tr class="bg-gray-50 border-b-2 border-gray-200">
                                <th class="px-5 py-3 text-left font-semibold text-gray-600 border-r border-gray-200">Title</th>
                                <th class="px-5 py-3 text-left font-semibold text-gray-600 border-r border-gray-200">Status</th>
                                <th class="px-5 py-3"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($myTickets as $ticket)
                                <tr class="border-b border-gray-100 last:border-b-0 hover:bg-gray-50/70 transition">
                                    <td class="px-5 py-3.5 font-medium text-gray-800 border-r border-gray-100">{{ $ticket->title }}</td>
                                    <td class="px-5 py-3.5 border-r border-gray-100"><x-status-badge :status="$ticket->status" /></td>
                                    <td class="px-5 py-3.5 text-right"><a href="{{ route('tickets.show', $ticket) }}" class="text-green-700 hover:underline font-medium">View</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>

    <script>
        // Listens for the poll results fetched by the nav bell (layouts/navigation.blade.php)
        // and live-updates the Open Queue table + Unassigned stat without a page reload.
        window.addEventListener('tickets:polled', (event) => {
            const { tickets, unassigned_count } = event.detail;

            const statEl = document.getElementById('stat-unassigned');
            if (statEl) statEl.textContent = unassigned_count;

            if (tickets.length === 0) return;

            const body = document.getElementById('open-queue-body');
            const table = document.getElementById('open-queue-table');
            const empty = document.getElementById('open-queue-empty');
            if (!body) return;

            table.classList.remove('hidden');
            empty.classList.add('hidden');

            tickets.forEach(ticket => {
                if (body.querySelector(`[data-ticket-id="${ticket.id}"]`)) return; // already rendered

                const priorityClasses = {
                    critical: 'bg-red-100 text-red-700',
                    high: 'bg-orange-100 text-orange-700',
                    medium: 'bg-amber-100 text-amber-700',
                    low: 'bg-gray-100 text-gray-600',
                };

                const row = document.createElement('tr');
                row.dataset.ticketId = ticket.id;
                row.className = 'border-b border-gray-100 last:border-b-0 hover:bg-gray-50/70 transition bg-green-50/60';
                row.innerHTML = `
                    <td class="px-5 py-3.5 font-medium text-gray-800 border-r border-gray-100">${ticket.title}</td>
                    <td class="px-5 py-3.5 text-gray-600 border-r border-gray-100">${ticket.department ?? '—'}</td>
                    <td class="px-5 py-3.5 text-gray-600 border-r border-gray-100">${ticket.category}${ticket.subcategory ? ` <span class="text-gray-400">· ${ticket.subcategory}</span>` : ''}</td>
                    <td class="px-5 py-3.5 border-r border-gray-100"><span class="px-2 py-0.5 rounded-full text-xs font-medium capitalize ${priorityClasses[ticket.priority] ?? priorityClasses.low}">${ticket.priority}</span></td>
                    <td class="px-5 py-3.5 text-gray-600 border-r border-gray-100">
                        <span class="inline-flex items-center gap-1.5">
                            ${ticket.requester}
                            ${ticket.requester_is_vip ? '<span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-200">★ VIP</span>' : ''}
                        </span>
                    </td>
                    <td class="px-5 py-3.5 text-right"><a href="${ticket.url}" class="text-green-700 hover:underline font-medium">View</a></td>
                `;
                body.prepend(row);

                setTimeout(() => row.classList.remove('bg-green-50/60'), 3000);
            });
        });
    </script>
</x-app-layout>
