@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="flex flex-col sm:flex-row items-center justify-between gap-3 px-1">
        {{-- Results count --}}
        <p class="text-sm text-gray-500 order-2 sm:order-1">
            @if ($paginator->firstItem())
                Showing
                <span class="font-medium text-gray-700">{{ $paginator->firstItem() }}</span>
                &ndash;
                <span class="font-medium text-gray-700">{{ $paginator->lastItem() }}</span>
                of
                <span class="font-medium text-gray-700">{{ $paginator->total() }}</span>
                results
            @else
                {{ $paginator->total() }} {{ Str::plural('result', $paginator->total()) }}
            @endif
        </p>

        {{-- Page controls --}}
        <div class="inline-flex items-center gap-0.5 order-1 sm:order-2 rounded-xl border border-gray-200 bg-white p-1 shadow-sm">
            {{-- Previous --}}
            @if ($paginator->onFirstPage())
                <span class="inline-flex items-center gap-1 pl-2 pr-3 h-9 rounded-lg text-sm font-medium text-gray-300 cursor-not-allowed select-none" aria-disabled="true">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
                    <span class="hidden sm:inline">Prev</span>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex items-center gap-1 pl-2 pr-3 h-9 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-900 active:scale-95 transition" aria-label="{{ __('pagination.previous') }}">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
                    <span class="hidden sm:inline">Prev</span>
                </a>
            @endif

            {{-- Divider --}}
            <span class="hidden sm:block w-px h-5 bg-gray-200 mx-0.5"></span>

            {{-- Page numbers (hidden on very small screens to save width; Prev/Next + count still work) --}}
            <div class="hidden xs:flex sm:flex items-center gap-0.5">
                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span class="inline-flex items-center justify-center w-9 h-9 text-sm text-gray-300 select-none">&hellip;</span>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span aria-current="page" class="inline-flex items-center justify-center w-9 h-9 rounded-lg text-sm font-semibold text-white shadow-sm ring-1 ring-inset ring-black/5" style="background-color:#1a6b3c;">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="inline-flex items-center justify-center w-9 h-9 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-900 active:scale-95 transition">{{ $page }}</a>
                            @endif
                        @endforeach
                    @endif
                @endforeach
            </div>

            {{-- Compact "Page X of Y" for narrow screens instead of number buttons --}}
            <span class="sm:hidden inline-flex items-center px-3 h-9 text-sm text-gray-600 font-medium whitespace-nowrap">
                {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}
            </span>

            {{-- Divider --}}
            <span class="hidden sm:block w-px h-5 bg-gray-200 mx-0.5"></span>

            {{-- Next --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex items-center gap-1 pl-3 pr-2 h-9 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-900 active:scale-95 transition" aria-label="{{ __('pagination.next') }}">
                    <span class="hidden sm:inline">Next</span>
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                </a>
            @else
                <span class="inline-flex items-center gap-1 pl-3 pr-2 h-9 rounded-lg text-sm font-medium text-gray-300 cursor-not-allowed select-none" aria-disabled="true">
                    <span class="hidden sm:inline">Next</span>
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                </span>
            @endif
        </div>
    </nav>
@endif
