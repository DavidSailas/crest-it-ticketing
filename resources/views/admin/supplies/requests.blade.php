@php
    $badge = [
        'pending' => 'bg-sky-50 text-sky-700 ring-sky-200',
        'issued' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'short' => 'bg-orange-50 text-orange-700 ring-orange-300',
        'cancelled' => 'bg-gray-100 text-gray-500 ring-gray-200',
        'for_approval' => 'bg-amber-50 text-amber-700 ring-amber-200',
        'declined' => 'bg-rose-50 text-rose-700 ring-rose-200',
    ];
    $tabs = $canApprove
        ? ['approval' => 'Needs approval', 'open' => 'Open', 'all' => 'All']
        : ['open' => 'Open', 'all' => 'All'];
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
        @if(session('error'))
            <div class="p-3 bg-red-50 text-red-700 text-sm rounded-lg border border-red-100" role="alert">{{ session('error') }}</div>
        @endif
        @if($focusNote)
            <div class="p-3 bg-sky-50 text-sky-800 text-sm rounded-lg border border-sky-100">{{ $focusNote }}</div>
        @endif

        <div class="flex items-center justify-between gap-3 flex-wrap">
            <div class="inline-flex rounded-lg border border-gray-200 bg-white p-1 text-sm">
                @foreach($tabs as $key => $label)
                    <a href="{{ route('supplies.requests', ['show' => $key]) }}"
                       class="inline-flex items-center gap-2 rounded-md px-3.5 py-1.5 font-medium transition {{ $filter === $key ? 'bg-green-50 text-green-800 shadow-sm' : 'text-gray-500 hover:text-gray-800' }}">
                        {{ $label }}
                        <span class="text-xs {{ $filter === $key ? 'text-green-700' : ($key === 'approval' && $counts[$key] > 0 ? 'font-semibold text-amber-600' : 'text-gray-400') }}">{{ $counts[$key] }}</span>
                    </a>
                @endforeach
            </div>
            <p class="text-xs text-gray-400 max-w-md">
                @if($filter === 'approval')
                    Approve a request and it becomes a ticket for IT Support. Decline it and the requester is told why.
                @elseif($isStaff)
                    Your head approves a request first, then it becomes a ticket. Stock is updated once IT Support marks it Resolved.
                @else
                    Stock is deducted automatically when you mark the request's ticket Resolved.
                @endif
            </p>
        </div>

        <div class="md:bg-white md:shadow-sm md:rounded-2xl md:border md:border-gray-100 md:overflow-hidden">
            @if($requests->isEmpty())
                <div class="flex flex-col items-center justify-center rounded-2xl border border-gray-100 bg-white px-6 py-14 text-center shadow-sm md:rounded-none md:border-0 md:shadow-none">
                    <p class="text-gray-700 font-medium">{{ $filter === 'approval' ? 'Nothing is waiting for approval' : ($filter === 'open' ? 'No open requests' : 'No requests yet') }}</p>
                    <p class="text-gray-400 text-sm mt-1">
                        @if($filter === 'approval')
                            New supply requests from staff will appear here for you to approve.
                        @else
                            {{ $isStaff ? 'Find what you need in IT Supplies and press Request.' : 'When staff request supplies they will show up here.' }}
                        @endif
                    </p>
                    <a href="{{ route('supplies.index') }}" class="mt-4 inline-flex items-center rounded-lg px-4 py-2 text-sm font-semibold text-white" style="background-color:#1a6b3c;">Browse supplies</a>
                </div>
            @elseif($filter === 'approval')
                {{-- Department heads: every request waiting for a decision --}}
                <div class="space-y-3 md:space-y-0 md:divide-y md:divide-gray-100">
                    @foreach($requests as $r)
                        @php
                            $available = $r->supply->available_quantity;
                            $short = $available < $r->quantity;
                        @endphp
                        @php
                            $waitedDays = (int) $r->created_at->diffInDays(now());
                            $isFocus = $focus === $r->id;
                        @endphp
                        <div id="request-{{ $r->id }}" @if($isFocus) data-focus @endif
                             class="rounded-2xl border bg-white p-4 shadow-sm md:rounded-none md:border-0 md:shadow-none md:px-5 md:py-4 {{ $isFocus ? 'border-green-400 ring-2 ring-green-600/25 md:bg-green-50/40' : 'border-gray-100' }}">
                            <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                                <div class="flex items-start gap-3 min-w-0">
                                    <x-supply-image :supply="$r->supply" size="md" />
                                    <div class="min-w-0">
                                        <a href="{{ route('supplies.show', $r->supply) }}" class="font-semibold text-gray-900 hover:text-green-800">{{ $r->supply->name }}</a>
                                        <p class="text-sm text-gray-700 mt-0.5">
                                            <span class="font-semibold tabular-nums">{{ number_format($r->quantity) }}</span> {{ $r->supply->unit }}
                                            @if($r->urgency === 'urgent')
                                                <span class="ml-1.5 inline-flex items-center rounded-full bg-rose-50 px-2 py-0.5 text-[11px] font-medium text-rose-700 ring-1 ring-inset ring-rose-200">Urgent</span>
                                            @endif
                                        </p>
                                        <p class="text-sm text-gray-600 mt-1">For: {{ $r->purpose }}</p>
                                        <p class="text-xs text-gray-400 mt-1">
                                            by <span class="text-gray-600">{{ $r->requester?->name ?? 'a removed user' }}</span>
                                            @if($r->requester?->branch_name) · {{ $r->requester->branch_name }} @endif
                                            ·
                                            <span class="{{ $waitedDays >= 1 ? 'font-medium text-amber-700' : '' }}">waiting {{ $r->created_at->diffForHumans(null, true) }}</span>
                                        </p>
                                        <p class="text-xs mt-1 {{ $short ? 'text-orange-700 font-medium' : 'text-gray-400' }}">
                                            {{ number_format($available) }} {{ $r->supply->unit }} available at {{ $r->supply->branch_name }}{{ $short ? ' — not enough for this request' : '' }}
                                        </p>
                                    </div>
                                </div>

                                <div class="flex gap-2 md:shrink-0">
                                    <form method="POST" action="{{ route('supplies.requests.approve', $r) }}" class="flex-1 md:flex-none">
                                        @csrf
                                        <button type="submit" @disabled($available <= 0) class="w-full inline-flex justify-center rounded-lg px-4 py-2 text-sm font-semibold text-white shadow-sm disabled:opacity-50" style="background-color:#1a6b3c;">Approve</button>
                                    </form>
                                    <button type="button" x-data @click="$dispatch('open-modal', 'decline-{{ $r->id }}')"
                                            class="flex-1 md:flex-none inline-flex justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">Decline</button>
                                </div>
                            </div>
                        </div>

                        <x-modal name="decline-{{ $r->id }}" :show="$errors->decline->any() && (int) old('decline_id') === $r->id" maxWidth="md" focusable>
                            <form method="POST" action="{{ route('supplies.requests.decline', $r) }}" novalidate>
                                @csrf
                                <input type="hidden" name="decline_id" value="{{ $r->id }}">
                                <div class="px-6 pt-6 pb-2">
                                    <h2 class="text-lg font-semibold text-gray-900">Decline this request?</h2>
                                    <p class="mt-0.5 text-sm text-gray-600">{{ number_format($r->quantity) }} {{ $r->supply->unit }} × {{ $r->supply->name }} for {{ $r->requester?->name }}</p>
                                </div>
                                <div class="px-6 py-4">
                                    <label for="decline-reason-{{ $r->id }}" class="block text-sm font-medium text-gray-700 mb-1.5">Reason (the requester will see this)</label>
                                    <textarea id="decline-reason-{{ $r->id }}" name="reason" rows="3" maxlength="255" required
                                              class="block w-full rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700">{{ (int) old('decline_id') === $r->id ? old('reason') : '' }}</textarea>
                                    @if((int) old('decline_id') === $r->id && $errors->decline->has('reason'))
                                        <p class="text-red-500 text-xs mt-1.5">{{ $errors->decline->first('reason') }}</p>
                                    @endif
                                </div>
                                <div class="flex flex-col-reverse gap-2 border-t border-gray-100 bg-gray-50 px-6 py-4 sm:flex-row sm:justify-end sm:gap-3">
                                    <button type="button" x-on:click="$dispatch('close')" class="inline-flex justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 sm:py-2">Keep it</button>
                                    <button type="submit" class="inline-flex justify-center rounded-lg bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-rose-700 sm:py-2">Decline request</button>
                                </div>
                            </form>
                        </x-modal>
                    @endforeach
                </div>
            @else
                {{-- Phones: one card per request --}}
                <div class="space-y-3 md:hidden">
                    @foreach($requests as $r)
                        @php $status = $r->display_status; @endphp
                        <x-m-card :href="$r->ticket ? route('tickets.show', $r->ticket) : route('supplies.show', $r->supply)" :label="$r->ticket ? 'Open ticket '.$r->ticket->ticket_number : 'View '.$r->supply->name">
                            <x-slot name="lead"><x-supply-image :supply="$r->supply" size="md" /></x-slot>
                            <x-slot name="title">{{ $r->supply->name }}</x-slot>
                            <x-slot name="sub">
                                <span class="font-mono font-semibold text-gray-600">{{ $r->ticket?->ticket_number ?? 'No ticket yet' }}</span> · {{ $r->purpose }}
                                <span class="mt-1.5 flex flex-wrap items-center gap-1.5">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset {{ $badge[$status] ?? $badge['pending'] }}">{{ $r->status_label }}</span>
                                    @if($status === 'pending')
                                        <span class="text-[11px] text-gray-400">Ticket {{ strtolower(\App\Models\Ticket::statusLabel($r->ticket->status)) }}</span>
                                    @elseif($status === 'issued' && $r->issued_at)
                                        <span class="text-[11px] text-gray-400">{{ $r->issued_at->format('M j, g:i A') }}</span>
                                    @elseif($status === 'for_approval')
                                        <span class="text-[11px] text-gray-400">with your approver</span>
                                    @elseif($status === 'declined')
                                        <span class="text-[11px] text-gray-500">“{{ $r->decline_reason }}”</span>
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
                                @if($r->ticket)
                                    <a href="{{ route('tickets.show', $r->ticket) }}" class="btn btn-soft btn-sm flex-1">Open ticket</a>
                                @elseif($status === 'for_approval' && $r->user_id === auth()->id())
                                    <form method="POST" action="{{ route('supplies.requests.cancel', $r) }}" class="flex-1">
                                        @csrf
                                        <button type="submit" class="btn btn-soft btn-sm w-full">Cancel request</button>
                                    </form>
                                @endif
                                <a href="{{ route('supplies.show', $r->supply) }}" class="btn btn-secondary btn-sm flex-1">View item</a>
                            </x-slot>
                        </x-m-card>
                    @endforeach
                </div>

                {{-- Tablets and up: the table --}}
                <table class="hidden w-full text-sm md:table">
                    <thead>
                        <tr class="asset-thead border-b border-gray-200">
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Item</th>
                            @unless($isStaff)
                                <th class="hidden md:table-cell px-4 py-3 text-left text-xs font-semibold text-gray-500">Requested by</th>
                            @endunless
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 whitespace-nowrap">Qty</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Status</th>
                            <th class="hidden sm:table-cell px-4 py-3 text-left text-xs font-semibold text-gray-500">Requested</th>
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
                                                <span class="font-mono">{{ $r->ticket?->ticket_number ?? 'No ticket yet' }}</span> · {{ $r->purpose }}
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
                                    @elseif($status === 'for_approval')
                                        <p class="text-[11px] text-gray-400 mt-1">With your approver</p>
                                    @elseif($status === 'declined')
                                        <p class="text-[11px] text-gray-500 mt-1 max-w-[16rem] whitespace-normal">“{{ $r->decline_reason }}”@if($r->decliner) — {{ $r->decliner->name }}@endif</p>
                                    @endif
                                </td>
                                <td class="hidden sm:table-cell px-4 py-3.5 align-top whitespace-nowrap text-gray-500">{{ $r->created_at->format('M j, Y') }}</td>
                                <td class="px-3 sm:px-4 py-3.5 align-top text-right whitespace-nowrap">
                                    @if($r->ticket)
                                        <a href="{{ route('tickets.show', $r->ticket) }}" class="inline-flex items-center rounded-md soft-green px-2.5 py-1 font-medium text-xs" style="background:#ecf7f0;color:#1a6b3c;">Open ticket</a>
                                    @elseif($status === 'for_approval' && $r->user_id === auth()->id())
                                        <form method="POST" action="{{ route('supplies.requests.cancel', $r) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="inline-flex items-center rounded-md border border-gray-200 bg-white px-2.5 py-1 font-medium text-xs text-gray-600 hover:bg-gray-50">Cancel</button>
                                        </form>
                                    @else
                                        <a href="{{ route('supplies.show', $r->supply) }}" class="text-xs font-medium text-gray-500 hover:text-gray-800">View item</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        @if($focus)
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    var el = document.querySelector('[data-focus]');
                    if (el) { el.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
                });
            </script>
        @endif

        @if($requests->hasPages())
            <div class="bg-white border border-gray-200 rounded-xl px-4 py-3.5">{{ $requests->links() }}</div>
        @endif
    </div>
</x-app-layout>
