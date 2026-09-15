@props(['user' => null, 'size' => 'md'])

@php
    $user = $user ?? auth()->user();
    $sizes = [
        'sm' => 'w-8 h-8 text-xs',
        'md' => 'w-10 h-10 text-sm',
        'lg' => 'w-20 h-20 text-2xl',
        'xl' => 'w-28 h-28 text-4xl',
    ];
    $sizeClasses = $sizes[$size] ?? $sizes['md'];

    $initials = collect(explode(' ', $user->name))
        ->map(fn ($part) => mb_substr($part, 0, 1))
        ->take(2)
        ->implode('');
@endphp

@if($user->avatar)
    <img src="{{ \Illuminate\Support\Facades\Storage::url($user->avatar) }}"
         alt="{{ $user->name }}"
         {{ $attributes->merge(['class' => "$sizeClasses rounded-full object-cover shrink-0"]) }}>
@else
    <span {{ $attributes->merge(['class' => "$sizeClasses rounded-full flex items-center justify-center font-semibold shrink-0 text-white"]) }}
          style="background-color:#1a6b3c;">
        {{ strtoupper($initials) ?: '?' }}
    </span>
@endif
