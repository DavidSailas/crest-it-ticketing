@props([
    'name',
    'show' => false,
    'maxWidth' => '2xl',
    'labelledby' => null,
])

@php
$maxWidth = [
    'sm' => 'sm:max-w-sm',
    'md' => 'sm:max-w-md',
    'lg' => 'sm:max-w-lg',
    'xl' => 'sm:max-w-xl',
    '2xl' => 'sm:max-w-2xl',
][$maxWidth];
@endphp

{{--
    The modal is teleported to <body> so it never inherits styles from where
    it was written. Before this, a modal placed inside a table cell picked up
    that cell's `whitespace-nowrap` / `text-right` and got clipped.
--}}
{{-- Alpine only runs x-teleport inside an x-data scope, hence this (hidden) wrapper. --}}
<div x-data class="hidden" aria-hidden="true">
<template x-teleport="body">
    <div
        x-data="{
            show: @js($show),
            focusables() {
                // All focusable element types...
                let selector = 'a, button, input:not([type=\'hidden\']), textarea, select, details, [tabindex]:not([tabindex=\'-1\'])'
                return [...$el.querySelectorAll(selector)]
                    // All non-disabled elements...
                    .filter(el => ! el.hasAttribute('disabled'))
            },
            firstFocusable() { return this.focusables()[0] },
            lastFocusable() { return this.focusables().slice(-1)[0] },
            nextFocusable() { return this.focusables()[this.nextFocusableIndex()] || this.firstFocusable() },
            prevFocusable() { return this.focusables()[this.prevFocusableIndex()] || this.lastFocusable() },
            nextFocusableIndex() { return (this.focusables().indexOf(document.activeElement) + 1) % (this.focusables().length + 1) },
            prevFocusableIndex() { return Math.max(0, this.focusables().indexOf(document.activeElement)) -1 },
            // Start on the safe choice (Cancel) when a button asks for it,
            // otherwise on the first focusable element.
            initialFocus() {
                let target = $el.querySelector('[data-autofocus]') || this.firstFocusable()
                if (target) target.focus()
            },
        }"
        x-init="$watch('show', value => {
            if (value) {
                document.body.classList.add('overflow-y-hidden');
                {{ $attributes->has('focusable') ? 'setTimeout(() => initialFocus(), 100)' : '' }}
            } else {
                document.body.classList.remove('overflow-y-hidden');
            }
        })"
        x-on:open-modal.window="$event.detail == '{{ $name }}' ? show = true : null"
        x-on:close-modal.window="$event.detail == '{{ $name }}' ? show = false : null"
        x-on:close.stop="show = false"
        x-on:keydown.escape.window="show = false"
        x-on:keydown.tab.prevent="$event.shiftKey || nextFocusable().focus()"
        x-on:keydown.shift.tab.prevent="prevFocusable().focus()"
        x-show="show"
        class="fixed inset-0 z-50 overflow-y-auto"
        style="display: {{ $show ? 'block' : 'none' }};"
        role="dialog"
        aria-modal="true"
        @if($labelledby) aria-labelledby="{{ $labelledby }}" @endif
    >
        {{-- Dimmed, softly blurred backdrop --}}
        <div
            x-show="show"
            class="fixed inset-0 bg-gray-900/50 backdrop-blur-[2px]"
            aria-hidden="true"
            x-transition:enter="ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
        ></div>

        {{-- Centered on desktop, docked to the bottom on phones. Clicking the empty area closes it. --}}
        <div class="relative flex min-h-full items-end justify-center p-4 sm:items-center sm:p-6" x-on:click.self="show = false">
            <div
                x-show="show"
                class="relative w-full {{ $maxWidth }} overflow-hidden rounded-2xl bg-white text-left whitespace-normal break-words shadow-2xl ring-1 ring-gray-900/5"
                x-transition:enter="ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            >
                {{ $slot }}
            </div>
        </div>
    </div>
</template>
</div>
