@props(['label', 'wide' => false])

{{-- A small "label / value" pair inside <x-m-card>. wide = span both columns. --}}
<div {{ $attributes->merge(['class' => 'min-w-0 '.($wide ? 'col-span-2' : '')]) }}>
    <dt class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">{{ $label }}</dt>
    <dd class="mt-0.5 break-words text-sm text-gray-800">{{ $slot }}</dd>
</div>
