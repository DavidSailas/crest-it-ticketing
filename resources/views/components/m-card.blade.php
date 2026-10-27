@props(['href' => null, 'label' => null, 'actionsClass' => 'm-actions flex flex-wrap'])

{{--
    One list row as a card, for phones (tables are used from tablet width up).

      <x-m-card :href="route(...)" label="View Dell Laptop">
          <x-slot name="lead">…icon or photo…</x-slot>
          <x-slot name="title">Dell Latitude 5420</x-slot>
          <x-slot name="sub">CFI-CEB-ACC-LT-001</x-slot>
          <x-slot name="badge">…status…</x-slot>
          <x-m-meta label="Branch">Cebu</x-m-meta> …
          <x-slot name="actions"><a class="btn btn-secondary btn-sm flex-1">View</a> …</x-slot>
      </x-m-card>

    With an href the whole card is tappable; the buttons in "actions" stay independent.
--}}
<article {{ $attributes->merge(['class' => 'relative overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm']) }}>
    @if($href)
        <a href="{{ $href }}" class="absolute inset-0 z-0 rounded-xl focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-green-700" aria-label="{{ $label ?? 'Open' }}"></a>
    @endif

    <div class="p-4">
        <div class="flex items-start gap-3">
            @isset($lead)<div class="shrink-0">{{ $lead }}</div>@endisset
            <div class="min-w-0 flex-1">
                <div class="break-words text-[15px] font-semibold leading-snug text-gray-900">{{ $title }}</div>
                @isset($sub)<div class="mt-0.5 break-words text-xs text-gray-500">{{ $sub }}</div>@endisset
            </div>
            @isset($badge)<div class="shrink-0">{{ $badge }}</div>@endisset
        </div>

        @if($slot->isNotEmpty())
            <dl class="mt-3 grid grid-cols-2 gap-x-4 gap-y-2.5">{{ $slot }}</dl>
        @endif
    </div>

    @isset($actions)
        <div class="relative {{ $actionsClass }} gap-2 border-t border-gray-100 bg-gray-50/70 p-2.5">{{ $actions }}</div>
    @endisset
</article>
