{{-- Config for public/js/announcements-live.js: where to poll, and the fingerprint of what this page already shows. --}}
<div id="ann-live" hidden
     data-url="{{ route('announcements.poll') }}"
     data-sig="{{ \App\Models\Announcement::liveState(auth()->user())['sig'] }}"></div>
