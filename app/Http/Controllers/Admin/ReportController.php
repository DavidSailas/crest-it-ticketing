<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTicketRequest;
use App\Models\Asset;
use App\Models\Ticket;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    /**
     * Named date ranges an admin can report on, in the order shown in the
     * picker. "This month" is the default landing view.
     */
    private const RANGES = [
        'this_month' => 'This month',
        'last_month' => 'Last month',
        'this_year' => 'This year',
        'last_30' => 'Last 30 days',
        'last_90' => 'Last 90 days',
        'all_time' => 'All time',
        'custom' => 'Custom range',
    ];

    public function index(Request $request)
    {
        [$from, $to, $rangeLabel] = $this->resolveRange($request);
        $report = $this->buildReport($from, $to);

        return view('admin.reports.index', array_merge(
            $report,
            [
                'ranges' => self::RANGES,
                'selectedRange' => $request->query('range', 'this_month'),
                'rangeLabel' => $rangeLabel,
                'from' => $from,
                'to' => $to,
                'customFrom' => $request->query('from'),
                'customTo' => $request->query('to'),
                'comparison' => $this->previousPeriodComparison($from, $to, $report),
                'summary' => $this->narrativeSummary($rangeLabel, $report),
                'agentActivity' => $this->agentActivity($request),
                'engineerReport' => $this->teamTable('engineers', $from, $to),
                'staffReport' => $this->teamTable('staff', $from, $to),
            ]
        ));
    }

    public function exportPdf(Request $request)
    {
        [$from, $to, $rangeLabel] = $this->resolveRange($request);
        $report = $this->buildReport($from, $to);

        $data = array_merge($report, [
            'rangeLabel' => $rangeLabel,
            'from' => $from,
            'to' => $to,
            'generatedAt' => now(),
            'preparedBy' => $request->user()->name,
            'comparison' => $this->previousPeriodComparison($from, $to, $report),
            'summary' => $this->narrativeSummary($rangeLabel, $report),
            'logoData' => $this->logoDataUri(),
            'engineerReport' => $this->teamTable('engineers', $from, $to),
            'staffReport' => $this->teamTable('staff', $from, $to),
        ]);

        $pdf = Pdf::loadView('admin.reports.export-pdf', $data)->setPaper('a4', 'portrait');

        $filename = 'it-report-'.\Illuminate\Support\Str::slug($rangeLabel).'-'.now()->format('Y-m-d').'.pdf';

        return $pdf->download($filename);
    }

    /**
     * Download the IT Engineer or Staff report as Excel or PDF, for the same
     * reporting period that is selected on the Reports page.
     *
     * $team: 'engineers' | 'staff'      $format: 'excel' | 'pdf'
     */
    public function exportTeam(Request $request, string $team, string $format)
    {
        return $this->deliverTeam($request, $team, $format);
    }

    private function deliverTeam(Request $request, string $team, string $format)
    {
        [$from, $to, $rangeLabel] = $this->resolveRange($request);
        $table = $this->teamTable($team, $from, $to);
        $slug = match ($team) {
            'staff' => 'staff-report-',
            default => 'it-engineer-report-',
        }.\Illuminate\Support\Str::slug($rangeLabel).'-'.now()->format('Y-m-d');

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('admin.reports.team-pdf', [
                'table' => $table,
                'rangeLabel' => $rangeLabel,
                'generatedAt' => now(),
                'preparedBy' => $request->user()->name,
                'logoData' => $this->logoDataUri(),
            ])->setPaper('a4', count($table['headers']) > 6 ? 'landscape' : 'portrait');

            return $pdf->download($slug.'.pdf');
        }

        return $this->teamExcel($table, $rangeLabel, $slug.'.xlsx');
    }

    /**
     * One table definition shared by the Reports page, the PDF and the Excel
     * file so the three can never disagree.
     *
     * Returns: title, subtitle, headers, rows (plain arrays, same order as
     * headers), numeric (column indexes to right-align and total), totals.
     * Rows are ordered busiest first; the page itself only shows the top few.
     */
    private function teamTable(string $team, ?Carbon $from, ?Carbon $to): array
    {
        return match ($team) {
            'staff' => $this->staffTable($from, $to),
            default => $this->engineerTable($from, $to),
        };
    }

    private function engineerTable(?Carbon $from, ?Carbon $to): array
    {
        $engineers = User::where('role', 'it_support')->with('department')->orderBy('name')->get();

        // Every ticket assigned to an engineer that was created in the period,
        // split by where it stands now — so "Tickets" is exactly the sum of the
        // five status columns next to it.
        $tickets = Ticket::whereNotNull('assigned_to')
            ->when($from, fn ($q) => $q->where('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->where('created_at', '<=', $to))
            ->get(['assigned_to', 'status', 'created_at', 'resolved_at'])
            ->groupBy('assigned_to');

        $rows = $engineers->map(function ($e) use ($tickets) {
            $mine = $tickets[$e->id] ?? collect();
            $done = $mine->filter(fn ($t) => $t->resolved_at);
            $avg = $done->isNotEmpty()
                ? $done->avg(fn ($t) => $t->created_at->diffInMinutes($t->resolved_at) / 60)
                : null;

            return [
                'name' => $e->name,
                'department' => $e->department->name ?? '—',
                'total' => $mine->count(),
                'open' => $mine->where('status', 'open')->count(),
                'in_progress' => $mine->where('status', 'in_progress')->count(),
                'pending' => $mine->where('status', 'pending')->count(),
                'resolved' => $mine->where('status', 'resolved')->count(),
                'closed' => $mine->where('status', 'closed')->count(),
                'avg' => $avg === null ? '—' : ($avg < 24 ? round($avg, 1).' h' : round($avg / 24, 1).' d'),
            ];
        })->sortBy([['total', 'desc'], ['name', 'asc']])->values();

        return [
            'key' => 'engineers',
            'title' => 'IT Engineer report',
            'headers' => ['IT Engineer', 'Department', 'Tickets', 'Open', 'In progress', 'Pending', 'Resolved', 'Closed', 'Avg. resolution'],
            'numeric' => [2, 3, 4, 5, 6, 7, 8],
            'totalCols' => [2, 3, 4, 5, 6, 7],
            'rows' => $rows->map(fn ($r) => array_values($r))->all(),
            'note' => 'Tickets = everything assigned to the engineer that was created in the period, shown by its current status · Avg. resolution = created to resolved',
        ];
    }

    private function staffTable(?Carbon $from, ?Carbon $to): array
    {
        $staff = User::where('role', 'staff')->with('department')->orderBy('name')->get();

        $tickets = Ticket::whereNotNull('user_id')
            ->when($from, fn ($q) => $q->where('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->where('created_at', '<=', $to))
            ->get(['user_id', 'status', 'created_at'])
            ->groupBy('user_id');

        $rows = $staff->map(function ($u) use ($tickets) {
            $mine = $tickets[$u->id] ?? collect();

            return [
                'name' => $u->name,
                'department' => $u->department->name ?? '—',
                'branch' => $u->branch_name ?: '—',
                'submitted' => $mine->count(),
                'open' => $mine->whereIn('status', ['open', 'in_progress', 'pending'])->count(),
                'resolved' => $mine->whereIn('status', ['resolved', 'closed'])->count(),
                'last' => $mine->isNotEmpty() ? $mine->max('created_at')->format('M j, Y') : '—',
            ];
        })->sortBy([['submitted', 'desc'], ['name', 'asc']])->values();

        return [
            'key' => 'staff',
            'title' => 'Staff report',
            'headers' => ['Staff', 'Department', 'Branch', 'Tickets submitted', 'Still open', 'Resolved / closed', 'Last ticket'],
            'numeric' => [3, 4, 5],
            'totalCols' => [3, 4, 5],
            'rows' => $rows->map(fn ($r) => array_values($r))->all(),
            'note' => 'Tickets submitted by each staff member in the period · Still open = Open, In Progress or Pending',
        ];
    }

    /** Branded Excel file for a team table (same look as the user directory export). */
    private function teamExcel(array $table, string $rangeLabel, string $filename): StreamedResponse
    {
        $cols = count($table['headers']);
        $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($cols);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(mb_substr($table['title'], 0, 31));

        $sheet->setCellValue('A1', 'Crest IT Service Desk — '.$table['title']);
        $sheet->mergeCells("A1:{$lastCol}1");
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('123F24');

        $sheet->setCellValue('A2', $rangeLabel.' · Generated '.now()->format('F j, Y g:i A'));
        $sheet->mergeCells("A2:{$lastCol}2");
        $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(9)->getColor()->setRGB('6B7280');

        $sheet->fromArray($table['headers'], null, 'A4');
        $head = $sheet->getStyle("A4:{$lastCol}4");
        $head->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $head->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1A6B3C');
        $head->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        $row = 5;
        foreach ($table['rows'] as $r) {
            $sheet->fromArray($r, null, "A{$row}");
            if ($row % 2 === 0) {
                $sheet->getStyle("A{$row}:{$lastCol}{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F9FAFB');
            }
            $row++;
        }
        $lastRow = max($row - 1, 4);

        // Totals row
        if (count($table['rows']) > 0 && ! empty($table['totalCols'])) {
            $sheet->setCellValue("A{$row}", 'Total');
            foreach ($table['totalCols'] as $c) {
                $letter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c + 1);
                $sheet->setCellValue("{$letter}{$row}", "=SUM({$letter}5:{$letter}".($row - 1).')');
            }
            $total = $sheet->getStyle("A{$row}:{$lastCol}{$row}");
            $total->getFont()->setBold(true);
            $total->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E8F3EC');
            $lastRow = $row;
        }

        $sheet->getStyle("A4:{$lastCol}{$lastRow}")->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('E5E7EB');

        foreach ($table['numeric'] as $c) {
            $letter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c + 1);
            $sheet->getStyle("{$letter}4:{$letter}{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }

        for ($i = 1; $i <= $cols; $i++) {
            $sheet->getColumnDimensionByColumn($i)->setAutoSize(true);
        }
        $sheet->freezePane('A5');

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /** Periods offered by the "IT Support activity" filter. Defaults to Today. */
    private const ACTIVITY_RANGES = [
        'today' => 'Today',
        'yesterday' => 'Yesterday',
        'this_week' => 'This week',
        'this_month' => 'This month',
        'custom' => 'Custom range',
    ];

    /**
     * "How many tickets did each IT Support agent handle?" for a period that
     * is filtered independently of the main report range (so you can look at
     * Today while the report above shows This month).
     *
     * Handled = tickets assigned to that agent that were resolved/closed in
     * the period (resolved_at). "Working now" is their live workload: tickets
     * still In Progress or Pending, regardless of period.
     *
     * Query params: act (period), act_from/act_to (custom), act_agent (id|all).
     */
    private function agentActivity(Request $request): array
    {
        $period = $request->query('act', 'today');
        if (! array_key_exists($period, self::ACTIVITY_RANGES)) {
            $period = 'today';
        }

        $now = now();
        switch ($period) {
            case 'yesterday':
                $start = $now->copy()->subDay()->startOfDay();
                $end = $now->copy()->subDay()->endOfDay();
                break;
            case 'this_week':
                $start = $now->copy()->startOfWeek();
                $end = $now->copy()->endOfWeek();
                break;
            case 'this_month':
                $start = $now->copy()->startOfMonth();
                $end = $now->copy()->endOfMonth();
                break;
            case 'custom':
                $start = $request->filled('act_from') ? Carbon::parse($request->query('act_from'))->startOfDay() : $now->copy()->startOfDay();
                $end = $request->filled('act_to') ? Carbon::parse($request->query('act_to'))->endOfDay() : $now->copy()->endOfDay();
                break;
            case 'today':
            default:
                $start = $now->copy()->startOfDay();
                $end = $now->copy()->endOfDay();
                break;
        }

        $label = match ($period) {
            'today' => 'Today, '.$now->format('M j'),
            'yesterday' => 'Yesterday, '.$start->format('M j'),
            'custom' => $start->format('M j, Y').' – '.$end->format('M j, Y'),
            default => self::ACTIVITY_RANGES[$period].' ('.$start->format('M j').' – '.$end->format('M j').')',
        };

        $agents = User::where('role', 'it_support')->orderBy('name')->get();

        $selectedId = $request->query('act_agent', 'all');
        $selectedId = ctype_digit((string) $selectedId) && $agents->contains('id', (int) $selectedId)
            ? (int) $selectedId
            : null;

        $handled = Ticket::whereNotNull('assigned_to')
            ->whereNotNull('resolved_at')
            ->where('resolved_at', '>=', $start)
            ->where('resolved_at', '<=', $end)
            ->toBase()
            ->selectRaw('assigned_to, COUNT(*) as total')
            ->groupBy('assigned_to')
            ->pluck('total', 'assigned_to');

        $working = Ticket::whereNotNull('assigned_to')
            ->whereIn('status', ['in_progress', 'pending'])
            ->toBase()
            ->selectRaw('assigned_to, COUNT(*) as total')
            ->groupBy('assigned_to')
            ->pluck('total', 'assigned_to');

        $rows = $agents
            ->filter(fn ($a) => $selectedId === null || $a->id === $selectedId)
            ->map(fn ($a) => [
                'id' => $a->id,
                'name' => $a->name,
                'online' => $a->isOnline(),
                'handled' => (int) ($handled[$a->id] ?? 0),
                'working' => (int) ($working[$a->id] ?? 0),
            ])
            ->sortByDesc('handled')
            ->values()
            ->all();

        // When a single agent is picked, list the tickets behind their number.
        $tickets = collect();
        if ($selectedId !== null) {
            $tickets = Ticket::where('assigned_to', $selectedId)
                ->where('resolved_at', '>=', $start)
                ->where('resolved_at', '<=', $end)
                ->with('creator')
                ->orderByDesc('resolved_at')
                ->take(25)
                ->get();
        }

        return [
            'period' => $period,
            'periods' => self::ACTIVITY_RANGES,
            'label' => $label,
            'from' => $request->query('act_from'),
            'to' => $request->query('act_to'),
            'agents' => $agents,
            'selectedAgent' => $selectedId,
            'rows' => $rows,
            'totalHandled' => array_sum(array_column($rows, 'handled')),
            'tickets' => $tickets,
        ];
    }

    /**
     * How this period compares to the equivalent period immediately before
     * it (same length, shifted back) — e.g. this month vs. last month. Null
     * for "all time", where there's no equivalent prior window.
     */
    private function previousPeriodComparison(?Carbon $from, ?Carbon $to, array $report): ?array
    {
        if (! $from || ! $to) {
            return null;
        }

        $lengthDays = $from->diffInDays($to) + 1;
        $prevTo = $from->copy()->subSecond();
        $prevFrom = $prevTo->copy()->subDays($lengthDays - 1)->startOfDay();

        $prevTickets = Ticket::where('created_at', '>=', $prevFrom)->where('created_at', '<=', $prevTo)->count();
        $prevResolved = Ticket::where('created_at', '>=', $prevFrom)->where('created_at', '<=', $prevTo)
            ->whereIn('status', ['resolved', 'closed'])->count();
        $prevRate = $prevTickets > 0 ? round(($prevResolved / $prevTickets) * 100) : null;

        return [
            'label' => 'vs. previous period',
            'ticketsDelta' => $this->percentDelta($prevTickets, $report['totalTickets']),
            'rateDelta' => $report['resolutionRate'] !== null && $prevRate !== null
                ? $report['resolutionRate'] - $prevRate
                : null,
        ];
    }

    /**
     * Percent change from $before to $after, direction-aware (null when
     * there's nothing to compare against — e.g. zero tickets in both).
     */
    private function percentDelta(int $before, int $after): ?float
    {
        if ($before === 0) {
            return $after > 0 ? 100.0 : null;
        }

        return round((($after - $before) / $before) * 100);
    }

    /**
     * A short, plain-English paragraph summarizing the period — the kind of
     * line an admin would otherwise have to write by hand before forwarding
     * this report to a manager.
     */
    private function narrativeSummary(string $rangeLabel, array $report): string
    {
        if ($report['totalTickets'] === 0) {
            return "No tickets were created during {$rangeLabel}.";
        }

        $sentence = "During {$rangeLabel}, {$report['totalTickets']} ".
            ($report['totalTickets'] === 1 ? 'ticket was' : 'tickets were')." submitted";

        if ($report['resolutionRate'] !== null) {
            $sentence .= ", with {$report['resolutionRate']}% resolved or closed";
        }

        if ($report['avgResolutionHours'] !== null) {
            $sentence .= $report['avgResolutionHours'] < 24
                ? ' at an average resolution time of '.round($report['avgResolutionHours'], 1).' hours'
                : ' at an average resolution time of '.round($report['avgResolutionHours'] / 24, 1).' days';
        }

        $sentence .= '.';

        if ($report['unassignedInRange'] > 0) {
            $sentence .= " {$report['unassignedInRange']} ".
                ($report['unassignedInRange'] === 1 ? 'ticket remains' : 'tickets remain').' unassigned and need attention.';
        }

        return $sentence;
    }

    /**
     * The company logo as a data: URI, so it's embedded directly in the PDF
     * rather than requiring dompdf to resolve a filesystem/HTTP path.
     */
    private function logoDataUri(): ?string
    {
        $path = public_path('images/logo.png');

        if (! file_exists($path)) {
            return null;
        }

        return 'data:image/png;base64,'.base64_encode(file_get_contents($path));
    }

    /**
     * Turns the "range" query param (plus from/to for a custom range) into
     * a concrete [start, end] pair and a human label used in both the page
     * heading and the PDF cover.
     */
    private function resolveRange(Request $request): array
    {
        $range = $request->query('range', 'this_month');
        $now = now();

        switch ($range) {
            case 'last_month':
                $start = $now->copy()->subMonthNoOverflow()->startOfMonth();
                $end = $now->copy()->subMonthNoOverflow()->endOfMonth();
                break;
            case 'this_year':
                $start = $now->copy()->startOfYear();
                $end = $now->copy()->endOfYear();
                break;
            case 'last_30':
                $start = $now->copy()->subDays(29)->startOfDay();
                $end = $now->copy()->endOfDay();
                break;
            case 'last_90':
                $start = $now->copy()->subDays(89)->startOfDay();
                $end = $now->copy()->endOfDay();
                break;
            case 'all_time':
                $start = null;
                $end = null;
                break;
            case 'custom':
                $start = $request->filled('from') ? Carbon::parse($request->query('from'))->startOfDay() : $now->copy()->startOfMonth();
                $end = $request->filled('to') ? Carbon::parse($request->query('to'))->endOfDay() : $now->copy()->endOfDay();
                break;
            case 'this_month':
            default:
                $range = 'this_month';
                $start = $now->copy()->startOfMonth();
                $end = $now->copy()->endOfMonth();
                break;
        }

        if ($range === 'all_time') {
            $label = 'All time';
        } elseif ($range === 'custom') {
            $label = $start->format('M j, Y').' – '.$end->format('M j, Y');
        } else {
            $label = self::RANGES[$range];
        }

        return [$start, $end, $label];
    }

    /**
     * Everything the report page and the PDF both need, built once so the
     * two never drift apart. $from/$to are null for "all time".
     */
    private function buildReport(?Carbon $from, ?Carbon $to): array
    {
        // Qualified with the table name throughout (tickets.created_at, not
        // just created_at) because byAgent below joins the users table,
        // which also has a created_at column — leaving it bare caused an
        // "ambiguous column" error once that join was in play.
        $ticketsInRange = Ticket::query()
            ->when($from, fn ($q) => $q->where('tickets.created_at', '>=', $from))
            ->when($to, fn ($q) => $q->where('tickets.created_at', '<=', $to));

        $totalTickets = (clone $ticketsInRange)->count();

        $byStatus = (clone $ticketsInRange)->toBase()
            ->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');

        $byPriority = (clone $ticketsInRange)->toBase()
            ->selectRaw('priority, COUNT(*) as total')->groupBy('priority')->pluck('total', 'priority');

        $byCategory = (clone $ticketsInRange)->toBase()
            ->selectRaw('category, COUNT(*) as total')->groupBy('category')
            ->orderByDesc('total')->pluck('total', 'category');

        // Full per-category breakdown: every category on the ticket form is
        // listed (even with 0 tickets) with its count and share, because the
        // pie above folds everything after the top 5 into "Other". Older
        // tickets with a category no longer on the form are still included.
        $categoryCounts = (clone $ticketsInRange)->toBase()
            ->selectRaw('category, COUNT(*) as total')->groupBy('category')->pluck('total', 'category');
        $categoryBreakdown = collect(StoreTicketRequest::CATEGORIES)
            ->merge($categoryCounts->keys())
            ->unique()
            ->map(fn ($name) => [
                'label' => $name,
                'value' => (int) ($categoryCounts[$name] ?? 0),
                'percent' => $totalTickets > 0 ? round((($categoryCounts[$name] ?? 0) / $totalTickets) * 100, 1) : 0,
            ])
            ->sortByDesc('value')
            ->values()
            ->all();

        // Resolution time: only tickets that actually reached resolved/closed
        // in this window, so "average" doesn't get skewed by ones still open.
        // Computed in PHP (rather than a driver-specific SQL date function)
        // so this works the same on SQLite and MySQL.
        $resolvedRows = (clone $ticketsInRange)->whereNotNull('resolved_at')->get(['created_at', 'resolved_at']);
        $avgResolutionHours = $resolvedRows->isNotEmpty()
            ? $resolvedRows->avg(fn ($t) => $t->created_at->diffInMinutes($t->resolved_at) / 60)
            : null;

        $closedCount = (clone $ticketsInRange)->where('status', 'closed')->count();
        $resolvedOrClosed = (clone $ticketsInRange)->whereIn('status', ['resolved', 'closed'])->count();

        // Workload per IT Support agent — tickets assigned to them that were
        // *created* in this window, so a busy month shows up as busy.
        $byAgent = (clone $ticketsInRange)->toBase()
            ->join('users', 'users.id', '=', 'tickets.assigned_to')
            ->selectRaw('users.name as agent, COUNT(*) as total')
            ->groupBy('users.name')->orderByDesc('total')->pluck('total', 'agent');

        $unassignedInRange = (clone $ticketsInRange)->whereNull('assigned_to')->count();

        // Volume trend — tickets created per day (short ranges) or per month
        // (year / all-time), so the line makes sense either way.
        $trend = $this->buildTrend($from, $to);

        // Assets: a snapshot as of now (assets don't really belong to a
        // "created in range" story the way tickets do — the inventory is
        // whatever's on hand today).
        $totalAssets = Asset::count();
        $assetsByType = Asset::toBase()->selectRaw('type, COUNT(*) as total')->groupBy('type')->pluck('total', 'type');
        $assignedAssets = Asset::whereNotNull('user_id')->count();
        $unassignedAssets = $totalAssets - $assignedAssets;
        $assetsByStatus = Asset::toBase()->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');

        $usersByRole = User::toBase()->selectRaw('role, COUNT(*) as total')->groupBy('role')->pluck('total', 'role');

        return [
            'totalTickets' => $totalTickets,
            'byStatus' => $this->labelPieData($byStatus, [
                'open' => 'Open', 'in_progress' => 'In Progress', 'pending' => 'Pending',
                'resolved' => 'Resolved', 'closed' => 'Closed', 'cancelled' => 'Cancelled',
            ], [
                'open' => '#3b82f6', 'in_progress' => '#6366f1', 'pending' => '#f59e0b',
                'resolved' => '#10b981', 'closed' => '#9ca3af', 'cancelled' => '#f87171',
            ]),
            'byPriority' => $this->labelPieData($byPriority, [
                'low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'critical' => 'Critical',
            ], [
                'low' => '#9ca3af', 'medium' => '#3b82f6', 'high' => '#f59e0b', 'critical' => '#ef4444',
            ]),
            'byCategory' => $this->labelPieData($byCategory, null, null, true),
            'categoryBreakdown' => $categoryBreakdown,
            'avgResolutionHours' => $avgResolutionHours,
            'closedCount' => $closedCount,
            'resolutionRate' => $totalTickets > 0 ? round(($resolvedOrClosed / $totalTickets) * 100) : null,
            'byAgent' => $byAgent,
            'unassignedInRange' => $unassignedInRange,
            'trend' => $trend,

            'totalAssets' => $totalAssets,
            'assignedAssets' => $assignedAssets,
            'unassignedAssets' => $unassignedAssets,
            'assetsByType' => $this->labelPieData($assetsByType, Asset::TYPES, null),
            'assetsByStatus' => $this->labelPieData($assetsByStatus, Asset::STATUSES, [
                'active' => '#10b981', 'in_repair' => '#f59e0b', 'retired' => '#9ca3af',
            ]),

            'usersByRole' => $this->labelPieData($usersByRole, [
                'staff' => 'Staff', 'it_support' => 'IT Support', 'admin' => 'Admin',
            ], [
                'staff' => '#6366f1', 'it_support' => '#1a6b3c', 'admin' => '#b45309',
            ]),
        ];
    }

    /**
     * A rotating palette for facets with no fixed brand color (categories
     * are free text, so we can't hardcode one color per value).
     */
    /** Fixed colour per calendar month (October = brand green), matching the on-screen chart. */
    private const MONTH_PALETTE = ['#1a6b3c', '#2563eb', '#d97706', '#7c3aed', '#0891b2', '#e11d48', '#65a30d', '#c026d3', '#ea580c', '#0d9488', '#4f46e5', '#b45309'];

    private const PALETTE = ['#1a6b3c', '#3b82f6', '#f59e0b', '#ef4444', '#6366f1', '#14b8a6', '#ec4899', '#9ca3af'];

    /**
     * Normalizes a `pluck('total','key')` collection into the shape the pie
     * chart component and the PDF table both expect: label, value, color,
     * percent. $labels/$colors are optional lookup maps; when omitted the
     * raw key is title-cased and colors come from the rotating palette.
     */
    private function labelPieData($rows, ?array $labels, ?array $colors, bool $capOthers = false): array
    {
        $rows = collect($rows)->filter(fn ($v) => $v > 0);

        if ($capOthers && $rows->count() > 6) {
            $top = $rows->sortDesc()->take(5);
            $rest = $rows->sortDesc()->slice(5)->sum();
            $rows = $rest > 0 ? $top->put('Other', $rest) : $top;
        }

        $total = $rows->sum();
        $i = 0;

        return $rows->map(function ($value, $key) use ($labels, $colors, $total, &$i) {
            $label = $labels[$key] ?? (is_string($key) ? ucwords(str_replace(['_', '-'], ' ', $key)) : $key);
            $color = $colors[$key] ?? self::PALETTE[$i % count(self::PALETTE)];
            $i++;

            return [
                'label' => $label,
                'value' => (int) $value,
                'color' => $color,
                'percent' => $total > 0 ? round(($value / $total) * 100, 1) : 0,
            ];
        })->values()->all();
    }

    /**
     * Ticket volume over the window: daily buckets for a range of 3 months
     * or less, monthly buckets for anything longer (including all-time).
     */
    private function buildTrend(?Carbon $from, ?Carbon $to): array
    {
        $start = $from ?? Ticket::min('created_at');
        $end = $to ?? now();

        if (! $start) {
            return [];
        }

        $start = $start instanceof Carbon ? $start : Carbon::parse($start);
        $daily = $start->diffInDays($end) <= 92;

        // Grouped in PHP (rather than a driver-specific date-format SQL
        // function) so this works the same on SQLite and MySQL.
        $rows = Ticket::query()
            ->where('created_at', '>=', $start)
            ->where('created_at', '<=', $end)
            ->get(['created_at'])
            ->groupBy(fn ($t) => $daily ? $t->created_at->format('Y-m-d') : $t->created_at->format('Y-m'))
            ->map->count();

        $points = [];
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $key = $daily ? $cursor->format('Y-m-d') : $cursor->format('Y-m');
            $points[] = [
                'label' => $daily ? $cursor->format('M j') : $cursor->format('M Y'),
                'value' => (int) ($rows[$key] ?? 0),
                'month' => $cursor->format('Y-m'),
                'month_label' => $cursor->format('F Y'),
                'color' => self::MONTH_PALETTE[($cursor->month - 10 + 12) % 12],
            ];
            $cursor = $daily ? $cursor->addDay() : $cursor->addMonthNoOverflow();
        }

        // Cap to the most recent 60 points so a multi-year "all time" range
        // doesn't render an unreadable wall of bars.
        return array_slice($points, -60);
    }
}
