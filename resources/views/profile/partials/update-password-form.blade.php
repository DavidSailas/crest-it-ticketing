<div id="password-section" x-data="{ showCurrent: false }"
     x-init="@if($errors->updatePassword->any() || session('status') === 'password-updated') $nextTick(() => $el.scrollIntoView({ behavior: 'smooth', block: 'center' })) @endif">

    {{-- Section intro --}}
    <div class="flex items-start gap-3.5 mb-6">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0" style="background-color:#e8f3ec; color:#1a6b3c;">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z" /></svg>
        </div>
        <div>
            <h3 class="text-base font-semibold text-gray-800">Change your password</h3>
            <p class="text-sm text-gray-500 mt-0.5">Choose a strong password you don't use on other sites.</p>
        </div>
    </div>

    {{-- Result banners: visible right where the user is looking --}}
    @if (session('status') === 'password-updated')
        <div class="mb-6 flex items-start gap-3 rounded-lg border border-green-200 bg-green-50 px-4 py-3">
            <svg class="w-5 h-5 text-green-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <div>
                <p class="text-sm font-semibold text-green-800">Password updated</p>
                <p class="text-sm text-green-700/90 mt-0.5">Use your new password the next time you sign in.</p>
            </div>
        </div>
    @elseif ($errors->updatePassword->any())
        <div class="mb-6 flex items-start gap-3 rounded-lg border border-red-200 bg-red-50 px-4 py-3">
            <svg class="w-5 h-5 text-red-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>
            <div>
                <p class="text-sm font-semibold text-red-800">Your password wasn't changed</p>
                <p class="text-sm text-red-700/90 mt-0.5">Please fix the highlighted fields below and try again. Your current password is still active.</p>
            </div>
        </div>
    @endif

    <form method="post" action="{{ route('password.update') }}" class="space-y-6">
        @csrf
        @method('put')

        {{-- Step 1: verify identity --}}
        <div class="sm:max-w-md">
            <label for="update_password_current_password" class="block text-sm font-medium text-gray-700 mb-1.5">Current password</label>
            <div class="relative group">
                <svg class="w-4 h-4 text-gray-400 group-focus-within:text-green-700 transition-colors absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" /></svg>
                <input id="update_password_current_password" name="current_password" :type="showCurrent ? 'text' : 'password'" autocomplete="current-password" placeholder="Enter your current password"
                       class="block w-full rounded-lg border-gray-300 bg-white py-2.5 pl-10 pr-11 text-sm shadow-sm placeholder-gray-400 focus:border-green-700 focus:ring-green-700 {{ $errors->updatePassword->has('current_password') ? '!border-red-300 !ring-red-100' : '' }}">
                <button type="button" @click="showCurrent = !showCurrent" class="absolute right-2 top-1/2 -translate-y-1/2 p-1.5 rounded-md text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition" :title="showCurrent ? 'Hide password' : 'Show password'">
                    <svg x-show="!showCurrent" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                    <svg x-show="showCurrent" x-cloak class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>
                </button>
            </div>
            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
        </div>

        <div class="border-t border-gray-100"></div>

        {{-- Step 2: choose the new one --}}
        <x-password-fields id-prefix="update_" bag="updatePassword" />

        <div class="flex items-center justify-end gap-3 pt-5 border-t border-gray-100">
            <button type="reset" class="px-4 py-2.5 rounded-lg text-sm font-medium text-gray-600 border border-gray-300 hover:bg-gray-50 transition">Clear</button>
            <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg text-sm font-semibold text-white shadow-sm transition"
                style="background-color:#1a6b3c;" onmouseover="this.style.backgroundColor='#145530'" onmouseout="this.style.backgroundColor='#1a6b3c'">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" /></svg>
                Update password
            </button>
        </div>
    </form>
</div>
