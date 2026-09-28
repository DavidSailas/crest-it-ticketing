@props(['user' => null, 'empty' => 'Unassigned', 'sub' => null, 'you' => false, 'size' => 'xs'])

{{-- One consistent way to show a person in a table cell: avatar + name on a
     single line (never wraps into a tall column), with optional VIP / "You"
     markers and a small subtitle. Long names are cut with an ellipsis. --}}
@if($user)
    <span class="inline-flex items-center gap-2.5 min-w-0 max-w-full align-middle">
        <x-avatar :user="$user" :size="$size" />
        <span class="min-w-0 leading-tight">
            <span class="flex items-center gap-1.5 min-w-0">
                <span class="truncate max-w-[13rem] text-gray-800 font-medium">{{ $user->name }}</span>
                @if($you)
                    <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold uppercase tracking-wide bg-green-100 text-green-700 shrink-0">You</span>
                @endif
                @if($user->is_vip ?? false)
                    <x-vip-badge size="compact" />
                @endif
            </span>
            @if($sub)
                <span class="block truncate max-w-[13rem] text-xs text-gray-400 mt-0.5">{{ $sub }}</span>
            @endif
        </span>
    </span>
@else
    <span class="inline-flex items-center gap-1.5 text-xs text-gray-400 whitespace-nowrap">
        <span class="w-6 h-6 rounded-full border border-dashed border-gray-300 flex items-center justify-center">
            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5" /></svg>
        </span>
        {{ $empty }}
    </span>
@endif
