{{-- Brand secondary button (white, grey border). --}}
<button {{ $attributes->merge(['type' => 'button', 'class' => 'btn btn-secondary']) }}>
    {{ $slot }}
</button>
