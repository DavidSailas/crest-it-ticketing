<style>[x-cloak] { display: none !important; }</style>

@php
    $authUser = auth()->user();
    $isAdmin = $authUser->isAdmin();

    // How many supply items are low or out — a nudge for the people who restock them.
    $restock = $authUser->isStaff() ? ['total' => 0, 'severe' => 0] : \App\Models\Supply::restockAlert();
    $restockCount = $restock['total'];
    // Amber when items are merely low; red as soon as one is critical or out.
    $restockBadge = $restock['severe'] > 0 ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700';

    // One list drives both the desktop bar and the phone menu, so they can never drift apart.
    // Same order for every role: the pages used all day first, reference pages after.
    $navLinks = [
        ['label' => 'Dashboard', 'href' => route('dashboard'), 'active' => ['dashboard']],
        ['label' => 'Tickets', 'href' => route('tickets.index'), 'active' => ['tickets.*']],
        ['label' => 'Assets', 'href' => route('assets.index'), 'active' => ['assets.*']],
        ['label' => 'Supplies', 'href' => route('supplies.index'), 'active' => ['supplies.*', 'admin.supplies.*'], 'badge' => true],
        $isAdmin
            ? ['label' => 'Users', 'href' => route('admin.users.index'), 'active' => ['admin.users.*']]
            : ['label' => 'Users', 'href' => route('users.directory'), 'active' => ['users.directory*']],
        ['label' => 'Reports', 'href' => route('admin.reports.index'), 'active' => ['admin.reports.*']],
        ['label' => 'IT Policy', 'href' => route('it-policy.show'), 'active' => ['it-policy.*']],
    ];

    // Admin-only setup pages, grouped so the bar stays short.
    $orgLinks = $isAdmin ? [
        ['label' => 'Departments', 'hint' => 'Teams and asset codes', 'href' => route('admin.departments.index'), 'active' => ['admin.departments.*'],
         'icon' => 'M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21'],
        ['label' => 'Branches', 'hint' => 'Office locations', 'href' => route('admin.branches.index'), 'active' => ['admin.branches.*'],
         'icon' => 'M15 10.5a3 3 0 11-6 0 3 3 0 016 0zM19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z'],
        ['label' => 'Positions', 'hint' => 'Job titles', 'href' => route('admin.positions.index'), 'active' => ['admin.positions.*'],
         'icon' => 'M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5zm6-10.125a1.875 1.875 0 11-3.75 0 1.875 1.875 0 013.75 0zm1.294 6.336a6.721 6.721 0 01-3.17.789 6.721 6.721 0 01-3.168-.789 3.376 3.376 0 016.338 0z'],
    ] : [];
    $orgActive = collect($orgLinks)->contains(fn ($l) => request()->routeIs(...$l['active']));

    $roleLabel = ['admin' => 'Administrator', 'it_support' => 'IT Support', 'staff' => 'Staff'][$authUser->role] ?? ucfirst(str_replace('_', ' ', $authUser->role));

    $supplyBadge = fn (string $extra = '') => $restockCount > 0
        ? '<span class="inline-flex h-5 min-w-[1.25rem] items-center justify-center rounded-full '.$restockBadge.' px-1.5 text-[10px] font-semibold '.$extra.'" title="'.$restockCount.' supply item(s) need restocking">'.$restockCount.'</span>'
        : '';
@endphp

<nav x-data="{ open: false }" @keydown.escape.window="open = false" class="bg-white border-b border-gray-200 print:hidden" aria-label="Main navigation">
    <div class="max-w-[96rem] mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center justify-between gap-3">

            {{-- Left: logo + links. min-w-0 lets this side shrink instead of pushing the right side over it. --}}
            <div class="flex h-full min-w-0 items-center gap-3 lg:gap-4 xl:gap-6">
                <a href="{{ route('dashboard') }}" class="flex shrink-0 items-center gap-2.5 rounded-md focus:outline-none focus-visible:ring-2 focus-visible:ring-green-700">
                    <img src="{{ asset('images/logo.png') }}" alt="Crest Forwarder Inc." class="h-9 w-9 object-contain">
                    {{-- Hidden on phones and on narrow laptops, where the links need the room. --}}
                    <span class="hidden sm:inline lg:hidden xl:inline whitespace-nowrap font-semibold text-gray-800">IT Service Ticketing</span>
                </a>

                {{-- Desktop links (laptops and up) --}}
                <div class="hidden h-full items-stretch gap-0.5 lg:flex xl:gap-1">
                    @foreach($navLinks as $link)
                        <x-nav-link :href="$link['href']" :active="request()->routeIs(...$link['active'])">
                            {{ __($link['label']) }}
                            @if(! empty($link['badge'])) {!! $supplyBadge('ms-1') !!} @endif
                        </x-nav-link>
                    @endforeach

                    @if($orgLinks)
                        <div class="relative flex" x-data="{ menu: false }" @click.outside="menu = false" @keydown.escape="menu = false">
                            <button type="button" @click="menu = ! menu" :aria-expanded="menu" aria-haspopup="true"
                                    class="inline-flex items-center gap-1 border-b-2 px-2.5 text-sm font-medium leading-5 whitespace-nowrap transition duration-150 ease-in-out focus:outline-none focus-visible:bg-gray-50 xl:px-3 {{ $orgActive ? 'border-[#1a6b3c] text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-800' }}">
                                {{ __('Organization') }}
                                <svg class="h-4 w-4 transition-transform duration-150" :class="menu ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                            </button>

                            <div x-show="menu" x-cloak
                                 x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                                 x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                                 class="absolute left-0 top-full z-50 mt-px w-64 rounded-xl border border-gray-200 bg-white p-1.5 shadow-lg ring-1 ring-black/5" role="menu">
                                @foreach($orgLinks as $item)
                                    @php $itemActive = request()->routeIs(...$item['active']); @endphp
                                    <a href="{{ $item['href'] }}" role="menuitem" @if($itemActive) aria-current="page" @endif
                                       class="flex items-center gap-3 rounded-lg px-3 py-2.5 transition focus:outline-none focus-visible:bg-gray-50 {{ $itemActive ? 'bg-green-50' : 'hover:bg-gray-50' }}">
                                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg {{ $itemActive ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-500' }}">
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}" /></svg>
                                        </span>
                                        <span class="min-w-0">
                                            <span class="block text-sm font-medium {{ $itemActive ? 'text-green-900' : 'text-gray-800' }}">{{ $item['label'] }}</span>
                                            <span class="block text-xs text-gray-400">{{ $item['hint'] }}</span>
                                        </span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Right: notifications, account (laptops and up), menu button (phones and tablets) --}}
            <div class="flex shrink-0 items-center gap-1 sm:gap-2">

                {{-- Notifications bell — visible on every screen size --}}
                <div x-data="notificationBell('{{ route('notifications.poll') }}', '{{ route('notifications.read-all') }}')" x-init="init()">
                    <button type="button" @click="open = !open" aria-label="Notifications" :aria-expanded="open"
                            class="relative inline-flex h-10 w-10 items-center justify-center rounded-full text-gray-500 transition hover:bg-gray-100 hover:text-gray-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-green-700">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
                        <span x-show="unreadCount > 0" x-cloak x-text="unreadCount > 9 ? '9+' : unreadCount"
                              class="absolute -right-0.5 -top-0.5 flex h-4 min-w-[16px] items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold text-white"></span>
                    </button>

                    {{-- Phones: a full-width sheet under the bar. Tablets and up: a normal dropdown. --}}
                    <div x-show="open" x-cloak @click.outside="open = false" @keydown.escape.window="open = false"
                         x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                         class="fixed inset-x-3 top-[4.25rem] z-50 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-lg ring-1 ring-black/5 sm:absolute sm:inset-x-auto sm:right-0 sm:top-auto sm:mt-2 sm:w-96"
                         style="display: none;" role="dialog" aria-label="Notifications">
                        <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3">
                            <p class="text-sm font-semibold text-gray-800">Notifications</p>
                            <button type="button" @click="markAllRead()" class="text-xs font-medium text-green-700 hover:underline" x-show="unreadCount > 0">Mark all read</button>
                        </div>

                        <div class="max-h-[60vh] overflow-y-auto sm:max-h-96">
                            <template x-if="notifications.length === 0">
                                <p class="py-10 text-center text-sm text-gray-400">You're all caught up.</p>
                            </template>

                            <template x-for="n in notifications" :key="n.id">
                                <a :href="'{{ url('notifications') }}/' + n.id + '/read'"
                                   class="block border-b border-gray-50 px-4 py-3 transition last:border-0 hover:bg-gray-50"
                                   :class="!n.read ? 'bg-green-50/40' : ''">
                                    <div class="flex items-start gap-2.5">
                                        <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full" :class="!n.read ? 'bg-green-600' : 'bg-transparent'"></span>
                                        <div class="min-w-0">
                                            <p class="text-sm font-medium text-gray-800" x-text="n.title"></p>
                                            <p class="mt-0.5 text-xs text-gray-500" x-text="n.message"></p>
                                            <p class="mt-1 text-[11px] text-gray-400" x-text="n.created_at_human"></p>
                                        </div>
                                    </div>
                                </a>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- Account menu (laptops and up). The name only shows on wide screens, and never wraps. --}}
                <div class="hidden lg:block">
                    <x-dropdown align="right" width="w-64">
                        <x-slot name="trigger">
                            <button type="button" aria-label="Account menu"
                                    class="inline-flex items-center gap-2 rounded-full py-1 pl-1 pr-2 text-sm font-medium text-gray-600 transition hover:bg-gray-100 hover:text-gray-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-green-700">
                                <x-avatar size="sm" />
                                <span class="hidden max-w-[9rem] truncate xl:inline">{{ $authUser->name }}</span>
                                @if($authUser->is_vip)
                                    <span class="hidden items-center rounded-full bg-amber-50 px-1.5 py-0.5 text-[10px] font-semibold text-amber-700 ring-1 ring-inset ring-amber-200 xl:inline-flex">★ VIP</span>
                                @endif
                                <svg class="h-4 w-4 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <div class="border-b border-gray-100 px-4 py-3">
                                <p class="flex items-center gap-1.5 truncate text-sm font-semibold text-gray-800">
                                    <span class="truncate">{{ $authUser->name }}</span>
                                    @if($authUser->is_vip)
                                        <span class="inline-flex shrink-0 items-center rounded-full bg-amber-50 px-1.5 py-0.5 text-[10px] font-semibold text-amber-700 ring-1 ring-inset ring-amber-200">★ VIP</span>
                                    @endif
                                </p>
                                <p class="truncate text-xs text-gray-500">{{ $authUser->username }}</p>
                                <p class="mt-1 text-[11px] font-medium uppercase tracking-wide text-gray-400">{{ $roleLabel }}</p>
                            </div>

                            <x-dropdown-link :href="route('profile.edit')">
                                {{ __('Profile') }}
                            </x-dropdown-link>

                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">
                                    {{ __('Log Out') }}
                                </x-dropdown-link>
                            </form>
                        </x-slot>
                    </x-dropdown>
                </div>

                {{-- Menu button (phones and tablets) --}}
                <button type="button" @click="open = ! open" class="inline-flex h-10 w-10 items-center justify-center rounded-md text-gray-500 transition hover:bg-gray-100 hover:text-gray-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-green-700 lg:hidden"
                        aria-label="Menu" :aria-expanded="open" aria-controls="mobile-menu">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path x-show="! open" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path x-show="open" x-cloak stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Live toast when a new notification arrives (assigned / accepted / status change) -->
    <script>
        function showNotificationToast(n) {
            const container = document.getElementById('ticket-toast-container');
            if (!container) return;

            const toast = document.createElement('a');
            toast.href = n.url || '#';
            toast.className = 'block bg-white border border-gray-100 shadow-lg rounded-xl px-4 py-3 w-[calc(100vw-2rem)] max-w-xs sm:w-80 pointer-events-auto transition transform translate-x-full opacity-0';
            toast.innerHTML = `
                <div class="flex items-start gap-2.5">
                    <span class="mt-1.5 shrink-0 w-2 h-2 rounded-full" style="background-color:#1a6b3c;"></span>
                    <div class="min-w-0">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">${n.title ?? 'Notification'}</p>
                        <p class="text-sm font-medium text-gray-800 truncate">${n.message ?? ''}</p>
                    </div>
                </div>`;
            container.appendChild(toast);

            requestAnimationFrame(() => toast.classList.remove('translate-x-full', 'opacity-0'));

            setTimeout(() => {
                toast.classList.add('opacity-0');
                setTimeout(() => toast.remove(), 300);
            }, 6000);
        }
    </script>
    <div id="ticket-toast-container" class="fixed top-20 right-4 z-[100] space-y-2 pointer-events-none"></div>

    {{-- Phone / tablet menu --}}
    <div id="mobile-menu" x-show="open" x-cloak
         x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
         class="max-h-[calc(100vh-4rem)] overflow-y-auto border-t border-gray-100 lg:hidden">
        <div class="space-y-1 px-3 py-3">
            @foreach($navLinks as $link)
                <x-responsive-nav-link :href="$link['href']" :active="request()->routeIs(...$link['active'])">
                    <span class="flex items-center justify-between gap-3">
                        <span>{{ __($link['label']) }}</span>
                        @if(! empty($link['badge'])) {!! $supplyBadge() !!} @endif
                    </span>
                </x-responsive-nav-link>
            @endforeach

            @if($orgLinks)
                <p class="px-3 pb-1 pt-4 text-[11px] font-semibold uppercase tracking-wider text-gray-400">{{ __('Organization') }}</p>
                @foreach($orgLinks as $item)
                    <x-responsive-nav-link :href="$item['href']" :active="request()->routeIs(...$item['active'])">
                        {{ __($item['label']) }}
                    </x-responsive-nav-link>
                @endforeach
            @endif
        </div>

        <div class="border-t border-gray-100 bg-gray-50/60 px-3 pb-4 pt-4">
            <div class="flex items-center gap-3 px-3">
                <x-avatar size="md" />
                <div class="min-w-0">
                    <p class="flex items-center gap-1.5 truncate text-base font-semibold text-gray-800">
                        <span class="truncate">{{ $authUser->name }}</span>
                        @if($authUser->is_vip)
                            <span class="inline-flex shrink-0 items-center rounded-full bg-amber-50 px-1.5 py-0.5 text-[10px] font-semibold text-amber-700 ring-1 ring-inset ring-amber-200">★ VIP</span>
                        @endif
                    </p>
                    <p class="truncate text-sm text-gray-500">{{ $authUser->username }}</p>
                    <p class="text-[11px] font-medium uppercase tracking-wide text-gray-400">{{ $roleLabel }}</p>
                </div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>

<script>
    function notificationBell(pollUrl, markAllReadUrl) {
        return {
            open: false,
            unreadCount: 0,
            notifications: [],
            seenIds: new Set(JSON.parse(localStorage.getItem('notif_seen_ids') || '[]')),
            isFirstPoll: localStorage.getItem('notif_seen_ids') === null,

            init() {
                this.poll();
                setInterval(() => this.poll(), 15000);
            },

            async poll() {
                try {
                    const res = await fetch(pollUrl, { headers: { 'Accept': 'application/json' } });
                    if (!res.ok) return;
                    const data = await res.json();
                    this.unreadCount = data.unread_count ?? 0;
                    this.notifications = data.notifications ?? [];

                    const incoming = this.notifications.filter(n => !n.read);

                    if (this.isFirstPoll) {
                        // Don't toast every pre-existing unread notification the moment
                        // the page loads — just record the baseline.
                        this.isFirstPoll = false;
                    } else if (!this.open) {
                        incoming.filter(n => !this.seenIds.has(n.id)).forEach(n => showNotificationToast(n));
                    }

                    incoming.forEach(n => this.seenIds.add(n.id));
                    localStorage.setItem('notif_seen_ids', JSON.stringify([...this.seenIds].slice(-50)));
                } catch (e) {
                    // fail silently — retried on next interval
                }
            },

            async markAllRead() {
                try {
                    await fetch(markAllReadUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '{{ csrf_token() }}',
                        },
                    });
                    this.unreadCount = 0;
                    this.notifications = this.notifications.map(n => ({ ...n, read: true }));
                } catch (e) {
                    //
                }
            },
        };
    }
</script>
