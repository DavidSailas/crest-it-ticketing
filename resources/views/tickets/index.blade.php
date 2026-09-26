@php
    $isStaff = auth()->user()->isStaff();
    $isItSupport = auth()->user()->isItSupport();
    $isAdmin = auth()->user()->isAdmin();
    // Requested by is redundant on the staff view (it's always themselves);
    // Assigned to is redundant on the IT support view (it's always themselves).
    $showRequestedBy = ! $isStaff;
    $showAssignedTo = ! $isItSupport;
    $columnCount = 6 + ($showRequestedBy ? 1 : 0) + ($showAssignedTo ? 1 : 0);
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap gap-3 justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                @if($isStaff) My Tickets
                @elseif($isItSupport) My Tickets
                @else All Tickets @endif
            </h2>
            <div class="flex items-center gap-2">
                @if($isAdmin)
                    {{-- Report export — reflects whatever search/status/priority/date filters are
                         currently applied above, so the download always matches what's on screen. --}}
                    <div class="relative" x-data="{ showExport: false }">
                        <button @click="showExport = !showExport" @click.outside="showExport = false"
                                class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg text-sm font-medium text-gray-600 border border-gray-300 hover:bg-gray-50">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v13.5m0 0l-4.5-4.5M12 16.5l4.5-4.5M4.5 19.5h15" /></svg>
                            Export
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
                        </button>
                        <div x-show="showExport" x-cloak x-transition
                             class="absolute right-0 mt-1.5 w-48 bg-white rounded-lg shadow-lg border border-gray-100 py-1 z-20">
                            <a href="{{ route('admin.tickets.export.pdf', request()->query()) }}" class="flex items-center gap-2 px-3.5 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                <svg class="w-4 h-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                Export as PDF
                            </a>
                            <a href="{{ route('admin.tickets.export.excel', request()->query()) }}" class="flex items-center gap-2 px-3.5 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                <svg class="w-4 h-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                Export as Excel
                            </a>
                        </div>
                    </div>
                @endif
                @if($isStaff)
                    <a href="{{ route('tickets.create') }}" class="inline-flex items-center gap-2 px-4 py-2 text-white rounded-lg text-sm font-semibold shadow-sm transition hover:opacity-90" style="background-color:#1a6b3c;">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                        New Ticket
                    </a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-6 sm:py-8 max-w-[96rem] mx-auto px-4 sm:px-6 lg:px-8">
        @if(session('status'))
            <div class="mb-4 p-3 bg-green-50 text-green-800 text-sm rounded-lg border border-green-100">{{ session('status') }}</div>
        @endif

        @if($isItSupport)
            <div class="flex items-start gap-2.5 mb-5 px-4 py-3 rounded-lg bg-blue-50/60 border border-blue-100">
                <svg class="w-4 h-4 text-blue-500 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <p class="text-sm text-blue-900/80">Tickets you're currently working on or have already closed out. Unclaimed tickets waiting for pickup live on your <a href="{{ route('dashboard') }}" class="font-semibold underline underline-offset-2 decoration-blue-300 hover:decoration-blue-500">dashboard</a>.</p>
            </div>
        @endif

        {{-- Filters --}}
        <div class="bg-white shadow-sm rounded-xl border border-gray-200 px-4 sm:px-5 py-4 mb-5">
            <form method="GET" action="{{ route('tickets.index') }}" class="flex flex-wrap items-end gap-3">
                <div class="flex-1 min-w-[14rem]">
                    <label for="filter-search" class="block text-xs font-medium text-gray-500 mb-1">Search</label>
                    <div class="relative">
                        <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M11 19a8 8 0 100-16 8 8 0 000 16z" /></svg>
                        <input type="text" name="search" id="filter-search" value="{{ $filters['search'] }}"
                               placeholder="Ticket #, category, department{{ $showRequestedBy ? ', requester' : '' }}…"
                               class="w-full pl-9 rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700">
                    </div>
                </div>

                <div class="w-full sm:w-40">
                    <label for="filter-status" class="block text-xs font-medium text-gray-500 mb-1">Status</label>
                    <select name="status" id="filter-status" class="w-full rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700">
                        <option value="">All statuses</option>
                        @foreach($statusOptions as $option)
                            <option value="{{ $option }}" @selected($filters['status'] === $option)>{{ str_replace('_', ' ', ucfirst($option)) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="w-full sm:w-36">
                    <label for="filter-priority" class="block text-xs font-medium text-gray-500 mb-1">Priority</label>
                    <select name="priority" id="filter-priority" class="w-full rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700">
                        <option value="">All priorities</option>
                        @foreach(['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'critical' => 'Critical'] as $value => $label)
                            <option value="{{ $value }}" @selected($filters['priority'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="w-1/2 sm:w-36">
                    <label for="filter-date-from" class="block text-xs font-medium text-gray-500 mb-1">From</label>
                    <input type="date" name="date_from" id="filter-date-from" value="{{ $filters['dateFrom'] }}"
                           max="{{ $filters['dateTo'] ?: '' }}"
                           class="w-full rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700">
                </div>

                <div class="w-1/2 sm:w-36">
                    <label for="filter-date-to" class="block text-xs font-medium text-gray-500 mb-1">To</label>
                    <input type="date" name="date_to" id="filter-date-to" value="{{ $filters['dateTo'] }}"
                           min="{{ $filters['dateFrom'] ?: '' }}"
                           class="w-full rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700">
                </div>

                <div class="flex items-center gap-3">
                    <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-white text-sm font-semibold shadow-sm transition hover:opacity-90" style="background-color:#1a6b3c;">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z" /></svg>
                        Filter
                    </button>
                    @if($hasActiveFilters)
                        <a href="{{ route('tickets.index') }}" class="text-sm text-gray-500 hover:text-gray-700 underline underline-offset-2">Clear</a>
                    @endif
                </div>
            </form>
        </div>

        {{-- A lean column set, horizontal-only dividers, and a fixed layout —
             this reads as one clean sheet instead of a boxed grid, and never
             needs to scroll. Anything not shown here (department, location,
             full description) is one click away on the ticket page. --}}
        <div class="bg-white shadow-sm rounded-xl border border-gray-200 overflow-hidden">
            <table class="w-full text-sm border-collapse">
                <thead>
                    <tr class="bg-gray-50/80 border-b border-gray-200">
                        <th class="px-4 sm:px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 whitespace-nowrap">Ticket ID</th>
                        <th class="px-4 sm:px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Category</th>
                        <th class="hidden sm:table-cell px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Priority</th>
                        <th class="px-4 sm:px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Status</th>
                        @if($showRequestedBy)
                            <th class="hidden lg:table-cell px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Requested by</th>
                        @endif
                        @if($showAssignedTo)
                            <th class="hidden lg:table-cell px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Assigned to</th>
                        @endif
                        <th class="hidden md:table-cell px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 whitespace-nowrap">Created</th>
                        <th class="w-12 px-3 sm:px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($tickets as $ticket)
                        <tr class="group hover:bg-gray-50/60 transition-colors">
                            <td class="px-4 sm:px-5 py-4 whitespace-nowrap">
                                <span class="font-mono text-xs font-semibold tracking-tight text-gray-500 group-hover:text-gray-700">{{ $ticket->ticket_number }}</span>
                            </td>
                            <td class="px-4 sm:px-5 py-4 min-w-[10rem]">
                                <p class="font-medium text-gray-800 flex items-center gap-1.5">
                                    {{ $ticket->category }}
                                    @if($ticket->attachment_path)
                                        <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" title="Has attachment"><path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364l10.94-10.94A3 3 0 1119.5 7.372L8.552 18.32m.009-.01l-.01.01m5.699-9.941l-7.81 7.81a1.5 1.5 0 002.112 2.13" /></svg>
                                    @endif
                                </p>
                                @if($ticket->subcategory)
                                    <p class="text-xs text-gray-400 mt-0.5">{{ $ticket->subcategory }}</p>
                                @endif
                                {{-- Columns that are hidden on smaller screens fold into this line --}}
                                <div class="lg:hidden mt-1.5 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-gray-500">
                                    <span class="sm:hidden"><x-priority-badge :priority="$ticket->priority" /></span>
                                    @if($showRequestedBy)
                                        <span class="inline-flex items-center gap-1.5">
                                            {{ $ticket->creator->name }}
                                            @if($ticket->creator->is_vip)
                                                <x-vip-badge size="compact" />
                                            @endif
                                        </span>
                                    @endif
                                    @if($showAssignedTo)
                                        <span class="text-gray-400">{{ $ticket->assignee ? '→ '.$ticket->assignee->name : 'Unassigned' }}</span>
                                    @endif
                                    <span class="md:hidden text-gray-400">{{ $ticket->created_at->format('M j, Y') }}</span>
                                </div>
                            </td>
                            <td class="hidden sm:table-cell px-5 py-4"><x-priority-badge :priority="$ticket->priority" /></td>
                            <td class="px-4 sm:px-5 py-4">
                                <x-status-badge :status="$ticket->status" />
                            </td>
                            @if($showRequestedBy)
                                <td class="hidden lg:table-cell px-5 py-4 text-gray-600">
                                    <span class="inline-flex items-center gap-1.5">
                                        {{ $ticket->creator->name }}
                                        @if($ticket->creator->is_vip)
                                            <x-vip-badge size="compact" />
                                        @endif
                                    </span>
                                </td>
                            @endif
                            @if($showAssignedTo)
                                <td class="hidden lg:table-cell px-5 py-4 text-gray-600">
                                    @if($ticket->assignee)
                                        {{ $ticket->assignee->name }}
                                    @else
                                        <span class="text-gray-300">—</span>
                                    @endif
                                </td>
                            @endif
                            <td class="hidden md:table-cell px-5 py-4 text-gray-500 whitespace-nowrap">{{ $ticket->created_at->format('M j, Y') }}</td>
                            <td class="px-3 sm:px-5 py-4 text-right">
                                <a href="{{ route('tickets.show', $ticket) }}"
                                   class="inline-flex items-center justify-center w-8 h-8 rounded-full text-gray-400 hover:text-white transition-colors"
                                   onmouseover="this.style.backgroundColor='#1a6b3c'" onmouseout="this.style.backgroundColor='transparent'"
                                   title="View ticket">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $columnCount }}" class="px-5 py-16">
                                <div class="flex flex-col items-center text-center">
                                    <div class="w-12 h-12 rounded-full bg-gray-50 flex items-center justify-center mb-3">
                                        <svg class="w-6 h-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h.01M15 12h.01M9 16h.01M15 16h.01M4 7h16a1 1 0 011 1v2a2 2 0 000 4v2a1 1 0 01-1 1H4a1 1 0 01-1-1v-2a2 2 0 000-4V8a1 1 0 011-1z" /></svg>
                                    </div>
                                    <p class="text-gray-600 font-medium">No tickets found</p>
                                    <p class="text-gray-400 text-sm mt-1">
                                        @if($hasActiveFilters)
                                            No tickets match your filters. <a href="{{ route('tickets.index') }}" class="text-green-700 font-medium underline underline-offset-2">Clear them</a> to see everything.
                                        @elseif($isStaff)
                                            Submit a request and it'll show up here.
                                        @else
                                            Nothing matches right now.
                                        @endif
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4 bg-white border border-gray-200 rounded-xl px-4 py-3.5">{{ $tickets->links() }}</div>
    </div>
</x-app-layout>
