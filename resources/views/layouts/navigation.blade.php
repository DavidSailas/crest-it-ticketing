<style>[x-cloak] { display: none !important; }</style>

<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <!-- Primary Navigation Menu -->
    <div class="max-w-[96rem] mx-auto px-4 sm:px-6 lg:px-8">
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
                        <x-nav-link :href="route('admin.departments.index')" :active="request()->routeIs('admin.departments.*')">
                            {{ __('Departments') }}
                        </x-nav-link>
                        <x-nav-link :href="route('admin.branches.index')" :active="request()->routeIs('admin.branches.*')">
                            {{ __('Branches') }}
                        </x-nav-link>
                        <x-nav-link :href="route('admin.positions.index')" :active="request()->routeIs('admin.positions.*')">
                            {{ __('Positions') }}
                        </x-nav-link>
                        <x-nav-link :href="route('assets.index')" :active="request()->routeIs('assets.*')">
                            {{ __('Assets') }}
                        </x-nav-link>
                    @elseif(auth()->user()->role === 'it_support')
                        <x-nav-link :href="route('users.directory')" :active="request()->routeIs('users.directory')">
                            {{ __('Users') }}
                        </x-nav-link>
                        <x-nav-link :href="route('assets.index')" :active="request()->routeIs('assets.*')">
                            {{ __('Assets') }}
                        </x-nav-link>
                    @elseif(auth()->user()->role === 'staff')
                        <x-nav-link :href="route('users.directory')" :active="request()->routeIs('users.directory')">
                            {{ __('Users') }}
                        </x-nav-link>
                    @endif
                </div>
            </div>

            <div class="hidden sm:flex sm:items-center sm:ms-6 gap-2">
                <!-- Notifications Bell (the only one — covers assignments, acceptances, and status changes) -->
                <div class="relative mr-2" x-data="notificationBell('{{ route('notifications.poll') }}', '{{ route('notifications.read-all') }}')" x-init="init()">
                    <button @click="open = !open" class="relative inline-flex items-center justify-center w-9 h-9 rounded-full text-gray-500 hover:bg-gray-100 hover:text-gray-700 transition">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
                        <span x-show="unreadCount > 0" x-text="unreadCount > 9 ? '9+' : unreadCount"
                              class="absolute -top-0.5 -right-0.5 min-w-[16px] h-4 px-1 rounded-full bg-red-500 text-white text-[10px] font-bold flex items-center justify-center"></span>
                    </button>

                    <div x-show="open" @click.outside="open = false" x-transition
                         class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg border border-gray-200 z-50 overflow-hidden" style="display: none;">
                        <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                            <p class="text-sm font-semibold text-gray-800">Notifications</p>
                            <button @click="markAllRead()" class="text-xs text-green-700 hover:underline" x-show="unreadCount > 0">Mark all read</button>
                        </div>

                        <div class="max-h-96 overflow-y-auto">
                            <template x-if="notifications.length === 0">
                                <p class="text-sm text-gray-400 text-center py-8">You're all caught up.</p>
                            </template>

                            <template x-for="n in notifications" :key="n.id">
                                <a :href="'{{ url('notifications') }}/' + n.id + '/read'"
                                   class="block px-4 py-3 border-b border-gray-50 last:border-0 hover:bg-gray-50 transition"
                                   :class="!n.read ? 'bg-green-50/40' : ''">
                                    <div class="flex items-start gap-2">
                                        <span class="w-1.5 h-1.5 rounded-full mt-1.5 shrink-0" :class="!n.read ? 'bg-green-600' : 'bg-transparent'"></span>
                                        <div>
                                            <p class="text-sm font-medium text-gray-800" x-text="n.title"></p>
                                            <p class="text-xs text-gray-500 mt-0.5" x-text="n.message"></p>
                                            <p class="text-[11px] text-gray-300 mt-1" x-text="n.created_at_human"></p>
                                        </div>
                                    </div>
                                </a>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Settings Dropdown -->
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-2 py-1.5 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                            <x-avatar size="sm" />
                            <div class="flex items-center gap-1.5 ml-2">
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

                        <!-- <x-dropdown-link :href="auth()->user()->isStaff() ? route('support-chat.show') : route('support-chat.inbox')">
                            {{ __('Chat Support') }}
                        </x-dropdown-link> -->

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
                <x-responsive-nav-link :href="route('admin.departments.index')" :active="request()->routeIs('admin.departments.*')">
                    {{ __('Departments') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.branches.index')" :active="request()->routeIs('admin.branches.*')">
                    {{ __('Branches') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.positions.index')" :active="request()->routeIs('admin.positions.*')">
                    {{ __('Positions') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('assets.index')" :active="request()->routeIs('assets.*')">
                    {{ __('Assets') }}
                </x-responsive-nav-link>
            @elseif(auth()->user()->role === 'it_support')
                <x-responsive-nav-link :href="route('users.directory')" :active="request()->routeIs('users.directory')">
                    {{ __('Users') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('assets.index')" :active="request()->routeIs('assets.*')">
                    {{ __('Assets') }}
                </x-responsive-nav-link>
            @elseif(auth()->user()->role === 'staff')
                <x-responsive-nav-link :href="route('users.directory')" :active="request()->routeIs('users.directory')">
                    {{ __('Users') }}
                </x-responsive-nav-link>
            @endif
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4 flex items-center gap-3">
                <x-avatar size="sm" />
                <div>
                    <div class="font-medium text-base text-gray-800 flex items-center gap-1.5">
                        {{ Auth::user()->name }}
                        @if(Auth::user()->is_vip)
                            <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-full text-[10px] font-semibold bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-200">★ VIP</span>
                        @endif
                    </div>
                    <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
                </div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <!-- <x-responsive-nav-link :href="auth()->user()->isStaff() ? route('support-chat.show') : route('support-chat.inbox')">
                    {{ __('Chat Support') }}
                </x-responsive-nav-link> -->

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
