@props(['href', 'label' => 'View'])

<a href="{{ $href }}" {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold text-green-700 hover:bg-green-50 transition whitespace-nowrap']) }}>
    {{ $label }}
    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
</a>
