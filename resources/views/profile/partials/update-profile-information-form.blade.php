<form id="send-verification" method="post" action="{{ route('verification.send') }}">
    @csrf
</form>

<form method="post" action="{{ route('profile.update') }}" class="space-y-5">
    @csrf
    @method('patch')

    @php
        // Accounts created before the name was split only have the combined
        // "name" — fall back to splitting it so the fields aren't empty.
        $nameParts = preg_split('/\s+/', trim((string) $user->name), 2);
        $firstValue = old('first_name', $user->first_name ?: ($nameParts[0] ?? ''));
        $middleValue = old('middle_name', $user->middle_name);
        $lastValue = old('last_name', $user->last_name ?: ($nameParts[1] ?? ''));
    @endphp

    <div x-data="{ first: @js($firstValue), middle: @js($middleValue ?? ''), last: @js($lastValue),
            get initial() { const m = this.middle.trim(); return m ? m.charAt(0).toUpperCase() + '.' : ''; },
            get preview() { return [this.first.trim(), this.initial, this.last.trim()].filter(Boolean).join(' '); } }">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1.5">First name</label>
                <div class="relative">
                    <svg class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                    <input id="first_name" name="first_name" type="text" x-model="first" class="block w-full rounded-lg border-gray-300 pl-10 focus:border-green-700 focus:ring-green-700 text-sm" required autofocus autocomplete="given-name" placeholder="e.g. Maria">
                </div>
                <x-input-error class="mt-2" :messages="$errors->get('first_name')" />
            </div>

            <div>
                <label for="middle_name" class="block text-sm font-medium text-gray-700 mb-1.5">Middle name <span class="text-gray-400 font-normal">(optional)</span></label>
                <div class="relative">
                    <svg class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                    <input id="middle_name" name="middle_name" type="text" x-model="middle" class="block w-full rounded-lg border-gray-300 pl-10 focus:border-green-700 focus:ring-green-700 text-sm" autocomplete="additional-name" placeholder="e.g. Cruz">
                </div>
                <x-input-error class="mt-2" :messages="$errors->get('middle_name')" />
            </div>

            <div>
                <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1.5">Last name</label>
                <div class="relative">
                    <svg class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                    <input id="last_name" name="last_name" type="text" x-model="last" class="block w-full rounded-lg border-gray-300 pl-10 focus:border-green-700 focus:ring-green-700 text-sm" required autocomplete="family-name" placeholder="e.g. Santos">
                </div>
                <x-input-error class="mt-2" :messages="$errors->get('last_name')" />
            </div>
        </div>

        <p class="mt-2.5 text-xs text-gray-500">
            Will be displayed as:
            <span class="font-semibold text-gray-800" x-text="preview || '—'"></span>
            <span class="text-gray-400">· your middle name is shown as an initial</span>
        </p>
    </div>

    <div>
        <label for="username" class="block text-sm font-medium text-gray-700 mb-1.5">Username</label>
        <div class="relative">
            <svg class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
            @if ($user->role === 'admin')
                <input id="username" name="username" type="text" class="block w-full rounded-lg border-gray-300 pl-10 focus:border-green-700 focus:ring-green-700 text-sm" value="{{ old('username', $user->username) }}" required autocomplete="username" autocapitalize="none" spellcheck="false">
            @else
                <input id="username" type="text" class="block w-full rounded-lg border-gray-200 bg-gray-50 pl-10 text-sm text-gray-600 cursor-not-allowed" value="{{ $user->username ?? '—' }}" disabled>
            @endif
        </div>
        @if ($user->role === 'admin')
            <x-input-error class="mt-2" :messages="$errors->get('username')" />
            <p class="mt-1.5 text-xs text-gray-400">This is what you use to sign in. Letters, numbers, dashes, and underscores only.</p>
        @else
            <p class="mt-1.5 text-xs text-gray-400">This is what you use to sign in. Contact the IT Support Team if you need it changed.</p>
        @endif
    </div>

    <div class="flex items-center gap-4 pt-2">
        <button type="submit" class="px-5 py-2.5 rounded-lg text-sm font-semibold text-white shadow-sm transition"
            style="background-color:#1a6b3c;" onmouseover="this.style.backgroundColor='#145530'" onmouseout="this.style.backgroundColor='#1a6b3c'">
            Save Changes
        </button>

        @if (session('status') === 'profile-updated')
            <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)" class="text-sm text-green-700 font-medium">Saved.</p>
        @endif
    </div>
</form>
