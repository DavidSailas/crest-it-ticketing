{{--
    The one email layout every notification uses. Table-based with inline styles so it looks right in
    Gmail, Outlook and phones. Content comes from App\Notifications\AppNotification::brandedMail().
--}}
@php
    $fmt = fn ($text) => preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', e((string) $text));
    $chips = [
        'open' => ['#dbeafe', '#1d4ed8'],
        'in_progress' => ['#e0e7ff', '#4338ca'],
        'pending' => ['#ede9fe', '#6d28d9'],
        'pending_backend' => ['#ccfbf1', '#0f766e'],
        'pending_vendor' => ['#ffedd5', '#c2410c'],
        'resolved' => ['#dcfce7', '#15803d'],
        'closed' => ['#f3f4f6', '#4b5563'],
        'cancelled' => ['#f3f4f6', '#6b7280'],
    ];
    $chip = $chips[$ticket['statusKey'] ?? ''] ?? ['#f3f4f6', '#4b5563'];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>{{ $heading }}</title>
</head>
<body style="margin:0;padding:0;background-color:#f3f4f6;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#1f2937;">
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;">{{ $preheader ?? $heading }}</div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f3f4f6;">
        <tr>
            <td align="center" style="padding:28px 12px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;">
                    {{-- Brand --}}
                    <tr>
                        <td style="padding:0 4px 14px 4px;font-size:15px;font-weight:700;letter-spacing:.2px;color:#1a6b3c;">
                            {{ $appName }}
                        </td>
                    </tr>

                    <tr>
                        <td style="background-color:#ffffff;border-radius:12px;border:1px solid #e5e7eb;overflow:hidden;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr><td style="height:4px;background-color:{{ $accent }};font-size:0;line-height:0;">&nbsp;</td></tr>
                                <tr>
                                    <td style="padding:28px 28px 8px 28px;">
                                        <h1 style="margin:0 0 14px 0;font-size:20px;line-height:1.3;font-weight:700;color:#111827;">{{ $heading }}</h1>

                                        @if(filled($firstName))
                                            <p style="margin:0 0 12px 0;font-size:15px;line-height:1.6;color:#374151;">Hi {{ $firstName }},</p>
                                        @endif

                                        @foreach(($paragraphs ?? []) as $paragraph)
                                            <p style="margin:0 0 12px 0;font-size:15px;line-height:1.6;color:#374151;">{!! $fmt($paragraph) !!}</p>
                                        @endforeach
                                    </td>
                                </tr>

                                {{-- Ticket card --}}
                                @if(!empty($ticket))
                                    <tr>
                                        <td style="padding:4px 28px 8px 28px;">
                                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e5e7eb;border-radius:10px;background-color:#f9fafb;">
                                                <tr>
                                                    <td style="padding:14px 16px 6px 16px;">
                                                        <span style="font-family:Consolas,Menlo,monospace;font-size:12px;font-weight:700;color:#6b7280;">{{ $ticket['number'] }}</span>
                                                        <span style="display:inline-block;margin-left:8px;padding:2px 9px;border-radius:999px;font-size:11px;font-weight:600;background-color:{{ $chip[0] }};color:{{ $chip[1] }};">{{ $ticket['status'] }}</span>
                                                        <div style="margin-top:6px;font-size:16px;font-weight:600;color:#111827;line-height:1.4;">{{ $ticket['title'] }}</div>
                                                    </td>
                                                </tr>
                                                @if(!empty($ticket['rows']))
                                                    <tr>
                                                        <td style="padding:6px 16px 14px 16px;">
                                                            <table role="presentation" cellpadding="0" cellspacing="0">
                                                                @foreach($ticket['rows'] as $label => $value)
                                                                    <tr>
                                                                        <td style="padding:2px 14px 2px 0;font-size:13px;color:#6b7280;white-space:nowrap;">{{ $label }}</td>
                                                                        <td style="padding:2px 0;font-size:13px;color:#111827;">{{ $value }}</td>
                                                                    </tr>
                                                                @endforeach
                                                            </table>
                                                        </td>
                                                    </tr>
                                                @endif
                                            </table>
                                        </td>
                                    </tr>
                                @endif

                                {{-- Details (e.g. what changed) --}}
                                @if(!empty($details))
                                    <tr>
                                        <td style="padding:8px 28px 0 28px;">
                                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-top:1px solid #f3f4f6;">
                                                @foreach($details as $label => $value)
                                                    <tr>
                                                        <td style="padding:8px 14px 8px 0;font-size:13px;color:#6b7280;vertical-align:top;white-space:nowrap;">{{ $label }}</td>
                                                        <td style="padding:8px 0;font-size:14px;color:#111827;vertical-align:top;border-bottom:1px solid #f3f4f6;">{{ $value }}</td>
                                                    </tr>
                                                @endforeach
                                            </table>
                                        </td>
                                    </tr>
                                @endif

                                {{-- Quoted message --}}
                                @if(filled($quote ?? null))
                                    <tr>
                                        <td style="padding:12px 28px 0 28px;">
                                            @if(filled($quoteBy ?? null))
                                                <div style="margin-bottom:6px;font-size:12px;font-weight:600;color:#6b7280;">{{ $quoteBy }}</div>
                                            @endif
                                            <div style="border-left:3px solid {{ $accent }};background-color:#f9fafb;padding:12px 14px;border-radius:0 8px 8px 0;font-size:14px;line-height:1.6;color:#374151;">{!! nl2br(e($quote)) !!}</div>
                                        </td>
                                    </tr>
                                @endif

                                {{-- Button --}}
                                @if(filled($ctaUrl ?? null))
                                    <tr>
                                        <td style="padding:22px 28px 8px 28px;">
                                            <a href="{{ $ctaUrl }}" style="display:inline-block;padding:12px 22px;border-radius:8px;background-color:#1a6b3c;color:#ffffff;font-size:15px;font-weight:600;text-decoration:none;">{{ $ctaLabel ?? 'Open in the system' }}</a>
                                        </td>
                                    </tr>
                                @endif

                                @if(filled($outro ?? null))
                                    <tr>
                                        <td style="padding:10px 28px 0 28px;">
                                            <p style="margin:0;font-size:13px;line-height:1.6;color:#6b7280;">{!! $fmt($outro) !!}</p>
                                        </td>
                                    </tr>
                                @endif

                                <tr><td style="height:24px;font-size:0;line-height:0;">&nbsp;</td></tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="padding:16px 8px 0 8px;font-size:12px;line-height:1.6;color:#9ca3af;text-align:center;">
                            You are receiving this because of activity on your {{ $appName }} account.<br>
                            <a href="{{ $prefsUrl }}" style="color:#6b7280;text-decoration:underline;">Choose which emails you get</a>
                            @if(filled($ctaUrl ?? null))
                                <br><span style="word-break:break-all;">Button not working? Copy this link: {{ $ctaUrl }}</span>
                            @endif
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
