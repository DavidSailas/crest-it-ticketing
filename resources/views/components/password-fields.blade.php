@props([
    'idPrefix' => '',
    'bag' => 'default',
    'newLabel' => 'New password',
    'confirmLabel' => 'Confirm new password',
])

@php
    $errs = $errors->getBag($bag);
    $lock = 'M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z';
    $eyeOn = 'M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z M15 12a3 3 0 11-6 0 3 3 0 016 0z';
    $eyeOff = 'M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88';
    $inputBase = 'block w-full rounded-lg border-gray-300 bg-white py-2.5 pl-10 pr-11 text-sm shadow-sm placeholder-gray-400 focus:border-green-700 focus:ring-green-700';
@endphp

<div x-data="{
        pw: '',
        confirm: '',
        showPw: false,
        showConfirm: false,
        get rules() {
            return [
                { label: '8+ characters', ok: this.pw.length >= 8 },
                { label: 'Upper & lowercase', ok: /[a-z]/.test(this.pw) && /[A-Z]/.test(this.pw) },
                { label: 'A number', ok: /[0-9]/.test(this.pw) },
                { label: 'A symbol (! ? # $)', ok: /[^A-Za-z0-9]/.test(this.pw) },
            ];
        },
        get score() { return this.rules.filter(r => r.ok).length; },
        get strengthLabel() { return this.pw.length === 0 ? 'Not set' : ['', 'Weak', 'Fair', 'Good', 'Strong'][this.score] || 'Weak'; },
        get strengthText() { return this.pw.length === 0 ? 'text-gray-400' : ['text-red-600', 'text-red-600', 'text-orange-600', 'text-amber-600', 'text-green-700'][this.score]; },
        get matches() { return this.confirm.length > 0 && this.pw === this.confirm; },
        get mismatch() { return this.confirm.length > 0 && this.pw !== this.confirm; },
     }" class="space-y-5">

    <div class="grid gap-5 sm:grid-cols-2">
        {{-- New password --}}
        <div>
            <label for="{{ $idPrefix }}password" class="block text-sm font-medium text-gray-700 mb-1.5">{{ $newLabel }}</label>
            <div class="relative group">
                <svg class="w-4 h-4 text-gray-400 group-focus-within:text-green-700 transition-colors absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $lock }}" /></svg>
                <input id="{{ $idPrefix }}password" name="password" x-model="pw" :type="showPw ? 'text' : 'password'" autocomplete="new-password" placeholder="Create a password"
                       class="{{ $inputBase }} {{ $errs->has('password') ? '!border-red-300 !ring-red-100' : '' }}">
                <button type="button" @click="showPw = !showPw" class="absolute right-2 top-1/2 -translate-y-1/2 p-1.5 rounded-md text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition" :title="showPw ? 'Hide password' : 'Show password'">
                    <svg x-show="!showPw" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $eyeOn }}" /></svg>
                    <svg x-show="showPw" x-cloak class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $eyeOff }}" /></svg>
                </button>
            </div>
            <x-input-error :messages="$errs->get('password')" class="mt-2" />
        </div>

        {{-- Confirm --}}
        <div>
            <label for="{{ $idPrefix }}password_confirmation" class="block text-sm font-medium text-gray-700 mb-1.5">{{ $confirmLabel }}</label>
            <div class="relative group">
                <svg class="w-4 h-4 text-gray-400 group-focus-within:text-green-700 transition-colors absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $lock }}" /></svg>
                <input id="{{ $idPrefix }}password_confirmation" name="password_confirmation" x-model="confirm" :type="showConfirm ? 'text' : 'password'" autocomplete="new-password" placeholder="Re-enter the password"
                       class="{{ $inputBase }}"
                       :class="mismatch ? '!border-red-300' : (matches ? '!border-green-500' : '')">
                <div class="absolute right-2 top-1/2 -translate-y-1/2 flex items-center gap-0.5">
                    <svg x-show="matches" x-cloak class="w-4 h-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                    <button type="button" @click="showConfirm = !showConfirm" class="p-1.5 rounded-md text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition" :title="showConfirm ? 'Hide password' : 'Show password'">
                        <svg x-show="!showConfirm" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $eyeOn }}" /></svg>
                        <svg x-show="showConfirm" x-cloak class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $eyeOff }}" /></svg>
                    </button>
                </div>
            </div>
            <p x-show="mismatch" x-cloak class="mt-2 text-xs font-medium text-red-600">Passwords don't match yet.</p>
            <p x-show="matches" x-cloak class="mt-2 text-xs font-medium text-green-700">Passwords match.</p>
            <x-input-error :messages="$errs->get('password_confirmation')" class="mt-2" />
        </div>
    </div>

    {{-- Strength + requirements panel --}}
    <div class="rounded-xl border border-gray-200 bg-gray-50/70 px-4 py-3.5">
        <div class="flex items-center justify-between mb-2.5">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Password strength</p>
            <p class="text-xs font-semibold" :class="strengthText" x-text="strengthLabel"></p>
        </div>
        <div class="flex items-center gap-1.5 mb-3.5">
            <template x-for="i in 4" :key="i">
                <div class="h-1.5 flex-1 rounded-full transition-colors duration-300"
                     :class="pw.length && i <= score ? (score <= 1 ? 'bg-red-500' : score === 2 ? 'bg-orange-500' : score === 3 ? 'bg-amber-500' : 'bg-green-600') : 'bg-gray-200'"></div>
            </template>
        </div>
        <ul class="grid grid-cols-2 gap-x-4 gap-y-2">
            <template x-for="rule in rules" :key="rule.label">
                <li class="flex items-center gap-2 text-xs transition-colors" :class="rule.ok ? 'text-green-700 font-medium' : 'text-gray-500'">
                    <span class="w-4 h-4 rounded-full shrink-0 flex items-center justify-center transition-colors" :class="rule.ok ? 'bg-green-600' : 'bg-gray-200'">
                        <svg x-show="rule.ok" class="w-2.5 h-2.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="4"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                    </span>
                    <span x-text="rule.label"></span>
                </li>
            </template>
        </ul>
    </div>
</div>
