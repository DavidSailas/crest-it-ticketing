@props([
    'data' => [],       // [['label' => 'Open', 'value' => 12, 'color' => '#3b82f6', 'percent' => 40.0], ...]
    'title' => null,
    'size' => 160,       // px, viewBox is square
    'donut' => true,     // ring vs full pie
    'emptyText' => 'No data for this range',
])

@php
    // Pure server-side SVG (no JS, no chart library) so this renders
    // identically in the browser and inside the dompdf PDF export.
    $total = collect($data)->sum('value');
    $r = $size / 2;
    $strokeWidth = $donut ? $r * 0.42 : $r;          // donut ring thickness, or a filled pie
    $radius = $donut ? $r - $strokeWidth / 2 : $r - 1;
    $circumference = 2 * M_PI * $radius;
    $cursor = -90; // start at 12 o'clock
@endphp

<div class="flex flex-col items-center">
    @if($title)
        <p class="text-sm font-semibold text-gray-700 mb-3">{{ $title }}</p>
    @endif

    @if($total <= 0)
        <div class="flex flex-col items-center justify-center text-center" style="width:{{ $size }}px; height:{{ $size }}px;">
            <svg width="{{ $size * 0.4 }}" height="{{ $size * 0.4 }}" viewBox="0 0 24 24" fill="none" stroke="#d1d5db" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75c0-1.03.84-1.875 2.25-1.875s2.25.845 2.25 1.875c0 .857-.628 1.245-1.409 1.891l-.091.075C12.081 12.211 12 12.847 12 13.5m0 3.75h.008v.008H12v-.008zM21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <p class="text-xs text-gray-400 mt-2">{{ $emptyText }}</p>
        </div>
    @else
        <svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 {{ $size }} {{ $size }}">
            @if(count($data) === 1)
                <circle cx="{{ $r }}" cy="{{ $r }}" r="{{ $radius }}" fill="none" stroke="{{ $data[0]['color'] }}" stroke-width="{{ $strokeWidth }}" />
            @else
                @foreach($data as $slice)
                    @php
                        $fraction = $slice['value'] / $total;
                        $dash = max($fraction * $circumference - 1.5, 0); // 1.5px gap between slices
                        $gap = $circumference - $dash;
                        $rotation = $cursor;
                        $cursor += $fraction * 360;
                    @endphp
                    <circle cx="{{ $r }}" cy="{{ $r }}" r="{{ $radius }}" fill="none"
                            stroke="{{ $slice['color'] }}" stroke-width="{{ $strokeWidth }}"
                            stroke-dasharray="{{ $dash }} {{ $gap }}" stroke-dashoffset="0"
                            transform="rotate({{ $rotation }} {{ $r }} {{ $r }})" />
                @endforeach
            @endif

            @if($donut)
                <text x="{{ $r }}" y="{{ $r - 4 }}" text-anchor="middle" font-size="{{ $size * 0.16 }}" font-weight="700" fill="#1f2937">{{ number_format($total) }}</text>
                <text x="{{ $r }}" y="{{ $r + 14 }}" text-anchor="middle" font-size="{{ $size * 0.075 }}" fill="#9ca3af">total</text>
            @endif
        </svg>
    @endif

    {{-- Legend --}}
    @if($total > 0)
        <div class="mt-4 w-full space-y-1.5">
            @foreach($data as $slice)
                <div class="flex items-center justify-between gap-3 text-xs">
                    <span class="flex items-center gap-1.5 min-w-0 text-gray-600">
                        <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background-color:{{ $slice['color'] }};"></span>
                        <span class="truncate">{{ $slice['label'] }}</span>
                    </span>
                    <span class="shrink-0 font-medium text-gray-700 tabular-nums">{{ $slice['value'] }} <span class="text-gray-400 font-normal">({{ $slice['percent'] }}%)</span></span>
                </div>
            @endforeach
        </div>
    @endif
</div>
