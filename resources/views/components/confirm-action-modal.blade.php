@props([
    'id',
    'action',
    'method' => 'DELETE',
    'title',
    'message',
    'confirmLabel' => 'Delete',
    'confirmClass' => 'bg-red-600 hover:bg-red-700',
    'cancelLabel' => 'Cancel',
    'triggerLabel' => 'Delete',
    'triggerClass' => 'text-red-500 hover:underline font-medium text-xs',
    'tone' => 'danger',
])

@php
    $toneStyles = [
        'danger' => 'bg-red-50 text-red-500',
        'warning' => 'bg-amber-50 text-amber-600',
    ];
    $iconBg = $toneStyles[$tone] ?? $toneStyles['danger'];
@endphp

<button type="button" x-data="" x-on:click.prevent="$dispatch('open-modal', '{{ $id }}')" class="{{ $triggerClass }}">
    {{ $triggerLabel }}
</button>

<x-modal :name="$id" maxWidth="sm" focusable>
    <div class="p-6">
        <div class="flex items-start gap-4">
            <span class="flex items-center justify-center w-10 h-10 rounded-full shrink-0 {{ $iconBg }}">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                </svg>
            </span>
            <div class="flex-1 min-w-0 pt-0.5">
                <h2 class="text-base font-semibold text-gray-800">{{ $title }}</h2>
                <p class="mt-1.5 text-sm text-gray-500">{{ $message }}</p>
            </div>
        </div>

        <form method="POST" action="{{ $action }}" class="mt-6 flex justify-end gap-3">
            @csrf
            @if(strtoupper($method) !== 'POST')
                @method($method)
            @endif
            <button type="button" x-on:click="$dispatch('close')"
                    class="px-4 py-2 rounded-lg text-sm font-medium text-gray-600 border border-gray-300 hover:bg-gray-50">
                {{ $cancelLabel }}
            </button>
            <button type="submit" class="px-4 py-2 rounded-lg text-sm font-semibold text-white transition {{ $confirmClass }}">
                {{ $confirmLabel }}
            </button>
        </form>
    </div>
</x-modal>
