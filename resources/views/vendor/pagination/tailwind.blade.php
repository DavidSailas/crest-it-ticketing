@if ($paginator->hasPages())
    @php
        $current = $paginator->currentPage();
        $last = $paginator->lastPage();
        $pageName = $paginator->getPageName();

        // Never more than 7 slots, so the bar keeps the same width whether there
        // are 8 pages or 800: first, last, the current page and its neighbours.
        if ($last <= 7) {
            $items = range(1, $last);
        } elseif ($current <= 4) {
            $items = [1, 2, 3, 4, 5, '…', $last];
        } elseif ($current >= $last - 3) {
            $items = [1, '…', $last - 4, $last - 3, $last - 2, $last - 1, $last];
        } else {
            $items = [1, '…', $current - 1, $current, $current + 1, '…', $last];
        }

        $base = 'inline-flex items-center justify-center h-9 min-w-[2.25rem] rounded-lg border text-sm font-medium transition select-none focus:outline-none focus-visible:ring-2 focus-visible:ring-green-700 focus-visible:ring-offset-1';
        $idle = $base.' border-gray-200 bg-white text-gray-600 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-900 active:scale-95';
        $off = $base.' border-gray-100 bg-gray-50 text-gray-300 cursor-not-allowed';
        $arrow = 'w-4 h-4 shrink-0';
    @endphp

    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}"
         class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">

        {{-- Result count --}}
        <p class="text-sm text-gray-500 whitespace-nowrap text-center sm:text-left">
            <span class="hidden md:inline">Showing </span><span class="font-semibold text-gray-800">{{ $paginator->firstItem() }}–{{ $paginator->lastItem() }}</span>
            of <span class="font-semibold text-gray-800">{{ number_format($paginator->total()) }}</span><span class="hidden md:inline"> results</span>
        </p>

        {{-- Controls --}}
        <div class="flex items-center justify-center sm:justify-end gap-1.5">
            {{-- Previous --}}
            @if ($paginator->onFirstPage())
                <span class="{{ $off }} px-2.5 md:px-3 gap-1.5" aria-disabled="true">
                    <svg class="{{ $arrow }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
                    <span class="hidden md:inline">Previous</span>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="{{ $idle }} px-2.5 md:px-3 gap-1.5" aria-label="{{ __('pagination.previous') }}">
                    <svg class="{{ $arrow }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
                    <span class="hidden md:inline">Previous</span>
                </a>
            @endif

            {{-- Page numbers (tablet and up) --}}
            <div class="hidden sm:flex items-center gap-1.5">
                @foreach ($items as $item)
                    @if ($item === '…')
                        {{-- Clicking the dots opens a small "jump to page" box --}}
                        <div class="relative" @click.outside="open = false" @keydown.escape="open = false"
                             x-data="{
                                 open: false,
                                 n: '',
                                 go() {
                                     let v = parseInt(this.n, 10);
                                     if (!v) return;
                                     v = Math.min(Math.max(v, 1), {{ $last }});
                                     const u = new URL(window.location.href);
                                     u.searchParams.set('{{ $pageName }}', v);
                                     // Use a real link so lists that load in place (Recent Activity)
                                     // handle it exactly like a normal page click.
                                     const a = document.createElement('a');
                                     a.href = u.toString();
                                     this.$root.closest('nav').appendChild(a);
                                     a.click();
                                     a.remove();
                                 }
                             }">
                            <button type="button" @click="open = !open; if (open) $nextTick(() => $refs.jump.focus())"
                                    class="{{ $idle }} px-2 tracking-widest" title="Jump to a page" aria-label="Jump to a page" :aria-expanded="open">…</button>
                            <div x-show="open" x-cloak x-transition.opacity.duration.100ms
                                 class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 z-30 w-48 rounded-xl border border-gray-200 bg-white p-3 shadow-lg">
                                <p class="text-xs font-medium text-gray-500 mb-2">Jump to page <span class="text-gray-400">(1–{{ $last }})</span></p>
                                <form @submit.prevent="go()" class="flex items-center gap-2">
                                    <input x-ref="jump" x-model="n" type="number" min="1" max="{{ $last }}" placeholder="{{ $current }}"
                                           class="w-full min-w-0 h-9 rounded-lg border-gray-300 text-sm text-center focus:border-green-700 focus:ring-green-700 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
                                    <button type="submit" class="h-9 px-3 rounded-lg text-sm font-semibold text-white shrink-0" style="background-color:#1a6b3c;">Go</button>
                                </form>
                            </div>
                        </div>
                    @elseif ($item === $current)
                        <span aria-current="page" class="{{ $base }} px-2 border-transparent font-semibold text-white shadow-sm" style="background-color:#1a6b3c;">{{ $item }}</span>
                    @else
                        <a href="{{ $paginator->url($item) }}" aria-label="Go to page {{ $item }}" class="{{ $idle }} px-2">{{ $item }}</a>
                    @endif
                @endforeach
            </div>

            {{-- Compact "Page X of Y" (phones) --}}
            <span class="sm:hidden px-2 text-sm text-gray-500 whitespace-nowrap">
                Page <span class="font-semibold text-gray-800">{{ $current }}</span> of {{ $last }}
            </span>

            {{-- Next --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="{{ $idle }} px-2.5 md:px-3 gap-1.5" aria-label="{{ __('pagination.next') }}">
                    <span class="hidden md:inline">Next</span>
                    <svg class="{{ $arrow }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                </a>
            @else
                <span class="{{ $off }} px-2.5 md:px-3 gap-1.5" aria-disabled="true">
                    <span class="hidden md:inline">Next</span>
                    <svg class="{{ $arrow }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                </span>
            @endif
        </div>
    </nav>
@endif
