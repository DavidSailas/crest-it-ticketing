@props(['user' => null, 'size' => 'lg'])

@php
    $user = $user ?? auth()->user();
@endphp

@if($user->avatar)
    <div x-data="{ showFull: false }" class="inline-block">
        <button type="button" @click="showFull = true"
                class="block rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-700"
                title="View full photo">
            <x-avatar :user="$user" :size="$size" {{ $attributes }} />
        </button>

        {{-- Full-size preview — click the backdrop, the close button, or press
             Escape to dismiss. Kept out of the page's normal flow (fixed +
             high z-index) so it overlays everything, including modals. --}}
        <div x-show="showFull" x-cloak x-transition.opacity
             class="fixed inset-0 z-[60] bg-black/80 flex items-center justify-center p-4"
             @click.self="showFull = false" @keydown.escape.window="showFull = false" style="display: none;">
            <button type="button" @click="showFull = false"
                    class="absolute top-4 right-4 sm:top-6 sm:right-6 text-white/70 hover:text-white transition">
                <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
            <img src="{{ \Illuminate\Support\Facades\Storage::url($user->avatar) }}" alt="{{ $user->name }}"
                 class="max-w-[90vw] max-h-[85vh] rounded-xl shadow-2xl object-contain">
        </div>
    </div>
@else
    <x-avatar :user="$user" :size="$size" {{ $attributes }} />
@endif
