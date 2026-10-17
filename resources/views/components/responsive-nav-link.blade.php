@props(['active'])

{{-- Phone / tablet menu link: a roomy 48px tap target, brand-green when active. --}}
@php
$base = 'block w-full rounded-lg border-l-4 px-3 py-3 text-start text-base font-medium transition duration-150 ease-in-out focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-green-700';
$classes = ($active ?? false)
            ? "$base border-[#1a6b3c] bg-green-50 text-green-800"
            : "$base border-transparent text-gray-600 hover:bg-gray-50 hover:text-gray-900";
@endphp

<a {{ $attributes->merge(['class' => $classes, 'aria-current' => ($active ?? false) ? 'page' : null]) }}>
    {{ $slot }}
</a>
