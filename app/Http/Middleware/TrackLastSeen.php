<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class TrackLastSeen
{
    /**
     * Stamps users.last_seen_at so the dashboard can show who is online.
     * Written at most once a minute per user so it adds no real load; the
     * notification bell polls every 15s on every page, which keeps an open
     * tab "online" without any extra JavaScript.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && (! $user->last_seen_at || $user->last_seen_at->lt(now()->subMinute()))) {
            // Query-builder update on purpose: an Eloquent update would also
            // bump updated_at and fire the model's saving hooks. Wrapped so a
            // not-yet-run migration (no last_seen_at column) never takes the
            // site down — presence just stays "offline" until it is migrated.
            try {
                DB::table('users')->where('id', $user->id)->update(['last_seen_at' => now()]);
                $user->last_seen_at = now();
            } catch (\Throwable $e) {
                // ignore
            }
        }

        return $next($request);
    }
}
