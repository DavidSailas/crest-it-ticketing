@props([
    'id',
    'action' => null,
    // Pass the id of a form that lives elsewhere on the page to submit THAT
    // form instead of `action` (used by "Unassign asset").
    'form' => null,
    'method' => 'DELETE',
    'title',
    'message',
    'confirmLabel' => 'Delete',
    'confirmClass' => 'bg-red-600 hover:bg-red-700',
    'cancelLabel' => 'Cancel',
    'triggerLabel' => 'Delete',
    'triggerClass' => 'text-red-500 hover:underline font-medium text-xs',
    'tone' => 'danger',
    'busyLabel' => null,
])

@php
    $tones = [
        'danger'  => ['icon' => 'bg-red-100 text-red-600',     'ring' => 'focus-visible:ring-red-500'],
        'warning' => ['icon' => 'bg-amber-100 text-amber-600', 'ring' => 'focus-visible:ring-amber-500'],
    ];
    $t = $tones[$tone] ?? $tones['danger'];

    $isDelete = strtoupper($method) === 'DELETE';

    // Real deletions get a bin icon; everything else gets the warning triangle.
    $iconPath = ($isDelete && $tone === 'danger')
        ? 'M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16'
        : 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z';

    $busyText = $busyLabel ?? ($isDelete ? 'Deleting…' : 'Please wait…');
    $titleId  = $id.'-title';
@endphp

<button type="button" x-data="" x-on:click.prevent="$dispatch('open-modal', '{{ $id }}')" class="{{ $triggerClass }}">
    {{ $triggerLabel }}
</button>

<x-modal :name="$id" maxWidth="md" :labelledby="$titleId" focusable>
    <div class="px-6 pb-5 pt-6 text-center sm:flex sm:items-start sm:gap-4 sm:text-left">
        <span class="mx-auto flex h-11 w-11 shrink-0 items-center justify-center rounded-full sm:mx-0 {{ $t['icon'] }}">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $iconPath }}" />
            </svg>
        </span>

        <div class="mt-3 min-w-0 flex-1 sm:mt-0.5">
            <h2 id="{{ $titleId }}" class="text-lg font-semibold leading-6 text-gray-900">{{ $title }}</h2>
            <p class="mt-2 text-sm leading-relaxed text-gray-600">{{ $message }}</p>

            @if(! $slot->isEmpty())
                <div class="mt-3 text-sm text-gray-600">{{ $slot }}</div>
            @endif
        </div>
    </div>

    <form
        x-data="{ busy: false }"
        @if($form)
            x-on:submit.prevent
        @else
            method="POST"
            action="{{ $action }}"
            x-on:submit="busy = true"
        @endif
        x-on:pageshow.window="busy = false"
        class="flex flex-col-reverse gap-2 border-t border-gray-100 bg-gray-50 px-6 py-4 sm:flex-row sm:justify-end sm:gap-3"
    >
        @unless($form)
            @csrf
            @if(strtoupper($method) !== 'POST')
                @method($method)
            @endif
        @endunless

        <button
            type="button"
            data-autofocus
            x-on:click="$dispatch('close')"
            x-bind:disabled="busy"
            class="inline-flex justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-400 focus-visible:ring-offset-2 disabled:opacity-60 sm:py-2"
        >
            {{ $cancelLabel }}
        </button>

        <button
            type="submit"
            @if($form) form="{{ $form }}" x-on:click="setTimeout(() => busy = true)" @endif
            x-bind:disabled="busy"
            class="inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-70 sm:py-2 {{ $t['ring'] }} {{ $confirmClass }}"
        >
            <svg x-show="busy" x-cloak class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
            </svg>
            <span x-text="busy ? @js($busyText) : @js($confirmLabel)">{{ $confirmLabel }}</span>
        </button>
    </form>
</x-modal>
