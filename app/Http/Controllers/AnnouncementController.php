<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * What every signed-in person sees: the announcements feed, and the
 * "I've seen this" call the pop-up makes when it is closed.
 */
class AnnouncementController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $announcements = Announcement::live()->forUser($user)
            ->with('creator:id,name')
            ->orderByDesc('is_pinned')->orderByDesc('created_at')
            ->paginate(10);

        $readIds = DB::table('announcement_reads')
            ->where('user_id', $user->id)
            ->pluck('announcement_id')
            ->all();

        return view('announcements.index', compact('announcements', 'readIds'));
    }

    /**
     * The live poll. The browser calls this every few seconds with the
     * fingerprint it last saw; when nothing changed we answer with a tiny
     * {changed:false}, otherwise with the new unread count and any pop-ups the
     * person has not seen yet.
     */
    public function poll(Request $request)
    {
        $user = $request->user();
        $state = Announcement::liveState($user);

        if ($state['sig'] === (string) $request->query('sig')) {
            return response()->json(['changed' => false])->header('Cache-Control', 'no-store');
        }

        return response()->json([
            'changed' => true,
            'sig' => $state['sig'],
            'unread' => $state['unread'],
            'popups' => Announcement::popupsFor($user)->map->popupPayload()->values()->all(),
        ])->header('Cache-Control', 'no-store');
    }

    /**
     * Mark announcements as read. Send ids[] to mark specific ones, or nothing
     * to mark everything the person can currently see.
     */
    public function markRead(Request $request)
    {
        $user = $request->user();

        $query = Announcement::live()->forUser($user);
        if (is_array($request->input('ids'))) {
            $query->whereIn('id', array_map('intval', $request->input('ids')));
        }

        $now = now();
        $rows = $query->pluck('id')
            ->map(fn ($id) => ['announcement_id' => $id, 'user_id' => $user->id, 'read_at' => $now])
            ->all();

        if ($rows) {
            DB::table('announcement_reads')->insertOrIgnore($rows);
        }

        return $request->expectsJson()
            ? response()->json(['ok' => true])
            : back()->with('status', 'All announcements marked as read.');
    }
}
