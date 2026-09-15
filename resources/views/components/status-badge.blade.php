@props(['status'])

@php
    $labels = [
        'open' => 'Open',
        'in_progress' => 'In Progress',
        'pending' => 'Pending',
        'resolved' => 'Resolved',
        'closed' => 'Closed',
    ];
    $styles = [
        'open' => 'bg-amber-100 text-amber-700',
        'in_progress' => 'bg-blue-100 text-blue-700',
        'pending' => 'bg-purple-100 text-purple-700',
        'resolved' => 'bg-green-100 text-green-700',
        'closed' => 'bg-gray-200 text-gray-600',
    ][$status] ?? 'bg-gray-100 text-gray-600';

    $dot = [
        'open' => 'bg-amber-500',
        'in_progress' => 'bg-blue-500',
        'pending' => 'bg-purple-500',
        'resolved' => 'bg-green-600',
        'closed' => 'bg-gray-400',
    ][$status] ?? 'bg-gray-400';
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium whitespace-nowrap $styles"]) }}>
    <span class="w-1.5 h-1.5 rounded-full {{ $dot }}"></span>
    {{ $labels[$status] ?? ucfirst(str_replace('_', ' ', $status)) }}
</span>
