<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Announcements</h2>
            <a href="{{ route('admin.announcements.create') }}" class="btn btn-primary">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                New announcement
            </a>
        </div>
    </x-slot>

    @php
        $tiles = [
            'live' => ['Live now', '#15803d', '#ecf7f0', 'M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178zM15 12a3 3 0 11-6 0 3 3 0 016 0z'],
            'scheduled' => ['Scheduled', '#1d4ed8', '#eff6ff', 'M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z'],
            'draft' => ['Drafts', '#475569', '#f1f5f9', 'M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125'],
            'expired' => ['Expired', '#b91c1c', '#fef2f2', 'M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m6 4.125l2.25 2.25m0 0l2.25 2.25M12 13.875l2.25-2.25M12 13.875l-2.25 2.25M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z'],
        ];
    @endphp

    <div class="py-6 sm:py-8 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">
        @if(session('status'))
            <div class="p-3 bg-green-50 text-green-800 text-sm rounded-lg border border-green-100">{{ session('status') }}</div>
        @endif

        <div class="ann-hero">
            <div>
                <h1>Keep everyone in the loop</h1>
                <p>Share new features, maintenance windows and reminders. Each post appears as a pop-up the next time someone opens the system, and stays in their feed.</p>
            </div>
            <div class="ann-hero__actions">
                <a href="{{ route('announcements.index') }}" class="btn btn-ghost">See what staff see</a>
            </div>
        </div>

        <div class="ann-stats">
            @foreach($tiles as $key => [$label, $fg, $bg, $icon])
                <a href="{{ $status === $key ? route('admin.announcements.index') : route('admin.announcements.index', ['status' => $key]) }}"
                   class="ann-stat {{ $status === $key ? 'is-on' : '' }}" @if($status === $key) aria-current="true" @endif>
                    <span class="ann-ico" style="--fg:{{ $fg }};--bg:{{ $bg }}"><x-announcement-icon :path="$icon" /></span>
                    <span>
                        <span class="ann-stat__n" style="display:block">{{ $counts[$key] }}</span>
                        <span class="ann-stat__l" style="display:block">{{ $label }}</span>
                    </span>
                </a>
            @endforeach
        </div>

        @if($status)
            <p class="text-sm text-gray-500">
                Showing <strong class="text-gray-700">{{ $tiles[$status][0] }}</strong> announcements ({{ $announcements->total() }}).
                <a href="{{ route('admin.announcements.index') }}" class="font-medium" style="color:#15803d">Show all {{ $counts['all'] }}</a>
            </p>
        @endif

        <div class="ann-feed">
            @forelse($announcements as $a)
                @php
                    $c = $a->category();
                    $st = $a->status();
                    $audience = $reach[$a->audience] ?? $reach['all'];
                    $pct = $audience > 0 ? min(100, round($a->readers_count / $audience * 100)) : 0;
                @endphp
                <article class="ann-row {{ in_array($st, ['draft', 'expired']) ? 'is-muted' : '' }}" style="--fg:{{ $c['fg'] }}">
                    <span class="ann-ico" style="--fg:{{ $c['fg'] }};--bg:{{ $c['bg'] }}"><x-announcement-icon :path="$c['icon']" /></span>

                    <div class="ann-row__main">
                        <div class="ann-row__title">
                            <a href="{{ route('admin.announcements.edit', $a) }}">{{ $a->title }}</a>
                            <span class="ann-pill ann-pill--{{ $st }}">{{ ucfirst($st) }}</span>
                            @if($a->is_pinned)<span class="ann-chip ann-chip--plain">Pinned</span>@endif
                        </div>
                        <p class="ann-row__meta">
                            <span>{{ $c['label'] }}</span>
                            <span>{{ $a->audienceLabel() }}</span>
                            <span>{{ $a->is_popup ? 'Pop-up on open' : 'Feed only' }}</span>
                            <span>{{ $a->created_at->format('M j, Y') }}@if($a->creator) by {{ $a->creator->name }}@endif</span>
                            @if($a->starts_at && $st === 'scheduled')<span>Starts {{ $a->starts_at->format('M j, g:i A') }}</span>@endif
                            @if($a->ends_at)<span>{{ $st === 'expired' ? 'Ended' : 'Ends' }} {{ $a->ends_at->format('M j, g:i A') }}</span>@endif
                        </p>
                        <p class="ann-row__text">{{ $a->excerpt(170) }}</p>
                    </div>

                    <div class="ann-row__side">
                        @if(in_array($st, ['live', 'expired']))
                            <div class="ann-reach">
                                Seen by <strong>{{ $a->readers_count }}</strong> of {{ $audience }} ({{ $pct }}%)
                                <div class="ann-reach__bar"><span style="width:{{ $pct }}%"></span></div>
                            </div>
                        @else
                            <div class="ann-reach ann-muted">{{ $st === 'draft' ? 'Not visible to anyone yet' : 'Waiting for its start time' }}</div>
                        @endif

                        <div class="ann-row__actions">
                            <a href="{{ route('admin.announcements.edit', $a) }}" class="btn btn-soft btn-sm">Edit</a>
                            <form method="POST" action="{{ route('admin.announcements.toggle', $a) }}">
                                @csrf @method('PATCH')
                                <button class="btn btn-secondary btn-sm">{{ $a->is_published ? 'Unpublish' : 'Publish' }}</button>
                            </form>
                            <x-confirm-action-modal
                                id="delete-announcement-{{ $a->id }}"
                                action="{{ route('admin.announcements.destroy', $a) }}"
                                title="Delete this announcement?"
                                message="Delete &quot;{{ $a->title }}&quot;? People will no longer see it. This can't be undone."
                                confirm-label="Delete"
                                trigger-label="Delete"
                                trigger-class="btn btn-danger btn-sm"
                            />
                        </div>
                    </div>
                </article>
            @empty
                <div class="ann-empty">
                    <span class="ann-ico" style="--fg:#15803d;--bg:#ecf7f0"><x-announcement-icon path="M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 110-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.117-1.527-.461a20.845 20.845 0 01-1.44-4.282m3.102.069a18.03 18.03 0 01-.59-4.59c0-1.586.205-3.124.59-4.59m0 9.18a23.848 23.848 0 018.835 2.535M10.34 6.66a23.847 23.847 0 008.835-2.535" /></span>
                    <h3>{{ $status ? 'Nothing here yet' : 'No announcements yet' }}</h3>
                    <p>{{ $status ? 'No announcements match this filter.' : "Post your first update to let everyone know what's new." }}</p>
                    @unless($status)
                        <a href="{{ route('admin.announcements.create') }}" class="btn btn-primary" style="margin-top:1rem">New announcement</a>
                    @endunless
                </div>
            @endforelse
        </div>

        {{ $announcements->links() }}
    </div>
</x-app-layout>
