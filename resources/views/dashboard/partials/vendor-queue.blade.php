{{--
    "Awaiting vendor" list for IT Support and admins: everything that is with a supplier or service provider,
    nearest expected date first, with how long it has been away and a red mark when it is overdue.
    Renders nothing when no ticket is with a vendor.
--}}
@php
    $vendorBase = \App\Models\Ticket::where('status', 'pending_vendor');
    $vendorTotal = (clone $vendorBase)->count();
@endphp

@if($vendorTotal > 0)
    @php
        $vendorTickets = (clone $vendorBase)
            ->with('assignee:id,name')
            ->orderByRaw('vendor_expected_at IS NULL')
            ->orderBy('vendor_expected_at')
            ->orderBy('vendor_sent_at')
            ->limit(8)
            ->get();
        $vendorOverdue = (clone $vendorBase)->whereDate('vendor_expected_at', '<', today())->count();
    @endphp

    <section class="rounded-2xl border border-orange-100 bg-white shadow-sm" aria-label="Tickets awaiting a vendor">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-orange-100 bg-orange-50/60 px-5 py-3.5 rounded-t-2xl">
            <div class="flex items-center gap-2.5">
                <span class="h-2.5 w-2.5 rounded-full bg-orange-500"></span>
                <h3 class="text-sm font-semibold text-gray-800">Awaiting vendor</h3>
                <span class="text-xs text-gray-500">{{ $vendorTotal }} {{ $vendorTotal === 1 ? 'ticket' : 'tickets' }}</span>
                @if($vendorOverdue > 0)
                    <span class="rounded-full bg-red-50 px-2 py-0.5 text-[11px] font-semibold text-red-700 ring-1 ring-inset ring-red-200">{{ $vendorOverdue }} overdue</span>
                @endif
            </div>
            <a href="{{ route('tickets.index', ['status' => 'pending_vendor']) }}" class="text-xs font-medium text-green-700 hover:underline">View all</a>
        </div>

        <ul class="divide-y divide-gray-100">
            @foreach($vendorTickets as $vt)
                @php
                    $due = $vt->vendor_expected_at;
                    $late = $due && $due->lt(today());
                    $away = $vt->vendor_sent_at ? (int) $vt->vendor_sent_at->diffInDays(now()) : null;
                @endphp
                <li>
                    <a href="{{ route('tickets.show', $vt) }}" class="flex flex-col gap-1 px-5 py-3 hover:bg-gray-50 sm:flex-row sm:items-center sm:justify-between sm:gap-4">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-gray-900">
                                <span class="font-mono text-xs font-semibold text-gray-400">{{ $vt->ticket_number }}</span>
                                {{ $vt->title }}
                            </p>
                            <p class="mt-0.5 truncate text-xs text-gray-500">
                                With <span class="font-medium text-gray-700">{{ $vt->vendor_name ?: 'a vendor' }}</span>
                                @if($vt->vendor_reference) · {{ $vt->vendor_reference }} @endif
                                · {{ $vt->assignee?->name ?? 'Unassigned' }}
                            </p>
                        </div>
                        <div class="flex shrink-0 items-center gap-3 text-xs">
                            @if($away !== null)
                                <span class="text-gray-400">{{ $away === 0 ? 'sent today' : 'away '.$away.' '.\Illuminate\Support\Str::plural('day', $away) }}</span>
                            @endif
                            @if($due)
                                <span class="{{ $late ? 'font-semibold text-red-600' : 'text-gray-600' }}">
                                    {{ $late ? 'Overdue · ' : 'Due ' }}{{ $due->format('M j') }}
                                </span>
                            @else
                                <span class="text-gray-400">No date set</span>
                            @endif
                        </div>
                    </a>
                </li>
            @endforeach
        </ul>

        @if($vendorTotal > $vendorTickets->count())
            <div class="rounded-b-2xl border-t border-gray-100 px-5 py-2.5 text-xs text-gray-500">
                and {{ $vendorTotal - $vendorTickets->count() }} more —
                <a href="{{ route('tickets.index', ['status' => 'pending_vendor']) }}" class="font-medium text-green-700 hover:underline">view all</a>
            </div>
        @endif
    </section>
@endif
