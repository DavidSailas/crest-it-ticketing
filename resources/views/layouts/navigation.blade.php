<style>[x-cloak] { display: none !important; }</style>

@php
    $authUser = auth()->user();
    $isAdmin = $authUser->isAdmin();

    // How many supply items are low or out — a nudge for the people who restock them.
    $restock = $authUser->isStaff() ? ['total' => 0, 'severe' => 0] : \App\Models\Supply::restockAlert();
    $restockCount = $restock['total'];
    $annUnread = config('features.announcements') ? \App\Models\Announcement::unreadCountFor($authUser) : 0;
    // Amber when items are merely low; red as soon as one is critical or out.
    $restockBadge = $restock['severe'] > 0 ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700';

    // One list drives the desktop bar and the phone tab bar / More sheet, so they can never drift apart.
    // Same order for every role: the pages used all day first, reference pages after.
    $navLinks = [
        ['label' => 'Dashboard', 'icon' => 'M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25', 'href' => route('dashboard'), 'active' => ['dashboard']],
        ['label' => 'Tickets', 'icon' => 'M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 010 5.198v3.026c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.026a2.999 2.999 0 010-5.198V6.375c0-.621-.504-1.125-1.125-1.125H3.375z', 'href' => route('tickets.index'), 'active' => ['tickets.*']],
        ['label' => 'Assets', 'icon' => 'M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25', 'href' => route('assets.index'), 'active' => ['assets.*']],
        ['label' => 'Supplies', 'icon' => 'M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9', 'href' => route('supplies.index'), 'active' => ['supplies.*', 'admin.supplies.*'], 'badge' => true],
        $isAdmin
            ? ['label' => 'Users', 'icon' => 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z', 'href' => route('admin.users.index'), 'active' => ['admin.users.*']]
            : ['label' => 'Users', 'icon' => 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z', 'href' => route('users.directory'), 'active' => ['users.directory*']],
        ['label' => 'Reports', 'icon' => 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z', 'href' => route('admin.reports.index'), 'active' => ['admin.reports.*']],
        ['label' => 'IT Policy', 'icon' => 'M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z', 'href' => route('it-policy.show'), 'active' => ['it-policy.*']],
    ];

    // Phones and tablets: the four pages used all day get tabs along the bottom; the rest live in the "More" sheet.
    $tabLinks = array_slice($navLinks, 0, 4);
    $moreLinks = array_slice($navLinks, 4);

    // Admin-only setup pages, grouped so the bar stays short.
    $orgLinks = $isAdmin ? [
        ['label' => 'Announcements', 'hint' => 'Post updates to everyone', 'href' => config('features.announcements') ? route('admin.announcements.index') : '#', 'active' => ['admin.announcements.*'],
         'icon' => 'M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 110-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.117-1.527-.461a20.845 20.845 0 01-1.44-4.282m3.102.069a18.03 18.03 0 01-.59-4.59c0-1.586.205-3.124.59-4.59m0 9.18a23.848 23.848 0 018.835 2.535M10.34 6.66a23.847 23.847 0 008.835-2.535m0 0A23.74 23.74 0 0018.795 3m.38 1.125a23.91 23.91 0 011.014 5.395m-1.014 8.855c-.118.38-.245.754-.38 1.125m.38-1.125a23.91 23.91 0 001.014-5.395m0-3.46c.495.413.811 1.035.811 1.73 0 .695-.316 1.317-.811 1.73m0-3.46a24.347 24.347 0 010 3.46'],
        ['label' => 'Departments', 'hint' => 'Teams and asset codes', 'href' => route('admin.departments.index'), 'active' => ['admin.departments.*'],
         'icon' => 'M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21'],
        ['label' => 'Branches', 'hint' => 'Office locations', 'href' => route('admin.branches.index'), 'active' => ['admin.branches.*'],
         'icon' => 'M15 10.5a3 3 0 11-6 0 3 3 0 016 0zM19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z'],
        ['label' => 'Positions', 'hint' => 'Job titles', 'href' => route('admin.positions.index'), 'active' => ['admin.positions.*'],
         'icon' => 'M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5zm6-10.125a1.875 1.875 0 11-3.75 0 1.875 1.875 0 013.75 0zm1.294 6.336a6.721 6.721 0 01-3.17.789 6.721 6.721 0 01-3.168-.789 3.376 3.376 0 016.338 0z'],
    ] : [];

    // Announcements are switched off for now (config/features.php) — keep the code, hide the menu entry.
    $orgLinks = collect($orgLinks)
        ->reject(fn ($link) => ($link['label'] ?? '') === 'Announcements' && ! config('features.announcements'))
        ->values()
        ->all();
    $orgActive = collect($orgLinks)->contains(fn ($l) => request()->routeIs(...$l['active']));
    // The "More" tab is lit whenever the current page is one that lives inside it.
    $moreActive = $orgActive
        || request()->routeIs('profile.*', 'announcements.*')
        || collect($moreLinks)->contains(fn ($l) => request()->routeIs(...$l['active']));

    $roleLabel = ['admin' => 'Administrator', 'it_support' => 'IT Support', 'staff' => 'Staff'][$authUser->role] ?? ucfirst(str_replace('_', ' ', $authUser->role));

    // Approvers (heads, managers, admins) see a solid amber badge for requests waiting on them.
    $approvalCount = $authUser->supplyApprovalsWaiting();
    // One count for the Supplies tab on phones: requests waiting on me + items low or out (red once one is critical).
    $supplyTabCount = $approvalCount + $restockCount;

    $supplyBadge = fn (string $extra = '') => ($approvalCount > 0
            ? '<span class="inline-flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-amber-500 px-1.5 text-[10px] font-semibold text-white '.$extra.'" title="'.$approvalCount.' supply request(s) waiting for your approval">'.$approvalCount.'</span>'
            : '')
        .($restockCount > 0
            ? '<span class="inline-flex h-5 min-w-[1.25rem] items-center justify-center rounded-full '.$restockBadge.' px-1.5 text-[10px] font-semibold '.$extra.'" title="'.$restockCount.' supply item(s) need restocking">'.$restockCount.'</span>'
            : '');
@endphp

<nav x-data="{ more: false, canInstall: false }"
     x-init="canInstall = !!(window.PWA && window.PWA.canInstall())"
     x-effect="document.documentElement.classList.toggle('mnav-lock', more)"
     @pwa-state.window="canInstall = $event.detail.canInstall"
     @keydown.escape.window="more = false"
     class="app-topbar bg-white border-b border-gray-200 print:hidden" aria-label="Main navigation">
    <div class="max-w-[96rem] mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center justify-between gap-3">

            {{-- Left: logo + links. min-w-0 lets this side shrink instead of pushing the right side over it. --}}
            <div class="flex h-full min-w-0 items-center gap-3 lg:gap-4 xl:gap-6">
                <a href="{{ route('dashboard') }}" class="flex shrink-0 items-center gap-2.5 rounded-md focus:outline-none focus-visible:ring-2 focus-visible:ring-green-700">
                    <img src="{{ asset('images/logo-light.png') }}" alt="Crest Forwarder Inc." class="logo-light h-9 w-9 object-contain">
                    {{-- Dark mode: same logo on a transparent background with the dark greens lightened --}}
                    <img src="{{ asset('images/logo-dark.png') }}" alt="" aria-hidden="true" class="logo-dark h-9 w-9 object-contain">
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

            {{-- Right: notifications and theme everywhere; the account menu on laptops (phones use the More tab) --}}
            <div class="flex shrink-0 items-center gap-1 sm:gap-2">

                @if(config('features.announcements'))
                {{-- Announcements — everyone can read; the dot shows when something is unread --}}
                <a href="{{ route('announcements.index') }}" data-ann-bell aria-label="Announcements{{ $annUnread ? ' ('.$annUnread.' unread)' : '' }}" title="Announcements"
                   class="relative inline-flex h-10 w-10 items-center justify-center rounded-full transition hover:bg-gray-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-green-700 {{ request()->routeIs('announcements.*') ? 'bg-green-50 text-green-800' : 'text-gray-500 hover:text-gray-700' }}">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 110-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.117-1.527-.461a20.845 20.845 0 01-1.44-4.282m3.102.069a18.03 18.03 0 01-.59-4.59c0-1.586.205-3.124.59-4.59m0 9.18a23.848 23.848 0 018.835 2.535M10.34 6.66a23.847 23.847 0 008.835-2.535m0 0A23.74 23.74 0 0018.795 3m.38 1.125a23.91 23.91 0 011.014 5.395m-1.014 8.855c-.118.38-.245.754-.38 1.125m.38-1.125a23.91 23.91 0 001.014-5.395m0-3.46c.495.413.811 1.035.811 1.73 0 .695-.316 1.317-.811 1.73m0-3.46a24.347 24.347 0 010 3.46" /></svg>
                    <span data-ann-badge="cap" class="absolute -right-0.5 -top-0.5 flex h-4 min-w-[16px] items-center justify-center rounded-full px-1 text-[10px] font-bold text-white" style="background:#1a6b3c;{{ $annUnread > 0 ? '' : 'display:none' }}">{{ $annUnread > 9 ? '9+' : $annUnread }}</span>
                </a>
                @endif

                {{-- Light / dark theme --}}
                <button type="button" onclick="Theme.toggle()" aria-label="Toggle dark mode" title="Light / dark mode"
                        class="theme-toggle relative inline-flex h-10 w-10 items-center justify-center rounded-full text-gray-500 transition hover:bg-gray-100 hover:text-gray-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-green-700">
                    <svg class="icon-moon h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z"/></svg>
                    <svg class="icon-sun h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"/></svg>
                </button>

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

    {{-- Phones & tablets: app-style tab bar along the bottom (no hamburger) --}}
    <div class="mnav" role="navigation" aria-label="Main tabs">
        @foreach($tabLinks as $link)
            @php $tabActive = request()->routeIs(...$link['active']); @endphp
            <a href="{{ $link['href'] }}" class="mnav__item {{ $tabActive ? 'is-active' : '' }}" @if($tabActive) aria-current="page" @endif>
                <span class="mnav__ico">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="{{ $tabActive ? 2 : 1.7 }}" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $link['icon'] }}" /></svg>
                    @if(! empty($link['badge']) && $supplyTabCount > 0)
                        <span class="mnav__badge {{ $approvalCount === 0 && $restock['severe'] > 0 ? 'mnav__badge--red' : '' }}"
                              title="{{ $approvalCount }} waiting for approval, {{ $restockCount }} to restock">{{ $supplyTabCount > 99 ? '99+' : $supplyTabCount }}</span>
                    @endif
                </span>
                <span class="mnav__label">{{ __($link['label']) }}</span>
            </a>
        @endforeach

        <button type="button" class="mnav__item {{ $moreActive ? 'is-active' : '' }}" @click="more = true" :aria-expanded="more" aria-haspopup="dialog" aria-controls="more-sheet">
            <span class="mnav__ico">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="{{ $moreActive ? 2 : 1.7 }}" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25a2.25 2.25 0 01-2.25-2.25v-2.25z" /></svg>
            </span>
            <span class="mnav__label">{{ __('More') }}</span>
        </button>
    </div>

    {{-- "More" sheet: everything that isn't a tab, plus account, install and log out --}}
    <div x-show="more" x-cloak class="msheet-backdrop" @click="more = false" aria-hidden="true"
         x-transition:enter="mfade-in" x-transition:enter-start="mfade-from" x-transition:enter-end="mfade-to"
         x-transition:leave="mfade-out" x-transition:leave-start="mfade-to" x-transition:leave-end="mfade-from"></div>

    <div id="more-sheet" x-show="more" x-cloak class="msheet" role="dialog" aria-modal="true" aria-label="{{ __('More') }}"
         x-transition:enter="msheet-in" x-transition:enter-start="msheet-from" x-transition:enter-end="msheet-to"
         x-transition:leave="msheet-out" x-transition:leave-start="msheet-to" x-transition:leave-end="msheet-from">
        <div class="msheet__grab" aria-hidden="true"></div>

        <div class="msheet__body">
            <div class="msheet__me">
                <x-avatar size="md" />
                <div class="min-w-0">
                    <p class="msheet__me-name">
                        <span>{{ $authUser->name }}</span>
                        @if($authUser->is_vip)<span class="msheet__vip">★ VIP</span>@endif
                    </p>
                    <p class="msheet__me-sub">{{ $authUser->username }} · {{ $roleLabel }}</p>
                </div>
            </div>

            <ul class="msheet__list">
                @foreach($moreLinks as $link)
                    @php $rowActive = request()->routeIs(...$link['active']); @endphp
                    <li>
                        <a href="{{ $link['href'] }}" class="msheet__row {{ $rowActive ? 'is-active' : '' }}" @if($rowActive) aria-current="page" @endif>
                            <span class="msheet__tile"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $link['icon'] }}" /></svg></span>
                            <span class="msheet__text">{{ __($link['label']) }}</span>
                            <svg class="msheet__chev" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
                        </a>
                    </li>
                @endforeach

                @if(config('features.announcements'))
                    @php $annActive = request()->routeIs('announcements.*'); @endphp
                    <li>
                        <a href="{{ route('announcements.index') }}" class="msheet__row {{ $annActive ? 'is-active' : '' }}" @if($annActive) aria-current="page" @endif>
                            <span class="msheet__tile"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 110-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.117-1.527-.461a20.845 20.845 0 01-1.44-4.282m3.102.069a18.03 18.03 0 01-.59-4.59c0-1.586.205-3.124.59-4.59m0 9.18a23.848 23.848 0 018.835 2.535M10.34 6.66a23.847 23.847 0 008.835-2.535m0 0A23.74 23.74 0 0018.795 3m.38 1.125a23.91 23.91 0 011.014 5.395m-1.014 8.855c-.118.38-.245.754-.38 1.125m.38-1.125a23.91 23.91 0 001.014-5.395m0-3.46c.495.413.811 1.035.811 1.73 0 .695-.316 1.317-.811 1.73m0-3.46a24.347 24.347 0 010 3.46" /></svg></span>
                            <span class="msheet__text">{{ __('Announcements') }}</span>
                            <span data-ann-badge="full" class="inline-flex h-5 min-w-[1.25rem] items-center justify-center rounded-full px-1.5 text-[10px] font-semibold text-white" style="background:#1a6b3c;{{ $annUnread > 0 ? '' : 'display:none' }}">{{ $annUnread }}</span>
                        </a>
                    </li>
                @endif
            </ul>

            @if($orgLinks)
                <p class="msheet__group">{{ __('Organization') }}</p>
                <ul class="msheet__list">
                    @foreach($orgLinks as $item)
                        @php $itemActive = request()->routeIs(...$item['active']); @endphp
                        <li>
                            <a href="{{ $item['href'] }}" class="msheet__row {{ $itemActive ? 'is-active' : '' }}" @if($itemActive) aria-current="page" @endif>
                                <span class="msheet__tile"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}" /></svg></span>
                                <span class="msheet__text">{{ __($item['label']) }}<small>{{ $item['hint'] }}</small></span>
                                <svg class="msheet__chev" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif

            <hr class="msheet__hr">

            <ul class="msheet__list">
                @php $profActive = request()->routeIs('profile.*'); @endphp
                <li>
                    <a href="{{ route('profile.edit') }}" class="msheet__row {{ $profActive ? 'is-active' : '' }}" @if($profActive) aria-current="page" @endif>
                        <span class="msheet__tile"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z" /></svg></span>
                        <span class="msheet__text">{{ __('Profile') }}</span>
                        <svg class="msheet__chev" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
                    </a>
                </li>

                {{-- Only appears where the app can be installed and isn't yet (set by public/js/pwa.js) --}}
                <li x-show="canInstall" x-cloak>
                    <button type="button" class="msheet__row msheet__row--install" @click="more = false; window.PWA && window.PWA.install()">
                        <span class="msheet__tile"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg></span>
                        <span class="msheet__text">{{ __('Install app') }}<small>{{ __('Add ITSM to your home screen') }}</small></span>
                    </button>
                </li>

                <li>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="msheet__row msheet__row--danger">
                            <span class="msheet__tile"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" /></svg></span>
                            <span class="msheet__text">{{ __('Log Out') }}</span>
                        </button>
                    </form>
                </li>
            </ul>
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
