{{--
    Shown at the top of a ticket that was created by an IT Supplies request.
    Included from tickets/partials/live-ticket.blade.php; renders nothing for any other ticket.
--}}
@php
    $supplyRequest = \App\Models\SupplyRequest::with(['supply', 'issuer:id,name'])->where('ticket_id', $ticket->id)->first();
@endphp

@if($supplyRequest && $supplyRequest->supply)
    @php
        $viewer = auth()->user();
        $isIt = $viewer->isItSupport() || $viewer->isAdmin();
        $supply = $supplyRequest->supply;
        $state = $supplyRequest->display_status;   // pending | issued | short | cancelled
        $tone = [
            'pending' => ['border-sky-200', 'bg-sky-50/60', 'bg-sky-100 text-sky-700', 'text-sky-900'],
            'issued' => ['border-emerald-200', 'bg-emerald-50/60', 'bg-emerald-100 text-emerald-700', 'text-emerald-900'],
            'short' => ['border-orange-200', 'bg-orange-50/70', 'bg-orange-100 text-orange-700', 'text-orange-900'],
            'cancelled' => ['border-gray-200', 'bg-gray-50', 'bg-gray-100 text-gray-500', 'text-gray-700'],
        ][$state];
        $notEnough = $state === 'pending' && $supply->quantity < $supplyRequest->quantity;
    @endphp

    <div class="rounded-xl border {{ $tone[0] }} {{ $tone[1] }} px-4 sm:px-6 py-4 flex items-start gap-3">
        <span class="flex items-center justify-center w-9 h-9 rounded-lg shrink-0 {{ $tone[2] }}">
            <x-supply-icon :category="$supply->category" class="w-5 h-5" />
        </span>
        <div class="min-w-0 text-sm">
            <p class="font-semibold {{ $tone[3] }}">
                Supply request · {{ number_format($supplyRequest->quantity) }} {{ $supply->unit }} of
                <a href="{{ route('supplies.show', $supply) }}" class="underline decoration-dotted underline-offset-2 hover:decoration-solid">{{ $supply->name }}</a>
            </p>
            <p class="text-xs text-gray-500 mt-0.5">Needed for: {{ $supplyRequest->purpose }}</p>

            <p class="mt-2 text-xs {{ $tone[3] }}">
                @if($state === 'pending')
                    @if($isIt)
                        The stock is taken out <strong>automatically when you mark this ticket Resolved</strong>.
                        On hand now: {{ number_format($supply->quantity) }} {{ $supply->unit }}.
                    @else
                        IT Support will prepare this. Your stock is updated once they mark the ticket Resolved.
                    @endif
                @elseif($state === 'issued')
                    ✓ {{ number_format($supplyRequest->quantity) }} {{ $supply->unit }} issued
                    @if($supplyRequest->issued_at) on {{ $supplyRequest->issued_at->format('M j, Y g:i A') }} @endif
                    @if($supplyRequest->issuer) by {{ $supplyRequest->issuer->name }} @endif
                    — stock has been updated.
                @elseif($state === 'short')
                    Not deducted — {{ $supplyRequest->note ?: 'there was not enough stock.' }}
                    @if($isIt) Record it with <a href="{{ route('supplies.show', [$supply, 'adjust' => 'out']) }}" class="font-semibold underline">Stock Out</a> once handed over. @endif
                @else
                    Cancelled with the ticket — nothing was deducted and the reserved stock is free again.
                @endif
            </p>

            @if($notEnough && $isIt)
                <p class="mt-2 inline-flex items-center gap-1.5 rounded-md bg-orange-100 px-2 py-1 text-xs font-semibold text-orange-800">
                    Only {{ number_format($supply->quantity) }} on hand — restock before resolving.
                </p>
            @endif
        </div>
    </div>
@endif
