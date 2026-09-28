<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 32px 36px; }
        body { font-family: 'Helvetica', Arial, sans-serif; color: #1f2937; font-size: 10.5px; }

        .header { border-bottom: 2px solid #123f24; padding-bottom: 12px; margin-bottom: 14px; }
        .header-title { color: #123f24; font-size: 18px; font-weight: bold; margin: 0; }
        .header-sub { color: #6b7280; font-size: 9.5px; margin-top: 3px; }

        /* Summary cards (table-based: dompdf has no flex/grid) */
        table.cards { width: 100%; border-collapse: separate; border-spacing: 8px 0; margin: 0 -8px; }
        table.cards td { width: 25%; border: 1px solid #e5e7eb; border-radius: 6px; padding: 9px 12px; background-color: #ffffff; vertical-align: top; }
        table.cards td.warn { background-color: #fffbeb; border-color: #fde68a; }
        .card-label { font-size: 8.5px; text-transform: uppercase; letter-spacing: 0.04em; color: #6b7280; font-weight: bold; }
        .card-value { font-size: 22px; font-weight: bold; color: #123f24; margin-top: 3px; }
        .warn .card-label, .warn .card-value { color: #b45309; }
        .card-note { font-size: 8.5px; color: #9ca3af; margin-top: 2px; }

        .section-title { font-size: 11px; font-weight: bold; color: #123f24; margin: 16px 0 6px; }

        table.grid { width: 100%; border-collapse: collapse; }
        table.grid thead th {
            background-color: #1a6b3c; color: #ffffff; text-align: left;
            padding: 7px 8px; font-size: 9px; text-transform: uppercase; letter-spacing: 0.03em;
        }
        table.grid tbody td { padding: 6px 8px; border-bottom: 1px solid #e5e7eb; font-size: 9.5px; vertical-align: top; }
        table.grid tbody tr:nth-child(even) { background-color: #f9fafb; }
        table.grid tfoot td { padding: 7px 8px; font-weight: bold; background-color: #e8f3ec; border-top: 1px solid #1a6b3c; font-size: 9.5px; }
        .num { text-align: right; }
        .muted { color: #9ca3af; }
        .amber { color: #b45309; font-weight: bold; }

        .mono { font-family: 'Courier New', monospace; font-weight: bold; color: #374151; }

        .badge { display: inline-block; padding: 2px 7px; border-radius: 10px; font-size: 8.5px; font-weight: bold; white-space: nowrap; }
        .status-active { background-color: #d1fae5; color: #047857; }
        .status-in_repair { background-color: #fef3c7; color: #92400e; }
        .status-retired { background-color: #e5e7eb; color: #4b5563; }
        .unassigned { background-color: #fef3c7; color: #92400e; }

        .footer { width: 100%; margin-top: 20px; padding-top: 10px; border-top: 1px dashed #d1d5db; font-size: 9px; color: #9ca3af; }
        .page-break { page-break-before: always; }
    </style>
</head>
<body>
    <div class="header">
        <p class="header-title">Crest IT Service Desk</p>
        <p class="header-sub">Asset Inventory Report — {{ $filterSummary }}</p>
    </div>

    {{-- Overview cards --}}
    <table class="cards">
        <tr>
            <td>
                <div class="card-label">Total assets</div>
                <div class="card-value">{{ number_format($summary['total']) }}</div>
                <div class="card-note">In this report</div>
            </td>
            <td>
                <div class="card-label">Assigned</div>
                <div class="card-value">{{ number_format($summary['assigned']) }}</div>
                <div class="card-note">With a user</div>
            </td>
            <td class="{{ $summary['unassigned'] > 0 ? 'warn' : '' }}">
                <div class="card-label">Unassigned</div>
                <div class="card-value">{{ number_format($summary['unassigned']) }}</div>
                <div class="card-note">Available in stock</div>
            </td>
            <td>
                <div class="card-label">In repair</div>
                <div class="card-value">{{ number_format($summary['in_repair']) }}</div>
                <div class="card-note">{{ $summary['retired'] }} retired</div>
            </td>
        </tr>
    </table>

    {{-- Breakdown by device type --}}
    <p class="section-title">Devices by type</p>
    <table class="grid">
        <thead>
            <tr>
                <th>Device type</th>
                <th class="num">Total</th>
                <th class="num">Assigned</th>
                <th class="num">Unassigned</th>
                <th class="num">In repair</th>
            </tr>
        </thead>
        <tbody>
            @foreach($summary['byType'] as $row)
                <tr class="{{ $row['total'] === 0 ? 'muted' : '' }}">
                    <td>{{ $row['label'] }}</td>
                    <td class="num">{{ $row['total'] }}</td>
                    <td class="num">{{ $row['assigned'] }}</td>
                    <td class="num {{ $row['unassigned'] > 0 ? 'amber' : '' }}">{{ $row['unassigned'] }}</td>
                    <td class="num">{{ $row['in_repair'] }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td>Total</td>
                <td class="num">{{ $summary['total'] }}</td>
                <td class="num">{{ $summary['assigned'] }}</td>
                <td class="num">{{ $summary['unassigned'] }}</td>
                <td class="num">{{ $summary['in_repair'] }}</td>
            </tr>
        </tfoot>
    </table>

    {{-- Full asset list --}}
    <div class="page-break"></div>
    <p class="section-title" style="margin-top:0;">Asset list</p>
    <table class="grid">
        <thead>
            <tr>
                <th>Asset Tag</th>
                <th>Device Name</th>
                <th>Type</th>
                <th>Company</th>
                <th>Location</th>
                <th>Department</th>
                <th>Assigned To</th>
                <th>Serial Number</th>
                <th>Status</th>
                <th>Assigned Since</th>
            </tr>
        </thead>
        <tbody>
            @forelse($assets as $asset)
                <tr>
                    <td class="mono">{{ $asset->asset_tag }}</td>
                    <td>{{ $asset->device_name }}</td>
                    <td>{{ \App\Models\Asset::TYPES[$asset->type] ?? $asset->type }}</td>
                    <td>{{ \App\Models\Asset::COMPANIES[$asset->company] ?? $asset->company }}</td>
                    <td>{{ \App\Models\Asset::locations()[$asset->location] ?? $asset->location }}</td>
                    <td>{{ $asset->department->name ?? '—' }}</td>
                    <td>
                        @if($asset->user)
                            {{ $asset->user->name }}
                        @else
                            <span class="badge unassigned">Unassigned</span>
                        @endif
                    </td>
                    <td>{{ $asset->serial_number ?? '—' }}</td>
                    <td><span class="badge status-{{ $asset->status }}">{{ \App\Models\Asset::STATUSES[$asset->status] ?? ucfirst($asset->status) }}</span></td>
                    <td>{{ $asset->assigned_date?->format('M j, Y') ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="10" style="text-align:center; color:#9ca3af; padding: 18px;">No assets found for this filter.</td></tr>
            @endforelse
        </tbody>
    </table>

    <table class="footer">
        <tr>
            <td>Generated {{ $generatedAt->format('F j, Y — g:i A') }}</td>
            <td style="text-align:right;">{{ $assets->count() }} asset{{ $assets->count() === 1 ? '' : 's' }} total</td>
        </tr>
    </table>
</body>
</html>
