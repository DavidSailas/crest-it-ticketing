<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

abstract class Controller
{
    /**
     * Remember the exact list URL (filters, tab, page) the person was looking at,
     * so that after editing a record they land back on the same view instead of
     * an unfiltered page 1.
     */
    protected function rememberList(Request $request, string $key): void
    {
        session(["list_url.{$key}" => $request->getRequestUri()]);
    }

    /** The remembered list URL for $key, or $fallback if there isn't one yet. */
    protected function listUrl(string $key, string $fallback): string
    {
        return (string) session("list_url.{$key}", $fallback);
    }
}
