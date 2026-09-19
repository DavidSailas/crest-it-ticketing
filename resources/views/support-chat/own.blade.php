<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Chat Support</h2>
    </x-slot>

    <div class="py-8 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Masthead --}}
        <div class="relative overflow-hidden rounded-xl mb-6" style="background-color:#123f24;">
            <svg class="absolute -right-6 -top-10 w-48 h-48 opacity-[0.07] pointer-events-none" viewBox="0 0 100 100" fill="none" stroke="white" stroke-width="1.5">
                <path d="M20 30h60v35H45l-12 12v-12H20z" stroke-linejoin="round"/>
                <circle cx="38" cy="47" r="2.5" fill="white" stroke="none"/>
                <circle cx="50" cy="47" r="2.5" fill="white" stroke="none"/>
                <circle cx="62" cy="47" r="2.5" fill="white" stroke="none"/>
            </svg>
            <div class="relative px-6 py-6 sm:px-8 sm:py-7">
                <p class="text-white text-lg font-semibold">Talk to IT Support</p>
                <p class="text-green-100/80 text-sm mt-1">A quick, direct line to the team — no ticket needed for simple questions.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
            <div class="lg:col-span-2">
                <x-support-chat-widget
                    :thread-owner="$threadOwner"
                    :messages="$messages"
                    :other-party-last-read-at="$otherPartyLastReadAt"
                    :is-own-thread="true"
                    :send-url="route('support-chat.send')"
                    :poll-url="route('support-chat.poll')"
                />
            </div>

            <div class="space-y-4">
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                    <p class="text-sm font-semibold text-gray-800 mb-3">When to use this</p>
                    <ul class="space-y-2.5 text-sm text-gray-600">
                        <li class="flex items-start gap-2">
                            <svg class="w-4 h-4 text-green-700 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                            Quick questions that don't need a formal ticket
                        </li>
                        <li class="flex items-start gap-2">
                            <svg class="w-4 h-4 text-green-700 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                            Checking on something before you submit a request
                        </li>
                        <li class="flex items-start gap-2">
                            <svg class="w-4 h-4 text-green-700 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                            General how-to or account questions
                        </li>
                    </ul>
                </div>

                <div class="bg-amber-50 rounded-xl border border-amber-100 p-5">
                    <p class="text-sm font-semibold text-amber-900 mb-1.5">For actual issues</p>
                    <p class="text-xs text-amber-800 leading-relaxed">If something's broken or you need IT to fix or set something up, please <a href="{{ route('tickets.create') }}" class="underline font-medium">submit a ticket</a> instead — it gets tracked, prioritized, and assigned properly.</p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
