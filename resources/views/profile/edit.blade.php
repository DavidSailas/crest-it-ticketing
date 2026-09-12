<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">My Profile</h2>
    </x-slot>

    <div class="py-8 max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">

        {{-- Masthead --}}
        <div class="relative overflow-hidden rounded-xl" style="background-color:#123f24;">
            <svg class="absolute -right-6 -top-10 w-48 h-48 opacity-[0.07] pointer-events-none" viewBox="0 0 100 100" fill="none" stroke="white" stroke-width="1.5">
                <circle cx="50" cy="35" r="14"/>
                <path d="M20 85 a30 30 0 0 1 60 0"/>
            </svg>
            <div class="relative px-6 py-6 sm:px-8 sm:py-7 flex items-center gap-4">
                <div class="w-14 h-14 rounded-full bg-white/15 flex items-center justify-center text-white text-xl font-bold shrink-0">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
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

            @php
                $activityLogs = \App\Models\ActivityLog::forUser(auth()->id())->latest()->take(15)->get();
            @endphp

            @if ($activityLogs->isEmpty())
                <p class="text-sm text-gray-400">No activity recorded yet.</p>
            @else
                <ul class="divide-y divide-gray-100">
                    @foreach ($activityLogs as $log)
                        <li class="flex items-start gap-3 py-3 first:pt-0 last:pb-0">
                            <span @class([
                                'mt-1 inline-flex w-2 h-2 rounded-full shrink-0',
                                'bg-green-500' => in_array($log->action, ['login', 'ticket_created', 'ticket_accepted']),
                                'bg-gray-400' => $log->action === 'logout',
                                'bg-blue-500' => $log->action === 'ticket_status_updated',
                                'bg-amber-500' => $log->action === 'ticket_comment_added',
                                'bg-red-500' => $log->action === 'login_failed',
                            ])></span>
                            <div class="min-w-0">
                                <p class="text-sm text-gray-700">{{ $log->description }}</p>
                                <p class="text-xs text-gray-400 mt-0.5">
                                    {{ $log->created_at->diffForHumans() }}
                                    &middot; {{ $log->created_at->format('M j, Y g:i A') }}
                                    @if ($log->ip_address)
                                        &middot; {{ $log->ip_address }}
                                    @endif
                                </p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
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
</x-app-layout>
