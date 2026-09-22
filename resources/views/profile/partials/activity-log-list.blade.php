@if ($activityLogs->isEmpty())
    <p class="text-sm text-gray-400">No activity recorded yet.</p>
@else
    <ul class="divide-y divide-gray-100">
        @foreach ($activityLogs as $log)
            <li class="flex items-start gap-3 py-3 first:pt-0 last:pb-0">
                <span @class([
                    'mt-1 inline-flex w-2 h-2 rounded-full shrink-0',
                    'bg-green-500' => in_array($log->action, ['login', 'ticket_created', 'ticket_accepted', 'ticket_approved']),
                    'bg-gray-400' => $log->action === 'logout',
                    'bg-blue-500' => in_array($log->action, ['ticket_status_updated', 'ticket_assigned']),
                    'bg-amber-500' => $log->action === 'ticket_comment_added',
                    'bg-purple-500' => in_array($log->action, ['ticket_assist_joined', 'ticket_assist_left']),
                    'bg-red-500' => $log->action === 'login_failed',
                ])></span>
                <div class="min-w-0">
                    <p class="text-sm text-gray-700">{{ $log->description }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">
                        {{ $log->created_at->diffForHumans() }}
                        &middot; {{ $log->created_at->format('M j, Y g:i A') }}
                        @if ($log->ip_address)
                            &middot; {{ $log->ip_address }}
                        @endif
                    </p>
                </div>
            </li>
        @endforeach
    </ul>

    <div class="mt-4">{{ $activityLogs->links() }}</div>
@endif
