@props(['supply', 'size' => 'sm', 'full' => false])

{{--
    An item's photo, or — when it has none — a tidy tile with its category icon, so lists
    stay aligned. Sizes: sm 44px (lists) · md 56px · xl 80px (dialogs).
    The small thumbnail is used unless :full="true".
--}}
@php
    $box = [
        'sm' => 'h-11 w-11 rounded-lg',
        'md' => 'h-14 w-14 rounded-xl',
        'xl' => 'h-20 w-20 rounded-xl',
    ][$size] ?? 'h-11 w-11 rounded-lg';
    $icon = ['sm' => 'w-5 h-5', 'md' => 'w-6 h-6', 'xl' => 'w-8 h-8'][$size] ?? 'w-5 h-5';
    $url = $full ? $supply->image_url : $supply->thumb_url;
@endphp

@if($url)
    <img src="{{ $url }}" alt="{{ $supply->name }}" loading="lazy" decoding="async"
         {{ $attributes->merge(['class' => "$box shrink-0 bg-gray-100 object-cover ring-1 ring-black/5"]) }}>
@else
    <span aria-hidden="true" {{ $attributes->merge(['class' => "$box flex shrink-0 items-center justify-center text-green-800"]) }}
          style="background:linear-gradient(135deg,#dff2e6,#c4e6d0);">
        <x-supply-icon :category="$supply->category" class="{{ $icon }}" />
    </span>
@endif
