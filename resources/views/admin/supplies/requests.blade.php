@php
    $badge = [
        'pending' => 'bg-sky-50 text-sky-700 ring-sky-200',
        'issued' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'short' => 'bg-orange-50 text-orange-700 ring-orange-300',
        'cancelled' => 'bg-gray-100 text-gray-500 ring-gray-200',
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center gap-3">
            <div class="min-w-0">
                <a href="{{ route('supplies.index') }}" class="inline-flex items-center gap-1 text-xs font-medium text-gray-400 hover:text-gray-700">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
                    All supplies
                </a>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight mt-0.5">{{ $isStaff ? 'My supply requests' : 'Supply requests' }}</h2>
            </div>
        </div>
    </x-slot>

    <div class="py-8 max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

        @if(session('status'))
            <div class="p-3 bg-green-50 text-green-800 text-sm rounded-lg border border-green-100">{{ session('status') }}</div>
        @endif

        <div class="flex items-center justify-between gap-3 flex-wrap">
            <div class="inline-flex rounded-lg border border-gray-200 bg-white p-1 text-sm">
                @foreach(['open' => 'Open', 'all' => 'All'] as $key => $label)
                    <a href="{{ route('supplies.requests', ['show' => $key]) }}"
                       class="inline-flex items-center gap-2 rounded-md px-3.5 py-1.5 font-medium transition {{ $filter === $key ? 'bg-green-50 text-green-800 shadow-sm' : 'text-gray-500 hover:text-gray-800' }}">
                        {{ $label }}
                        <span class="text-xs {{ $filter === $key ? 'text-green-700' : 'text-gray-400' }}">{{ $counts[$key] }}</span>
                    </a>
                @endforeach
            </div>
            <p class="text-xs text-gray-400 max-w-md">
                {{ $isStaff
                    ? 'Each request is a ticket. The stock is updated once IT Support marks it Resolved.'
                    : 'Stock is deducted automatically when you mark the request\'s ticket Resolved.' }}
            </p>
        </div>

        <div class="md:bg-white md:shadow-sm md:rounded-2xl md:border md:border-gray-100 md:overflow-hidden">
            @if($requests->isEmpty())
                <div class="flex flex-col items-center justify-center rounded-2xl border border-gray-100 bg-white px-6 py-14 text-center shadow-sm md:rounded-none md:border-0 md:shadow-none">
                    <p class="text-gray-700 font-medium">{{ $filter === 'open' ? 'No open requests' : 'No requests yet' }}</p>
                    <p class="text-gray-400 text-sm mt-1">
                        {{ $isStaff ? 'Find what you need in IT Supplies and press Request.' : 'When staff request supplies they will show up here.' }}
                    </p>
                    <a href="{{ route('supplies.index') }}" class="mt-4 inline-flex items-center rounded-lg px-4 py-2 text-sm font-semibold text-white" style="background-color:#1a6b3c;">Browse supplies</a>
                </div>
            @else
                {{-- Phones: one card per request --}}
                <div class="space-y-3 md:hidden">
                    @foreach($requests as $r)
                        @php $status = $r->display_status; @endphp
                        <x-m-card :href="route('tickets.show', $r->ticket)" :label="'Open ticket '.$r->ticket->ticket_number">
                            <x-slot name="lead"><x-supply-image :supply="$r->supply" size="md" /></x-slot>
                            <x-slot name="title">{{ $r->supply->name }}</x-slot>
                            <x-slot name="sub">
                                <span class="font-mono font-semibold text-gray-600">{{ $r->ticket->ticket_number }}</span> · {{ $r->purpose }}
                                <span class="mt-1.5 flex flex-wrap items-center gap-1.5">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset {{ $badge[$status] ?? $badge['pending'] }}">{{ $r->status_label }}</span>
                                    @if($status === 'pending')
                                        <span class="text-[11px] text-gray-400">Ticket {{ strtolower(\App\Models\Ticket::statusLabel($r->ticket->status)) }}</span>
                                    @elseif($status === 'issued' && $r->issued_at)
                                        <span class="text-[11px] text-gray-400">{{ $r->issued_at->format('M j, g:i A') }}</span>
                                    @endif
                                </span>
                            </x-slot>

                            <x-m-meta label="Quantity">
                                <span class="font-semibold tabular-nums">{{ number_format($r->quantity) }}</span>
                                <span class="text-xs text-gray-400">{{ $r->supply->unit }}</span>
                            </x-m-meta>
                            <x-m-meta label="Requested">{{ $r->created_at->format('M j, Y') }}</x-m-meta>
                            @unless($isStaff)
                                <x-m-meta label="Requested by" wide>{{ $r->requester?->name ?? 'a removed user' }}</x-m-meta>
                            @endunless

                            <x-slot name="actions">
                                <a href="{{ route('tickets.show', $r->ticket) }}" class="btn btn-soft btn-sm flex-1">Open ticket</a>
                                <a href="{{ route('supplies.show', $r->supply) }}" class="btn btn-secondary btn-sm flex-1">View item</a>
                            </x-slot>
                        </x-m-card>
                    @endforeach
                </div>

                {{-- Tablets and up: the table --}}
                <table class="hidden w-full text-sm md:table">
                    <thead>
                        <tr class="asset-thead border-b border-gray-200" style="background: linear-gradient(90deg, #e6f3eb 0%, #f4faf6 100%);">
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Item</th>
                            @unless($isStaff)
                                <th class="hidden md:table-cell px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Requested by</th>
                            @endunless
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 whitespace-nowrap">Qty</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Status</th>
                            <th class="hidden sm:table-cell px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Requested</th>
                            <th class="px-3 sm:px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($requests as $r)
                            @php $status = $r->display_status; @endphp
                            <tr class="hover:bg-green-50/40 transition-colors">
                                <td class="px-4 py-3.5 align-top max-w-0 w-full">
                                    <div class="flex items-start gap-3">
                                        <x-supply-image :supply="$r->supply" size="sm" />
                                        <div class="min-w-0">
                                            <a href="{{ route('supplies.show', $r->supply) }}" class="font-medium text-gray-800 hover:text-green-800 truncate block">{{ $r->supply->name }}</a>
                                            <p class="text-xs text-gray-400 mt-0.5 truncate">
                                                <span class="font-mono">{{ $r->ticket->ticket_number }}</span> · {{ $r->purpose }}
                                            </p>
                                            @unless($isStaff)
                                                <p class="md:hidden text-xs text-gray-500 mt-1">by {{ $r->requester?->name ?? 'a removed user' }}</p>
                                            @endunless
                                        </div>
                                    </div>
                                </td>
                                @unless($isStaff)
                                    <td class="hidden md:table-cell px-4 py-3.5 align-top text-gray-600 whitespace-nowrap">{{ $r->requester?->name ?? 'a removed user' }}</td>
                                @endunless
                                <td class="px-4 py-3.5 align-top whitespace-nowrap font-semibold tabular-nums text-gray-800">
                                    {{ number_format($r->quantity) }} <span class="text-xs font-normal text-gray-400">{{ $r->supply->unit }}</span>
                                </td>
                                <td class="px-4 py-3.5 align-top whitespace-nowrap">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset {{ $badge[$status] ?? $badge['pending'] }}">{{ $r->status_label }}</span>
                                    @if($status === 'pending')
                                        <p class="text-[11px] text-gray-400 mt-1">Ticket {{ strtolower(\App\Models\Ticket::statusLabel($r->ticket->status)) }}</p>
                                    @elseif($status === 'issued' && $r->issued_at)
                                        <p class="text-[11px] text-gray-400 mt-1">{{ $r->issued_at->format('M j, g:i A') }}</p>
                                    @endif
                                </td>
                                <td class="hidden sm:table-cell px-4 py-3.5 align-top whitespace-nowrap text-gray-500">{{ $r->created_at->format('M j, Y') }}</td>
                                <td class="px-3 sm:px-4 py-3.5 align-top text-right whitespace-nowrap">
                                    <a href="{{ route('tickets.show', $r->ticket) }}" class="inline-flex items-center rounded-md soft-green px-2.5 py-1 font-medium text-xs" style="background:#ecf7f0;color:#1a6b3c;">Open ticket</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        @if($requests->hasPages())
            <div class="bg-white border border-gray-200 rounded-xl px-4 py-3.5">{{ $requests->links() }}</div>
        @endif
    </div>
</x-app-layout>
