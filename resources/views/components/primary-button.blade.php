{{-- Brand primary button (the shared .btn system: 44px on phones, brand green, white label). --}}
<button {{ $attributes->merge(['type' => 'submit', 'class' => 'btn btn-primary']) }}>
    {{ $slot }}
</button>
