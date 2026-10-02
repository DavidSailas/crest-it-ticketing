<x-app-layout>
    <style>
        html { scroll-behavior: smooth; }
        .rpt-anchor { scroll-margin-top: 4.5rem; }
        .rpt-card { background:#fff; border:1px solid #e5e7eb; border-radius:14px; box-shadow:0 1px 2px rgba(16,24,40,.04); }
        .rpt-bar { transition: opacity .15s ease; }
        .rpt-sticky-nav a[aria-current="true"] { color:#14532d; background:#e8f3ec; }
        @media (prefers-reduced-motion: reduce) { html { scroll-behavior: auto; } }
    </style>

    @php
        // Small helpers used throughout the page
        $initials = fn ($name) => collect(preg_split('/\s+/', trim((string) $name)))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('') ?: '?';
        $deltaChip = function ($delta, $goodIsUp = true) {
            if ($delta === null) return null;
            $up = $delta > 0;
            $flat = $delta == 0;
            $good = $flat ? null : ($goodIsUp ? $up : ! $up);
            $color = $flat ? 'text-gray-500 bg-gray-100' : ($good ? 'text-green-700 bg-green-50 ring-1 ring-green-600/15' : 'text-red-600 bg-red-50 ring-1 ring-red-600/15');
            $arrow = $flat ? 'M5 12h14' : ($up ? 'M12 19V5m0 0l-6 6m6-6l6 6' : 'M12 5v14m0 0l-6-6m6 6l6-6');
            return ['color' => $color, 'arrow' => $arrow, 'text' => ($flat ? 'No change' : ($up ? '+' : '').$delta.'%')];
        };
        $ticketsChip = $comparison ? $deltaChip($comparison['ticketsDelta'], false) : null;
        $rateChip = $comparison ? $deltaChip($comparison['rateDelta'], true) : null;
        $presetRanges = collect($ranges)->except('custom');
    @endphp

    {{-- Page header: title, period and the one primary action --}}
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3.5 min-w-0">
                <div class="min-w-0">
                    <h2 class="font-semibold text-xl text-gray-900 leading-tight">Management report</h2>
                    <p class="text-sm text-gray-500 truncate">IT Service Desk &middot; {{ $rangeLabel }} &middot; Generated {{ now()->format('F j, Y') }}</p>
                </div>
            </div>
            <a href="{{ route('admin.reports.export.pdf', request()->query()) }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm font-semibold text-white shadow-sm transition hover:brightness-110 focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-green-700" style="background-color:#1a6b3c;">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m0 0l-4-4m4 4l4-4M4 17v2a2 2 0 002 2h12a2 2 0 002-2v-2" /></svg>
                Export full report (PDF)
            </a>
        </div>
    </x-slot>

    <div class="py-6 sm:py-8 max-w-[80rem] mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

        {{-- Reporting period: one-click presets, custom dates on demand --}}
        <div class="rpt-card p-3 sm:p-4" x-data="{ custom: {{ $selectedRange === 'custom' ? 'true' : 'false' }} }">
            <div class="flex flex-wrap items-center gap-x-4 gap-y-3">
                <span class="text-sm font-medium text-gray-500 pl-1">Reporting period</span>
                <div class="inline-flex flex-wrap gap-1 p-1 rounded-xl bg-gray-100" role="group" aria-label="Reporting period">
                    @foreach($presetRanges as $value => $label)
                        @php $active = $selectedRange === $value; @endphp
                        <a href="{{ route('admin.reports.index', ['range' => $value]) }}"
                           @if($active) aria-current="true" @endif
                           class="px-3.5 py-1.5 rounded-lg text-sm font-medium transition focus:outline-none focus-visible:ring-2 focus-visible:ring-green-700 {{ $active ? 'bg-white text-green-800 shadow-sm ring-1 ring-gray-200' : 'text-gray-600 hover:text-gray-900 hover:bg-white/60' }}">{{ $label }}</a>
                    @endforeach
                    <button type="button" @click="custom = ! custom" :aria-expanded="custom.toString()"
                            class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg text-sm font-medium transition focus:outline-none focus-visible:ring-2 focus-visible:ring-green-700 {{ $selectedRange === 'custom' ? 'bg-white text-green-800 shadow-sm ring-1 ring-gray-200' : 'text-gray-600 hover:text-gray-900 hover:bg-white/60' }}">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" /></svg>
                        Custom
                    </button>
                </div>
            </div>

            <form method="GET" action="{{ route('admin.reports.index') }}" x-show="custom" x-cloak x-transition.opacity class="mt-3 pt-3 border-t border-gray-100 flex flex-wrap items-end gap-3">
                <input type="hidden" name="range" value="custom">
                <div>
                    <label for="rpt-from" class="block text-xs font-medium text-gray-500 mb-1">From</label>
                    <input id="rpt-from" type="date" name="from" value="{{ $customFrom }}" class="rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700">
                </div>
                <div>
                    <label for="rpt-to" class="block text-xs font-medium text-gray-500 mb-1">To</label>
                    <input id="rpt-to" type="date" name="to" value="{{ $customTo }}" class="rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700">
                </div>
                <button type="submit" class="px-4 py-2 rounded-lg text-sm font-semibold text-white transition hover:brightness-110" style="background-color:#1a6b3c;">Apply dates</button>
            </form>
        </div>

        {{-- Plain-language summary --}}
        <div class="rpt-card p-5 sm:p-6 flex gap-4" style="border-left:4px solid #1a6b3c;">
            <span class="hidden sm:flex w-9 h-9 rounded-lg items-center justify-center shrink-0" style="background-color:#e8f3ec; color:#1a6b3c;">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>
            </span>
            <div class="min-w-0">
                <h3 class="text-sm font-semibold text-gray-900">At a glance</h3>
                <p class="mt-1 text-[15px] leading-relaxed text-gray-600 max-w-3xl">{{ $summary }}</p>
            </div>
        </div>

        {{-- Headline numbers: one connected strip instead of five separate boxes --}}
        <div class="rpt-card overflow-hidden">
            <dl class="grid grid-cols-2 lg:grid-cols-5 divide-y lg:divide-y-0 lg:divide-x divide-gray-100">
                <div class="p-5 border-r border-gray-100 lg:border-r-0">
                    <dt class="text-sm text-gray-500">Tickets created</dt>
                    <dd class="mt-2 flex flex-wrap items-baseline gap-x-2 gap-y-1">
                        <span class="text-3xl font-semibold text-gray-900 tabular-nums">{{ number_format($totalTickets) }}</span>
                        @if($ticketsChip)
                            <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-md text-[11px] font-semibold {{ $ticketsChip['color'] }}">
                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $ticketsChip['arrow'] }}" /></svg>
                                {{ $ticketsChip['text'] }}
                            </span>
                        @endif
                    </dd>
                    <p class="mt-1 text-xs text-gray-400">{{ $comparison ? $comparison['label'] : $rangeLabel }}</p>
                </div>

                <div class="p-5">
                    <dt class="text-sm text-gray-500">Resolution rate</dt>
                    <dd class="mt-2 flex flex-wrap items-baseline gap-x-2 gap-y-1">
                        <span class="text-3xl font-semibold tabular-nums" style="color:#1a6b3c;">{{ $resolutionRate !== null ? $resolutionRate.'%' : '—' }}</span>
                        @if($rateChip)
                            <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-md text-[11px] font-semibold {{ $rateChip['color'] }}">
                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $rateChip['arrow'] }}" /></svg>
                                {{ $rateChip['text'] }}
                            </span>
                        @endif
                    </dd>
                    @if($resolutionRate !== null)
                        <div class="mt-2 h-1.5 rounded-full bg-gray-100 overflow-hidden"><div class="h-full rounded-full" style="width:{{ min(100, $resolutionRate) }}%; background-color:#1a6b3c;"></div></div>
                    @endif
                    <p class="mt-1.5 text-xs text-gray-400">Resolved or closed, excluding cancelled</p>
                </div>

                <div class="p-5 border-r border-gray-100 lg:border-r-0">
                    <dt class="text-sm text-gray-500">Avg. resolution time</dt>
                    <dd class="mt-2 text-3xl font-semibold text-gray-900 tabular-nums">
                        @if($avgResolutionHours === null)
                            —
                        @elseif($avgResolutionHours < 24)
                            {{ round($avgResolutionHours, 1) }}<span class="ml-0.5 text-lg font-medium text-gray-400">hrs</span>
                        @else
                            {{ round($avgResolutionHours / 24, 1) }}<span class="ml-0.5 text-lg font-medium text-gray-400">days</span>
                        @endif
                    </dd>
                    <p class="mt-1 text-xs text-gray-400">From created to resolved</p>
                </div>

                <div class="p-5 {{ $unassignedInRange > 0 ? 'bg-amber-50/70' : '' }}">
                    <dt class="text-sm {{ $unassignedInRange > 0 ? 'text-amber-800' : 'text-gray-500' }} flex items-center gap-1.5">
                        @if($unassignedInRange > 0)<span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>@endif
                        Needs an engineer
                    </dt>
                    <dd class="mt-2 text-3xl font-semibold tabular-nums {{ $unassignedInRange > 0 ? 'text-amber-700' : 'text-gray-900' }}">{{ number_format($unassignedInRange) }}</dd>
                    <p class="mt-1 text-xs {{ $unassignedInRange > 0 ? 'text-amber-700/80' : 'text-gray-400' }}">Open and unassigned</p>
                </div>

                <div class="p-5 col-span-2 lg:col-span-1 border-t lg:border-t-0 border-gray-100">
                    <dt class="text-sm text-gray-500">Cancelled</dt>
                    <dd class="mt-2 text-3xl font-semibold tabular-nums {{ $cancelledCount > 0 ? 'text-red-600' : 'text-gray-900' }}">{{ number_format($cancelledCount) }}</dd>
                    <p class="mt-1 text-xs text-gray-400">Withdrawn before pickup</p>
                </div>
            </dl>
        </div>

        {{-- Jump links so a long report is easy to move around --}}
        <nav class="rpt-sticky-nav sticky top-0 z-20 -mx-4 sm:mx-0 px-4 sm:px-2 py-2 bg-gray-100/90 backdrop-blur border-b border-gray-200 sm:border-0 sm:rounded-xl sm:bg-white/90 sm:ring-1 sm:ring-gray-200 overflow-x-auto" aria-label="Report sections">
            <ul class="flex items-center gap-1 text-sm font-medium text-gray-600 whitespace-nowrap">
                @if(count($trend) > 1)<li><a href="#trend" class="block px-3 py-1.5 rounded-lg hover:bg-gray-100 hover:text-gray-900">Ticket trend</a></li>@endif
                <li><a href="#breakdown" class="block px-3 py-1.5 rounded-lg hover:bg-gray-100 hover:text-gray-900">Breakdown</a></li>
                <li><a href="#it-activity" class="block px-3 py-1.5 rounded-lg hover:bg-gray-100 hover:text-gray-900">Engineer activity</a></li>
                <li><a href="#engineers-report" class="block px-3 py-1.5 rounded-lg hover:bg-gray-100 hover:text-gray-900">IT engineers</a></li>
                <li><a href="#staff-report" class="block px-3 py-1.5 rounded-lg hover:bg-gray-100 hover:text-gray-900">Staff</a></li>
                <li><a href="#assets" class="block px-3 py-1.5 rounded-lg hover:bg-gray-100 hover:text-gray-900">Assets &amp; users</a></li>
            </ul>
        </nav>

        {{-- Ticket volume trend --}}
        @if(count($trend) > 1)
            @php
                $maxV = max(1, collect($trend)->max('value'));
                // Round the top of the scale up to a multiple of 4 so the four gridlines carry whole numbers
                $niceMax = max(4, (int) ceil($maxV / 4) * 4);
                // One fixed colour per calendar month (the same month always gets the
                // same colour, whatever date filter is applied). October = brand green,
                // then Nov, Dec, Jan ... Sep. Repeats each year.
                $monthPalette = ['#1a6b3c', '#2563eb', '#d97706', '#7c3aed', '#0891b2', '#e11d48', '#65a30d', '#c026d3', '#ea580c', '#0d9488', '#4f46e5', '#b45309'];
                $monthColors = [];
                $monthTotals = [];
                $monthNames = [];
                $monthCounts = [];
                foreach ($trend as $pt) {
                    if (! isset($monthColors[$pt['month']])) {
                        $monthColors[$pt['month']] = $monthPalette[(((int) substr($pt['month'], 5, 2)) - 10 + 12) % 12];
                        $monthTotals[$pt['month']] = 0;
                        $monthCounts[$pt['month']] = 0;
                        $monthNames[$pt['month']] = $pt['month_label'];
                    }
                    $monthTotals[$pt['month']] += $pt['value'];
                    $monthCounts[$pt['month']]++;
                }
                $peak = collect($trend)->sortByDesc('value')->first();
                $trendTotal = array_sum($monthTotals);
                $manyYears = collect(array_keys($monthColors))->map(fn ($m) => substr($m, 0, 4))->unique()->count() > 1;
                $axisH = 176; // px height of the plotting area
            @endphp
            <section id="trend" class="rpt-card rpt-anchor p-5 sm:p-6">
                <div class="flex flex-wrap items-start justify-between gap-3 mb-6">
                    <div>
                        <h3 class="text-base font-semibold text-gray-900">Ticket volume over time</h3>
                        <p class="text-sm text-gray-500 mt-0.5">New tickets per {{ count($monthColors) < count($trend) ? 'day' : 'month' }}, coloured by month</p>
                    </div>
                    <div class="flex items-center gap-5 text-sm">
                        <div>
                            <p class="text-xs text-gray-400">Total</p>
                            <p class="font-semibold text-gray-900 tabular-nums">{{ number_format($trendTotal) }}</p>
                        </div>
                        @if($peak && $peak['value'] > 0)
                            <div>
                                <p class="text-xs text-gray-400">Busiest</p>
                                <p class="font-semibold text-gray-900 tabular-nums">{{ $peak['label'] }} <span class="text-gray-400 font-normal">({{ $peak['value'] }})</span></p>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="flex gap-3">
                    {{-- Y axis --}}
                    <div class="relative w-8 shrink-0 text-right text-[11px] leading-none text-gray-400 tabular-nums select-none" style="height: {{ $axisH }}px;" aria-hidden="true">
                        @foreach([0, 1, 2, 3, 4] as $k)
                            <span class="absolute right-0 -translate-y-1/2" style="top: {{ $k * 25 }}%;">{{ (int) round($niceMax * (4 - $k) / 4) }}</span>
                        @endforeach
                    </div>

                    <div class="flex-1 min-w-0">
                        <div class="relative" style="height: {{ $axisH }}px;">
                            {{-- Gridlines --}}
                            <div class="absolute inset-0 flex flex-col justify-between pointer-events-none" aria-hidden="true">
                                @foreach([0, 1, 2, 3, 4] as $k)
                                    <div class="border-t {{ $k === 4 ? 'border-gray-300' : 'border-dashed border-gray-200' }}"></div>
                                @endforeach
                            </div>
                            {{-- Bars --}}
                            <div class="absolute inset-0 flex items-end gap-[3px] px-px">
                                @foreach($trend as $point)
                                    @php $c = $monthColors[$point['month']]; @endphp
                                    <div class="flex-1 min-w-0 group relative flex flex-col justify-end h-full">
                                        <div class="rpt-bar w-full rounded-t-[3px] group-hover:opacity-75" style="height: {{ $point['value'] > 0 ? max(($point['value'] / $niceMax) * 100, 1.5) : 0 }}%; background-color: {{ $c }};"></div>
                                        <div class="absolute bottom-full mb-2 left-1/2 -translate-x-1/2 hidden group-hover:block whitespace-nowrap bg-gray-900 text-white text-xs px-2.5 py-1.5 rounded-lg shadow-lg z-10 pointer-events-none">
                                            <span class="text-gray-300">{{ $point['label'] }}</span>
                                            <span class="font-semibold ml-1.5">{{ $point['value'] }} {{ $point['value'] === 1 ? 'ticket' : 'tickets' }}</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- Month labels sit under the bars they belong to --}}
                        <div class="mt-2 flex gap-[3px] px-px">
                            @foreach($monthColors as $m => $color)
                                <div class="min-w-0 text-center border-t-2 pt-1.5" style="flex: {{ $monthCounts[$m] }} 1 0%; border-color: {{ $color }};">
                                    <span class="block truncate text-[11px] font-medium text-gray-500">{{ \Carbon\Carbon::createFromFormat('Y-m-d', $m.'-01')->format($manyYears ? "M 'y" : 'M') }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Month legend with each month's total --}}
                <ul class="mt-5 pt-4 border-t border-gray-100 flex flex-wrap gap-2">
                    @foreach($monthColors as $m => $color)
                        <li class="inline-flex items-center gap-2 pl-2.5 pr-3 py-1 rounded-full bg-gray-50 ring-1 ring-gray-200 text-xs text-gray-600">
                            <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background-color: {{ $color }};"></span>
                            <span>{{ $monthNames[$m] }}</span>
                            <span class="font-semibold tabular-nums text-gray-800">{{ number_format($monthTotals[$m]) }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        {{-- Ticket breakdown: status / priority / category donuts --}}
        <section id="breakdown" class="rpt-anchor">
            <div class="flex items-baseline gap-2 mb-3 px-1">
                <h3 class="text-base font-semibold text-gray-900">Ticket breakdown</h3>
                <span class="text-sm text-gray-500">{{ $rangeLabel }}</span>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
                <div class="rpt-card p-5"><x-pie-chart :data="$byStatus" title="By status" /></div>
                <div class="rpt-card p-5"><x-pie-chart :data="$byPriority" title="By priority" /></div>
                <div class="rpt-card p-5"><x-pie-chart :data="$byCategory" title="By category" /></div>
            </div>
        </section>

        {{-- Every category, ranked, with count and share --}}
        @if($totalTickets > 0)
            <section class="rpt-card p-5 sm:p-6">
                <div class="flex flex-wrap items-baseline justify-between gap-2 mb-5">
                    <h3 class="text-base font-semibold text-gray-900">Tickets by category</h3>
                    <span class="text-sm text-gray-500">{{ number_format($totalTickets) }} tickets &middot; {{ $rangeLabel }}</span>
                </div>
                @php $maxC = max(1, collect($categoryBreakdown)->max('value')); @endphp
                <ul class="divide-y divide-gray-100">
                    @foreach($categoryBreakdown as $row)
                        <li class="flex items-center gap-3 sm:gap-4 py-2.5 {{ $row['value'] === 0 ? 'opacity-50' : '' }}">
                            <span class="w-40 sm:w-60 shrink-0 text-sm text-gray-700 truncate" title="{{ $row['label'] }}">{{ $row['label'] }}</span>
                            <div class="flex-1 h-2 rounded-full bg-gray-100 overflow-hidden">
                                <div class="h-full rounded-full" style="width: {{ ($row['value'] / $maxC) * 100 }}%; background-color:#1a6b3c;"></div>
                            </div>
                            <span class="w-9 shrink-0 text-sm font-semibold text-gray-900 text-right tabular-nums">{{ $row['value'] }}</span>
                            <span class="w-12 shrink-0 text-sm text-gray-400 text-right tabular-nums">{{ $row['percent'] }}%</span>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        {{-- IT engineer activity: tickets resolved per engineer for a period of its own (default Today) --}}
        @php $act = $agentActivity; @endphp
        <section class="rpt-card rpt-anchor overflow-hidden" id="it-activity">
            <div class="p-5 sm:p-6 pb-4 flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h3 class="text-base font-semibold text-gray-900">IT engineer activity</h3>
                    <p class="text-sm text-gray-500 mt-0.5">Tickets resolved &middot; {{ $act['label'] }} &middot; <span class="font-medium text-gray-700">{{ $act['totalHandled'] }} total</span></p>
                </div>

                <form method="GET" action="{{ route('admin.reports.index') }}#it-activity" x-data="{ p: '{{ $act['period'] }}' }" class="flex flex-wrap items-end gap-3">
                    {{-- keep the main report period when this filter is applied --}}
                    @foreach(['range', 'from', 'to'] as $keep)
                        @if(request()->filled($keep))<input type="hidden" name="{{ $keep }}" value="{{ request($keep) }}">@endif
                    @endforeach
                    <div>
                        <label for="act-period" class="block text-xs font-medium text-gray-500 mb-1">Show</label>
                        <select id="act-period" name="act" x-model="p" onchange="if (this.value !== 'custom') this.form.submit()" class="rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700">
                            @foreach($act['periods'] as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div x-show="p === 'custom'" x-cloak class="flex items-end gap-2">
                        <input type="date" name="act_from" value="{{ $act['from'] }}" aria-label="From date" class="rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700">
                        <input type="date" name="act_to" value="{{ $act['to'] }}" aria-label="To date" class="rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700">
                        <button type="submit" class="px-3.5 py-2 rounded-lg text-sm font-semibold text-white transition hover:brightness-110" style="background-color:#1a6b3c;">Apply</button>
                    </div>
                    <div>
                        <label for="act-agent" class="block text-xs font-medium text-gray-500 mb-1">IT engineer</label>
                        <select id="act-agent" name="act_agent" onchange="this.form.submit()" class="rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700">
                            <option value="all">All IT engineers</option>
                            @foreach($act['agents'] as $a)
                                <option value="{{ $a->id }}" @selected($act['selectedAgent'] === $a->id)>{{ $a->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </form>
            </div>

            @if(count($act['rows']) === 0)
                <div class="px-6 pb-10 pt-4 text-center">
                    <p class="text-sm font-medium text-gray-700">No IT engineers yet</p>
                    <p class="text-sm text-gray-400 mt-1">Add an account with the IT Support role to see activity here.</p>
                </div>
            @else
                @php $maxH = max(1, collect($act['rows'])->max('handled')); @endphp
                <ul class="divide-y divide-gray-100 border-t border-gray-100">
                    @foreach($act['rows'] as $row)
                        <li class="flex items-center gap-3 sm:gap-4 px-5 sm:px-6 py-3 hover:bg-gray-50/70 transition-colors">
                            <span class="relative shrink-0">
                                <span class="w-9 h-9 rounded-full flex items-center justify-center text-xs font-semibold" style="background-color:#e8f3ec; color:#14532d;">{{ $initials($row['name']) }}</span>
                                <span class="absolute -bottom-0.5 -right-0.5 w-3 h-3 rounded-full ring-2 ring-white {{ $row['online'] ? 'bg-green-500' : 'bg-gray-300' }}" title="{{ $row['online'] ? 'Online' : 'Offline' }}"></span>
                            </span>
                            <div class="w-36 sm:w-48 shrink-0 min-w-0">
                                <p class="text-sm font-medium text-gray-900 truncate">{{ $row['name'] }}</p>
                                <p class="text-xs text-gray-400">{{ $row['online'] ? 'Online' : 'Offline' }}</p>
                            </div>
                            <div class="flex-1 h-2 rounded-full bg-gray-100 overflow-hidden">
                                <div class="h-full rounded-full" style="width: {{ ($row['handled'] / $maxH) * 100 }}%; background-color:#1a6b3c;"></div>
                            </div>
                            <div class="w-20 shrink-0 text-right">
                                <span class="text-base font-semibold text-gray-900 tabular-nums">{{ $row['handled'] }}</span>
                                <span class="text-xs text-gray-400">resolved</span>
                            </div>
                            <span class="hidden md:inline-flex shrink-0 w-28 justify-center px-2 py-1 rounded-full text-xs font-medium tabular-nums {{ $row['working'] > 0 ? 'bg-blue-50 text-blue-700 ring-1 ring-blue-600/15' : 'bg-gray-50 text-gray-400 ring-1 ring-gray-200' }}">{{ $row['working'] }} working now</span>
                        </li>
                    @endforeach
                </ul>

                @if($act['selectedAgent'])
                    <div class="border-t border-gray-100 bg-gray-50/60 p-5 sm:p-6">
                        @if($act['tickets']->isEmpty())
                            <p class="text-sm text-gray-500 text-center py-2">No tickets resolved in this period.</p>
                        @else
                            <h4 class="text-sm font-semibold text-gray-900 mb-3">Resolved tickets</h4>
                            <div class="rounded-xl bg-white ring-1 ring-gray-200 overflow-hidden">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="text-left text-xs font-medium text-gray-500 bg-gray-50 border-b border-gray-200">
                                            <th class="py-2.5 pl-4 pr-3">Ticket</th>
                                            <th class="py-2.5 pr-3 hidden sm:table-cell">Category</th>
                                            <th class="py-2.5 pr-3 hidden md:table-cell">Requested by</th>
                                            <th class="py-2.5 pr-4 text-right">Resolved</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        @foreach($act['tickets'] as $t)
                                            <tr class="hover:bg-gray-50/70 transition-colors">
                                                <td class="py-2.5 pl-4 pr-3"><a href="{{ route('tickets.show', $t) }}" class="text-gray-900 hover:text-green-700 hover:underline"><span class="font-mono text-xs text-gray-400 mr-1.5">{{ $t->ticket_number }}</span>{{ $t->title }}</a></td>
                                                <td class="py-2.5 pr-3 text-gray-500 hidden sm:table-cell">{{ $t->category }}</td>
                                                <td class="py-2.5 pr-3 text-gray-500 hidden md:table-cell">{{ $t->creator->name ?? '—' }}</td>
                                                <td class="py-2.5 pr-4 text-right text-gray-500 whitespace-nowrap">{{ $t->resolved_at->format('M j, g:i A') }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                @endif
            @endif
        </section>

        {{-- Tickets handled per IT engineer (selected reporting period) --}}
        @if(count($byAgent) > 0)
            <section class="rpt-card p-5 sm:p-6">
                <div class="flex flex-wrap items-baseline justify-between gap-2 mb-4">
                    <h3 class="text-base font-semibold text-gray-900">Tickets handled per IT engineer</h3>
                    <span class="text-sm text-gray-500">{{ $rangeLabel }}</span>
                </div>
                @php $maxA = max(1, collect($byAgent)->max()); @endphp
                <ul class="space-y-3">
                    @foreach($byAgent as $agent => $count)
                        <li class="flex items-center gap-3">
                            <span class="w-8 h-8 rounded-full flex items-center justify-center text-[11px] font-semibold shrink-0" style="background-color:#e8f3ec; color:#14532d;">{{ $initials($agent) }}</span>
                            <span class="w-32 sm:w-44 shrink-0 text-sm text-gray-700 truncate">{{ $agent }}</span>
                            <div class="flex-1 h-2 rounded-full bg-gray-100 overflow-hidden">
                                <div class="h-full rounded-full" style="width: {{ ($count / $maxA) * 100 }}%; background-color:#1a6b3c;"></div>
                            </div>
                            <span class="w-10 shrink-0 text-sm font-semibold text-gray-900 text-right tabular-nums">{{ $count }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        {{-- Team reports: IT engineers and staff, each with Excel / PDF export --}}
        @php
            $teamReports = [
                ['data' => $engineerReport, 'team' => 'engineers', 'limit' => 10, 'noun' => 'IT engineers', 'sub' => 'Workload and results for each engineer'],
                ['data' => $staffReport, 'team' => 'staff', 'limit' => 10, 'noun' => 'staff', 'sub' => 'Who is raising tickets, and how they turned out'],
            ];
        @endphp
        @foreach($teamReports as $rep)
            @php
                $t = $rep['data'];
                $shown = array_slice($t['rows'], 0, $rep['limit']);
                $hidden = count($t['rows']) - count($shown);
                $emphasisCol = $rep['team'] === 'staff' ? 3 : 2;
            @endphp
            <section class="rpt-card rpt-anchor overflow-hidden" id="{{ $rep['team'] }}-report">
                <div class="p-5 sm:p-6 pb-4 flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h3 class="text-base font-semibold text-gray-900">{{ $t['title'] }}</h3>
                        <p class="text-sm text-gray-500 mt-0.5">{{ $rep['sub'] }} &middot; {{ $rangeLabel }} &middot; {{ count($t['rows']) }} {{ $rep['noun'] }}</p>
                    </div>
                    <div class="inline-flex rounded-lg shadow-sm" role="group" aria-label="Export {{ $rep['noun'] }} report">
                        <a href="{{ route('admin.reports.team.export', array_merge(request()->only('range', 'from', 'to'), ['team' => $rep['team'], 'format' => 'excel'])) }}"
                           class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-l-lg text-sm font-medium border border-gray-300 text-gray-700 bg-white hover:bg-gray-50 transition focus:outline-none focus-visible:z-10 focus-visible:ring-2 focus-visible:ring-green-700">
                            <svg class="w-4 h-4 text-green-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M3.375 19.5h17.25m-17.25 0a1.125 1.125 0 01-1.125-1.125M3.375 19.5h7.5c.621 0 1.125-.504 1.125-1.125m-9.75 0V5.625m0 12.75v-1.5c0-.621.504-1.125 1.125-1.125m18.375 2.625V5.625m0 12.75c0 .621-.504 1.125-1.125 1.125m1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125m0 3.75h-7.5A1.125 1.125 0 0112 18.375m9.75-12.75c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125m19.5 0v1.5c0 .621-.504 1.125-1.125 1.125M2.25 5.625v1.5c0 .621.504 1.125 1.125 1.125m0 0h17.25m-17.25 0h7.5c.621 0 1.125.504 1.125 1.125M3.375 8.25c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125m17.25-3.75h-7.5c-.621 0-1.125.504-1.125 1.125m8.625-1.125c.621 0 1.125.504 1.125 1.125v1.5c0 .621-.504 1.125-1.125 1.125m-17.25 0h7.5m-7.5 0c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125M12 10.875v-1.5m0 1.5c0 .621-.504 1.125-1.125 1.125M12 10.875c0 .621.504 1.125 1.125 1.125m-2.25 0c.621 0 1.125.504 1.125 1.125M13.125 12h7.5m-7.5 0c-.621 0-1.125.504-1.125 1.125M20.625 12c.621 0 1.125.504 1.125 1.125v1.5c0 .621-.504 1.125-1.125 1.125m-17.25 0h7.5M12 14.625v-1.5m0 1.5c0 .621-.504 1.125-1.125 1.125M12 14.625c0 .621.504 1.125 1.125 1.125m-2.25 0c.621 0 1.125.504 1.125 1.125m0 1.5v-1.5m0 0c0-.621.504-1.125 1.125-1.125m0 0h7.5" /></svg>
                            Excel
                        </a>
                        <a href="{{ route('admin.reports.team.export', array_merge(request()->only('range', 'from', 'to'), ['team' => $rep['team'], 'format' => 'pdf'])) }}"
                           class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-r-lg text-sm font-medium text-white -ml-px transition hover:brightness-110 focus:outline-none focus-visible:z-10 focus-visible:ring-2 focus-visible:ring-offset-1 focus-visible:ring-green-700" style="background-color:#1a6b3c;">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m0 0l-4-4m4 4l4-4M4 17v2a2 2 0 002 2h12a2 2 0 002-2v-2" /></svg>
                            PDF
                        </a>
                    </div>
                </div>

                @if(count($shown) === 0)
                    <div class="px-6 pb-10 pt-4 text-center">
                        <p class="text-sm font-medium text-gray-700">No {{ $rep['noun'] }} yet</p>
                        <p class="text-sm text-gray-400 mt-1">Accounts will appear here once they are created.</p>
                    </div>
                @else
                    <table class="w-full text-sm table-fixed border-t border-gray-200">
                        <thead>
                            <tr class="text-left text-xs font-medium text-gray-500 bg-gray-50 border-b border-gray-200">
                                @foreach($t['headers'] as $i => $h)
                                    {{-- Less important columns drop out on small screens so the table never scrolls sideways --}}
                                    <th scope="col" class="py-3 {{ $i === 0 ? 'pl-5 sm:pl-6' : '' }} pr-3 {{ $i === count($t['headers']) - 1 ? 'pr-5 sm:pr-6' : '' }} font-semibold {{ in_array($i, $t['numeric']) ? 'text-right' : '' }} {{ $i === 0 ? 'w-[30%]' : '' }} {{ $i >= 4 && ! in_array($i, [4]) ? 'hidden md:table-cell' : '' }} {{ $i === 1 || ($rep['team'] === 'staff' && $i === 2) ? 'hidden sm:table-cell' : '' }}">{{ $h }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($shown as $row)
                                <tr class="hover:bg-gray-50/70 transition-colors">
                                    @foreach($row as $i => $cell)
                                        <td class="py-3 {{ $i === 0 ? 'pl-5 sm:pl-6' : '' }} pr-3 {{ $i === count($row) - 1 ? 'pr-5 sm:pr-6' : '' }} {{ $i === 0 ? '' : 'truncate' }} {{ in_array($i, $t['numeric']) ? 'text-right tabular-nums text-gray-600' : 'text-gray-500' }} {{ $i === $emphasisCol ? '!font-semibold !text-gray-900' : '' }} {{ $i >= 4 && ! in_array($i, [4]) ? 'hidden md:table-cell' : '' }} {{ $i === 1 || ($rep['team'] === 'staff' && $i === 2) ? 'hidden sm:table-cell' : '' }}" @if($i !== 0) title="{{ $cell }}" @endif>
                                            @if($i === 0)
                                                <div class="flex items-center gap-2.5 min-w-0">
                                                    <span class="w-8 h-8 rounded-full flex items-center justify-center text-[11px] font-semibold shrink-0" style="background-color:#e8f3ec; color:#14532d;">{{ $initials($cell) }}</span>
                                                    <span class="font-medium text-gray-900 truncate" title="{{ $cell }}">{{ $cell }}</span>
                                                </div>
                                            @else
                                                {{ $cell }}
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="px-5 sm:px-6 py-3.5 bg-gray-50 border-t border-gray-200 flex flex-wrap items-center justify-between gap-x-6 gap-y-1 text-xs text-gray-500">
                        <p>{{ $t['note'] }}</p>
                        @if($hidden > 0)
                            <p class="font-medium text-gray-600">Showing top {{ count($shown) }} of {{ count($t['rows']) }} &middot; export for the full list</p>
                        @endif
                    </div>
                @endif
            </section>
        @endforeach

        {{-- Assets + accounts --}}
        <section id="assets" class="rpt-anchor">
            <div class="flex items-baseline gap-2 mb-3 px-1">
                <h3 class="text-base font-semibold text-gray-900">Assets &amp; users</h3>
                <span class="text-sm text-gray-500">Current snapshot, not affected by the period</span>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                <div class="rpt-card p-5"><x-pie-chart :data="$assetsByType" title="Assets by type" emptyText="No assets yet" /></div>
                <div class="rpt-card p-5">
                    <x-pie-chart :data="[
                        ['label' => 'Assigned', 'value' => $assignedAssets, 'color' => '#1a6b3c', 'percent' => $totalAssets > 0 ? round($assignedAssets / $totalAssets * 100, 1) : 0],
                        ['label' => 'Unassigned', 'value' => $unassignedAssets, 'color' => '#f59e0b', 'percent' => $totalAssets > 0 ? round($unassignedAssets / $totalAssets * 100, 1) : 0],
                    ]" title="Assignment" emptyText="No assets yet" />
                </div>
                <div class="rpt-card p-5"><x-pie-chart :data="$assetsByStatus" title="Asset condition" emptyText="No assets yet" /></div>
                <div class="rpt-card p-5"><x-pie-chart :data="$usersByRole" title="Accounts by role" emptyText="No users yet" /></div>
            </div>
        </section>

        <p class="text-center text-xs text-gray-400 pt-2">Crest Forwarder Inc. &mdash; IT Service Desk &middot; Internal use only</p>
    </div>

    {{-- Highlight the section jump link for whatever is on screen --}}
    <script>
        (function () {
            var links = document.querySelectorAll('.rpt-sticky-nav a');
            if (!('IntersectionObserver' in window) || !links.length) return;
            var map = {};
            links.forEach(function (a) { var el = document.querySelector(a.getAttribute('href')); if (el) map[el.id] = a; });
            var io = new IntersectionObserver(function (entries) {
                entries.forEach(function (e) {
                    if (!e.isIntersecting) return;
                    links.forEach(function (a) { a.removeAttribute('aria-current'); });
                    if (map[e.target.id]) map[e.target.id].setAttribute('aria-current', 'true');
                });
            }, { rootMargin: '-20% 0px -70% 0px' });
            Object.keys(map).forEach(function (id) { io.observe(document.getElementById(id)); });
        })();
    </script>
</x-app-layout>
