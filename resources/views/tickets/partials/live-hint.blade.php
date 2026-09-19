@if($ticket->isClosed())
    This ticket is closed — comments are now read-only.
@elseif((auth()->user()->isItSupport() || auth()->user()->isAdmin()) && !$ticket->assigned_to)
    Accept this ticket first — you can't comment until it's assigned.
@elseif(auth()->user()->isStaff())
    Add a comment or reply to IT support about this ticket.
@else
    Log updates, findings, or the resolution — {{ $ticket->creator->name }} will see them here.
@endif
