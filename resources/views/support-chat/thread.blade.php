<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            @unless($isOwnThread)
                <a href="{{ route('support-chat.inbox') }}" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
                </a>
            @endunless
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $isOwnThread ? 'Chat Support' : $threadOwner->name }}
            </h2>
        </div>
    </x-slot>

    <div class="py-8 max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
        <x-support-chat-widget
            :thread-owner="$threadOwner"
            :messages="$messages"
            :other-party-last-read-at="$otherPartyLastReadAt"
            :is-own-thread="$isOwnThread"
            :send-url="$isOwnThread ? route('support-chat.send') : route('support-chat.send.user', $threadOwner)"
            :poll-url="$isOwnThread ? route('support-chat.poll') : route('support-chat.poll.user', $threadOwner)"
        />
    </div>
</x-app-layout>
