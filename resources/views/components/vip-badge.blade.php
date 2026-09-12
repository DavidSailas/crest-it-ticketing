@props(['size' => 'default'])

@php
    $isCompact = $size === 'compact';
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1 rounded-full font-medium bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-200 " . ($isCompact ? 'px-1.5 py-0.5 text-[10px]' : 'px-2.5 py-1 text-xs')]) }}
      title="VIP account — tickets are automatically set to Critical priority">
    <svg class="{{ $isCompact ? 'w-2.5 h-2.5' : 'w-3 h-3' }}" viewBox="0 0 20 20" fill="currentColor">
        <path d="M10 1.5l2.29 4.64 5.12.74-3.7 3.61.87 5.1L10 13.9l-4.58 2.4.87-5.1-3.7-3.61 5.12-.74L10 1.5z" />
    </svg>
    VIP
</span>
