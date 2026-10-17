@props(['active'])

{{-- Desktop top-bar link. Never wraps; the active page gets the brand-green underline. --}}
@php
$base = 'inline-flex items-center border-b-2 px-2.5 text-sm font-medium leading-5 whitespace-nowrap transition duration-150 ease-in-out focus:outline-none focus-visible:bg-gray-50 xl:px-3';
$classes = ($active ?? false)
            ? "$base border-[#1a6b3c] text-gray-900"
            : "$base border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-800";
@endphp

<a {{ $attributes->merge(['class' => $classes, 'aria-current' => ($active ?? false) ? 'page' : null]) }}>
    {{ $slot }}
</a>
