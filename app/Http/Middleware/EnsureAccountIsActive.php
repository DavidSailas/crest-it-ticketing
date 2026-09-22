<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountIsActive
{
    /**
     * Catches the case where an admin suspends an account that's already
     * logged in elsewhere — the next request that user makes signs them out
     * immediately instead of waiting for their session to expire naturally.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isSuspended()) {
            Auth::guard('web')->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->with('status', 'This account has been suspended. Contact an administrator for help.');
        }

        return $next($request);
    }
}
