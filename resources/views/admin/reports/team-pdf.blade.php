<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 32px 36px; }
        body { font-family: 'Helvetica', Arial, sans-serif; color: #1f2937; font-size: 11px; }
        .header { border-bottom: 2px solid #123f24; padding-bottom: 12px; margin-bottom: 18px; }
        .header table { width: 100%; border-collapse: collapse; margin: 0; }
        .header td { border: 0; padding: 0; background: none; }
        .logo { width: 44px; height: 44px; }
        .eyebrow { color: #6b7280; font-size: 9px; text-transform: uppercase; letter-spacing: 0.08em; margin: 0; }
        .title { color: #123f24; font-size: 19px; font-weight: bold; margin: 3px 0 0; }
        .sub { color: #6b7280; font-size: 10px; margin-top: 3px; }
        table.grid { width: 100%; border-collapse: collapse; margin-top: 8px; }
        table.grid thead th { background-color: #1a6b3c; color: #fff; text-align: left; padding: 8px 10px; font-size: 9.5px; text-transform: uppercase; letter-spacing: 0.03em; }
        table.grid tbody td { padding: 7px 10px; border-bottom: 1px solid #e5e7eb; font-size: 10.5px; }
        table.grid tbody tr:nth-child(even) { background-color: #f9fafb; }
        table.grid tfoot td { padding: 8px 10px; font-weight: bold; background-color: #e8f3ec; border-top: 2px solid #1a6b3c; }
        .num { text-align: right; }
        .note { margin-top: 12px; font-size: 9px; color: #6b7280; }
        .footer { margin-top: 22px; padding-top: 10px; border-top: 1px dashed #d1d5db; font-size: 9px; color: #9ca3af; }
    </style>
</head>
<body>
    <div class="header">
        <table>
            <tr>
                <td>
                    <p class="eyebrow">Crest Forwarder Inc. &middot; IT Service Desk</p>
                    <p class="title">{{ $table['title'] }}</p>
                    <p class="sub">{{ $rangeLabel }} &middot; Generated {{ $generatedAt->format('F j, Y g:i A') }} &middot; Prepared by {{ $preparedBy }}</p>
                </td>
                @if($logoData)
                    <td style="text-align:right; width:56px;"><img src="{{ $logoData }}" class="logo" alt=""></td>
                @endif
            </tr>
        </table>
    </div>

    @if(count($table['rows']) === 0)
        <p style="color:#9ca3af;">No records for this report.</p>
    @else
        <table class="grid">
            <thead>
                <tr>
                    @foreach($table['headers'] as $i => $h)
                        <th class="{{ in_array($i, $table['numeric']) ? 'num' : '' }}">{{ $h }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($table['rows'] as $row)
                    <tr>
                        @foreach($row as $i => $cell)
                            <td class="{{ in_array($i, $table['numeric']) ? 'num' : '' }}">{{ $cell }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
            @if(! empty($table['totalCols']))
            <tfoot>
                <tr>
                    @foreach($table['headers'] as $i => $h)
                        <td class="{{ in_array($i, $table['numeric']) ? 'num' : '' }}">
                            @if($i === 0) Total
                            @elseif(in_array($i, $table['totalCols'])) {{ array_sum(array_column($table['rows'], $i)) }}
                            @endif
                        </td>
                    @endforeach
                </tr>
            </tfoot>
            @endif
        </table>
        <p class="note">{{ $table['note'] }}</p>
    @endif

    <div class="footer">Crest Forwarder Inc. &mdash; IT Service Desk &middot; Internal use only</div>
</body>
</html>
