<x-support-chat-widget
    :thread-owner="$user"
    :messages="$supportMessages"
    :other-party-last-read-at="$supportOtherPartyLastReadAt"
    :is-own-thread="true"
    :send-url="route('support-chat.send')"
    :poll-url="route('support-chat.poll')"
/>
