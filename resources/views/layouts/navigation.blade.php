<style>[x-cloak] { display: none !important; }</style>

<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center gap-2">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
                        <img src="{{ asset('images/logo.png') }}" alt="Crest Forwarder Inc." class="h-9 w-9 object-contain">
                        <span class="font-semibold text-gray-700 hidden sm:inline">IT Service Ticketing</span>
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Dashboard') }}
                    </x-nav-link>
                    <x-nav-link :href="route('tickets.index')" :active="request()->routeIs('tickets.*')">
                        {{ __('Tickets') }}
                    </x-nav-link>
                    @if(auth()->user()->isAdmin())
                        <x-nav-link :href="route('admin.users.index')" :active="request()->routeIs('admin.users.*')">
                            {{ __('Manage Users') }}
                        </x-nav-link>
                    @endif
                </div>
            </div>

            <div class="hidden sm:flex sm:items-center sm:ms-6 gap-2">
                @if(auth()->user()->isItSupport() || auth()->user()->isAdmin())
                    <div x-data="ticketNotifications()" x-init="init()" class="relative">
                        <button @click="open = !open; if (open) seen()" class="relative p-2 rounded-full hover:bg-gray-100 transition">
                            <svg class="w-5 h-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                            </svg>
                            <span x-show="unreadCount > 0" x-text="unreadCount > 9 ? '9+' : unreadCount"
                                  x-cloak
                                  class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1 flex items-center justify-center rounded-full bg-red-600 text-white text-[10px] font-bold"></span>
                        </button>

                        <div x-show="open" @click.outside="open = false" x-cloak
                             class="absolute right-0 mt-2 w-80 bg-white rounded-xl shadow-lg border border-gray-100 z-50 overflow-hidden">
                            <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                                <p class="text-sm font-semibold text-gray-800">New Tickets</p>
                                <span class="text-xs text-gray-400" x-text="unassignedCount + ' unassigned'"></span>
                            </div>
                            <div class="max-h-80 overflow-y-auto divide-y divide-gray-50">
                                <template x-if="tickets.length === 0">
                                    <p class="px-4 py-6 text-center text-sm text-gray-400">No new tickets yet.</p>
                                </template>
                                <template x-for="ticket in tickets.slice().reverse()" :key="ticket.id">
                                    <a :href="ticket.url" class="block px-4 py-3 hover:bg-gray-50 transition">
                                        <p class="text-sm font-medium text-gray-800 flex items-center gap-1.5">
                                            <span x-text="ticket.title"></span>
                                            <span x-show="ticket.requester_is_vip" x-cloak class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-200">★ VIP</span>
                                        </p>
                                        <p class="text-xs text-gray-400 mt-0.5">
                                            <span x-text="ticket.requester"></span> &middot; <span x-text="ticket.created_at"></span>
                                        </p>
                                    </a>
                                </template>
                            </div>
                            <a href="{{ route('tickets.index') }}" class="block text-center text-xs font-medium text-green-700 hover:bg-gray-50 py-2.5 border-t border-gray-100">
                                View Support Queue
                            </a>
                        </div>
                    </div>
                @endif

                <!-- Settings Dropdown -->
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                            <div class="flex items-center gap-1.5">
                                {{ Auth::user()->name }}
                                @if(Auth::user()->is_vip)
                                    <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-full text-[10px] font-semibold bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-200">★ VIP</span>
                                @endif
                            </div>
                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault(); this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    @if(auth()->user()->isItSupport() || auth()->user()->isAdmin())
        <script>
            function ticketNotifications() {
                return {
                    open: false,
                    tickets: [],
                    unreadCount: 0,
                    unassignedCount: 0,
                    sinceId: parseInt(localStorage.getItem('ticket_poll_since_id') || '0', 10),
                    isFirstPoll: localStorage.getItem('ticket_poll_since_id') === null,

                    init() {
                        this.poll();
                        setInterval(() => this.poll(), 5000);
                    },

                    seen() {
                        this.unreadCount = 0;
                    },

                    async poll() {
                        try {
                            const res = await fetch(`{{ route('tickets.queue.poll') }}?since_id=${this.sinceId}`, {
                                headers: { 'Accept': 'application/json' },
                            });
                            if (!res.ok) return;

                            const data = await res.json();
                            this.unassignedCount = data.unassigned_count;

                            // On the very first poll ever (fresh browser), don't treat every
                            // pre-existing unassigned ticket as "new" — just sync the baseline.
                            if (this.isFirstPoll) {
                                this.isFirstPoll = false;
                                this.sinceId = data.latest_id;
                                localStorage.setItem('ticket_poll_since_id', this.sinceId);
                                return;
                            }

                            if (data.tickets.length > 0) {
                                data.tickets.forEach(t => this.showToast(t));
                                this.tickets.push(...data.tickets);
                                if (this.tickets.length > 20) this.tickets = this.tickets.slice(-20);
                                if (!this.open) this.unreadCount += data.tickets.length;
                            }

                            this.sinceId = data.latest_id;
                            localStorage.setItem('ticket_poll_since_id', this.sinceId);

                            window.dispatchEvent(new CustomEvent('tickets:polled', { detail: data }));
                        } catch (e) {
                            // network hiccup — just try again next interval
                        }
                    },

                    showToast(ticket) {
                        const container = document.getElementById('ticket-toast-container');
                        const toast = document.createElement('a');
                        toast.href = ticket.url;
                        toast.className = 'block bg-white border border-gray-100 shadow-lg rounded-xl px-4 py-3 w-80 pointer-events-auto transition transform translate-x-full opacity-0';
                        toast.innerHTML = `
                            <div class="flex items-start gap-2.5">
                                <span class="mt-0.5 shrink-0 w-2 h-2 rounded-full ${ticket.priority === 'critical' ? 'bg-red-600' : 'bg-green-600'}"></span>
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">New Ticket${ticket.requester_is_vip ? ' · VIP' : ''}</p>
                                    <p class="text-sm font-medium text-gray-800 truncate">${ticket.title}</p>
                                    <p class="text-xs text-gray-400 mt-0.5">${ticket.requester} &middot; ${ticket.department ?? ''}</p>
                                </div>
                            </div>`;
                        container.appendChild(toast);

                        requestAnimationFrame(() => {
                            toast.classList.remove('translate-x-full', 'opacity-0');
                        });

                        setTimeout(() => {
                            toast.classList.add('opacity-0');
                            setTimeout(() => toast.remove(), 300);
                        }, 6000);
                    },
                };
            }
        </script>
        <div id="ticket-toast-container" class="fixed top-20 right-4 z-[100] space-y-2 pointer-events-none"></div>
    @endif

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('tickets.index')" :active="request()->routeIs('tickets.*')">
                {{ __('Tickets') }}
            </x-responsive-nav-link>
            @if(auth()->user()->isAdmin())
                <x-responsive-nav-link :href="route('admin.users.index')" :active="request()->routeIs('admin.users.*')">
                    {{ __('Manage Users') }}
                </x-responsive-nav-link>
            @endif
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800 flex items-center gap-1.5">
                    {{ Auth::user()->name }}
                    @if(Auth::user()->is_vip)
                        <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-full text-[10px] font-semibold bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-200">★ VIP</span>
                    @endif
                </div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault(); this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
