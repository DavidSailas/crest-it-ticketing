<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 32px 36px; }
        body { font-family: 'Helvetica', Arial, sans-serif; color: #1f2937; font-size: 11px; }

        .header { border-bottom: 2px solid #123f24; padding-bottom: 12px; margin-bottom: 18px; display: flex; }
        .header-title { color: #123f24; font-size: 18px; font-weight: bold; margin: 0; }
        .header-sub { color: #6b7280; font-size: 10px; margin-top: 3px; }

        table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        thead th {
            background-color: #1a6b3c;
            color: #ffffff;
            text-align: left;
            padding: 8px 10px;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }
        tbody td {
            padding: 7px 10px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 10.5px;
        }
        tbody tr:nth-child(even) { background-color: #f9fafb; }

        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 9px;
            font-weight: bold;
        }
        .badge-vip { background-color: #fef3c7; color: #92400e; }
        .badge-standard { background-color: #f3f4f6; color: #6b7280; }

        .footer { margin-top: 24px; padding-top: 10px; border-top: 1px dashed #d1d5db; font-size: 9px; color: #9ca3af; display: flex; justify-content: space-between; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <p class="header-title">Crest IT Service Desk</p>
            <p class="header-sub">User Directory Report — {{ $tab === 'all' ? 'All Users' : ucfirst(str_replace('_', ' ', $tab)) }}</p>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Username</th>
                <th>Email</th>
                <th>Department</th>
                <th>Position</th>
                <th>Role</th>
                <th>Branch</th>
                <th>VIP</th>
            </tr>
        </thead>
        <tbody>
            @forelse($users as $user)
                <tr>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->username ?? '—' }}</td>
                    <td>{{ $user->email }}</td>
                    <td>{{ $user->department->name ?? '—' }}</td>
                    <td>{{ $user->position->name ?? '—' }}</td>
                    <td style="text-transform: capitalize;">{{ str_replace('_', ' ', $user->role) }}</td>
                    <td>{{ $user->branch_name ?? '—' }}</td>
                    <td>
                        <span class="badge {{ $user->is_vip ? 'badge-vip' : 'badge-standard' }}">
                            {{ $user->is_vip ? 'VIP' : 'Standard' }}
                        </span>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" style="text-align:center; color:#9ca3af; padding: 18px;">No users found.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <span>Generated {{ $generatedAt->format('F j, Y — g:i A') }}</span>
        <span>{{ $users->count() }} user{{ $users->count() === 1 ? '' : 's' }} total</span>
    </div>
</body>
</html>
