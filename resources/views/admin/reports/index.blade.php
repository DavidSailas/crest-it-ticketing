<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap justify-between items-center gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Reports</h2>
            <a href="{{ route('admin.reports.export.pdf', request()->query()) }}"
               class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg text-sm font-medium text-white shadow-sm hover:opacity-90 transition" style="background-color:#1a6b3c;">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m0 0l-4-4m4 4l4-4M4 17v2a2 2 0 002 2h12a2 2 0 002-2v-2" /></svg>
                Export as PDF
            </a>
        </div>
    </x-slot>

    <div class="py-6 sm:py-8 max-w-[80rem] mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        {{-- Report banner: who this is for, what period, and the control to change it --}}
        <div class="rounded-2xl overflow-hidden shadow-sm border border-gray-100">
            <div class="px-5 sm:px-8 py-6 sm:py-7 text-white" style="background: linear-gradient(135deg, #123f24 0%, #1a6b3c 55%, #228a4a 100%);">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-widest text-green-100/80">Crest Forwarder Inc. · IT Service Desk</p>
                        <h1 class="text-2xl sm:text-[28px] font-semibold mt-1">Management Report</h1>
                        <p class="text-green-50/90 text-sm mt-1">{{ $rangeLabel }} <span class="text-green-100/50">·</span> Generated {{ now()->format('F j, Y') }}</p>
                    </div>
                    <div class="shrink-0 w-16 h-16 rounded-xl bg-white shadow-md overflow-hidden">
                        <img src="{{ asset('images/logo.png') }}" alt="Crest Forwarder Inc." class="w-full h-full object-cover">
                    </div>
                </div>

                <p class="mt-5 text-sm leading-relaxed text-green-50/95 max-w-3xl border-t border-white/15 pt-4">{{ $summary }}</p>
            </div>

            {{-- Period picker --}}
            <form method="GET" action="{{ route('admin.reports.index') }}" x-data="{ range: '{{ $selectedRange }}' }"
                  class="bg-white px-5 sm:px-8 py-3.5 flex flex-wrap items-end gap-3 border-t border-gray-100">
                <div>
                    <label class="block text-[11px] font-semibold uppercase tracking-wide text-gray-500 mb-1">Reporting period</label>
                    <select name="range" x-model="range" onchange="this.form.submit()" class="rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700 min-w-[11rem]">
                        @foreach($ranges as $value => $label)
                            <option value="{{ $value }}" @selected($selectedRange === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div x-show="range === 'custom'" x-cloak class="flex items-end gap-2">
                    <div>
                        <label class="block text-[11px] font-semibold uppercase tracking-wide text-gray-500 mb-1">From</label>
                        <input type="date" name="from" value="{{ $customFrom }}" class="rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold uppercase tracking-wide text-gray-500 mb-1">To</label>
                        <input type="date" name="to" value="{{ $customTo }}" class="rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700">
                    </div>
                    <button type="submit" class="px-4 py-2 rounded-lg text-sm font-semibold text-white shrink-0" style="background-color:#1a6b3c;">Apply</button>
                </div>
            </form>
        </div>

        {{-- Headline KPIs, with a vs.-previous-period indicator where we have one to compare against --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            @php
                $deltaChip = function ($delta, $goodIsUp = true) {
                    if ($delta === null) return null;
                    $up = $delta > 0;
                    $flat = $delta == 0;
                    $good = $flat ? null : ($goodIsUp ? $up : ! $up);
                    $color = $flat ? 'text-gray-400 bg-gray-50' : ($good ? 'text-green-700 bg-green-50' : 'text-red-600 bg-red-50');
                    $arrow = $flat ? 'M5 12h14' : ($up ? 'M12 19V5m0 0l-6 6m6-6l6 6' : 'M12 5v14m0 0l-6-6m6 6l6-6');
                    return ['color' => $color, 'arrow' => $arrow, 'text' => ($flat ? 'No change' : ($up ? '+' : '').$delta.'%')];
                };
                $ticketsChip = $comparison ? $deltaChip($comparison['ticketsDelta'], false) : null;
                $rateChip = $comparison ? $deltaChip($comparison['rateDelta'], true) : null;
            @endphp

            <div class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Tickets created</p>
                <div class="flex items-end justify-between mt-1.5">
                    <p class="text-3xl font-semibold text-gray-800 tabular-nums">{{ number_format($totalTickets) }}</p>
                    @if($ticketsChip)
                        <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-[11px] font-semibold {{ $ticketsChip['color'] }}">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $ticketsChip['arrow'] }}" /></svg>
                            {{ $ticketsChip['text'] }}
                        </span>
                    @endif
                </div>
                <p class="mt-0.5 text-xs text-gray-400">{{ $comparison ? $comparison['label'] : $rangeLabel }}</p>
            </div>

            <div class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Resolution rate</p>
                <div class="flex items-end justify-between mt-1.5">
                    <p class="text-3xl font-semibold tabular-nums" style="color:#1a6b3c;">{{ $resolutionRate !== null ? $resolutionRate.'%' : '—' }}</p>
                    @if($rateChip)
                        <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-[11px] font-semibold {{ $rateChip['color'] }}">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $rateChip['arrow'] }}" /></svg>
                            {{ $rateChip['text'] }}
                        </span>
                    @endif
                </div>
                <p class="mt-0.5 text-xs text-gray-400">Resolved or closed</p>
            </div>

            <div class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Avg. resolution time</p>
                <p class="mt-1.5 text-3xl font-semibold text-gray-800 tabular-nums">
                    @if($avgResolutionHours === null)
                        —
                    @elseif($avgResolutionHours < 24)
                        {{ round($avgResolutionHours, 1) }}<span class="text-lg text-gray-400">h</span>
                    @else
                        {{ round($avgResolutionHours / 24, 1) }}<span class="text-lg text-gray-400">d</span>
                    @endif
                </p>
                <p class="mt-0.5 text-xs text-gray-400">Created → resolved</p>
            </div>

            <div class="rounded-xl border p-4 shadow-sm {{ $unassignedInRange > 0 ? 'border-amber-200 bg-amber-50/50' : 'border-gray-100 bg-white' }}">
                <p class="text-xs font-semibold uppercase tracking-wide {{ $unassignedInRange > 0 ? 'text-amber-700' : 'text-gray-500' }}">Unassigned</p>
                <p class="mt-1.5 text-3xl font-semibold tabular-nums {{ $unassignedInRange > 0 ? 'text-amber-700' : 'text-gray-800' }}">{{ number_format($unassignedInRange) }}</p>
                <p class="mt-0.5 text-xs {{ $unassignedInRange > 0 ? 'text-amber-700/70' : 'text-gray-400' }}">Still need an IT engineer</p>
            </div>
        </div>

        {{-- Ticket volume trend --}}
        @if(count($trend) > 1)
            <div class="bg-white shadow-sm rounded-xl border border-gray-100 p-5 sm:p-6">
                <div class="flex items-center gap-2 mb-5">
                    <span class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0" style="background-color:#e8f3ec; color:#1a6b3c;">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125L7.5 8.25l4 4 5.5-6 4 4.5" /></svg>
                    </span>
                    <h3 class="text-sm font-semibold text-gray-700">Ticket volume over time</h3>
                </div>
                @php
                    $maxV = max(1, collect($trend)->max('value'));
                    // One fixed colour per calendar month (the same month always gets the
                    // same colour, whatever date filter is applied). October = brand green,
                    // then Nov, Dec, Jan ... Sep. Repeats each year.
                    $monthPalette = ['#1a6b3c', '#2563eb', '#d97706', '#7c3aed', '#0891b2', '#e11d48', '#65a30d', '#c026d3', '#ea580c', '#0d9488', '#4f46e5', '#b45309'];
                    $monthColors = [];
                    $monthTotals = [];
                    $monthNames = [];
                    foreach ($trend as $pt) {
                        if (! isset($monthColors[$pt['month']])) {
                            $monthColors[$pt['month']] = $monthPalette[(((int) substr($pt['month'], 5, 2)) - 10 + 12) % 12];
                            $monthTotals[$pt['month']] = 0;
                            $monthNames[$pt['month']] = $pt['month_label'];
                        }
                        $monthTotals[$pt['month']] += $pt['value'];
                    }
                @endphp
                <div class="flex items-end gap-[3px] h-32">
                    @foreach($trend as $point)
                        @php $c = $monthColors[$point['month']]; @endphp
                        <div class="flex-1 min-w-0 group relative flex flex-col items-center justify-end h-full">
                            <div class="w-full rounded-sm transition-opacity group-hover:opacity-80" style="height: {{ max(($point['value'] / $maxV) * 100, $point['value'] > 0 ? 4 : 1) }}%; background-color: {{ $c }}; {{ $point['value'] > 0 ? '' : 'opacity:.3;' }}"></div>
                            <div class="absolute bottom-full mb-1.5 hidden group-hover:block whitespace-nowrap bg-gray-800 text-white text-[10px] px-1.5 py-1 rounded z-10">{{ $point['label'] }}: {{ $point['value'] }}</div>
                        </div>
                    @endforeach
                </div>
                <div class="flex justify-between mt-2 text-[10px] text-gray-400">
                    <span>{{ $trend[0]['label'] }}</span>
                    <span>{{ end($trend)['label'] }}</span>
                </div>

                {{-- Month legend: colour key with each month's ticket total --}}
                <div class="mt-4 pt-4 border-t border-gray-100 flex flex-wrap gap-x-5 gap-y-2">
                    @foreach($monthColors as $m => $color)
                        <div class="flex items-center gap-1.5 text-xs text-gray-600">
                            <span class="w-2.5 h-2.5 rounded-sm shrink-0" style="background-color: {{ $color }};"></span>
                            <span>{{ $monthNames[$m] }}</span>
                            <span class="tabular-nums text-gray-400">· {{ number_format($monthTotals[$m]) }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Ticket pie charts --}}
        <div>
            <div class="flex items-center gap-2 mb-3">
                <span class="w-1.5 h-5 rounded-full" style="background-color:#1a6b3c;"></span>
                <h3 class="text-base font-semibold text-gray-800">Tickets</h3>
                <span class="text-sm text-gray-400">· {{ $rangeLabel }}</span>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
                <div class="bg-white shadow-sm rounded-xl border border-gray-100 p-5">
                    <x-pie-chart :data="$byStatus" title="By status" />
                </div>
                <div class="bg-white shadow-sm rounded-xl border border-gray-100 p-5">
                    <x-pie-chart :data="$byPriority" title="By priority" />
                </div>
                <div class="bg-white shadow-sm rounded-xl border border-gray-100 p-5">
                    <x-pie-chart :data="$byCategory" title="By category" />
                </div>
            </div>
        </div>

        {{-- Every category, ranked, with count and share --}}
        @if($totalTickets > 0)
            <div class="bg-white shadow-sm rounded-xl border border-gray-100 p-5 sm:p-6">
                <div class="flex flex-wrap items-center justify-between gap-2 mb-5">
                    <div class="flex items-center gap-2">
                        <span class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0" style="background-color:#e8f3ec; color:#1a6b3c;">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z" /></svg>
                        </span>
                        <h3 class="text-sm font-semibold text-gray-700">Tickets by category</h3>
                    </div>
                    <span class="text-xs text-gray-400">{{ $rangeLabel }} · {{ number_format($totalTickets) }} tickets</span>
                </div>
                @php $maxC = max(1, collect($categoryBreakdown)->max('value')); @endphp
                <div class="space-y-2.5">
                    @foreach($categoryBreakdown as $row)
                        <div class="flex items-center gap-3 {{ $row['value'] === 0 ? 'opacity-50' : '' }}">
                            <span class="w-44 sm:w-56 shrink-0 text-sm text-gray-600 truncate" title="{{ $row['label'] }}">{{ $row['label'] }}</span>
                            <div class="flex-1 h-2.5 rounded-full bg-gray-100 overflow-hidden">
                                <div class="h-full rounded-full" style="width: {{ ($row['value'] / $maxC) * 100 }}%; background-color:#1a6b3c;"></div>
                            </div>
                            <span class="w-20 shrink-0 text-sm text-right tabular-nums text-gray-700"><span class="font-medium">{{ $row['value'] }}</span> <span class="text-gray-400">({{ $row['percent'] }}%)</span></span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- IT Engineer activity: tickets handled per IT engineer for a period of its own (default Today) --}}
        @php $act = $agentActivity; @endphp
        <div class="bg-white shadow-sm rounded-xl border border-gray-100 p-5 sm:p-6" id="it-activity">
            <div class="flex flex-wrap items-start justify-between gap-3 mb-5">
                <div class="flex items-center gap-2">
                    <span class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0" style="background-color:#e8f3ec; color:#1a6b3c;">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                    </span>
                    <div>
                        <h3 class="text-sm font-semibold text-gray-700">IT Engineer activity</h3>
                        <p class="text-xs text-gray-400">Tickets each IT engineer resolved · {{ $act['label'] }} · {{ $act['totalHandled'] }} total</p>
                    </div>
                </div>

                <form method="GET" action="{{ route('admin.reports.index') }}#it-activity" x-data="{ p: '{{ $act['period'] }}' }" class="flex flex-wrap items-end gap-2">
                    {{-- keep the main report period when this filter is applied --}}
                    @foreach(['range', 'from', 'to'] as $keep)
                        @if(request()->filled($keep))<input type="hidden" name="{{ $keep }}" value="{{ request($keep) }}">@endif
                    @endforeach
                    <div>
                        <label class="block text-[11px] font-semibold uppercase tracking-wide text-gray-500 mb-1">Period</label>
                        <select name="act" x-model="p" onchange="if (this.value !== 'custom') this.form.submit()" class="rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700">
                            @foreach($act['periods'] as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div x-show="p === 'custom'" x-cloak class="flex items-end gap-2">
                        <input type="date" name="act_from" value="{{ $act['from'] }}" class="rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700">
                        <input type="date" name="act_to" value="{{ $act['to'] }}" class="rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700">
                        <button type="submit" class="px-3 py-2 rounded-lg text-sm font-semibold text-white" style="background-color:#1a6b3c;">Apply</button>
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold uppercase tracking-wide text-gray-500 mb-1">IT Engineer</label>
                        <select name="act_agent" onchange="this.form.submit()" class="rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700">
                            <option value="all">All IT engineers</option>
                            @foreach($act['agents'] as $a)
                                <option value="{{ $a->id }}" @selected($act['selectedAgent'] === $a->id)>{{ $a->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </form>
            </div>

            @if(count($act['rows']) === 0)
                <p class="text-sm text-gray-400 py-6 text-center">No IT engineer accounts yet.</p>
            @else
                @php $maxH = max(1, collect($act['rows'])->max('handled')); @endphp
                <div class="space-y-3">
                    @foreach($act['rows'] as $row)
                        <div class="flex items-center gap-3">
                            <span class="w-40 sm:w-48 shrink-0 text-sm text-gray-700 truncate flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full shrink-0 {{ $row['online'] ? 'bg-green-500' : 'bg-gray-300' }}" title="{{ $row['online'] ? 'Online' : 'Offline' }}"></span>
                                <span class="truncate">{{ $row['name'] }}</span>
                            </span>
                            <div class="flex-1 h-2.5 rounded-full bg-gray-100 overflow-hidden">
                                <div class="h-full rounded-full" style="width: {{ ($row['handled'] / $maxH) * 100 }}%; background-color:#1a6b3c;"></div>
                            </div>
                            <span class="w-24 shrink-0 text-sm text-right tabular-nums"><span class="font-semibold text-gray-800">{{ $row['handled'] }}</span> <span class="text-gray-400 text-xs">resolved</span></span>
                            <span class="hidden sm:inline-block w-28 shrink-0 text-xs text-right text-gray-400 tabular-nums">{{ $row['working'] }} working now</span>
                        </div>
                    @endforeach
                </div>

                @if($act['selectedAgent'])
                    <div class="mt-6 border-t border-gray-100 pt-4">
                        @if($act['tickets']->isEmpty())
                            <p class="text-sm text-gray-400">No tickets resolved in this period.</p>
                        @else
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="text-left text-xs uppercase tracking-wide text-gray-400">
                                        <th class="py-2 pr-3 font-semibold">Ticket</th>
                                        <th class="py-2 pr-3 font-semibold hidden sm:table-cell">Category</th>
                                        <th class="py-2 pr-3 font-semibold hidden md:table-cell">Requested by</th>
                                        <th class="py-2 font-semibold text-right">Resolved</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach($act['tickets'] as $t)
                                        <tr>
                                            <td class="py-2.5 pr-3"><a href="{{ route('tickets.show', $t) }}" class="text-green-700 hover:underline"><span class="font-mono text-xs text-gray-400 mr-1">{{ $t->ticket_number }}</span>{{ $t->title }}</a></td>
                                            <td class="py-2.5 pr-3 text-gray-500 hidden sm:table-cell">{{ $t->category }}</td>
                                            <td class="py-2.5 pr-3 text-gray-500 hidden md:table-cell">{{ $t->creator->name ?? '—' }}</td>
                                            <td class="py-2.5 text-right text-gray-500 whitespace-nowrap">{{ $t->resolved_at->format('M j, g:i A') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    </div>
                @endif
            @endif
        </div>

        {{-- IT Engineer workload --}}
        @if(count($byAgent) > 0)
            <div class="bg-white shadow-sm rounded-xl border border-gray-100 p-5 sm:p-6">
                <div class="flex items-center gap-2 mb-5">
                    <span class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0" style="background-color:#e8f3ec; color:#1a6b3c;">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg>
                    </span>
                    <h3 class="text-sm font-semibold text-gray-700">Tickets handled per IT engineer</h3>
                </div>
                @php $maxA = max(1, collect($byAgent)->max()); @endphp
                <div class="space-y-3">
                    @foreach($byAgent as $agent => $count)
                        <div class="flex items-center gap-3">
                            <span class="w-32 shrink-0 text-sm text-gray-600 truncate">{{ $agent }}</span>
                            <div class="flex-1 h-2.5 rounded-full bg-gray-100 overflow-hidden">
                                <div class="h-full rounded-full" style="width: {{ ($count / $maxA) * 100 }}%; background-color:#1a6b3c;"></div>
                            </div>
                            <span class="w-8 shrink-0 text-sm font-medium text-gray-700 text-right tabular-nums">{{ $count }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Team reports: IT Engineers and Staff, each with Excel / PDF export --}}
        @php
            $teamReports = [
                ['data' => $engineerReport, 'team' => 'engineers', 'limit' => 10, 'icon' => 'M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437l1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008z'],
                ['data' => $staffReport, 'team' => 'staff', 'limit' => 10, 'icon' => 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z'],
            ];
        @endphp
        @foreach($teamReports as $rep)
            @php
                $t = $rep['data'];
                $shown = array_slice($t['rows'], 0, $rep['limit']);
                $hidden = count($t['rows']) - count($shown);
            @endphp
            <div class="bg-white shadow-sm rounded-xl border border-gray-100 p-5 sm:p-6" id="{{ $rep['team'] }}-report">
                <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
                    <div class="flex items-center gap-2">
                        <span class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0" style="background-color:#e8f3ec; color:#1a6b3c;">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $rep['icon'] }}" /></svg>
                        </span>
                        <div>
                            <h3 class="text-sm font-semibold text-gray-700">{{ $t['title'] }}</h3>
                            <p class="text-xs text-gray-400">{{ $rangeLabel }} · {{ count($t['rows']) }} {{ $rep['team'] === 'staff' ? 'staff' : 'IT engineers' }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('admin.reports.team.export', array_merge(request()->only('range', 'from', 'to'), ['team' => $rep['team'], 'format' => 'excel'])) }}"
                           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium border border-gray-200 text-gray-700 bg-white hover:bg-gray-50 transition">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.375 19.5h17.25m-17.25 0a1.125 1.125 0 01-1.125-1.125M3.375 19.5h7.5c.621 0 1.125-.504 1.125-1.125m-9.75 0V5.625m0 12.75v-1.5c0-.621.504-1.125 1.125-1.125m18.375 2.625V5.625m0 12.75c0 .621-.504 1.125-1.125 1.125m1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125m0 3.75h-7.5A1.125 1.125 0 0112 18.375m9.75-12.75c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125m19.5 0v1.5c0 .621-.504 1.125-1.125 1.125M2.25 5.625v1.5c0 .621.504 1.125 1.125 1.125m0 0h17.25m-17.25 0h7.5c.621 0 1.125.504 1.125 1.125M3.375 8.25c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125m17.25-3.75h-7.5c-.621 0-1.125.504-1.125 1.125m8.625-1.125c.621 0 1.125.504 1.125 1.125v1.5c0 .621-.504 1.125-1.125 1.125m-17.25 0h7.5m-7.5 0c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125M12 10.875v-1.5m0 1.5c0 .621-.504 1.125-1.125 1.125M12 10.875c0 .621.504 1.125 1.125 1.125m-2.25 0c.621 0 1.125.504 1.125 1.125M13.125 12h7.5m-7.5 0c-.621 0-1.125.504-1.125 1.125M20.625 12c.621 0 1.125.504 1.125 1.125v1.5c0 .621-.504 1.125-1.125 1.125m-17.25 0h7.5M12 14.625v-1.5m0 1.5c0 .621-.504 1.125-1.125 1.125M12 14.625c0 .621.504 1.125 1.125 1.125m-2.25 0c.621 0 1.125.504 1.125 1.125m0 1.5v-1.5m0 0c0-.621.504-1.125 1.125-1.125m0 0h7.5" /></svg>
                            Excel
                        </a>
                        <a href="{{ route('admin.reports.team.export', array_merge(request()->only('range', 'from', 'to'), ['team' => $rep['team'], 'format' => 'pdf'])) }}"
                           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium text-white shadow-sm hover:opacity-90 transition" style="background-color:#1a6b3c;">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m0 0l-4-4m4 4l4-4M4 17v2a2 2 0 002 2h12a2 2 0 002-2v-2" /></svg>
                            PDF
                        </a>
                    </div>
                </div>

                @if(count($shown) === 0)
                    <p class="text-sm text-gray-400 py-6 text-center">No {{ $rep['team'] === 'staff' ? 'staff' : 'IT engineer' }} accounts yet.</p>
                @else
                    <table class="w-full text-sm table-fixed">
                        <thead>
                            <tr class="text-left text-[11px] uppercase tracking-wide text-gray-400 border-b border-gray-100">
                                @foreach($t['headers'] as $i => $h)
                                    {{-- Less important columns drop out on small screens so the table never scrolls sideways --}}
                                    <th class="py-2 pr-3 font-semibold {{ in_array($i, $t['numeric']) ? 'text-right' : '' }} {{ $i === 0 ? 'w-[30%]' : '' }} {{ $i >= 4 && ! in_array($i, [4]) ? 'hidden md:table-cell' : '' }} {{ $i === 1 || ($rep['team'] === 'staff' && $i === 2) ? 'hidden sm:table-cell' : '' }}">{{ $h }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($shown as $row)
                                <tr>
                                    @foreach($row as $i => $cell)
                                        <td class="py-2.5 pr-3 truncate {{ in_array($i, $t['numeric']) ? 'text-right tabular-nums' : 'text-gray-500' }} {{ $i === 0 ? 'font-medium text-gray-800' : '' }} {{ $i === 3 ? 'font-semibold text-gray-800' : '' }} {{ $i >= 4 && ! in_array($i, [4]) ? 'hidden md:table-cell' : '' }} {{ $i === 1 || ($rep['team'] === 'staff' && $i === 2) ? 'hidden sm:table-cell' : '' }}" title="{{ $cell }}">{{ $cell }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <p class="mt-3 text-xs text-gray-400">
                        @if($hidden > 0) Showing the top {{ count($shown) }} of {{ count($t['rows']) }} — export for the full list. · @endif
                        {{ $t['note'] }}
                    </p>
                @endif
            </div>
        @endforeach

        {{-- Assets + Users pie charts --}}
        <div>
            <div class="flex items-center gap-2 mb-3">
                <span class="w-1.5 h-5 rounded-full bg-gray-300"></span>
                <h3 class="text-base font-semibold text-gray-800">Assets &amp; users</h3>
                <span class="text-sm text-gray-400">· Current snapshot</span>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                <div class="bg-white shadow-sm rounded-xl border border-gray-100 p-5">
                    <x-pie-chart :data="$assetsByType" title="Assets by type" emptyText="No assets yet" />
                </div>
                <div class="bg-white shadow-sm rounded-xl border border-gray-100 p-5">
                    <x-pie-chart :data="[
                        ['label' => 'Assigned', 'value' => $assignedAssets, 'color' => '#1a6b3c', 'percent' => $totalAssets > 0 ? round($assignedAssets / $totalAssets * 100, 1) : 0],
                        ['label' => 'Unassigned', 'value' => $unassignedAssets, 'color' => '#f59e0b', 'percent' => $totalAssets > 0 ? round($unassignedAssets / $totalAssets * 100, 1) : 0],
                    ]" title="Assignment" emptyText="No assets yet" />
                </div>
                <div class="bg-white shadow-sm rounded-xl border border-gray-100 p-5">
                    <x-pie-chart :data="$assetsByStatus" title="Asset condition" emptyText="No assets yet" />
                </div>
                <div class="bg-white shadow-sm rounded-xl border border-gray-100 p-5">
                    <x-pie-chart :data="$usersByRole" title="Accounts by role" emptyText="No users yet" />
                </div>
            </div>
        </div>

        <p class="text-center text-xs text-gray-400 pt-2">Crest Forwarder Inc. — IT Service Desk · Internal use only</p>
    </div>
</x-app-layout>
