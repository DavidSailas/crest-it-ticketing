@php
    $shown = array_slice($t['rows'], 0, $limit ?? 10);
    $hidden = count($t['rows']) - count($shown);
    $exportParams = ['range' => $selectedRange, 'team' => $team];
@endphp
<div class="bg-white shadow-sm rounded-xl border border-gray-100 p-5 sm:p-6">
    <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
        <div class="flex items-center gap-2">
            <span class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0" style="background-color:#e8f3ec; color:#1a6b3c;">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125L7.5 8.25l4 4 5.5-6 4 4.5" /></svg>
            </span>
            <div>
                <h3 class="text-sm font-semibold text-gray-700">{{ $t['title'] }}</h3>
                <p class="text-xs text-gray-400">{{ $rangeLabel }} · {{ count($t['rows']) }} {{ $count_label }}</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('reports.export', $exportParams + ['format' => 'excel']) }}"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium border border-gray-200 text-gray-700 bg-white hover:bg-gray-50 transition">Excel</a>
            <a href="{{ route('reports.export', $exportParams + ['format' => 'pdf']) }}"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium text-white shadow-sm hover:opacity-90 transition" style="background-color:#1a6b3c;">PDF</a>
        </div>
    </div>

    @if(count($shown) === 0)
        <p class="text-sm text-gray-400 py-6 text-center">{{ $empty_text }}</p>
    @else
        <table class="w-full text-sm table-fixed">
            <thead>
                <tr class="text-left text-[11px] uppercase tracking-wide text-gray-400 border-b border-gray-100">
                    @foreach($t['headers'] as $i => $h)
                        <th class="py-2 pr-3 font-semibold {{ in_array($i, $t['numeric']) ? 'text-right' : '' }} {{ $i === 0 ? 'w-[26%]' : '' }} {{ $i >= 5 ? 'hidden md:table-cell' : '' }} {{ $i === 1 || $i === 2 ? 'hidden sm:table-cell' : '' }}">{{ $h }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($shown as $row)
                    <tr>
                        @foreach($row as $i => $cell)
                            <td class="py-2.5 pr-3 truncate {{ in_array($i, $t['numeric']) ? 'text-right tabular-nums' : 'text-gray-500' }} {{ $i === 0 ? 'font-medium text-gray-800' : '' }} {{ $i >= 5 ? 'hidden md:table-cell' : '' }} {{ $i === 1 || $i === 2 ? 'hidden sm:table-cell' : '' }}" title="{{ $cell }}">{{ $cell }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
        <p class="mt-3 text-xs text-gray-400">
            @if($hidden > 0) Showing the first {{ count($shown) }} of {{ count($t['rows']) }} — export for the full list. · @endif
            {{ $t['note'] }}
        </p>
    @endif
</div>
