{{--
    Everything attached to a ticket: screenshots as thumbnails (click to open full size),
    other files as download chips. Usage: @include('tickets.partials.attachments', ['ticket' => $ticket])
--}}
@php $attachments = $ticket->attachments; @endphp

@if($attachments->isNotEmpty())
    <div class="{{ ($flush ?? false) ? '' : 'mt-4' }}">
        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-2">
            {{ $attachments->count() === 1 ? 'Attachment' : 'Attachments ('.$attachments->count().')' }}
        </p>
        <div class="flex flex-wrap gap-3">
            @foreach($attachments as $file)
                @if($file->isImage())
                    <a href="{{ $file->url }}" target="_blank" rel="noopener" class="group block" title="{{ $file->original_name }}">
                        <img src="{{ $file->url }}" alt="{{ $file->original_name }}" loading="lazy"
                             class="h-24 w-24 rounded-lg border border-gray-200 object-cover transition group-hover:opacity-90 group-hover:ring-2 group-hover:ring-green-600/40">
                    </a>
                @else
                    <a href="{{ $file->url }}" target="_blank" rel="noopener"
                       class="inline-flex max-w-full items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-700 transition hover:bg-gray-100">
                        <svg class="h-4 w-4 shrink-0 text-green-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>
                        <span class="truncate max-w-[200px] font-medium">{{ $file->original_name }}</span>
                        @if($file->human_size)<span class="shrink-0 text-xs text-gray-400">{{ $file->human_size }}</span>@endif
                    </a>
                @endif
            @endforeach
        </div>
    </div>
@endif
