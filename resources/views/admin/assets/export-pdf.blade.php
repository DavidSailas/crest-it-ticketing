<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 32px 36px; }
        body { font-family: 'Helvetica', Arial, sans-serif; color: #1f2937; font-size: 10.5px; }

        .header { border-bottom: 2px solid #123f24; padding-bottom: 12px; margin-bottom: 6px; }
        .header-title { color: #123f24; font-size: 18px; font-weight: bold; margin: 0; }
        .header-sub { color: #6b7280; font-size: 9.5px; margin-top: 3px; }

        table { width: 100%; border-collapse: collapse; margin-top: 14px; }
        thead th {
            background-color: #1a6b3c;
            color: #ffffff;
            text-align: left;
            padding: 7px 8px;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }
        tbody td {
            padding: 6px 8px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 9.5px;
            vertical-align: top;
        }
        tbody tr:nth-child(even) { background-color: #f9fafb; }

        .mono { font-family: 'Courier New', monospace; font-weight: bold; color: #374151; }

        .badge {
            display: inline-block;
            padding: 2px 7px;
            border-radius: 10px;
            font-size: 8.5px;
            font-weight: bold;
            white-space: nowrap;
        }
        .status-active { background-color: #d1fae5; color: #047857; }
        .status-in_repair { background-color: #fef3c7; color: #92400e; }
        .status-retired { background-color: #e5e7eb; color: #4b5563; }

        .footer { margin-top: 20px; padding-top: 10px; border-top: 1px dashed #d1d5db; font-size: 9px; color: #9ca3af; display: flex; justify-content: space-between; }
    </style>
</head>
<body>
    <div class="header">
        <p class="header-title">Crest IT Service Desk</p>
        <p class="header-sub">Asset Inventory Report — {{ $filterSummary }}</p>
    </div>

    <table>
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
                    <td>{{ $asset->user->name ?? '—' }}</td>
                    <td>{{ $asset->serial_number ?? '—' }}</td>
                    <td><span class="badge status-{{ $asset->status }}">{{ \App\Models\Asset::STATUSES[$asset->status] ?? ucfirst($asset->status) }}</span></td>
                    <td>{{ $asset->assigned_date?->format('M j, Y') ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="10" style="text-align:center; color:#9ca3af; padding: 18px;">No assets found for this filter.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <span>Generated {{ $generatedAt->format('F j, Y — g:i A') }}</span>
        <span>{{ $assets->count() }} asset{{ $assets->count() === 1 ? '' : 's' }} total</span>
    </div>
</body>
</html>
