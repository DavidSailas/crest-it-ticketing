@props(['status'])

@php
    $labels = [
        'active' => 'Active',
        'in_repair' => 'In Repair',
        'retired' => 'Retired',
    ];
    $styles = [
        'active' => 'bg-green-100 text-green-700',
        'in_repair' => 'bg-amber-100 text-amber-700',
        'retired' => 'bg-gray-200 text-gray-600',
    ][$status] ?? 'bg-gray-100 text-gray-600';
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium whitespace-nowrap $styles"]) }}>
    {{ $labels[$status] ?? ucfirst(str_replace('_', ' ', $status)) }}
</span>
