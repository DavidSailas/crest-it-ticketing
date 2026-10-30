{{-- Latest announcements strip for the top of a dashboard. Renders nothing when there are none. --}}
@php $annBanner = \App\Models\Announcement::bannerFor(auth()->user(), 2); @endphp
<div id="ann-banner" data-ann-live>
@if($annBanner->isNotEmpty())
    <section class="ann-banner" aria-label="Announcements">
        <div class="ann-banner__head">
            <span>Announcements</span>
            <a href="{{ route('announcements.index') }}">View all &rarr;</a>
        </div>
        @foreach($annBanner as $a)
            @php $c = $a->category(); @endphp
            <a href="{{ route('announcements.index') }}#a{{ $a->id }}" class="ann-banner__item" style="--fg:{{ $c['fg'] }}">
                <span class="ann-ico" style="--fg:{{ $c['fg'] }};--bg:{{ $c['bg'] }}"><x-announcement-icon :path="$c['icon']" /></span>
                <span style="min-width:0;flex:1">
                    <span class="ann-banner__title">
                        {{ $a->title }}
                        <span class="ann-chip" style="--fg:{{ $c['fg'] }};--bg:{{ $c['bg'] }}">{{ $c['label'] }}</span>
                        @if($a->is_pinned)<span class="ann-chip ann-chip--plain">Pinned</span>@endif
                    </span>
                    <span class="ann-banner__text" style="display:block">{{ $a->excerpt(150) }}</span>
                </span>
                <span class="ann-banner__date">{{ $a->created_at->format('M j') }}</span>
            </a>
        @endforeach
    </section>
@endif
</div>
