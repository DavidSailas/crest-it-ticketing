<?php

namespace App\Http\Controllers;

use App\Models\SupportChatRead;
use App\Models\SupportMessage;
use App\Models\User;
use Illuminate\Http\Request;

class SupportChatController extends Controller
{
    /**
     * Staff: their own thread with IT Support, embedded on the Profile page.
     */
    public function show(Request $request)
    {
        $user = $request->user();
        abort_unless($user->isStaff(), 403, 'IT Support and Admins use the Support Inbox instead.');

        SupportChatRead::markRead($user->id, $user->id);

        $messages = SupportMessage::with('sender')
            ->where('user_id', $user->id)
            ->orderBy('created_at')
            ->get();

        $otherPartyLastReadAt = SupportChatRead::where('thread_user_id', $user->id)
            ->where('reader_id', '!=', $user->id)
            ->max('last_read_at');

        return view('support-chat.own', [
            'threadOwner' => $user,
            'messages' => $messages,
            'otherPartyLastReadAt' => $otherPartyLastReadAt,
        ]);
    }

    /**
     * IT/Admin: view (and reply to) a specific staff member's thread.
     */
    public function showFor(Request $request, User $user)
    {
        abort_unless($request->user()->isItSupport() || $request->user()->isAdmin(), 403);
        abort_unless($user->isStaff(), 404);

        return $this->renderThread($request, $user);
    }

    private function renderThread(Request $request, User $threadOwner)
    {
        $reader = $request->user();

        SupportChatRead::markRead($threadOwner->id, $reader->id);

        $messages = SupportMessage::with('sender')
            ->where('user_id', $threadOwner->id)
            ->orderBy('created_at')
            ->get();

        // "Seen" receipts: has the other side of this conversation read up to
        // the point of my latest message? For a staff member that's any
        // IT/admin reader; for IT/admin viewing a staff thread, it's the
        // staff member themselves.
        $otherPartyLastReadAt = $reader->id === $threadOwner->id
            ? SupportChatRead::where('thread_user_id', $threadOwner->id)->where('reader_id', '!=', $reader->id)->max('last_read_at')
            : optional(SupportChatRead::where('thread_user_id', $threadOwner->id)->where('reader_id', $threadOwner->id)->first())->last_read_at;

        return view('support-chat.thread', [
            'threadOwner' => $threadOwner,
            'messages' => $messages,
            'otherPartyLastReadAt' => $otherPartyLastReadAt,
            'isOwnThread' => $reader->id === $threadOwner->id,
        ]);
    }

    /**
     * Staff sending into their own thread.
     */
    public function send(Request $request)
    {
        return $this->postMessage($request, $request->user());
    }

    /**
     * IT/Admin sending into a specific staff member's thread.
     */
    public function sendFor(Request $request, User $user)
    {
        abort_unless($request->user()->isItSupport() || $request->user()->isAdmin(), 403);
        abort_unless($user->isStaff(), 404);

        return $this->postMessage($request, $user);
    }

    private function postMessage(Request $request, User $threadOwner)
    {
        $request->validate(['body' => 'required|string|max:2000'], [
            'body.required' => 'Please type a message before sending.',
            'body.max' => 'Message is too long — please keep it under 2,000 characters.',
        ]);

        $message = SupportMessage::create([
            'user_id' => $threadOwner->id,
            'sender_id' => $request->user()->id,
            'body' => $request->body,
        ]);

        $message->load('sender');
        SupportChatRead::markRead($threadOwner->id, $request->user()->id);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => [
                    'id' => $message->id,
                    'body' => $message->body,
                    'sender_name' => $message->sender->name,
                    'sender_id' => $message->sender_id,
                    'is_mine' => true,
                    'created_at' => $message->created_at->toIso8601String(),
                ],
            ]);
        }

        return back();
    }

    /**
     * Polling endpoint — staff polling their own thread.
     */
    public function poll(Request $request)
    {
        return $this->pollThread($request, $request->user());
    }

    /**
     * Polling endpoint — IT/Admin polling a specific staff thread.
     */
    public function pollFor(Request $request, User $user)
    {
        abort_unless($request->user()->isItSupport() || $request->user()->isAdmin(), 403);
        abort_unless($user->isStaff(), 404);

        return $this->pollThread($request, $user);
    }

    private function pollThread(Request $request, User $threadOwner)
    {
        $reader = $request->user();
        $sinceId = (int) $request->query('since_id', 0);

        SupportChatRead::markRead($threadOwner->id, $reader->id);

        $otherPartyLastReadAt = $reader->id === $threadOwner->id
            ? SupportChatRead::where('thread_user_id', $threadOwner->id)->where('reader_id', '!=', $reader->id)->max('last_read_at')
            : optional(SupportChatRead::where('thread_user_id', $threadOwner->id)->where('reader_id', $threadOwner->id)->first())->last_read_at;

        $messages = SupportMessage::with('sender')
            ->where('user_id', $threadOwner->id)
            ->where('id', '>', $sinceId)
            ->orderBy('id')
            ->get()
            ->map(fn ($m) => [
                'id' => $m->id,
                'body' => $m->body,
                'sender_name' => $m->sender->name,
                'sender_id' => $m->sender_id,
                'is_mine' => $m->sender_id === $reader->id,
                'created_at' => $m->created_at->toIso8601String(),
            ]);

        return response()->json([
            'messages' => $messages,
            'latest_id' => SupportMessage::where('user_id', $threadOwner->id)->max('id') ?? $sinceId,
            'other_party_last_read_at' => $otherPartyLastReadAt ? \Carbon\Carbon::parse($otherPartyLastReadAt)->toIso8601String() : null,
        ]);
    }

    /**
     * IT/Admin: inbox listing every staff member who has an active thread,
     * most recently active first, with an unread indicator.
     */
    public function inbox(Request $request)
    {
        abort_unless($request->user()->isItSupport() || $request->user()->isAdmin(), 403);

        $threads = $this->buildInboxThreads($request);

        return view('support-chat.inbox', compact('threads'));
    }

    /**
     * JSON feed for the inbox — polled every few seconds so new messages
     * and unread counts update live without a page refresh.
     */
    public function inboxPoll(Request $request)
    {
        abort_unless($request->user()->isItSupport() || $request->user()->isAdmin(), 403);

        $threads = $this->buildInboxThreads($request)->map(fn ($t) => [
            'user_id' => $t['user']->id,
            'name' => $t['user']->name,
            'is_vip' => (bool) $t['user']->is_vip,
            'initial' => strtoupper(substr($t['user']->name, 0, 1)),
            'last_message_preview' => $t['last_message']
                ? ($t['last_message']->sender_id === $request->user()->id ? 'You: ' : '').\Illuminate\Support\Str::limit($t['last_message']->body, 60)
                : 'No messages yet',
            'unread' => $t['unread'],
            'last_message_at' => \Illuminate\Support\Carbon::parse($t['last_message_at'])->toIso8601String(),
            'last_message_at_human' => \Illuminate\Support\Carbon::parse($t['last_message_at'])->diffForHumans(),
            'url' => route('support-chat.show.user', $t['user']),
        ]);

        return response()->json(['threads' => $threads]);
    }

    private function buildInboxThreads(Request $request)
    {
        return SupportMessage::selectRaw('user_id, MAX(created_at) as last_message_at')
            ->groupBy('user_id')
            ->orderByDesc('last_message_at')
            ->get()
            ->map(function ($row) use ($request) {
                $owner = User::find($row->user_id);
                $lastMessage = SupportMessage::with('sender')->where('user_id', $row->user_id)->latest('created_at')->first();

                $myLastRead = optional(
                    SupportChatRead::where('thread_user_id', $row->user_id)->where('reader_id', $request->user()->id)->first()
                )->last_read_at;

                $unread = SupportMessage::where('user_id', $row->user_id)
                    ->where('sender_id', '!=', $request->user()->id)
                    ->when($myLastRead, fn ($q) => $q->where('created_at', '>', $myLastRead))
                    ->count();

                return [
                    'user' => $owner,
                    'last_message' => $lastMessage,
                    'unread' => $unread,
                    'last_message_at' => $row->last_message_at,
                ];
            });
    }
}
