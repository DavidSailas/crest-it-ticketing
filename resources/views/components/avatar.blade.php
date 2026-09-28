@props(['user' => null, 'size' => 'md'])

@php
    $user = $user ?? auth()->user();
    $sizes = [
        'xs' => 'w-6 h-6 text-[10px]',
        'sm' => 'w-8 h-8 text-xs',
        'md' => 'w-10 h-10 text-sm',
        'lg' => 'w-20 h-20 text-2xl',
        'xl' => 'w-32 h-32 text-4xl',
    ];
    $sizeClasses = $sizes[$size] ?? $sizes['md'];

    // First + last name initials (so "David Sailas Villondo" reads "DV", not "DS").
    $parts = array_values(array_filter(preg_split('/\s+/', trim($user->name))));
    $initials = count($parts) > 1
        ? mb_substr($parts[0], 0, 1).mb_substr(end($parts), 0, 1)
        : mb_substr($parts[0] ?? '', 0, 2);
@endphp

@if($user->avatar)
    <img src="{{ \Illuminate\Support\Facades\Storage::url($user->avatar) }}"
         alt="{{ $user->name }}"
         {{ $attributes->merge(['class' => "$sizeClasses rounded-full object-cover shrink-0 ring-2 ring-white shadow-sm"]) }}>
@else
    <span {{ $attributes->merge(['class' => "$sizeClasses rounded-full flex items-center justify-center font-semibold tracking-wide shrink-0 text-white ring-2 ring-white shadow-sm select-none"]) }}
          style="background-image:linear-gradient(135deg,#1a6b3c 0%,#2f9e5f 100%);">
        {{ strtoupper($initials) ?: '?' }}
    </span>
@endif
