{{--
    "Waiting for your approval" card for people who approve supply requests.
    Included at the top of the staff home page and the admin dashboard; renders nothing when there is nothing to approve.
--}}
@php
    $bannerUser = auth()->user();
    $bannerCount = $bannerUser->supplyApprovalsWaiting();
    $bannerItems = $bannerCount > 0
        ? \App\Models\SupplyRequest::awaitingFor($bannerUser)->with(['supply', 'requester:id,name,location'])->oldest()->limit(3)->get()
        : collect();
@endphp

@if($bannerCount > 0)
    <div class="rounded-xl border border-amber-200 bg-amber-50/70 px-4 py-4 sm:px-6" role="region" aria-label="Supply requests waiting for your approval">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-start gap-3 min-w-0">
                <span class="mt-0.5 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-700" aria-hidden="true">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </span>
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-amber-900">
                        {{ $bannerCount }} supply {{ \Illuminate\Support\Str::plural('request', $bannerCount) }} {{ $bannerCount === 1 ? 'is' : 'are' }} waiting for your approval
                    </p>
                    <ul class="mt-1.5 space-y-1">
                        @foreach($bannerItems as $item)
                            <li class="text-xs text-amber-900/80 truncate">
                                <a href="{{ route('supplies.requests', ['show' => 'approval', 'focus' => $item->id]) }}" class="hover:underline">
                                    <span class="font-medium">{{ number_format($item->quantity) }} {{ $item->supply->unit }} × {{ $item->supply->name }}</span>
                                    · {{ $item->requester?->name ?? 'a removed user' }}
                                    · waiting {{ $item->created_at->diffForHumans(null, true) }}
                                </a>
                            </li>
                        @endforeach
                        @if($bannerCount > $bannerItems->count())
                            <li class="text-xs text-amber-900/60">and {{ $bannerCount - $bannerItems->count() }} more…</li>
                        @endif
                    </ul>
                </div>
            </div>
            <a href="{{ route('supplies.requests', ['show' => 'approval']) }}"
               class="inline-flex shrink-0 items-center justify-center rounded-lg px-4 py-2.5 text-sm font-semibold text-white shadow-sm sm:py-2"
               style="background-color:#1a6b3c;">Review &amp; approve</a>
        </div>
    </div>
@endif
