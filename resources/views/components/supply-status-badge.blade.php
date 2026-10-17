@props(['status'])

{{-- Four distinct warning levels: green (ok), amber (low), orange + pulsing dot (critical), red (out). --}}
@php
    $labels = \App\Models\Supply::STOCK_STATUSES;
    $styles = [
        'ok' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'low' => 'bg-amber-50 text-amber-700 ring-amber-200',
        'critical' => 'bg-orange-50 text-orange-700 ring-orange-300 font-semibold',
        'out' => 'bg-rose-50 text-rose-700 ring-rose-200',
    ][$status] ?? 'bg-gray-100 text-gray-500 ring-gray-200';
    $dot = [
        'ok' => 'bg-emerald-500',
        'low' => 'bg-amber-500',
        'critical' => 'bg-orange-500 animate-pulse',
        'out' => 'bg-rose-500',
    ][$status] ?? 'bg-slate-400';
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium ring-1 ring-inset whitespace-nowrap $styles"]) }}>
    <span class="w-1.5 h-1.5 rounded-full {{ $dot }}"></span>
    {{ $labels[$status] ?? ucfirst($status) }}
</span>
