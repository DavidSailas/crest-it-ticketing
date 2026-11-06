{{-- Plain-text version of the branded email (for mail apps that don't show HTML, and for spam filters). --}}
{!! $appName !!}
============================================================

{!! $heading !!}

@if(filled($firstName))
Hi {!! $firstName !!},

@endif
@foreach(($paragraphs ?? []) as $paragraph)
{!! str_replace('**', '', $paragraph) !!}

@endforeach
@if(!empty($ticket))
{!! $ticket['number'] !!} · {!! $ticket['status'] !!}
{!! $ticket['title'] !!}
@foreach($ticket['rows'] as $label => $value)
{!! $label !!}: {!! $value !!}
@endforeach

@endif
@foreach(($details ?? []) as $label => $value)
{!! $label !!}: {!! $value !!}
@endforeach
@if(!empty($details))

@endif
@if(filled($quote ?? null))
@if(filled($quoteBy ?? null)){!! $quoteBy !!}:
@endif
"{!! $quote !!}"

@endif
@if(filled($ctaUrl ?? null))
{!! $ctaLabel ?? 'Open' !!}: {!! $ctaUrl !!}

@endif
@if(filled($outro ?? null))
{!! str_replace('**', '', $outro) !!}

@endif
------------------------------------------------------------
Choose which emails you get: {!! $prefsUrl !!}
