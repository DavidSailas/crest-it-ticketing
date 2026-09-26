<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTicketRequest;
use App\Models\ActivityLog;
use App\Models\Ticket;
use App\Models\TicketRead;
use App\Models\User;
use App\Notifications\TicketAcceptedNotification;
use App\Notifications\TicketApprovedNotification;
use App\Notifications\TicketAssignedNotification;
use App\Notifications\TicketStatusUpdatedNotification;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $search = trim((string) $request->query('search', ''));
        $status = trim((string) $request->query('status', ''));
        $priority = trim((string) $request->query('priority', ''));
        $dateFrom = trim((string) $request->query('date_from', ''));
        $dateTo = trim((string) $request->query('date_to', ''));

        $allStatuses = ['open', 'in_progress', 'pending', 'resolved', 'closed'];

        if ($user->isStaff()) {
            $query = Ticket::where('user_id', $user->id);
            $statusOptions = $allStatuses;
        } elseif ($user->isItSupport()) {
            // The Open Queue of unclaimed tickets lives on the dashboard — this
            // page is the agent's own ticket history: what they're actively
            // working on, plus what they've already resolved or closed.
            $statusOptions = ['in_progress', 'pending', 'resolved', 'closed'];
            $query = Ticket::where('assigned_to', $user->id)
                ->whereIn('status', $statusOptions);
        } else { // admin
            $query = Ticket::query();
            $statusOptions = $allStatuses;
        }

        if ($search !== '') {
            $ticketId = null;
            if (preg_match('/^(inc)?0*(\d+)$/i', $search, $matches)) {
                $ticketId = (int) $matches[2];
            }

            $query->where(function ($q) use ($search, $ticketId) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%")
                    ->orWhere('department', 'like', "%{$search}%")
                    ->orWhereHas('creator', fn ($c) => $c->where('name', 'like', "%{$search}%"));

                if ($ticketId !== null) {
                    $q->orWhere('id', $ticketId);
                }
            });
        }

        // Only ever filter by a status/priority value the app actually uses —
        // an unexpected value in the query string is silently ignored rather
        // than producing an empty (and confusing) result set.
        if (in_array($status, $statusOptions, true)) {
            $query->where('status', $status);
        }

        if (in_array($priority, ['low', 'medium', 'high', 'critical'], true)) {
            $query->where('priority', $priority);
        }

        if ($dateFrom !== '' && $this->isValidDate($dateFrom)) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo !== '' && $this->isValidDate($dateTo)) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $tickets = $query->latest()->paginate(10)->withQueryString();

        $filters = compact('search', 'status', 'priority', 'dateFrom', 'dateTo');
        $hasActiveFilters = $search !== '' || $status !== '' || $priority !== '' || $dateFrom !== '' || $dateTo !== '';

        return view('tickets.index', compact('tickets', 'filters', 'hasActiveFilters', 'statusOptions'));
    }

    /** Guards whereDate() against a malformed date_from/date_to query value. */
    private function isValidDate(string $value): bool
    {
        return (bool) \DateTime::createFromFormat('Y-m-d', $value);
    }

    /**
     * Same search/status/priority/date filters as index(), but scoped to
     * every ticket in the system (admin-only reporting, not "my tickets")
     * and returned as one plain collection instead of a paginator — a
     * report should read as a single document, not a paged list.
     */
    private function filteredTicketsForExport(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $status = trim((string) $request->query('status', ''));
        $priority = trim((string) $request->query('priority', ''));
        $dateFrom = trim((string) $request->query('date_from', ''));
        $dateTo = trim((string) $request->query('date_to', ''));

        $query = Ticket::with(['creator', 'assignee']);

        if ($search !== '') {
            $ticketId = null;
            if (preg_match('/^(inc)?0*(\d+)$/i', $search, $matches)) {
                $ticketId = (int) $matches[2];
            }

            $query->where(function ($q) use ($search, $ticketId) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%")
                    ->orWhere('department', 'like', "%{$search}%")
                    ->orWhereHas('creator', fn ($c) => $c->where('name', 'like', "%{$search}%"));

                if ($ticketId !== null) {
                    $q->orWhere('id', $ticketId);
                }
            });
        }

        if (in_array($status, ['open', 'in_progress', 'pending', 'resolved', 'closed'], true)) {
            $query->where('status', $status);
        }

        if (in_array($priority, ['low', 'medium', 'high', 'critical'], true)) {
            $query->where('priority', $priority);
        }

        if ($dateFrom !== '' && $this->isValidDate($dateFrom)) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo !== '' && $this->isValidDate($dateTo)) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        return $query->latest()->get();
    }

    /**
     * A short, human-readable line describing which filters shaped this
     * report, shown under the title on both the PDF and the Excel export
     * so nobody mistakes a filtered report for the full ticket list.
     */
    private function exportFilterSummary(Request $request): string
    {
        $parts = [];

        if ($search = trim((string) $request->query('search', ''))) {
            $parts[] = "Search: \"{$search}\"";
        }
        if ($status = trim((string) $request->query('status', ''))) {
            $parts[] = 'Status: '.str_replace('_', ' ', ucfirst($status));
        }
        if ($priority = trim((string) $request->query('priority', ''))) {
            $parts[] = 'Priority: '.ucfirst($priority);
        }
        if ($dateFrom = trim((string) $request->query('date_from', ''))) {
            $parts[] = 'From: '.$dateFrom;
        }
        if ($dateTo = trim((string) $request->query('date_to', ''))) {
            $parts[] = 'To: '.$dateTo;
        }

        return $parts ? implode(' · ', $parts) : 'All tickets';
    }

    /**
     * Download the current ticket report as a professionally formatted
     * PDF — letterhead, applied filters, and a clean table. Admin only.
     */
    public function exportPdf(Request $request)
    {
        $tickets = $this->filteredTicketsForExport($request);

        $pdf = Pdf::loadView('tickets.export-pdf', [
            'tickets' => $tickets,
            'filterSummary' => $this->exportFilterSummary($request),
            'generatedAt' => now(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('tickets-report-'.now()->format('Y-m-d').'.pdf');
    }

    /**
     * Download the current ticket report as a polished .xlsx workbook —
     * bold header row, brand color fill, borders, autosized columns, and
     * a frozen header row. Admin only.
     */
    public function exportExcel(Request $request): StreamedResponse
    {
        $tickets = $this->filteredTicketsForExport($request);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Tickets');

        $sheet->setCellValue('A1', 'Crest IT Service Desk — Ticket Report');
        $sheet->mergeCells('A1:J1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getFont()->getColor()->setRGB('123F24');

        $sheet->setCellValue('A2', 'Generated '.now()->format('F j, Y g:i A').' · '.$this->exportFilterSummary($request));
        $sheet->mergeCells('A2:J2');
        $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(9);
        $sheet->getStyle('A2')->getFont()->getColor()->setRGB('6B7280');

        $headers = ['Ticket #', 'Category', 'Subcategory', 'Requester', 'Assigned To', 'Priority', 'Status', 'Created', 'Resolved', 'Closed'];
        $sheet->fromArray($headers, null, 'A4');
        $sheet->getStyle('A4:J4')->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle('A4:J4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1A6B3C');
        $sheet->getStyle('A4:J4')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        $row = 5;
        foreach ($tickets as $ticket) {
            $sheet->setCellValue("A{$row}", $ticket->ticket_number);
            $sheet->setCellValue("B{$row}", $ticket->category);
            $sheet->setCellValue("C{$row}", $ticket->subcategory ?? '—');
            $sheet->setCellValue("D{$row}", $ticket->creator->name ?? '—');
            $sheet->setCellValue("E{$row}", $ticket->assignee->name ?? 'Unassigned');
            $sheet->setCellValue("F{$row}", ucfirst($ticket->priority));
            $sheet->setCellValue("G{$row}", str_replace('_', ' ', ucfirst($ticket->status)));
            $sheet->setCellValue("H{$row}", $ticket->created_at->format('M j, Y g:i A'));
            $sheet->setCellValue("I{$row}", $ticket->resolved_at?->format('M j, Y g:i A') ?? '—');
            $sheet->setCellValue("J{$row}", $ticket->closed_at?->format('M j, Y g:i A') ?? '—');
            $row++;
        }

        $lastRow = $row - 1;

        if ($lastRow >= 5) {
            $sheet->getStyle("A4:J{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('E5E7EB');

            for ($i = 5; $i <= $lastRow; $i++) {
                if ($i % 2 === 0) {
                    $sheet->getStyle("A{$i}:J{$i}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F9FAFB');
                }
            }
        }

        foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'] as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $sheet->freezePane('A5');

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 'tickets-report-'.now()->format('Y-m-d').'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function create()
{
    $colleagues = User::where('role', 'staff')
        ->where('id', '!=', auth()->id())
        ->with('department')
        ->orderBy('name')
        ->get()
        ->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email ?? $user->email_address ?? null, // ensures email is captured regardless of column name
                // Whether this colleague's own department/office is set —
                // used to auto-route a ticket raised on their behalf, and
                // to warn the requester up front if it isn't.
                'profile_complete' => filled($user->department?->name) && filled($user->branch_name),
            ];
        });

    return view('tickets.create', compact('colleagues'));
}

    public function store(StoreTicketRequest $request)
    {
        $data = $request->validated();

        $data['user_id'] = $request->user()->id;

        // There's no separate title field anymore — the category the requester
        // picked ("Printer", "Password / Account", etc.) doubles as the ticket
        // title everywhere else in the app (lists, chat, notifications).
        $data['title'] = $data['category'];

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $data['attachment_path'] = $file->store('ticket-attachments', 'public');
            $data['attachment_name'] = $file->getClientOriginalName();
        }

        // VIP accounts (owner, CEO, president, etc.) always get bumped to critical,
        // regardless of what was selected in the form.
        $wasAutoEscalated = false;
        if ($request->user()->is_vip && $data['priority'] !== 'critical') {
            $data['priority'] = 'critical';
            $wasAutoEscalated = true;
        }

        $ticket = Ticket::create($data);

        ActivityLog::record(
            'ticket_created',
            $wasAutoEscalated
                ? "Submitted ticket {$ticket->ticket_number}: {$ticket->title} (auto-escalated to Critical — VIP account)"
                : "Submitted ticket {$ticket->ticket_number}: {$ticket->title}",
            ['ticket_id' => $ticket->id, 'auto_escalated' => $wasAutoEscalated]
        );

        return redirect()->route('tickets.confirmation', $ticket);
    }

    /**
     * A professional "receipt" shown right after submission — the ticket
     * number, what was submitted, and where to go next. Only the requester
     * (or an admin) can view it.
     */
    public function confirmation(Ticket $ticket)
    {
        abort_unless(
            $ticket->user_id === auth()->id() || auth()->user()->isAdmin(),
            403
        );

        return view('tickets.confirmation', compact('ticket'));
    }

    /**
     * The other side of a ticket's 1:1 chat: the creator if you're IT/admin,
     * or the assigned IT staffer if you're the creator. Used to compute
     * "Seen" read receipts.
     */
    private function otherPartyId(Ticket $ticket, int $currentUserId): ?int
    {
        return $ticket->user_id === $currentUserId
            ? $ticket->assigned_to
            : $ticket->user_id;
    }

    public function show(Ticket $ticket)
    {
        $ticket->load('comments.author', 'creator', 'assignee');

        $currentUserId = auth()->id();
        TicketRead::markRead($ticket->id, $currentUserId);

        $otherPartyId = $this->otherPartyId($ticket, $currentUserId);
        $otherPartyLastReadAt = $otherPartyId
            ? optional(TicketRead::where('ticket_id', $ticket->id)->where('user_id', $otherPartyId)->first())->last_read_at
            : null;

        return view('tickets.show', compact('ticket', 'otherPartyLastReadAt'));
    }

    public function accept(Request $request, Ticket $ticket)
    {
        abort_unless($request->user()->isItSupport() || $request->user()->isAdmin(), 403);

        $ticket->update([
            'assigned_to' => $request->user()->id,
            'status' => 'in_progress',
        ]);

        ActivityLog::record(
            'ticket_accepted',
            "Accepted ticket {$ticket->ticket_number}: {$ticket->title}",
            ['ticket_id' => $ticket->id]
        );

        $ticket->creator?->notify(new TicketAcceptedNotification($ticket, $request->user()));

        return back()->with('status', 'Ticket accepted.');
    }

    /**
     * Assign a ticket to a specific IT support agent, rather than waiting
     * for someone to self-accept it from the queue.
     *
     * Admins can route any ticket to anyone. IT Support agents can do this
     * too, but only to redistribute work that's genuinely theirs to move:
     * an unclaimed ticket, or one already assigned to them. Handing off a
     * ticket that belongs to a *different* agent still needs an admin, so
     * ownership can't be taken from someone without them knowing.
     */
    public function assign(Request $request, Ticket $ticket)
    {
        $user = $request->user();
        abort_unless($user->isItSupport() || $user->isAdmin(), 403);

        if ($ticket->isClosed()) {
            return back()->with('error', 'This ticket is closed and can no longer be reassigned.');
        }

        if (! $user->isAdmin() && $ticket->assigned_to && $ticket->assigned_to !== $user->id) {
            abort(403, 'Only an admin can reassign a ticket that already belongs to another agent.');
        }

        $validated = $request->validate([
            'assigned_to' => 'required|exists:users,id',
        ], [
            'assigned_to.required' => 'Please choose an IT Support agent to assign this to.',
        ]);

        $agent = User::where('id', $validated['assigned_to'])->where('role', 'it_support')->firstOrFail();

        $ticket->update([
            'assigned_to' => $agent->id,
            'status' => $ticket->status === 'open' ? 'in_progress' : $ticket->status,
        ]);

        ActivityLog::record(
            'ticket_assigned',
            "Assigned ticket {$ticket->ticket_number} to {$agent->name}",
            ['ticket_id' => $ticket->id, 'assigned_to' => $agent->id]
        );

        // Notification delivery (email) can fail independently of the actual
        // assignment — e.g. a mail provider's rate limit — and shouldn't turn
        // a successful save into a 500 for whoever is reassigning it.
        try {
            $agent->notify(new TicketAssignedNotification($ticket, $user));
            $ticket->creator?->notify(new TicketAcceptedNotification($ticket, $agent));
        } catch (\Throwable $e) {
            report($e);
        }

        return back()->with('status', "Assigned to {$agent->name}.");
    }

    /**
     * Real-time refresh for the ticket page. The browser polls this every few
     * seconds with the fingerprint of each region it is showing; we only send
     * back HTML for the regions that actually changed (status, approval,
     * assignment, solution, comment box), so it stays cheap.
     *
     * GET /tickets/{ticket}/live?h[ticket]=...&h[hint]=...&h[composer]=...
     */
    public function live(Request $request, Ticket $ticket)
    {
        $user = $request->user();

        abort_unless(
            $user->isAdmin() || $user->isItSupport() || $ticket->user_id === $user->id,
            403
        );

        $ticket->load(['creator', 'assignee']);

        $known = (array) $request->query('h', []);

        $regions = [
            'ticket' => [$ticket->liveHash(), 'tickets.partials.live-ticket'],
            'hint' => [$ticket->threadHash(), 'tickets.partials.live-hint'],
            'composer' => [$ticket->threadHash(), 'tickets.partials.live-composer'],
        ];

        $changed = [];
        foreach ($regions as $name => [$hash, $view]) {
            if (($known[$name] ?? null) !== $hash) {
                $changed[$name] = [
                    'hash' => $hash,
                    'html' => view($view, ['ticket' => $ticket])->render(),
                ];
            }
        }

        return response()->json(['regions' => $changed])->header('Cache-Control', 'no-store');
    }

    /**
     * The requester confirms that a Resolved ticket is really fixed. This is
     * the gate IT Support needs before they're allowed to close the ticket.
     */
    public function approve(Request $request, Ticket $ticket)
    {
        // Only the person who raised the ticket can approve its resolution.
        abort_unless($ticket->user_id === $request->user()->id, 403);

        if ($ticket->isClosed()) {
            return back()->with('error', 'This ticket is already closed.');
        }

        if ($ticket->status !== 'resolved') {
            return back()->with('error', 'IT Support has not marked this ticket as resolved yet.');
        }

        if ($ticket->isApproved()) {
            return back()->with('status', 'You already approved this resolution.');
        }

        $ticket->update(['approved_at' => now()]);

        ActivityLog::record(
            'ticket_approved',
            "Approved the resolution of ticket {$ticket->ticket_number}: {$ticket->title}",
            ['ticket_id' => $ticket->id]
        );

        // A failed email shouldn't undo a successful approval.
        try {
            $ticket->assignee?->notify(new TicketApprovedNotification($ticket, $request->user()));
        } catch (\Throwable $e) {
            report($e);
        }

        return back()->with('status', 'Thanks! IT Support can now close this ticket.');
    }

    public function updateStatus(Request $request, Ticket $ticket)
    {
        abort_unless($request->user()->isItSupport() || $request->user()->isAdmin(), 403);

        if (is_null($ticket->assigned_to)) {
            return back()->with('error', 'This ticket must be assigned to an IT agent before its status can be changed.');
        }

        // Closed is terminal — once here, nothing about status, assignment,
        // or the solution can change. Enforced here too (not just hidden in
        // the UI) so a stale page or a crafted request can't reopen it.
        if ($ticket->isClosed()) {
            return back()->with('error', 'This ticket is closed and can no longer be updated.');
        }

        // A ticket can only be closed once IT has marked it Resolved.
        // Enforced server-side so it can't be skipped by a stale page or a
        // crafted request. (Confirming the fix with the requester now
        // happens off-platform — a call or message from IT before closing.)
        if ($request->input('status') === 'closed' && ! $ticket->canBeClosed()) {
            return back()->with('error', 'Mark this ticket as Resolved before it can be closed.');
        }

        $request->validate([
            'status' => 'required|in:open,in_progress,pending,resolved,closed',
            'solution' => ['required_if:status,closed', 'nullable', 'string', 'max:5000'],
        ], [
            'solution.required_if' => 'Describe how this was resolved before closing the ticket.',
        ]);

        $oldStatus = $ticket->status;

        $updates = ['status' => $request->status];

        // Stamp resolved_at the moment a ticket first becomes resolved; clear it
        // if it gets reopened later so the "solved" date always reflects reality.
        if ($request->status === 'resolved' && $oldStatus !== 'resolved') {
            $updates['resolved_at'] = now();
        } elseif (in_array($request->status, ['open', 'in_progress'])) {
            $updates['resolved_at'] = null;
        }

        // Approval only means something for the resolution being approved. If
        // the ticket is reopened (or moved anywhere other than resolved/closed),
        // the requester has to approve again next time it's resolved.
        if (! in_array($request->status, ['resolved', 'closed'])) {
            $updates['approved_at'] = null;
        }

        if ($request->status === 'closed') {
            $updates['solution'] = $request->solution;
            $updates['closed_at'] = now();
            // Closing implies the issue was solved, even if it skipped the
            // "resolved" step — keep resolved_at consistent either way.
            $updates['resolved_at'] = $ticket->resolved_at ?? now();
        }

        $ticket->update($updates);

        ActivityLog::record(
            'ticket_status_updated',
            "Changed ticket {$ticket->ticket_number} status from {$oldStatus} to {$request->status}",
            ['ticket_id' => $ticket->id, 'from' => $oldStatus, 'to' => $request->status]
        );

        if ($oldStatus !== $request->status) {
            $ticket->creator?->notify(new TicketStatusUpdatedNotification($ticket, $oldStatus, $request->status));
        }

        return back()->with('status', 'Ticket status updated.');
    }

    /**
     * Lightweight polling endpoint used by the IT support dashboard / nav bell
     * to check for newly created, unassigned tickets in near real-time.
     *
     * GET /tickets/queue/poll?since_id=123
     */
    public function pollQueue(Request $request)
    {
        abort_unless($request->user()->isItSupport() || $request->user()->isAdmin(), 403);

        $sinceId = (int) $request->query('since_id', 0);

        $newTickets = Ticket::with('creator')
            ->where('status', 'open')
            ->whereNull('assigned_to')
            ->where('id', '>', $sinceId)
            ->orderBy('id')
            ->get()
            ->map(fn (Ticket $ticket) => [
                'id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number,
                'title' => $ticket->title,
                'department' => $ticket->department,
                'category' => $ticket->category,
                'subcategory' => $ticket->subcategory,
                'priority' => $ticket->priority,
                'requester' => $ticket->creator->name,
                'requester_is_vip' => (bool) $ticket->creator->is_vip,
                'url' => route('tickets.show', $ticket),
                'created_at' => $ticket->created_at->diffForHumans(),
            ]);

        $unassignedCount = Ticket::where('status', 'open')->whereNull('assigned_to')->count();
        $latestId = Ticket::max('id') ?? $sinceId;

        return response()->json([
            'tickets' => $newTickets,
            'unassigned_count' => $unassignedCount,
            'latest_id' => $latestId,
        ]);
    }

    /**
     * Chat inbox — one thread per ticket, scoped to the same tickets each role
     * already sees on /tickets, ordered by most recent activity so active
     * conversations float to the top.
     */
    /**
     * The other side of a ticket's chat, and the pieces needed to render one
     * row of the Chat inbox: who it's with, the latest message, and whether
     * the current user has already seen that latest message.
     */
    private function chatThreadSummary(Ticket $ticket, $user, ?\Illuminate\Support\Carbon $lastReadAt): array
    {
        $isStaff = $user->isStaff();
        $otherParty = $isStaff ? $ticket->assignee : $ticket->creator;
        $lastMessage = $ticket->comments->first();

        // Unread = the other party's latest message hasn't been seen yet.
        // A thread I sent the last message in is never "unread" for me.
        $unread = $lastMessage
            && $lastMessage->user_id !== $user->id
            && (! $lastReadAt || $lastMessage->created_at->gt($lastReadAt));

        $activityAt = $lastMessage->created_at ?? $ticket->created_at;

        return [
            'ticket_id' => $ticket->id,
            'ticket_number' => $ticket->ticket_number,
            'url' => route('tickets.show', $ticket).'#chat',
            'other_party_name' => $isStaff ? ($ticket->assignee->name ?? 'IT Support (unassigned)') : $ticket->creator->name,
            'other_party_initials' => $otherParty
                ? strtoupper(collect(explode(' ', $otherParty->name))->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode(''))
                : null,
            'show_vip_badge' => ! $isStaff && $ticket->creator->is_vip,
            'title' => $ticket->title,
            'status' => $ticket->status,
            'has_messages' => (bool) $lastMessage,
            'last_message_author' => $lastMessage?->author->name,
            'last_message_body' => $lastMessage ? \Illuminate\Support\Str::limit($lastMessage->body, 80) : null,
            'last_activity_at' => $activityAt->toIso8601String(),
            'last_activity_human' => $activityAt->diffForHumans(),
            'unread' => $unread,
        ];
    }

    /** Same ticket scoping chatIndex/chatPoll both need, kept in one place. */
    private function chatTicketsQuery($user)
    {
        if ($user->isStaff()) {
            return Ticket::where('user_id', $user->id)->with(['comments.author', 'assignee']);
        } elseif ($user->isItSupport()) {
            // Same rule as their Tickets page: only tickets *specifically*
            // assigned to this agent — not the whole company's open queue.
            // If David has accepted 3, only those 3 show here for him.
            return Ticket::where('assigned_to', $user->id)->with(['comments.author', 'creator']);
        }

        return Ticket::with(['comments.author', 'creator', 'assignee']); // admin
    }

    /** @return array<int, array> */
    private function buildChatThreads($tickets, $user): array
    {
        $lastReads = TicketRead::where('user_id', $user->id)
            ->whereIn('ticket_id', $tickets->pluck('id'))
            ->pluck('last_read_at', 'ticket_id');

        return $tickets
            ->sortByDesc(fn (Ticket $ticket) => optional($ticket->comments->first())->created_at ?? $ticket->created_at)
            ->values()
            ->map(fn (Ticket $ticket) => $this->chatThreadSummary($ticket, $user, $lastReads->get($ticket->id)))
            ->all();
    }

    public function chatIndex(Request $request)
    {
        $user = $request->user();
        $tickets = $this->chatTicketsQuery($user)->get();
        $threads = $this->buildChatThreads($tickets, $user);

        return view('chat.index', compact('threads'));
    }

    /**
     * Polled every few seconds by the Chat inbox so new messages and
     * read/unread state show up without a page refresh.
     */
    public function chatPoll(Request $request)
    {
        $user = $request->user();
        $tickets = $this->chatTicketsQuery($user)->get();

        return response()->json(['threads' => $this->buildChatThreads($tickets, $user)]);
    }

    public function comment(Request $request, Ticket $ticket)
    {
        abort_if($ticket->status === 'closed', 403, 'This ticket is closed — the conversation can no longer be replied to.');

        $user = $request->user();
        if (($user->isItSupport() || $user->isAdmin()) && is_null($ticket->assigned_to)) {
            $message = 'This ticket must be assigned before IT can comment on it. Accept it first.';

            if ($request->wantsJson()) {
                return response()->json(['message' => $message], 403);
            }

            return back()->with('error', $message);
        }

        $request->validate(['body' => 'required|string|max:2000'], [
            'body.required' => 'Please type a message before sending.',
            'body.max' => 'Message is too long — please keep it under 2,000 characters.',
        ]);

        $comment = $ticket->comments()->create([
            'user_id' => $request->user()->id,
            'body' => $request->body,
        ]);

        $comment->load('author');

        ActivityLog::record(
            'ticket_comment_added',
            "Commented on ticket {$ticket->ticket_number}: {$ticket->title}",
            ['ticket_id' => $ticket->id]
        );

        if ($request->wantsJson()) {
            return response()->json([
                'comment' => [
                    'id' => $comment->id,
                    'body' => $comment->body,
                    'author_name' => $comment->author->name,
                    'author_id' => $comment->user_id,
                    'is_mine' => true,
                    'created_at_human' => $comment->created_at->diffForHumans(),
                    'created_at' => $comment->created_at->toIso8601String(),
                ],
            ]);
        }

        return back()->with('status', 'Comment added.');
    }

    /**
     * Lightweight polling endpoint for the ticket chat thread — the browser
     * asks every few seconds for anything newer than the last message it has,
     * giving a near real-time feel without needing WebSockets/queue workers.
     *
     * GET /tickets/{ticket}/chat/poll?since_id=123
     */
    public function pollChat(Request $request, Ticket $ticket)
    {
        $user = $request->user();

        abort_unless(
            $user->isAdmin()
                || $ticket->user_id === $user->id
                || $ticket->assigned_to === $user->id
                || ($user->isItSupport() && $ticket->status === 'open'),
            403
        );

        $sinceId = (int) $request->query('since_id', 0);

        // Polling means the user is actively looking at this thread right now.
        TicketRead::markRead($ticket->id, $user->id);

        $otherPartyId = $this->otherPartyId($ticket, $user->id);
        $otherPartyLastReadAt = $otherPartyId
            ? optional(TicketRead::where('ticket_id', $ticket->id)->where('user_id', $otherPartyId)->first())->last_read_at
            : null;

        $messages = $ticket->comments()
            ->with('author')
            ->where('id', '>', $sinceId)
            ->orderBy('id')
            ->get()
            ->map(fn ($comment) => [
                'id' => $comment->id,
                'body' => $comment->body,
                'author_name' => $comment->author->name,
                'author_id' => $comment->user_id,
                'is_mine' => $comment->user_id === $user->id,
                'is_it' => $comment->author->role !== 'staff',
                'created_at_human' => $comment->created_at->diffForHumans(),
                'created_at' => $comment->created_at->toIso8601String(),
            ]);

        return response()->json([
            'messages' => $messages,
            'latest_id' => $ticket->comments()->max('id') ?? $sinceId,
            'other_party_last_read_at' => $otherPartyLastReadAt?->toIso8601String(),
        ]);
    }
}
