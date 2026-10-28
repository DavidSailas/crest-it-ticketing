<x-guest-layout>
    @php
        // IT Support mailbox shown in the "Need help?" box (password resets are handled by the IT desk)
        $supportEmail = 'it@crestforwarderinc.com';
        $mailto = 'mailto:'.$supportEmail
            .'?subject='.rawurlencode('ITSM Login Help')
            .'&body='.rawurlencode("Hi IT Support,\n\nI need help signing in to the ITSM system.\n\nFull name:\nUsername:\nDepartment / Branch:\nProblem (forgot password / account locked / other):\n\nThank you.");
    @endphp
    <style>
        .itsm-login { font-family: 'Poppins', 'Figtree', ui-sans-serif, system-ui, sans-serif; }
        .itsm-login h2 { margin: 0; text-align: center; font-size: clamp(1.25rem, 1.6vw, 1.5rem); line-height: 1.2; font-weight: 700; color: var(--itsm-green-800); }
        .itsm-login .itsm-sub { margin: .35rem 0 0; text-align: center; font-size: .88rem; color: #4a5a54; }

        .itsm-field { position: relative; }
        .itsm-field .itsm-ico { position: absolute; inset: 0 auto 0 0; display: flex; align-items: center; padding-left: .95rem; color: #1d2b27; pointer-events: none; }
        .itsm-field input.itsm-input {
            display: block; width: 100%; height: 2.85rem; box-sizing: border-box;
            padding: 0 2.8rem 0 2.8rem;
            border: 1px solid var(--itsm-line); border-radius: .75rem;
            background: #fff; color: var(--itsm-ink); font-size: .9rem; box-shadow: none;
        }
        .itsm-field input.itsm-input::placeholder { color: #7a8a93; }
        .itsm-field .itsm-eye { position: absolute; inset: 0 0 0 auto; display: flex; align-items: center; padding: 0 .95rem; color: #1d2b27; background: none; border: 0; cursor: pointer; border-radius: 0 .75rem .75rem 0; }
        .itsm-field .itsm-eye:focus-visible { outline: 2px solid var(--itsm-green-600); outline-offset: -2px; }

        .itsm-remember { display: inline-flex; align-items: center; gap: .5rem; cursor: pointer; font-size: .82rem; color: var(--itsm-ink); }
        .itsm-remember input { width: 1.1rem; height: 1.1rem; border-radius: .3rem; border-color: var(--itsm-line); }
        .itsm-signin {
            display: flex; align-items: center; justify-content: center; gap: .55rem;
            width: 100%; height: 2.95rem; border: 0; border-radius: .75rem; cursor: pointer;
            background: var(--itsm-green-700); color: #fff; font-size: .98rem; font-weight: 600;
            transition: background-color .15s;
        }
        .itsm-signin:hover { background: var(--itsm-green-900); }
        .itsm-signin:focus-visible { outline: 3px solid rgba(26,107,60,.35); outline-offset: 2px; }
        .itsm-signin:disabled { opacity: .7; cursor: not-allowed; }

        .itsm-help { display: flex; flex-direction: column; gap: .7rem; padding: .85rem 1rem; border-radius: .75rem; background: #eaf4f1; }
        .itsm-help__top { display: flex; align-items: flex-start; gap: .75rem; }
        .itsm-help__dot { flex: none; width: 1.7rem; height: 1.7rem; border-radius: 9999px; background: #12332a; color: #fff; display: flex; align-items: center; justify-content: center; }
        .itsm-help__text { line-height: 1.35; }
        .itsm-help__text b { display: block; font-weight: 600; font-size: .84rem; color: var(--itsm-ink); }
        .itsm-help__text span { font-size: .76rem; color: #4a5a54; }
        .itsm-help__row { display: flex; flex-wrap: wrap; align-items: stretch; gap: .5rem; }
        .itsm-help__mail { flex: 1 1 100%; min-width: 0; display: flex; align-items: center; gap: .45rem; padding: .3rem .3rem .3rem .65rem; border: 1px solid var(--itsm-line); border-radius: .6rem; background: #fff; font-size: .78rem; color: var(--itsm-ink); }
        .itsm-help__addr { flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; user-select: all; }
        .itsm-help__copy { flex: none; border: 0; border-radius: .4rem; padding: .28rem .6rem; background: var(--itsm-green-100); color: var(--itsm-green-800); font-size: .72rem; font-weight: 600; cursor: pointer; transition: background-color .15s; }
        .itsm-help__copy:hover { background: #d5e9df; }
        .itsm-help__copy:focus-visible, .itsm-help__send:focus-visible { outline: 2px solid var(--itsm-green-600); outline-offset: 2px; }
        .itsm-help__send { flex: 1 1 100%; display: inline-flex; align-items: center; justify-content: center; gap: .4rem; padding: 0 .9rem; min-height: 2.3rem; border-radius: .6rem; border: 1px solid var(--itsm-green-700); color: var(--itsm-green-700); background: transparent; font-size: .8rem; font-weight: 600; text-decoration: none; transition: background-color .15s, color .15s; }
        .itsm-help__send:hover { background: var(--itsm-green-700); color: #fff; }
    </style>

    <div class="itsm-login">
        <h2>Welcome Back!</h2>
        <p class="itsm-sub">Sign in to your ITSM account</p>

        <x-auth-session-status class="mt-4" :status="session('status')" />

        <form method="POST" action="{{ route('login') }}" class="space-y-4" style="margin-top: clamp(1rem, 1.5vw, 1.4rem);" x-data="{ loading: false }" @submit="loading = true">
            @csrf

            {{-- Username (the account's login name) --}}
            <div>
                <div class="itsm-field">
                    <span class="itsm-ico">
                        <svg width="19" height="19" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20.118a7.5 7.5 0 0115 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.5-1.632z"/></svg>
                    </span>
                    <input id="username" name="username" type="text" value="{{ old('username') }}" required autofocus
                        autocomplete="username" autocapitalize="none" autocorrect="off" spellcheck="false"
                        placeholder="Username" aria-label="Username" class="itsm-input">
                </div>
                @if($errors->has('username'))
                    <div class="mt-2 flex items-start gap-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2" role="alert">
                        <svg class="w-4 h-4 text-red-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>
                        <p class="text-sm text-red-700">{{ $errors->first('username') }}</p>
                    </div>
                @endif
            </div>

            {{-- Password --}}
            <div x-data="{ show: false, caps: false }">
                <div class="itsm-field">
                    <span class="itsm-ico">
                        <svg width="19" height="19" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg>
                    </span>
                    <input id="password" name="password" required autocomplete="current-password"
                        placeholder="Password" aria-label="Password" class="itsm-input"
                        :type="show ? 'text' : 'password'" type="password"
                        @keyup="caps = $event.getModifierState && $event.getModifierState('CapsLock')"
                        @blur="caps = false">
                    <button type="button" class="itsm-eye" @click="show = !show"
                        :aria-label="show ? 'Hide password' : 'Show password'" :title="show ? 'Hide password' : 'Show password'">
                        <svg x-show="!show" width="19" height="19" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <svg x-show="show" x-cloak width="19" height="19" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/></svg>
                    </button>
                </div>
                <p x-show="caps" x-cloak class="mt-2 flex items-center gap-1.5 text-xs" style="color:#b45309;">
                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                    Caps Lock is on
                </p>
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <label for="remember_me" class="itsm-remember">
                <input id="remember_me" type="checkbox" name="remember" checked>
                <span>{{ __('Remember me') }}</span>
            </label>

            <button type="submit" :disabled="loading" class="itsm-signin">
                <svg x-show="!loading" width="19" height="19" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 9V5.25A2.25 2.25 0 0110.5 3h6a2.25 2.25 0 012.25 2.25v13.5A2.25 2.25 0 0116.5 21h-6a2.25 2.25 0 01-2.25-2.25V15M12 9l3 3m0 0l-3 3m3-3H2.25"/></svg>
                <svg x-show="loading" x-cloak class="animate-spin" width="19" height="19" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                <span x-text="loading ? 'Signing in…' : 'Sign In'">Sign In</span>
            </button>
        </form>

        {{-- Forgot password / can't sign in: handled by the IT desk (no self-service reset) --}}
        <div class="itsm-help" style="margin-top: 1.1rem;"
            x-data="{
                copied: false,
                copy() {
                    const text = '{{ $supportEmail }}';
                    const done = () => { this.copied = true; setTimeout(() => this.copied = false, 2000); };
                    if (navigator.clipboard && window.isSecureContext) {
                        navigator.clipboard.writeText(text).then(done);
                    } else {
                        const box = document.createElement('textarea');
                        box.value = text; document.body.appendChild(box); box.select();
                        try { document.execCommand('copy'); done(); } catch (e) {}
                        box.remove();
                    }
                }
            }">
            <div class="itsm-help__top">
                <span class="itsm-help__dot">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75a2.25 2.25 0 114.5 0c0 1.5-2.25 1.875-2.25 3.75M12 17.25h.008"/></svg>
                </span>
                <span class="itsm-help__text"><b>Forgot password or can't sign in?</b><span>Contact the IT Support Team and we'll reset it for you.</span></span>
            </div>
            <div class="itsm-help__row">
                <div class="itsm-help__mail">
                    <span class="itsm-help__addr">{{ $supportEmail }}</span>
                    <button type="button" class="itsm-help__copy" @click="copy()" :aria-label="copied ? 'Email address copied' : 'Copy email address'" x-text="copied ? 'Copied!' : 'Copy'">Copy</button>
                </div>
                <a class="itsm-help__send" href="{{ $mailto }}">
                    <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/></svg>
                    Send email
                </a>
            </div>
        </div>
    </div>
</x-guest-layout>
