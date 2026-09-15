@props(['priority'])

@php
    $styles = [
        'low' => 'bg-gray-100 text-gray-600',
        'medium' => 'bg-amber-100 text-amber-700',
        'high' => 'bg-orange-100 text-orange-700',
        'critical' => 'bg-red-100 text-red-700',
    ][$priority] ?? 'bg-gray-100 text-gray-600';

    $dot = [
        'low' => 'bg-gray-400',
        'medium' => 'bg-amber-500',
        'high' => 'bg-orange-500',
        'critical' => 'bg-red-600',
    ][$priority] ?? 'bg-gray-400';

    $level = [
        'low' => 'P4',
        'medium' => 'P3',
        'high' => 'P2',
        'critical' => 'P1',
    ][$priority] ?? '';
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium capitalize whitespace-nowrap $styles"]) }}>
    <span class="w-1.5 h-1.5 rounded-full {{ $dot }}"></span>
    @if($level)<span class="font-semibold">{{ $level }}</span>@endif
    {{ $priority }}
</span>
