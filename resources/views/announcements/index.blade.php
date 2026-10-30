<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Announcements</h2>
    </x-slot>

    @php
        $items = $announcements->getCollection();
        $unread = $items->reject(fn ($a) => in_array($a->id, $readIds))->count();
        $catCounts = $items->groupBy('category')->map->count();
        $cats = \App\Models\Announcement::CATEGORIES;
    @endphp

    <div id="ann-feed-live" data-ann-live class="py-6 sm:py-8 max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5"
         x-data="{
            f: 'all',
            ok(cat, isNew) { return this.f === 'all' || (this.f === 'unread' ? isNew : cat === this.f); },
            none() { return ! [...this.$root.querySelectorAll('[data-ann]')].some(e => this.ok(e.dataset.cat, e.dataset.new === '1')); },
         }">

        @if(session('status'))
            <div class="p-3 bg-green-50 text-green-800 text-sm rounded-lg border border-green-100">{{ session('status') }}</div>
        @endif

        <div class="ann-hero">
            <div>
                <h1>Stay up to date</h1>
                <p>New features, system updates and reminders from the IT team, all in one place.
                    @if($unread > 0) You have <strong style="color:#fff">{{ $unread }} unread</strong>. @else You're all caught up. @endif
                </p>
            </div>
            <div class="ann-hero__actions">
                @if($unread > 0)
                    <form method="POST" action="{{ route('announcements.read') }}">
                        @csrf
                        <button class="btn btn-light">Mark all as read</button>
                    </form>
                @endif
                @if(Auth::user()->isAdmin())
                    <a href="{{ route('admin.announcements.index') }}" class="btn btn-ghost">Manage</a>
                @endif
            </div>
        </div>

        @if($items->count() > 1)
            <div class="ann-filters" role="tablist" aria-label="Filter announcements">
                <button type="button" class="ann-filter" :class="f === 'all' ? 'is-on' : ''" @click="f = 'all'">All <b>{{ $items->count() }}</b></button>
                @if($unread > 0)
                    <button type="button" class="ann-filter" :class="f === 'unread' ? 'is-on' : ''" @click="f = 'unread'">Unread <b>{{ $unread }}</b></button>
                @endif
                @foreach($cats as $key => $c)
                    @if(($catCounts[$key] ?? 0) > 0)
                        <button type="button" class="ann-filter" :class="f === '{{ $key }}' ? 'is-on' : ''" @click="f = '{{ $key }}'">{{ $c['label'] }} <b>{{ $catCounts[$key] }}</b></button>
                    @endif
                @endforeach
            </div>
        @endif

        <div class="ann-feed">
            @forelse($announcements as $a)
                @php
                    $c = $a->category();
                    $isNew = ! in_array($a->id, $readIds);
                    $long = mb_strlen($a->body) > 320 || substr_count($a->body, "\n") > 6;
                    $by = $a->creator?->name ?? 'IT Team';
                    $initials = mb_strtoupper(collect(preg_split('/\s+/', trim($by)))->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->join(''));
                @endphp
                <article id="a{{ $a->id }}" data-ann data-cat="{{ $a->category }}" data-new="{{ $isNew ? 1 : 0 }}"
                         class="ann-card {{ $isNew ? 'is-unread' : '' }}" style="--fg:{{ $c['fg'] }};--bg:{{ $c['bg'] }}"
                         x-data="{ open: false }" x-show="ok('{{ $a->category }}', {{ $isNew ? 'true' : 'false' }})">
                    <div class="ann-card__inner">
                        <span class="ann-ico" style="--fg:{{ $c['fg'] }};--bg:{{ $c['bg'] }}"><x-announcement-icon :path="$c['icon']" /></span>
                        <div class="ann-card__main">
                            <div class="ann-card__top">
                                <span class="ann-chip" style="--fg:{{ $c['fg'] }};--bg:{{ $c['bg'] }}">{{ $c['label'] }}</span>
                                @if($a->is_pinned)<span class="ann-chip ann-chip--plain">Pinned</span>@endif
                                @if($isNew)<span class="ann-new">NEW</span>@endif
                                <span class="ann-card__when" title="{{ $a->created_at->format('M j, Y g:i A') }}">{{ $a->created_at->diffForHumans() }}</span>
                            </div>
                            <h3 class="ann-card__title">{{ $a->title }}</h3>
                            <div class="ann-card__text" @if($long) :class="open ? '' : 'is-clamped'" @endif>
                                <div class="ann-body">{!! $a->bodyHtml() !!}</div>
                            </div>
                            @if($long)
                                <button type="button" class="ann-more" @click="open = ! open" x-text="open ? 'Show less' : 'Read more'">Read more</button>
                            @endif
                            <div class="ann-card__foot">
                                <span class="who"><span class="ann-avatar">{{ $initials }}</span>{{ $by }}</span>
                                <span>{{ $a->created_at->format('M j, Y') }}</span>
                                @if($a->ends_at)<span>Until {{ $a->ends_at->format('M j, Y') }}</span>@endif
                            </div>
                        </div>
                    </div>
                </article>
            @empty
                <div class="ann-empty">
                    <span class="ann-ico" style="--fg:#15803d;--bg:#ecf7f0"><x-announcement-icon path="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" /></span>
                    <h3>Nothing new right now</h3>
                    <p>When the IT team shares an update, it will show up here.</p>
                </div>
            @endforelse

            @if($announcements->count())
                <div class="ann-empty" x-show="none()" x-cloak>
                    <h3>No announcements in this view</h3>
                    <p>Try another filter above to see more.</p>
                </div>
            @endif
        </div>

        {{ $announcements->links() }}
    </div>
</x-app-layout>
