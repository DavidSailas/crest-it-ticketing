{{--
    "With the vendor" card: who has the device or case, the vendor's reference number and when it is due back.
    Shown on a ticket once IT has recorded a vendor. Usage: @include('tickets.partials.vendor-info', ['ticket' => $ticket])
--}}
@if(filled($ticket->vendor_name))
    @php
        $withVendor = $ticket->status === 'pending_vendor';
        $due = $ticket->vendor_expected_at;
        $overdue = $withVendor && $due && $due->lt(today());
        $sent = $ticket->vendor_sent_at;
        $returned = $ticket->vendor_returned_at;
        $daysAway = $sent ? (int) $sent->diffInDays($withVendor ? now() : ($returned ?? now())) : null;
        $viewer = auth()->user();
        $canMark = $withVendor && $viewer && ($viewer->isItSupport() || $viewer->isAdmin());
    @endphp
    <div class="mt-4 rounded-lg border {{ $withVendor ? 'border-orange-200 bg-orange-50/60' : 'border-gray-200 bg-gray-50' }} px-4 py-3">
        <div class="flex flex-wrap items-center gap-2 mb-2">
            <p class="text-xs font-semibold uppercase tracking-wide {{ $withVendor ? 'text-orange-700' : 'text-gray-500' }}">Vendor</p>
            @if($withVendor)
                <span class="rounded-full bg-orange-100 px-2 py-0.5 text-[11px] font-semibold text-orange-700">With the vendor now</span>
            @elseif($returned)
                <span class="rounded-full bg-green-100 px-2 py-0.5 text-[11px] font-semibold text-green-700">Returned</span>
            @endif
        </div>

        <dl class="grid grid-cols-1 gap-x-6 gap-y-1.5 text-sm sm:grid-cols-3">
            <div>
                <dt class="text-xs text-gray-500">Vendor / supplier</dt>
                <dd class="font-medium text-gray-900">{{ $ticket->vendor_name }}</dd>
            </div>
            @if(filled($ticket->vendor_reference))
                <div>
                    <dt class="text-xs text-gray-500">Case / RMA no.</dt>
                    <dd class="font-mono text-gray-900">{{ $ticket->vendor_reference }}</dd>
                </div>
            @endif
            @if($due && $withVendor)
                <div>
                    <dt class="text-xs text-gray-500">Expected back</dt>
                    <dd class="{{ $overdue ? 'font-semibold text-red-600' : 'text-gray-900' }}">
                        {{ $due->format('M j, Y') }}@if($overdue) · overdue @endif
                    </dd>
                </div>
            @endif
        </dl>

        {{-- Timeline: sent -> returned --}}
        @if($sent)
            <p class="mt-2.5 text-xs text-gray-500">
                Sent {{ $sent->format('M j, Y') }}
                @if($withVendor)
                    · {{ $daysAway === 0 ? 'with the vendor since today' : 'with the vendor for '.$daysAway.' '.\Illuminate\Support\Str::plural('day', $daysAway) }}
                @elseif($returned)
                    · returned {{ $returned->format('M j, Y') }} · took {{ $daysAway }} {{ \Illuminate\Support\Str::plural('day', $daysAway) }}
                @endif
            </p>
        @endif

        @if($canMark)
            <form method="POST" action="{{ route('tickets.vendor-returned', $ticket) }}" class="mt-3"
                  onsubmit="return confirm('Mark this as returned by the vendor? The ticket goes back to In Progress and the requester is told.');">
                @csrf
                @method('PATCH')
                <button type="submit" class="inline-flex items-center rounded-lg border border-orange-300 bg-white px-3.5 py-2 text-sm font-medium text-orange-800 shadow-sm hover:bg-orange-50">
                    Vendor returned it
                </button>
            </form>
        @endif
    </div>
@endif
