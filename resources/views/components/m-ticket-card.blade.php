@props(['ticket', 'requester' => false, 'assignee' => false, 'department' => false, 'dates' => false, 'mine' => false, 'resolvedAsClosed' => false, 'created' => false])

{{-- A ticket as a phone-sized card (dashboards). The whole card opens the ticket. --}}
<x-m-card :href="route('tickets.show', $ticket)" :label="'Open ticket '.$ticket->ticket_number"
          :class="$ticket->priority === 'critical' ? 'border-l-4 border-l-red-500' : ''">
    <x-slot name="title">{{ $ticket->title }}</x-slot>
    <x-slot name="sub">
        <span class="font-mono font-semibold text-gray-600">{{ $ticket->ticket_number }}</span>
        <span class="mt-1.5 flex flex-wrap items-center gap-1.5">
            <x-priority-badge :priority="$ticket->priority" />
            @if($resolvedAsClosed && $ticket->status === 'resolved')
                <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600">Closed</span>
            @else
                <x-status-badge :status="$ticket->status" />
            @endif
        </span>
    </x-slot>
    <x-slot name="badge">
        <svg class="mt-1 h-5 w-5 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
    </x-slot>

    @if($requester)
        <x-m-meta label="Requested by">
            <span class="inline-flex flex-wrap items-center gap-1.5">{{ $ticket->creator->name }}@if($ticket->creator->is_vip)<x-vip-badge size="compact" />@endif</span>
        </x-m-meta>
    @endif
    @if($department)
        <x-m-meta label="Department">{{ $ticket->department ?? '—' }}</x-m-meta>
    @endif
    @if($assignee)
        <x-m-meta label="Assigned to">
            @if($ticket->assignee)
                {{ $ticket->assignee->name }}@if($mine) <span class="ml-1 rounded bg-green-100 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-green-700">You</span>@endif
            @else
                <span class="text-gray-400">{{ $ticket->isCancelled() ? 'N/A' : 'Unassigned' }}</span>
            @endif
        </x-m-meta>
    @endif
    @if($created && ! $dates)
        <x-m-meta label="Created">{{ $ticket->created_at->format('M j, Y') }}</x-m-meta>
    @endif
    @if($dates)
        <x-m-meta label="Created">{{ $ticket->created_at->format('M j, Y') }}</x-m-meta>
        <x-m-meta label="Resolved">{{ $ticket->resolved_at?->format('M j, Y') ?? '—' }}</x-m-meta>
    @endif
</x-m-card>
