@php
    $title = \App\Support\ItPolicy::title();
    $company = \App\Support\ItPolicy::company();
    $meta = \App\Support\ItPolicy::meta();
    $fileUrl = route('it-policy.file', ['v' => \App\Support\ItPolicy::version()]);
    $isAdmin = auth()->user()?->hasPermission('policy.manage');
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">IT Policy</h2>
                <p class="text-sm text-gray-500 mt-1">{{ $company }} &mdash; {{ $title }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-2 print:hidden">
                @if($isAdmin)
                    <a href="{{ route('admin.it-policy.edit') }}" class="btn btn-secondary">Manage policy</a>
                @endif
                <a href="{{ $fileUrl }}" target="_blank" rel="noopener" class="btn btn-secondary">Open in new tab</a>
                <a href="{{ route('it-policy.download') }}" class="btn btn-primary">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Download PDF
                </a>
            </div>
        </div>
    </x-slot>

    <style>
        .itp-meta { display: grid; grid-template-columns: repeat(auto-fit, minmax(11rem, 1fr)); gap: 1rem 1.5rem; margin: 0; }
        .itp-meta dt { font-size: .7rem; font-weight: 600; letter-spacing: .04em; text-transform: uppercase; color: #6b7280; }
        .itp-meta dd { margin: .15rem 0 0; font-size: .9rem; font-weight: 500; color: #1f2937; word-break: break-word; }
        .itp-frame { display: block; width: 100%; height: 80vh; min-height: 520px; border: 0; background: #f3f4f6; }
        .itp-mobile { display: none; text-align: center; padding: 2rem 1.25rem; }
        .itp-mobile .btn { width: 100%; max-width: 18rem; }
        /* Phones can't show a PDF inside a page reliably — give them a clear button instead */
        @media (max-width: 767px) {
            .itp-viewer { display: none; }
            .itp-mobile { display: block; }
        }
        @media print { .itp-meta-card { display: none; } .itp-frame { height: 95vh; } }
    </style>

    <div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">

        @if($meta)
            <section class="itp-meta-card bg-white rounded-xl border border-gray-100 shadow-sm p-4 sm:p-5" aria-label="Policy details">
                <dl class="itp-meta">
                    @foreach($meta as $label => $value)
                        <div><dt>{{ $label }}</dt><dd>{{ $value }}</dd></div>
                    @endforeach
                </dl>
            </section>
        @endif

        <section class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="itp-viewer">
                <iframe class="itp-frame" src="{{ $fileUrl }}#view=FitH" title="{{ $title }}"></iframe>
            </div>

            <div class="itp-mobile">
                <p class="text-base font-semibold text-gray-800">{{ $title }}</p>
                <p class="mt-1 mb-4 text-sm text-gray-500">This policy is a PDF document. Open it to read it on your device.</p>
                <div class="flex flex-col items-center gap-2">
                    <a href="{{ $fileUrl }}" target="_blank" rel="noopener" class="btn btn-primary">Read the policy</a>
                    <a href="{{ route('it-policy.download') }}" class="btn btn-secondary">Download PDF</a>
                </div>
            </div>
        </section>

        <p class="text-xs text-gray-400 print:hidden">
            Can't see the document above? <a href="{{ $fileUrl }}" target="_blank" rel="noopener" class="underline hover:text-gray-600">Open it in a new tab</a> or download it.
        </p>
    </div>
</x-app-layout>
