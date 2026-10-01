<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 30px 36px 50px; }
        body { font-family: 'Helvetica', Arial, sans-serif; color: #1f2937; font-size: 10px; line-height: 1.35; }
        table { border-collapse: collapse; }

        /* ---------- Footer ---------- */
        .footer { position: fixed; left: 0; right: 0; bottom: -36px; border-top: 1px solid #e5e7eb; padding-top: 6px; font-size: 8px; color: #9ca3af; }
        .footer table { width: 100%; }
        .pagenum:before { content: counter(page); }

        /* ---------- Cover band ---------- */
        table.cover { width: 100%; background-color: #123f24; margin-bottom: 14px; }
        table.cover td { padding: 18px 22px; vertical-align: middle; }
        .cover-eyebrow { color: #bfe6cc; font-size: 8.5px; text-transform: uppercase; letter-spacing: 0.08em; font-weight: bold; margin: 0; }
        .cover-title { color: #ffffff; font-size: 22px; font-weight: bold; margin: 5px 0 0; }
        .cover-meta { color: #d7f0dd; font-size: 9.5px; margin-top: 6px; }
        .cover-logo-cell { width: 64px; text-align: right; }
        .cover-logo-badge { display: inline-block; width: 50px; height: 50px; background-color: #ffffff; padding: 3px; }
        .cover-logo { width: 44px; height: 44px; }

        /* ---------- Executive summary ---------- */
        table.summary { width: 100%; margin-bottom: 14px; }
        table.summary td { background-color: #f4f8f5; border-left: 4px solid #1a6b3c; padding: 10px 14px; }
        .summary-label { font-size: 8px; text-transform: uppercase; letter-spacing: 0.07em; color: #1a6b3c; font-weight: bold; margin: 0 0 4px; }
        .summary-text { font-size: 10.5px; line-height: 1.5; color: #374151; margin: 0; }

        /* ---------- KPI cards ---------- */
        table.cards { width: 100%; margin-bottom: 16px; }
        table.cards td.card { width: 23.5%; border: 1px solid #e5e7eb; border-top: 3px solid #1a6b3c; padding: 9px 12px; background-color: #ffffff; vertical-align: top; }
        table.cards td.gap { width: 2%; }
        table.cards td.warn { background-color: #fffbeb; border-color: #fde68a; border-top-color: #d97706; }
        .card-label { font-size: 7.5px; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280; font-weight: bold; }
        .card-value { font-size: 21px; font-weight: bold; color: #123f24; margin-top: 3px; }
        .warn .card-label, .warn .card-value { color: #b45309; }
        .card-delta { font-size: 8px; font-weight: bold; margin-top: 3px; }
        .good { color: #15803d; }
        .bad { color: #dc2626; }
        .flat { color: #9ca3af; font-weight: normal; }

        /* ---------- Section headers ---------- */
        .section-title { font-size: 12.5px; font-weight: bold; color: #123f24; margin: 6px 0 4px; padding-left: 8px; border-left: 4px solid #1a6b3c; }
        .section-desc { font-size: 8.5px; color: #6b7280; margin: 0 0 8px 12px; }

        /* ---------- Panels (chart boxes) ---------- */
        table.panel { width: 100%; margin-bottom: 14px; }
        table.panel td { border: 1px solid #e5e7eb; padding: 10px 12px; background-color: #ffffff; vertical-align: top; }
        table.panels { width: 100%; margin-bottom: 14px; }
        table.panels td.p { width: 32%; border: 1px solid #e5e7eb; padding: 10px 12px; vertical-align: top; background-color: #ffffff; }
        table.panels td.gap { width: 2%; }
        .chart-title { font-size: 8.5px; font-weight: bold; color: #374151; margin-bottom: 8px; text-align: center; text-transform: uppercase; letter-spacing: 0.04em; }

        table.legend { width: 100%; margin-top: 8px; }
        table.legend td { padding: 2px 0; font-size: 8.5px; border: 0; }
        .swatch { display: inline-block; width: 7px; height: 7px; margin-right: 4px; }
        .legend-val { text-align: right; color: #6b7280; }

        .month-key { font-size: 8.5px; color: #4b5563; margin-top: 8px; }
        .month-key span.item { display: inline-block; margin-right: 14px; }

        /* ---------- Data tables ---------- */
        table.grid { width: 100%; margin-bottom: 6px; }
        table.grid thead th { background-color: #1a6b3c; color: #ffffff; text-align: left; padding: 7px 8px; font-size: 8px; text-transform: uppercase; letter-spacing: 0.03em; }
        table.grid thead th.num { text-align: right; }
        table.grid tbody td { padding: 6px 8px; border-bottom: 1px solid #e5e7eb; font-size: 9.5px; vertical-align: middle; }
        table.grid tbody tr.alt td { background-color: #f9fafb; }
        table.grid tbody tr.zero td { color: #9ca3af; }
        table.grid tfoot td { padding: 7px 8px; font-weight: bold; font-size: 9.5px; background-color: #e8f3ec; border-top: 2px solid #1a6b3c; }
        .num { text-align: right; }
        .strong { font-weight: bold; color: #123f24; }
        .bar-track { background-color: #eef2ef; height: 6px; width: 100%; }
        .bar-fill { background-color: #1a6b3c; height: 6px; }
        .table-note { font-size: 8px; color: #6b7280; margin: 0 0 16px 2px; }

        .page-break { page-break-before: always; }
        tr { page-break-inside: avoid; }
    </style>
</head>
<body>
@php
    // ---------- helpers (closures, so this view can safely render more than once) ----------
    $deltaCell = function ($delta, $goodIsUp = true) {
        if ($delta === null) return '<span class="flat">No prior-period data</span>';
        if ($delta == 0) return '<span class="flat">No change vs. previous period</span>';
        $up = $delta > 0;
        $good = $goodIsUp ? $up : ! $up;
        return '<span class="'.($good ? 'good' : 'bad').'">'.($up ? '&#9650; +' : '&#9660; ').$delta.'% vs. previous period</span>';
    };

    $piePaths = function (array $data, float $r = 40): string {
        $total = array_sum(array_column($data, 'value'));
        if ($total <= 0) return '';
        $cx = $r; $cy = $r; $angle = -90; $out = '';
        foreach ($data as $slice) {
            $frac = $slice['value'] / $total;
            $sweep = $frac * 360;
            $x1 = $cx + $r * cos(deg2rad($angle));
            $y1 = $cy + $r * sin(deg2rad($angle));
            $end = $angle + $sweep;
            $x2 = $cx + $r * cos(deg2rad($end));
            $y2 = $cy + $r * sin(deg2rad($end));
            $large = $sweep > 180 ? 1 : 0;
            $out .= $frac >= 0.999
                ? "<circle cx=\"{$cx}\" cy=\"{$cy}\" r=\"{$r}\" fill=\"{$slice['color']}\" />"
                : "<path d=\"M{$cx},{$cy} L{$x1},{$y1} A{$r},{$r} 0 {$large} 1 {$x2},{$y2} Z\" fill=\"{$slice['color']}\" />";
            $angle = $end;
        }
        return $out;
    };

    $pieBlock = function (string $title, array $data, string $emptyText = 'No data') use ($piePaths) {
        $html = '<div class="chart-title">'.e($title).'</div>';
        if (count($data) > 0) {
            $html .= '<svg width="84" height="84" viewBox="0 0 80 80" style="display:block;margin:0 auto;">'.$piePaths($data, 40).'</svg>';
            $html .= '<table class="legend">';
            foreach ($data as $slice) {
                $html .= '<tr><td><span class="swatch" style="background-color:'.$slice['color'].';"></span>'.e($slice['label']).'</td><td class="legend-val">'.$slice['value'].' ('.$slice['percent'].'%)</td></tr>';
            }
            $html .= '</table>';
        } else {
            $html .= '<p style="text-align:center;color:#9ca3af;font-size:9px;margin-top:24px;">'.e($emptyText).'</p>';
        }
        return $html;
    };
@endphp

    <div class="footer">
        <table>
            <tr>
                <td>Crest Forwarder Inc. &mdash; IT Service Desk &middot; Confidential, internal use only</td>
                <td style="text-align:right;">{{ $rangeLabel }} &middot; Page <span class="pagenum"></span></td>
            </tr>
        </table>
    </div>

    {{-- ============ Cover band ============ --}}
    <table class="cover">
        <tr>
            <td>
                <p class="cover-eyebrow">Crest Forwarder Inc. &middot; IT Service Desk</p>
                <p class="cover-title">Management Report</p>
                <p class="cover-meta">{{ $rangeLabel }} &middot; Generated {{ $generatedAt->format('F j, Y g:i A') }} &middot; Prepared by {{ $preparedBy }}</p>
            </td>
            @if($logoData)
                <td class="cover-logo-cell"><span class="cover-logo-badge"><img src="{{ $logoData }}" class="cover-logo" /></span></td>
            @endif
        </tr>
    </table>

    {{-- ============ Executive summary ============ --}}
    <table class="summary">
        <tr><td>
            <p class="summary-label">Executive summary</p>
            <p class="summary-text">{{ $summary }}</p>
        </td></tr>
    </table>

    {{-- ============ KPI cards ============ --}}
    <table class="cards">
        <tr>
            <td class="card">
                <div class="card-label">Tickets created</div>
                <div class="card-value">{{ number_format($totalTickets) }}</div>
                <div class="card-delta">{!! $comparison ? $deltaCell($comparison['ticketsDelta'], false) : '<span class="flat">'.e($rangeLabel).'</span>' !!}</div>
            </td>
            <td class="gap"></td>
            <td class="card">
                <div class="card-label">Resolution rate</div>
                <div class="card-value">{{ $resolutionRate !== null ? $resolutionRate.'%' : '—' }}</div>
                <div class="card-delta">{!! $comparison ? $deltaCell($comparison['rateDelta'], true) : '<span class="flat">Resolved or closed</span>' !!}</div>
            </td>
            <td class="gap"></td>
            <td class="card">
                <div class="card-label">Avg. resolution time</div>
                <div class="card-value">
                    @if($avgResolutionHours === null) — @elseif($avgResolutionHours < 24) {{ round($avgResolutionHours, 1) }}h @else {{ round($avgResolutionHours / 24, 1) }}d @endif
                </div>
                <div class="card-delta"><span class="flat">Created &rarr; resolved</span></div>
            </td>
            <td class="gap"></td>
            <td class="card {{ $unassignedInRange > 0 ? 'warn' : '' }}">
                <div class="card-label">Unassigned</div>
                <div class="card-value">{{ number_format($unassignedInRange) }}</div>
                <div class="card-delta"><span class="{{ $unassignedInRange > 0 ? 'bad' : 'flat' }}">{{ $unassignedInRange > 0 ? 'Needs an IT engineer' : 'All tickets assigned' }}</span></div>
            </td>
        </tr>
    </table>

    {{-- ============ Ticket volume over time ============ --}}
    @if(count($trend) > 1)
        @php
            $n = count($trend);
            $H = 92;                                  // tallest bar, in px
            $cellPad = $n > 45 ? 0.5 : 1;             // gap between bars
            $maxV = max(1, max(array_column($trend, 'value')));
            $months = [];
            foreach ($trend as $pt) {
                $months[$pt['month']] ??= ['label' => $pt['month_label'], 'color' => $pt['color'], 'total' => 0];
                $months[$pt['month']]['total'] += $pt['value'];
            }
            $unit = str_contains($trend[0]['label'], ' 20') ? 'month' : 'day';
        @endphp
        <p class="section-title">Ticket volume over time</p>
        <p class="section-desc">Tickets created per {{ $unit }} &middot; busiest bar: {{ $maxV }} &middot; each month has its own colour</p>
        <table class="panel">
            <tr><td>
                {{-- Bars are table cells (not SVG) so they always stretch to the full panel width --}}
                <table style="width:100%; table-layout:fixed; border-bottom:1px solid #9ca3af;">
                    <tr>
                        @foreach($trend as $pt)
                            @php $barH = $pt['value'] > 0 ? max(3, round(($pt['value'] / $maxV) * $H)) : 2; @endphp
                            <td style="width:{{ round(100 / $n, 3) }}%; height:{{ $H }}px; padding:0 {{ $cellPad }}px; border:0; vertical-align:bottom;"><div style="height:{{ $barH }}px; background-color:{{ $pt['value'] > 0 ? $pt['color'] : '#e5e7eb' }};"></div></td>
                        @endforeach
                    </tr>
                </table>
                <table style="width:100%; margin-top:3px;"><tr>
                    <td style="border:0; padding:0; font-size:8px; color:#9ca3af;">{{ $trend[0]['label'] }}</td>
                    <td style="border:0; padding:0; font-size:8px; color:#9ca3af; text-align:right;">{{ end($trend)['label'] }}</td>
                </tr></table>
                <div class="month-key">
                    @foreach($months as $m)
                        <span class="item"><span class="swatch" style="background-color: {{ $m['color'] }};"></span>{{ $m['label'] }} &middot; <b>{{ number_format($m['total']) }}</b></span>
                    @endforeach
                </div>
            </td></tr>
        </table>
    @endif

    {{-- ============ Ticket breakdown pies ============ --}}
    <p class="section-title">Tickets &mdash; {{ $rangeLabel }}</p>
    <p class="section-desc">{{ number_format($totalTickets) }} tickets created in this period</p>
    <table class="panels">
        <tr>
            <td class="p">{!! $pieBlock('By status', $byStatus) !!}</td>
            <td class="gap"></td>
            <td class="p">{!! $pieBlock('By priority', $byPriority) !!}</td>
            <td class="gap"></td>
            <td class="p">{!! $pieBlock('By category', $byCategory) !!}</td>
        </tr>
    </table>

    <div class="page-break"></div>

    {{-- ============ Tickets by category ============ --}}
    @if($totalTickets > 0)
        <p class="section-title" style="margin-top:0;">Tickets by category</p>
        <p class="section-desc">Every category on the ticket form, busiest first</p>
        @php $maxC = max(1, collect($categoryBreakdown)->max('value')); @endphp
        <table class="grid">
            <thead><tr><th style="width:38%;">Category</th><th class="num" style="width:12%;">Tickets</th><th class="num" style="width:12%;">Share</th><th style="width:38%;">&nbsp;</th></tr></thead>
            <tbody>
                @foreach($categoryBreakdown as $i => $row)
                    <tr class="{{ $i % 2 ? 'alt' : '' }} {{ $row['value'] === 0 ? 'zero' : '' }}">
                        <td>{{ $row['label'] }}</td>
                        <td class="num strong">{{ $row['value'] }}</td>
                        <td class="num">{{ $row['percent'] }}%</td>
                        <td><div class="bar-track"><div class="bar-fill" style="width: {{ round(($row['value'] / $maxC) * 100) }}%;"></div></div></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <p class="table-note">&nbsp;</p>
    @endif

    {{-- ============ IT Engineer report ============ --}}
    @php
        $eng = $engineerReport;
        $engRows = $eng['rows'];
        $engSum = fn ($col) => array_sum(array_column($engRows, $col));
        $engMax = max(1, count($engRows) ? max(array_column($engRows, 2)) : 1);
    @endphp
    <p class="section-title">IT Engineer report</p>
    <p class="section-desc">How many tickets each IT engineer has in {{ $rangeLabel }}, and where those tickets stand now</p>
    @if(count($engRows) === 0)
        <p class="table-note">No IT engineer accounts yet.</p>
    @else
        <table class="grid">
            <thead>
                <tr>
                    <th style="width:22%;">IT Engineer</th>
                    <th class="num" style="width:9%;">Tickets</th>
                    <th style="width:17%;">Share of workload</th>
                    <th class="num" style="width:7%;">Open</th>
                    <th class="num" style="width:9%;">In progress</th>
                    <th class="num" style="width:8%;">Pending</th>
                    <th class="num" style="width:8%;">Resolved</th>
                    <th class="num" style="width:7%;">Closed</th>
                    <th class="num" style="width:13%;">Avg. resolution</th>
                </tr>
            </thead>
            <tbody>
                @foreach($engRows as $i => $r)
                    <tr class="{{ $i % 2 ? 'alt' : '' }} {{ $r[2] === 0 ? 'zero' : '' }}">
                        <td>{{ $r[0] }}</td>
                        <td class="num strong">{{ $r[2] }}</td>
                        <td><div class="bar-track"><div class="bar-fill" style="width: {{ round(($r[2] / $engMax) * 100) }}%;"></div></div></td>
                        <td class="num">{{ $r[3] }}</td>
                        <td class="num">{{ $r[4] }}</td>
                        <td class="num">{{ $r[5] }}</td>
                        <td class="num">{{ $r[6] }}</td>
                        <td class="num">{{ $r[7] }}</td>
                        <td class="num">{{ $r[8] }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td>Total ({{ count($engRows) }} IT engineers)</td>
                    <td class="num">{{ $engSum(2) }}</td>
                    <td>&nbsp;</td>
                    <td class="num">{{ $engSum(3) }}</td>
                    <td class="num">{{ $engSum(4) }}</td>
                    <td class="num">{{ $engSum(5) }}</td>
                    <td class="num">{{ $engSum(6) }}</td>
                    <td class="num">{{ $engSum(7) }}</td>
                    <td>&nbsp;</td>
                </tr>
            </tfoot>
        </table>
        <p class="table-note">
            {{ $eng['note'] }}.
            @if($unassignedInRange > 0) <b style="color:#b45309;">{{ $unassignedInRange }} {{ $unassignedInRange === 1 ? 'ticket is' : 'tickets are' }} not assigned to anyone yet.</b> @endif
        </p>
    @endif

    {{-- ============ Staff report ============ --}}
    @php
        $staffAll = $staffReport['rows'];
        $staffActive = array_values(array_filter($staffAll, fn ($r) => $r[3] > 0));
        $staffShown = array_slice($staffActive, 0, 20);
    @endphp
    <p class="section-title">Staff report</p>
    <p class="section-desc">Who is raising tickets in {{ $rangeLabel }} &middot; {{ count($staffActive) }} of {{ count($staffAll) }} staff submitted at least one</p>
    @if(count($staffShown) === 0)
        <p class="table-note">No staff member submitted a ticket in this period.</p>
    @else
        <table class="grid">
            <thead>
                <tr>
                    <th style="width:24%;">Staff</th>
                    <th style="width:20%;">Department</th>
                    <th style="width:16%;">Branch</th>
                    <th class="num" style="width:10%;">Tickets</th>
                    <th class="num" style="width:9%;">Open</th>
                    <th class="num" style="width:10%;">Resolved</th>
                    <th class="num" style="width:11%;">Last ticket</th>
                </tr>
            </thead>
            <tbody>
                @foreach($staffShown as $i => $r)
                    <tr class="{{ $i % 2 ? 'alt' : '' }}">
                        <td>{{ $r[0] }}</td>
                        <td>{{ $r[1] }}</td>
                        <td>{{ $r[2] }}</td>
                        <td class="num strong">{{ $r[3] }}</td>
                        <td class="num">{{ $r[4] }}</td>
                        <td class="num">{{ $r[5] }}</td>
                        <td class="num">{{ $r[6] }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3">Total ({{ count($staffShown) }} shown)</td>
                    <td class="num">{{ array_sum(array_column($staffShown, 3)) }}</td>
                    <td class="num">{{ array_sum(array_column($staffShown, 4)) }}</td>
                    <td class="num">{{ array_sum(array_column($staffShown, 5)) }}</td>
                    <td>&nbsp;</td>
                </tr>
            </tfoot>
        </table>
        <p class="table-note">
            {{ $staffReport['note'] }}.
            @if(count($staffActive) > count($staffShown)) Showing the top {{ count($staffShown) }} of {{ count($staffActive) }} &mdash; use Export &rarr; Excel on the Staff report for the complete list. @endif
        </p>
    @endif

    <div class="page-break"></div>

    {{-- ============ Assets & accounts snapshot ============ --}}
    <p class="section-title" style="margin-top:0;">Assets &amp; Accounts &mdash; Current Snapshot</p>
    <p class="section-desc">{{ number_format($totalAssets) }} assets in the inventory today &middot; not affected by the reporting period</p>
    @php
        $assignPie = [
            ['label' => 'Assigned', 'value' => $assignedAssets, 'color' => '#1a6b3c', 'percent' => $totalAssets > 0 ? round($assignedAssets / $totalAssets * 100, 1) : 0],
            ['label' => 'Unassigned', 'value' => $unassignedAssets, 'color' => '#f59e0b', 'percent' => $totalAssets > 0 ? round($unassignedAssets / $totalAssets * 100, 1) : 0],
        ];
    @endphp
    <table class="panels">
        <tr>
            <td class="p">{!! $pieBlock('Assets by type', $assetsByType, 'No assets yet') !!}</td>
            <td class="gap"></td>
            <td class="p">{!! $pieBlock('Assignment', $totalAssets > 0 ? $assignPie : [], 'No assets yet') !!}</td>
            <td class="gap"></td>
            <td class="p">{!! $pieBlock('Asset condition', $assetsByStatus, 'No assets yet') !!}</td>
        </tr>
    </table>

    <p class="section-title">Accounts by role</p>
    <p class="section-desc">{{ number_format(array_sum(array_column($usersByRole, 'value'))) }} accounts in the system</p>
    <table class="panels">
        <tr>
            <td class="p">{!! $pieBlock('Accounts by role', $usersByRole, 'No users yet') !!}</td>
            <td class="gap"></td>
            <td style="width:66%; vertical-align:top;">
                <table class="grid">
                    <thead><tr><th>Role</th><th class="num">Accounts</th><th class="num">Share</th></tr></thead>
                    <tbody>
                        @foreach($usersByRole as $i => $row)
                            <tr class="{{ $i % 2 ? 'alt' : '' }}">
                                <td>{{ $row['label'] }}</td>
                                <td class="num strong">{{ $row['value'] }}</td>
                                <td class="num">{{ $row['percent'] }}%</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
