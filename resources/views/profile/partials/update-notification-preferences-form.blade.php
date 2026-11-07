@php
    $notifyUser = auth()->user();
    $prefs = $notifyUser->email_preferences ?? [];
    $hasEmail = filled($notifyUser->email);
@endphp

<div id="email-notifications"
     x-data
     x-init="@if($errors->notifications->any() || session('status') === 'notifications-updated') $nextTick(() => $el.scrollIntoView({ behavior: 'smooth', block: 'center' })) @endif">

    <div class="flex items-start gap-3.5 mb-6">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0" style="background-color:#e8f3ec; color:#1a6b3c;">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" /></svg>
        </div>
        <div>
            <h3 class="text-base font-semibold text-gray-800">Email notifications</h3>
            <p class="text-sm text-gray-500 mt-0.5">Get an email when something happens on your tickets — so you don't have to keep the system open.</p>
        </div>
    </div>

    @if (session('status') === 'email-confirmation-sent')
        <div class="mb-6 flex items-start gap-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3" role="status">
            <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25" /></svg>
            <div>
                <p class="text-sm font-semibold text-amber-900">Saved — now confirm your email</p>
                <p class="text-sm text-amber-800/90 mt-0.5">We sent a link to <span class="font-medium">{{ $notifyUser->email }}</span>. Open that email and press <span class="font-medium">Confirm my email</span>; updates start after that. Check the spam folder if you don't see it.</p>
            </div>
        </div>
    @elseif (session('status') === 'email-confirmed')
        <div class="mb-6 flex items-start gap-3 rounded-lg border border-green-200 bg-green-50 px-4 py-3" role="status">
            <svg class="w-5 h-5 text-green-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <p class="text-sm font-semibold text-green-800">Your email is confirmed. You'll now get ticket updates by email.</p>
        </div>
    @elseif (session('status') === 'notifications-updated')
        <div class="mb-6 flex items-start gap-3 rounded-lg border border-green-200 bg-green-50 px-4 py-3" role="status">
            <svg class="w-5 h-5 text-green-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <p class="text-sm font-semibold text-green-800">Your notification settings were saved.</p>
        </div>
    @endif

    <form method="POST" action="{{ route('profile.notifications.update') }}" class="space-y-6" novalidate>
        @csrf
        @method('PATCH')

        <div>
            <label for="notify-email" class="block text-sm font-medium text-gray-700 mb-1.5">Your email address</label>
            <input id="notify-email" type="email" name="email" value="{{ old('email', $notifyUser->email) }}" autocomplete="email" placeholder="name@company.com"
                   class="block w-full rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700 {{ $errors->notifications->has('email') ? 'border-red-400' : '' }}">
            @if($errors->notifications->has('email'))
                <p class="text-red-500 text-xs mt-1.5">{{ $errors->notifications->first('email') }}</p>
            @elseif(! $hasEmail)
                <p class="text-xs text-amber-700 mt-1.5">No email on your account yet — add one to receive updates. You'll still see everything inside the system.</p>
            @elseif($notifyUser->hasVerifiedEmail())
                <p class="text-xs text-green-700 mt-1.5">✓ Confirmed — updates are sent here. Leave it empty to stop all emails.</p>
            @else
                <p class="text-xs text-amber-700 mt-1.5">
                    Waiting for confirmation — open the email we sent to this address and press “Confirm my email”.
                    <button type="submit" form="resend-confirmation" class="font-semibold underline">Send the link again</button>
                </p>
            @endif
        </div>

        <fieldset>
            <legend class="text-sm font-medium text-gray-700 mb-2">Send me an email for…</legend>
            <div class="divide-y divide-gray-100 rounded-lg border border-gray-200">
                @foreach($notifyUser->emailTypes() as $type => $info)
                    <label class="flex cursor-pointer items-start gap-3 px-4 py-3.5 hover:bg-gray-50/60">
                        <input type="checkbox" name="email_types[{{ $type }}]" value="1" @checked(old("email_types.{$type}", $notifyUser->emailSwitchOn($type)))
                               class="mt-0.5 h-4 w-4 rounded border-gray-300 text-green-700 focus:ring-green-700">
                        <span class="min-w-0">
                            <span class="block text-sm font-medium text-gray-800">{{ $info['label'] }}</span>
                            <span class="block text-xs text-gray-500 mt-0.5">{{ $info['hint'] }}</span>
                        </span>
                    </label>
                @endforeach
            </div>
            <p class="text-xs text-gray-400 mt-2">The bell icon inside the system always keeps you up to date, whatever you choose here.</p>
        </fieldset>

        <div class="flex items-center gap-3">
            <button type="submit" class="inline-flex items-center rounded-lg px-5 py-2.5 text-sm font-semibold text-white shadow-sm" style="background-color:#1a6b3c;">Save notification settings</button>
        </div>
    </form>

    @if($hasEmail && ! $notifyUser->hasVerifiedEmail())
        <form id="resend-confirmation" method="POST" action="{{ route('profile.email.resend') }}" class="hidden">@csrf</form>
    @endif
</div>
