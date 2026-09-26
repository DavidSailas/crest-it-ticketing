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
        .status-open { background-color: #fef3c7; color: #92400e; }
        .status-in_progress { background-color: #dbeafe; color: #1d4ed8; }
        .status-pending { background-color: #ede9fe; color: #6d28d9; }
        .status-resolved { background-color: #d1fae5; color: #047857; }
        .status-closed { background-color: #e5e7eb; color: #4b5563; }

        .priority-low { background-color: #f3f4f6; color: #4b5563; }
        .priority-medium { background-color: #fef3c7; color: #92400e; }
        .priority-high { background-color: #ffedd5; color: #c2410c; }
        .priority-critical { background-color: #fee2e2; color: #b91c1c; }

        .footer { margin-top: 20px; padding-top: 10px; border-top: 1px dashed #d1d5db; font-size: 9px; color: #9ca3af; display: flex; justify-content: space-between; }
    </style>
</head>
<body>
    <div class="header">
        <p class="header-title">Crest IT Service Desk</p>
        <p class="header-sub">Ticket Report — {{ $filterSummary }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Ticket #</th>
                <th>Category</th>
                <th>Requester</th>
                <th>Assigned To</th>
                <th>Priority</th>
                <th>Status</th>
                <th>Created</th>
                <th>Resolved</th>
                <th>Closed</th>
            </tr>
        </thead>
        <tbody>
            @forelse($tickets as $ticket)
                <tr>
                    <td class="mono">{{ $ticket->ticket_number }}</td>
                    <td>
                        {{ $ticket->category }}
                        @if($ticket->subcategory)
                            <br><span style="color:#9ca3af; font-size:8.5px;">{{ $ticket->subcategory }}</span>
                        @endif
                    </td>
                    <td>{{ $ticket->creator->name ?? '—' }}</td>
                    <td>{{ $ticket->assignee->name ?? '—' }}</td>
                    <td><span class="badge priority-{{ $ticket->priority }}">{{ ucfirst($ticket->priority) }}</span></td>
                    <td><span class="badge status-{{ $ticket->status }}">{{ str_replace('_', ' ', ucfirst($ticket->status)) }}</span></td>
                    <td>{{ $ticket->created_at->format('M j, Y g:i A') }}</td>
                    <td>{{ $ticket->resolved_at?->format('M j, Y g:i A') ?? '—' }}</td>
                    <td>{{ $ticket->closed_at?->format('M j, Y g:i A') ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="9" style="text-align:center; color:#9ca3af; padding: 18px;">No tickets found for this filter.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <span>Generated {{ $generatedAt->format('F j, Y — g:i A') }}</span>
        <span>{{ $tickets->count() }} ticket{{ $tickets->count() === 1 ? '' : 's' }} total</span>
    </div>
</body>
</html>
