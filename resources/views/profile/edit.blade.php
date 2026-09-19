<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">My Profile</h2>
    </x-slot>

    <div class="py-8 max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

        @if(session('status') && !in_array(session('status'), ['profile-updated', 'verification-link-sent']))
            <div class="p-3 bg-green-50 text-green-800 text-sm rounded-lg border border-green-100">{{ session('status') }}</div>
        @endif

        {{-- Masthead --}}
        <div class="relative overflow-hidden rounded-xl" style="background-color:#123f24;">
            <svg class="absolute -right-6 -top-10 w-48 h-48 opacity-[0.07] pointer-events-none" viewBox="0 0 100 100" fill="none" stroke="white" stroke-width="1.5">
                <circle cx="50" cy="35" r="14"/>
                <path d="M20 85 a30 30 0 0 1 60 0"/>
            </svg>
            <div class="relative px-6 py-6 sm:px-8 sm:py-7 flex items-center gap-5">
                <div class="shrink-0 flex flex-col items-center gap-1.5">
                    <form method="POST" action="{{ route('profile.avatar.update') }}" enctype="multipart/form-data" id="avatar-form">
                        @csrf
                        @method('PATCH')
                        <label for="avatar-input" class="relative group block cursor-pointer rounded-full">
                            <x-avatar size="lg" class="ring-4 ring-white/10" />
                            <span class="absolute inset-0 rounded-full bg-black/50 opacity-0 group-hover:opacity-100 transition flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574v10.176c0 1.19.966 2.25 2.15 2.25h15.2c1.184 0 2.15-1.06 2.15-2.25V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z" />
                                </svg>
                            </span>
                        </label>
                        <input type="file" id="avatar-input" name="avatar" accept="image/png,image/jpeg,image/webp"
                               class="hidden" onchange="this.form.submit()">
                    </form>
                    @if(auth()->user()->avatar)
                        <form method="POST" action="{{ route('profile.avatar.destroy') }}">
                            @csrf
                            @method('DELETE')
                            <button class="text-[11px] text-green-100/70 hover:text-white underline">Remove photo</button>
                        </form>
                    @endif
                </div>
                <div>
                    <p class="text-white text-lg font-semibold">{{ auth()->user()->name }}</p>
                    <p class="text-green-100/80 text-sm mt-0.5">{{ auth()->user()->email }}</p>
                    <div class="flex items-center gap-2 mt-2">
                        <span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-medium bg-white/15 text-white capitalize">
                            {{ str_replace('_', ' ', auth()->user()->role) }}
                        </span>
                        @if(auth()->user()->is_vip)
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-400/20 text-amber-200 ring-1 ring-inset ring-amber-300/40">
                                <svg class="w-3 h-3" viewBox="0 0 20 20" fill="currentColor"><path d="M10 1.5l2.29 4.64 5.12.74-3.7 3.61.87 5.1L10 13.9l-4.58 2.4.87-5.1-3.7-3.61 5.12-.74L10 1.5z" /></svg>
                                VIP
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white shadow-sm rounded-xl border border-gray-100 p-6 sm:p-8">
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-4">Account Information</p>
            @include('profile.partials.update-profile-information-form')
        </div>


        <div class="bg-white shadow-sm rounded-xl border border-gray-100 p-6 sm:p-8">
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-4">Recent Activity</p>

            <div id="activity-log-panel" class="transition-opacity duration-150">
                @include('profile.partials.activity-log-list')
            </div>
        </div>

        <div class="bg-white shadow-sm rounded-xl border border-gray-100 p-6 sm:p-8">
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-4">Password</p>
            @include('profile.partials.update-password-form')
        </div>

        <div class="bg-white shadow-sm rounded-xl border border-red-100 p-6 sm:p-8">
            <p class="text-xs font-semibold uppercase tracking-wider text-red-400 mb-4">Danger Zone</p>
            @include('profile.partials.delete-user-form')
        </div>
    </div>

    <script>
        // Loads Recent Activity pages in place (no full page reload), so the
        // browser never resets scroll position back to the top of the page.
        document.addEventListener('DOMContentLoaded', function () {
            const panel = document.getElementById('activity-log-panel');
            if (!panel) return;

            const loadPage = async (url) => {
                panel.classList.add('opacity-40', 'pointer-events-none');

                try {
                    const response = await fetch(url, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    });

                    if (!response.ok) throw new Error('Request failed');

                    const html = await response.text();
                    const doc = new DOMParser().parseFromString(html, 'text/html');
                    const fresh = doc.getElementById('activity-log-panel');

                    if (fresh) {
                        panel.innerHTML = fresh.innerHTML;
                        window.history.replaceState(window.history.state, '', url);
                    } else {
                        window.location.href = url;
                    }
                } catch (e) {
                    // Fall back to a normal navigation if the fetch fails.
                    window.location.href = url;
                } finally {
                    panel.classList.remove('opacity-40', 'pointer-events-none');
                }
            };

            panel.addEventListener('click', function (e) {
                const link = e.target.closest('a');
                if (!link || !panel.contains(link)) return;
                if (!link.closest('nav[role="navigation"]')) return;

                e.preventDefault();
                loadPage(link.href);
            });
        });
    </script>
</x-app-layout>
